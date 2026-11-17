<?php
/* =====================================================
   CONTROLADOR - CENTRO LOGÍSTICO (administrador)
   CRUD de zonas y ubicaciones (una sola pantalla:
   ?tipo=zonas | ubicaciones), mapa del depósito e
   inventario por zona.
===================================================== */
class LogisticaController extends Controlador
{
    const FILTROS_INVENTARIO = ['Todas', 'Refrigerada', 'Seca', 'Voluminosa', 'Alertas'];
    const ZONAS_POR_PAGINA = 6;   // mapa del depósito: desde la séptima zona aparece la página 2

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

    /* Mapa del depósito: cada zona con sus productos y cantidades, en rojo si alguno
       está en el stock mínimo. Se arma con las zonas de la base, así que crece o se
       achica solo cuando se crean o se borran zonas; 6 zonas por página. */
    public function mapa()
    {
        $zonas = [];
        foreach ((new Zona())->conTotales() as $z) {
            $z['pasillos'] = [];
            $zonas[(int)$z['id_zona']] = $z;
        }
        // Plano: zona -> pasillo -> estantería -> nivel -> productos (los niveles vacíos también se dibujan)
        foreach ((new Ubicacion())->planoConProductos() as $u) {
            $idZona = (int)$u['id_zona'];
            if (!isset($zonas[$idZona])) {
                continue;
            }
            [$p, $e, $n] = [$u['pasillo'], $u['estante'], $u['posicion']];
            $pasillos = &$zonas[$idZona]['pasillos'];
            if (!isset($pasillos[$p])) {
                $pasillos[$p] = ['estantes' => [], 'productos' => 0, 'alertas' => 0];
            }
            if (!isset($pasillos[$p]['estantes'][$e])) {
                $pasillos[$p]['estantes'][$e] = ['niveles' => [], 'productos' => 0, 'alertas' => 0];
            }
            if (!isset($pasillos[$p]['estantes'][$e]['niveles'][$n])) {
                $pasillos[$p]['estantes'][$e]['niveles'][$n] = [];
            }
            if ($u['id_producto'] !== null) {
                $u['alerta'] = (int)$u['stock_actual'] <= (int)$u['stock_minimo'];
                $pasillos[$p]['estantes'][$e]['niveles'][$n][] = $u;
                $pasillos[$p]['estantes'][$e]['productos']++;
                $pasillos[$p]['productos']++;
                if ($u['alerta']) {
                    $pasillos[$p]['estantes'][$e]['alertas']++;
                    $pasillos[$p]['alertas']++;
                }
            }
            unset($pasillos);
        }
        // En orden alfabético (Zona A, B, C...), sin importar el depósito
        usort($zonas, fn($a, $b) => strnatcasecmp($a['nombre'], $b['nombre']));

        $totalPaginas = max(1, (int)ceil(count($zonas) / self::ZONAS_POR_PAGINA));
        $pagina = min(max(1, (int)($_GET['pagina'] ?? 1)), $totalPaginas);
        $this->vista('admin/mapa', [
            'zonas' => array_slice($zonas, ($pagina - 1) * self::ZONAS_POR_PAGINA, self::ZONAS_POR_PAGINA),
            'pagina' => $pagina,
            'totalPaginas' => $totalPaginas,
            'totalZonas' => count($zonas),
            'zonasPorPagina' => self::ZONAS_POR_PAGINA,
            'iconosZona' => ['refrigerada' => 'bi-snow', 'seca' => 'bi-sun', 'voluminosa' => 'bi-boxes'],
        ]);
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
