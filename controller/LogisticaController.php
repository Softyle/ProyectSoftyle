<?php
/* =====================================================
   CONTROLADOR - CENTRO LOGÍSTICO (administrador)
   CRUD de zonas y ubicaciones (una sola pantalla:
   ?tipo=zonas | ubicaciones) e inventario por zona.
===================================================== */
class LogisticaController extends Controlador
{
    const FILTROS_INVENTARIO = ['Todas', 'Refrigerada', 'Seca', 'Voluminosa', 'Alertas'];

    public function __construct()
    {
        $this->requerirRol('admin');
    }

    public function index()
    {
        $esZona = ($_GET['tipo'] ?? 'zonas') !== 'ubicaciones';
        $tipo = $esZona ? 'zonas' : 'ubicaciones';
        $modelo = $esZona ? new Zona() : new Ubicacion();
        $campos = $esZona
            ? ['nombre', 'tipo', 'capacidad_total', 'estado', 'id_deposito']
            : ['pasillo', 'estante', 'posicion', 'disponible', 'id_zona'];
        $error = '';
        $previo = null;

        if ($this->esPost()) {
            $id = (int)($_POST['id'] ?? 0);
            $accion = $_POST['accion'] ?? '';
            if (!$this->csrfValido()) {
                $error = 'La sesión del formulario venció. Actualizá la página.';
            } elseif ($accion === 'eliminar' && $id > 0) {
                try {
                    $modelo->eliminar($id);
                    $this->redirigir('logistica', 'index', ['tipo' => $tipo, 'mensaje' => 'Registro eliminado.']);
                } catch (Throwable $ex) {
                    $error = 'No se puede eliminar: puede tener ubicaciones, productos u otros registros asociados.';
                }
            } elseif (in_array($accion, ['guardar', 'editar'], true)) {
                $valores = [];
                foreach ($campos as $campo) {
                    $valores[$campo] = trim((string)($_POST[$campo] ?? ''));
                }
                $error = $esZona ? $this->validarZona($valores, $id, $modelo) : $this->validarUbicacion($valores, $id, $modelo);
                if (!$error) {
                    try {
                        $accion === 'editar' && $id > 0 ? $modelo->actualizar($id, $valores) : $modelo->crear($valores);
                        $this->redirigir('logistica', 'index', ['tipo' => $tipo, 'mensaje' => 'Cambios guardados correctamente.']);
                    } catch (Throwable $ex) {
                        $error = 'No se pudo guardar. Verificá que el depósito o la zona exista y los datos sean válidos.';
                    }
                }
                $previo = $valores; // para no borrar lo que escribió el usuario
            }
        }

        $editar = isset($_GET['editar']) ? $modelo->buscar((int)$_GET['editar']) : null;
        $this->vista('admin/logistica', [
            'tipo' => $tipo,
            'esZona' => $esZona,
            'campos' => $campos,
            'etiquetas' => $esZona
                ? ['Nombre', 'Tipo', 'Capacidad total', 'Estado', 'Depósito']
                : ['Pasillo', 'Estantería', 'Nivel', 'Disponible', 'Zona'],
            'clave' => $esZona ? 'id_zona' : 'id_ubicacion',
            'filas' => $esZona ? $modelo->listarConDeposito() : $modelo->listarConZona(),
            'opciones' => [
                'id_deposito' => (new Deposito())->listar(),
                'id_zona' => (new Zona())->listar(),
            ],
            'tiposZona' => Zona::TIPOS,
            'formatosUbicacion' => Ubicacion::FORMATOS,
            'editar' => $editar,
            // Lo que escribió el usuario si hubo un error, o el registro que se edita
            'valoresForm' => $previo ?? $editar,
            'error' => $error,
            'mensaje' => (string)($_GET['mensaje'] ?? ''),
        ]);
    }

    /* El nombre de la zona es "Zona" + una letra del alfabeto (Zona A ... Zona Z) y no se repite */
    private function validarZona(array &$v, $id, Zona $modelo)
    {
        foreach ($v as $valor) {
            if ($valor === '') {
                return 'Completá todos los campos.';
            }
        }
        // "zona g" o "Zona  g" se guardan como "Zona G"
        if (preg_match('/^zona\s*([a-z])$/i', $v['nombre'], $m)) {
            $v['nombre'] = 'Zona ' . strtoupper($m[1]);
        }
        if (!in_array($v['nombre'], Zona::nombresPosibles(), true)) {
            return 'El nombre de la zona tiene que ser "Zona" y una letra, por ejemplo Zona G.';
        }
        if ($modelo->existeNombre($v['nombre'], $id)) {
            return 'Ya existe la ' . $v['nombre'] . '.';
        }
        if (!ctype_digit($v['capacidad_total']) || !ctype_digit($v['id_deposito']) || (int)$v['id_deposito'] < 1) {
            return 'Revisá los valores numéricos.';
        }
        if (!in_array($v['tipo'], Zona::TIPOS, true)) {
            return 'Elegí un tipo de zona: Refrigerada, Seca o Voluminosa.';
        }
        return '';
    }

    /* Formato fijo del código de ubicación: P02-E01-N01 (es lo que escribe el operario al confirmar) */
    private function validarUbicacion(array &$v, $id, Ubicacion $modelo)
    {
        foreach ($v as $valor) {
            if ($valor === '') {
                return 'Completá todos los campos.';
            }
        }
        if (!ctype_digit($v['id_zona']) || (int)$v['id_zona'] < 1) {
            return 'Revisá los valores numéricos.';
        }
        if (!in_array($v['disponible'], ['0', '1'], true)) {
            return 'Disponibilidad inválida.';
        }
        foreach (Ubicacion::FORMATOS as $campo => [$regex, $mensaje]) {
            $v[$campo] = strtoupper($v[$campo]);
            if (!preg_match($regex, $v[$campo])) {
                return $mensaje;
            }
        }
        if ($modelo->existeCodigo($v['pasillo'], $v['estante'], $v['posicion'], $id)) {
            return 'Ya existe la ubicación ' . $v['pasillo'] . '-' . $v['estante'] . '-' . $v['posicion'] . '.';
        }
        return '';
    }

    /* Inventario por zona: stock de cada producto y alertas de reposición */
    public function inventario()
    {
        $filtro = $_GET['filtro'] ?? 'Todas';
        if (!in_array($filtro, self::FILTROS_INVENTARIO, true)) {
            $filtro = 'Todas';
        }
        $zonas = [];
        foreach ((new Zona())->conTotales() as $z) {
            $z['items'] = [];
            $zonas[(int)$z['id_zona']] = $z;
        }
        $totalAlertas = 0;
        foreach ((new Producto())->inventario() as $p) {
            $p['alerta'] = (int)$p['stock_actual'] <= (int)$p['stock_minimo'];
            $totalAlertas += $p['alerta'] ? 1 : 0;
            if (isset($zonas[(int)$p['id_zona']])) {
                $zonas[(int)$p['id_zona']]['items'][] = $p;
            }
        }
        $zonasVisibles = array_filter($zonas, function ($z) use ($filtro) {
            if ($filtro === 'Alertas') {
                return (int)$z['alertas'] > 0;
            }
            return $filtro === 'Todas' || strcasecmp($z['tipo'], $filtro) === 0;
        });

        $this->vista('admin/inventario', [
            'filtro' => $filtro,
            'filtros' => self::FILTROS_INVENTARIO,
            'zonasVisibles' => $zonasVisibles,
            'totalAlertas' => $totalAlertas,
            'notificaciones' => (new Notificacion())->ultimas(8),
            'iconosZona' => ['refrigerada' => 'bi-snow', 'seca' => 'bi-sun', 'voluminosa' => 'bi-boxes'],
        ]);
    }
}
