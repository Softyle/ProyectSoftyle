<?php
/* =====================================================
   CONTROLADOR - MONITOR DE SENSORES (administrador)
   API simulada de control ambiental: cada consulta a
   "datos" genera lecturas de temperatura y humedad, las
   guarda y devuelve el estado en JSON. Si una zona
   refrigerada sale de rango se genera una notificación
   de "Cadena de frío" para el administrador.
===================================================== */
class SensorController extends Controlador
{
    public function __construct()
    {
        $this->requerirRol('admin', ($_GET['a'] ?? '') === 'datos');
    }

    public function index()
    {
        $this->vista('admin/sensores');
    }

    /* Reporte de lecturas para exportar (Excel, PDF, CSV o imprimir).
       GET ?sensor=<id o 0>&desde=AAAA-MM-DD&hasta=AAAA-MM-DD&alertas=1 */
    public function reporte()
    {
        $modelo = new Sensor();
        $sensores = $modelo->activos();
        $idSensor = (int)($_GET['sensor'] ?? 0);
        if ($idSensor && !in_array($idSensor, array_map(fn($s) => (int)$s['id_sensor'], $sensores), true)) {
            $idSensor = 0;
        }
        $desde = (string)($_GET['desde'] ?? date('Y-m-d', strtotime('-1 day')));
        $hasta = (string)($_GET['hasta'] ?? date('Y-m-d'));
        $soloAlertas = ($_GET['alertas'] ?? '') === '1';

        $error = '';
        $fechaValida = fn($f) => preg_match('/^\d{4}-\d{2}-\d{2}$/', $f) && strtotime($f);
        if (!$fechaValida($desde) || !$fechaValida($hasta)) {
            $error = 'Las fechas tienen que tener el formato día/mes/año.';
        } elseif ($desde > $hasta) {
            $error = 'La fecha "desde" no puede ser posterior a "hasta".';
        } elseif ((strtotime($hasta) - strtotime($desde)) / 86400 > 31) {
            $error = 'Elegí un período de hasta 31 días.';
        }
        if ($error !== '') {
            $desde = date('Y-m-d', strtotime('-1 day'));
            $hasta = date('Y-m-d');
        }

        // "hasta" incluye el día completo: se consulta hasta el comienzo del día siguiente
        $hastaExclusivo = date('Y-m-d', strtotime($hasta . ' +1 day'));
        $limite = 5000;
        $lecturas = $modelo->lecturas($idSensor, $desde, $hastaExclusivo, $soloAlertas, $limite);

        $this->vista('admin/sensor_reporte', [
            'sensores' => $sensores,
            'idSensor' => $idSensor,
            'desde' => $desde,
            'hasta' => $hasta,
            'soloAlertas' => $soloAlertas,
            'error' => $error,
            'resumen' => $modelo->resumen($idSensor, $desde, $hastaExclusivo),
            'lecturas' => $lecturas,
            'recortado' => count($lecturas) >= $limite,
            'limite' => $limite,
        ]);
    }

    public function datos()
    {
        try {
            $modelo = new Sensor();
            $notificaciones = new Notificacion();
            $respuesta = [];
            foreach ($modelo->activos() as $s) {
                $id = (int)$s['id_sensor'];
                $ultima = $modelo->ultimaLectura($id);
                $nueva = $modelo->simularLectura($s, $ultima);

                // Notificación solo cuando una zona refrigerada PASA de normal a fuera de rango
                if ($nueva && strcasecmp($s['tipo_zona'], 'Refrigerada') === 0) {
                    $estadoNuevo = Sensor::estadoLectura($nueva['temperatura'], $nueva['humedad'], $s);
                    $estadoAnterior = $ultima ? Sensor::estadoLectura((float)$ultima['temperatura'], (float)$ultima['humedad'], $s) : 'Normal';
                    if ($estadoNuevo !== 'Normal' && $estadoAnterior === 'Normal') {
                        $notificaciones->crearDeZona('Cadena de frío',
                            $s['zona'] . ' (' . $s['codigo'] . '): ' . $nueva['temperatura'] . ' °C, ' . $nueva['humedad'] . ' % de humedad. Rango permitido '
                            . $s['temp_min'] . ' a ' . $s['temp_max'] . ' °C.', (int)$s['id_zona']);
                    }
                }

                $serie = $modelo->serie($id);
                $actual = end($serie);
                $respuesta[] = [
                    'codigo' => $s['codigo'],
                    'zona' => $s['zona'],
                    'tipo_zona' => $s['tipo_zona'],
                    'rango_temp' => [$s['temp_min'], $s['temp_max']],
                    'rango_hum' => [$s['hum_min'], $s['hum_max']],
                    'temperatura' => (float)$actual['temperatura'],
                    'humedad' => (float)$actual['humedad'],
                    'hora' => fecha($actual['fecha_hora'], 'H:i:s'),
                    'estado' => Sensor::estadoLectura((float)$actual['temperatura'], (float)$actual['humedad'], $s),
                    'alertas_24h' => $modelo->alertas24Horas($s),
                    'serie' => array_map(fn($l) => [
                        'hora' => fecha($l['fecha_hora'], 'H:i:s'),
                        'temperatura' => (float)$l['temperatura'],
                        'humedad' => (float)$l['humedad'],
                    ], $serie),
                ];
            }

            $this->json(200, [
                'ok' => true,
                'actualizado' => date('H:i:s'),
                'sensores' => $respuesta,
                'alertas' => array_map(fn($a) => ['fecha' => fecha($a['fecha'], 'd/m H:i:s'), 'mensaje' => $a['mensaje']],
                    $notificaciones->ultimasDeTipo('Cadena de frío', 8)),
            ]);
        } catch (Throwable $ex) {
            error_log('PICKFLOW sensores: ' . $ex->getMessage());
            $this->json(500, ['ok' => false, 'mensaje' => 'No se pudieron leer los sensores. Verificá que se importó softyleDEF.sql.']);
        }
    }
}
