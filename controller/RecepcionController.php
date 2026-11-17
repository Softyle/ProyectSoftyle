<?php
/* =====================================================
   CONTROLADOR - RECEPCIÓN DE MERCADERÍA Y VENCIMIENTOS
   (administrador y operario; vencimientos solo el administrador)

   index        -> últimas recepciones
   nueva        -> formulario: se escanea cada producto, se pone
                   la cantidad, el lote y el vencimiento; al guardar
                   sube el stock y queda anotado en el historial
   ver          -> detalle de una recepción
   vencimientos -> lotes vencidos o por vencer, y baja de lo vencido
===================================================== */
class RecepcionController extends Controlador
{
    const MAX_LINEAS = 200;

    public function __construct()
    {
        $this->requerirRol(($_GET['a'] ?? '') === 'vencimientos' ? 'admin' : ['admin', 'operario']);
    }

    public function index()
    {
        $esAdmin = $this->rol() === 'admin';
        $this->vista('compartido/recepciones', [
            'recepciones' => (new Ingreso())->recientes(50, $esAdmin ? '' : $this->ciUsuario()),
            'resumenVencimientos' => $esAdmin ? (new Lote())->resumen() : null,
            'mensaje' => (string)($_GET['mensaje'] ?? ''),
        ]);
    }

    public function nueva()
    {
        $ingresos = new Ingreso();
        $catalogo = $ingresos->catalogo();
        $error = '';
        $form = ['id_proveedor' => 0, 'id_orden' => (int)($_GET['orden'] ?? 0), 'remito' => '', 'observaciones' => '', 'lineas' => []];

        if ($this->esPost()) {
            $form = $this->datosFormulario();
            $error = $this->validar($form, $catalogo);
            if (!$error) {
                try {
                    $id = $ingresos->registrar($this->ciUsuario(), $form['id_proveedor'], $form['id_orden'], $form['remito'], $form['observaciones'], $form['lineas']);
                    $unidades = array_sum(array_column($form['lineas'], 'cantidad'));
                    $distintos = count(array_unique(array_column($form['lineas'], 'id_producto')));
                    $this->redirigir('recepcion', 'ver', ['id' => $id, 'mensaje' => 'Recepción #' . $id . ' guardada: entraron ' . $unidades
                        . ' unidades de ' . $distintos . ' producto' . ($distintos === 1 ? '' : 's') . ' y el stock ya está actualizado.']);
                } catch (RuntimeException $ex) {
                    $error = $ex->getMessage();
                } catch (mysqli_sql_exception $ex) {
                    error_log('PICKFLOW recepcion: ' . $ex->getMessage());
                    $error = 'No se pudo guardar la recepción. No se modificó el stock; intentá de nuevo.';
                }
            }
        }

        // El catálogo va a la página para buscar por nombre o código sin esperar al servidor
        $productosJs = array_map(fn($p) => [
            'id' => (int)$p['id'], 'nombre' => $p['nombre'], 'codigo' => (string)$p['codigo'], 'foto' => fotoProducto($p['foto']),
            'proveedor' => (int)$p['proveedor'], 'ubicacion' => $p['ubicacion'], 'categoria' => $p['categoria'],
            'refrigerado' => strcasecmp((string)$p['tipo_zona'], 'Refrigerada') === 0,
        ], $catalogo);

        $this->vista('compartido/recepcion_form', [
            'form' => $form,
            'error' => $error,
            'productos' => $productosJs,
            'proveedores' => (new Proveedor())->activos($form['id_proveedor']),
            'ordenes' => (new OrdenCompra())->abiertasConPendientes(),
        ]);
    }

    public function ver()
    {
        $id = (int)($_GET['id'] ?? 0);
        $ingresos = new Ingreso();
        $recepcion = $ingresos->buscar($id);
        // El operario solo ve las recepciones que hizo él
        if (!$recepcion || ($this->rol() !== 'admin' && $recepcion['ci_persona'] !== $this->ciUsuario())) {
            $this->redirigir('recepcion');
        }
        $this->vista('compartido/recepcion_ver', [
            'recepcion' => $recepcion,
            'lineas' => $ingresos->lineas($id),
            'mensaje' => (string)($_GET['mensaje'] ?? ''),
        ]);
    }

    /* Lotes vencidos o que vencen dentro de los días elegidos. POST: dar de baja un lote. */
    public function vencimientos()
    {
        $dias = (int)($_GET['dias'] ?? Lote::DIAS_ATENCION);
        if (!in_array($dias, [Lote::DIAS_AVISO, 15, Lote::DIAS_ATENCION, 60, 90], true)) {
            $dias = Lote::DIAS_ATENCION;
        }
        $lotes = new Lote();
        $mensaje = (string)($_GET['mensaje'] ?? '');
        $error = '';
        if ($this->esPost()) {
            if (!$this->csrfValido()) {
                $error = 'La sesión del formulario venció. Actualizá la página e intentá de nuevo.';
            } else {
                try {
                    $baja = $lotes->darDeBaja((int)($_POST['id_lote'] ?? 0), $this->ciUsuario(), 'Mercadería vencida retirada del depósito');
                    $this->redirigir('recepcion', 'vencimientos', ['dias' => $dias,
                        'mensaje' => 'Se dieron de baja ' . $baja['unidades'] . ' unidades de ' . $baja['producto'] . '. Quedó anotado en su historial.']);
                } catch (RuntimeException $ex) {
                    $error = $ex->getMessage();
                } catch (mysqli_sql_exception $ex) {
                    error_log('PICKFLOW baja de lote: ' . $ex->getMessage());
                    $error = 'No se pudo dar de baja el lote.';
                }
            }
        }
        $this->vista('admin/vencimientos', [
            'lotes' => $lotes->vencimientos($dias),
            'dias' => $dias,
            'diasAviso' => Lote::DIAS_AVISO,
            'mensaje' => $mensaje,
            'error' => $error,
        ]);
    }

    private function datosFormulario()
    {
        $lineas = [];
        foreach (array_slice((array)($_POST['lineas'] ?? []), 0, self::MAX_LINEAS + 1) as $l) {
            if (!is_array($l)) {
                continue;
            }
            $lineas[] = [
                'id_producto' => (int)($l['id_producto'] ?? 0),
                'cantidad' => filter_var($l['cantidad'] ?? null, FILTER_VALIDATE_INT),
                'lote' => mb_strtoupper(mb_substr(trim((string)($l['lote'] ?? '')), 0, 30)),
                'vencimiento' => trim((string)($l['vencimiento'] ?? '')),
            ];
        }
        return [
            'id_proveedor' => (int)($_POST['id_proveedor'] ?? 0),
            'id_orden' => (int)($_POST['id_orden'] ?? 0),
            'remito' => mb_substr(trim((string)($_POST['remito'] ?? '')), 0, 30),
            'observaciones' => mb_substr(trim((string)($_POST['observaciones'] ?? '')), 0, 255),
            'lineas' => $lineas,
        ];
    }

    private function validar(array $f, array $catalogo)
    {
        if (!$this->csrfValido()) {
            return 'La sesión del formulario venció. Actualizá la página e intentá de nuevo.';
        }
        if (!$f['lineas']) {
            return 'Agregá al menos un producto: escanealo o buscalo por nombre.';
        }
        if (count($f['lineas']) > self::MAX_LINEAS) {
            return 'Se pueden recibir hasta ' . self::MAX_LINEAS . ' productos por vez.';
        }
        if ($f['id_proveedor'] && !(new Proveedor())->buscar($f['id_proveedor'])) {
            return 'El proveedor elegido no existe.';
        }
        $porId = array_column($catalogo, null, 'id');
        $hoy = date('Y-m-d');
        foreach ($f['lineas'] as $i => $l) {
            $producto = $porId[$l['id_producto']] ?? null;
            $n = 'Fila ' . ($i + 1);
            if (!$producto) {
                return $n . ': el producto no existe. Volvé a buscarlo.';
            }
            $n .= ' (' . $producto['nombre'] . ')';
            if ($l['cantidad'] === false || $l['cantidad'] < 1 || $l['cantidad'] > 100000) {
                return $n . ': la cantidad tiene que ser un número entero mayor que cero.';
            }
            if ($l['vencimiento'] !== '') {
                $fecha = DateTime::createFromFormat('!Y-m-d', $l['vencimiento']);
                if (!$fecha || $fecha->format('Y-m-d') !== $l['vencimiento']) {
                    return $n . ': la fecha de vencimiento no es válida.';
                }
                if ($l['vencimiento'] < $hoy) {
                    return $n . ': ese lote ya está vencido (' . fecha($l['vencimiento'], 'd/m/Y') . '). La mercadería vencida no se recibe.';
                }
            } elseif (strcasecmp((string)$producto['tipo_zona'], 'Refrigerada') === 0) {
                return $n . ': los productos refrigerados necesitan la fecha de vencimiento.';
            }
        }
        return '';
    }
}
