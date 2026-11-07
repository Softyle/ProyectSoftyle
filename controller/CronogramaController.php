<?php
/* =====================================================
   CONTROLADOR - CRONOGRAMA DE DESPACHOS (ambos roles)
   Pedidos programados por fecha de despacho y zona, con
   la ruta de picking de cada pedido (la refrigerada
   siempre al final, RNE-8).

   Filtro por rol (lo decide el servidor con la sesión,
   no el navegador):
     - Administrador: ve todo el depósito.
     - Operario: solo los pedidos que tiene asignados.
===================================================== */
class CronogramaController extends Controlador
{
    public function __construct()
    {
        $this->requerirRol(['admin', 'operario'], ($_GET['a'] ?? '') === 'datos');
    }

    public function index()
    {
        $this->vista('compartido/cronograma', [
            'esAdmin' => $this->rol() === 'admin',
            'zonas' => (new Zona())->listar(),
        ]);
    }

    /* JSON. GET ?desde=AAAA-MM-DD&hasta=AAAA-MM-DD&zona=<id opcional> */
    public function datos()
    {
        // Fechas con formato AAAA-MM-DD (expresión regular) y rango de hasta 92 días
        $desde = (string)($_GET['desde'] ?? date('Y-m-d', strtotime('-7 days')));
        $hasta = (string)($_GET['hasta'] ?? date('Y-m-d', strtotime('+14 days')));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta) || !strtotime($desde) || !strtotime($hasta)) {
            $this->json(422, ['ok' => false, 'mensaje' => 'Las fechas deben tener el formato AAAA-MM-DD.']);
        }
        if ($desde > $hasta) {
            $this->json(422, ['ok' => false, 'mensaje' => 'La fecha "desde" no puede ser posterior a "hasta".']);
        }
        if ((strtotime($hasta) - strtotime($desde)) / 86400 > 92) {
            $this->json(422, ['ok' => false, 'mensaje' => 'Elegí un rango de hasta 3 meses.']);
        }
        $idZona = (int)($_GET['zona'] ?? 0);

        try {
            $filas = (new Pedido())->cronograma($desde, $hasta, $this->rol() === 'operario' ? $this->ciUsuario() : null);

            // Ruta de picking de cada pedido: sus zonas en orden de recorrido (refrigerada al final)
            $rutas = [];
            foreach ($filas as $f) {
                $rutas[$f['id_pedido']][$f['zona']] = strcasecmp($f['tipo_zona'], 'Refrigerada') === 0;
            }
            foreach ($rutas as $id => $zonas) {
                uksort($zonas, fn($a, $b) => [$zonas[$a], $a] <=> [$zonas[$b], $b]);
                $rutas[$id] = array_keys($zonas);
            }

            // Estructura día -> zona -> pedidos (con filtro opcional de zona)
            $dias = [];
            foreach ($filas as $f) {
                if ($idZona > 0 && (int)$f['id_zona'] !== $idZona) {
                    continue;
                }
                $dia = $f['fecha'];
                $zona = $f['zona'];
                $dias[$dia] = $dias[$dia] ?? ['fecha' => $dia, 'unidades' => 0, 'zonas' => []];
                $dias[$dia]['zonas'][$zona] = $dias[$dia]['zonas'][$zona] ?? ['zona' => $zona, 'tipo' => $f['tipo_zona'], 'unidades' => 0, 'pedidos' => []];
                $dias[$dia]['unidades'] += (int)$f['unidades'];
                $dias[$dia]['zonas'][$zona]['unidades'] += (int)$f['unidades'];
                $dias[$dia]['zonas'][$zona]['pedidos'][] = [
                    'id' => (int)$f['id_pedido'],
                    'estado' => $f['estado'],
                    'cliente' => $f['cliente'],
                    'operario' => $f['operario'],
                    'unidades' => (int)$f['unidades'],
                    'programado' => (bool)$f['programado'],
                    'ruta' => $rutas[$f['id_pedido']],
                ];
            }
            foreach ($dias as &$dia) {
                $dia['zonas'] = array_values($dia['zonas']);
            }
            unset($dia);

            $this->json(200, [
                'ok' => true,
                'rol' => $this->rol(),
                'desde' => $desde,
                'hasta' => $hasta,
                'hoy' => date('Y-m-d'),
                'dias' => array_values($dias),
            ]);
        } catch (Throwable $ex) {
            error_log('PICKFLOW cronograma: ' . $ex->getMessage());
            $this->json(500, ['ok' => false, 'mensaje' => 'No se pudo cargar el cronograma.']);
        }
    }
}
