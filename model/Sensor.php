<?php
/* =====================================================
   MODELO - SENSORES (tablas Sensor + Lectura_Sensor)
   Control ambiental simulado: cada consulta genera una
   lectura nueva por sensor a partir de la anterior
   (variación chica), con alguna salida de rango ocasional
   para poder ver las alertas de cadena de frío.
===================================================== */
class Sensor extends Modelo
{
    const SEGUNDOS_ENTRE_LECTURAS = 4; // si hay varias pestañas abiertas no se duplican lecturas

    /* Sensores activos con su zona (los de zonas refrigeradas primero) */
    public function activos()
    {
        $sensores = $this->filas("SELECT s.*, z.nombre AS zona, z.tipo AS tipo_zona, z.id_zona
                                  FROM Sensor s INNER JOIN Zona z ON z.id_zona = s.id_zona
                                  WHERE s.activo = TRUE ORDER BY z.tipo = 'Refrigerada' DESC, z.nombre");
        foreach ($sensores as &$s) {
            foreach (['temp_min', 'temp_max', 'hum_min', 'hum_max'] as $campo) {
                $s[$campo] = (float)$s[$campo];
            }
        }
        unset($s);
        return $sensores;
    }

    public function ultimaLectura($idSensor)
    {
        return $this->fila("SELECT temperatura, humedad, fecha_hora FROM Lectura_Sensor WHERE id_sensor = ?
                            ORDER BY fecha_hora DESC, id_lectura DESC LIMIT 1", 'i', [$idSensor]);
    }

    /* Estado de una lectura respecto del rango permitido del sensor */
    public static function estadoLectura($temperatura, $humedad, array $sensor)
    {
        if ($temperatura < $sensor['temp_min'] || $temperatura > $sensor['temp_max']) {
            return 'ALERTA: temperatura';
        }
        if ($humedad < $sensor['hum_min'] || $humedad > $sensor['hum_max']) {
            return 'ALERTA: humedad';
        }
        return 'Normal';
    }

    /* Genera y guarda una lectura nueva si pasó el tiempo mínimo desde la anterior.
       Devuelve [lectura nueva, lectura anterior] o null si todavía no corresponde. */
    public function simularLectura(array $sensor, $ultima)
    {
        if ($ultima && time() - strtotime($ultima['fecha_hora']) < self::SEGUNDOS_ENTRE_LECTURAS) {
            return null;
        }
        $centroTemp = ($sensor['temp_min'] + $sensor['temp_max']) / 2;
        $centroHum = ($sensor['hum_min'] + $sensor['hum_max']) / 2;
        $tempAnterior = $ultima ? (float)$ultima['temperatura'] : $centroTemp;
        $humAnterior = $ultima ? (float)$ultima['humedad'] : $centroHum;

        if (mt_rand(1, 100) <= 6) {
            // 6 % de las veces: falla (por ejemplo, puerta de la cámara abierta)
            $temperatura = $sensor['temp_max'] + self::azar(0.5, 2.5);
        } else {
            // Variación chica que tiende a volver al centro del rango
            $temperatura = $tempAnterior + self::azar(-0.4, 0.4) + ($centroTemp - $tempAnterior) * 0.25;
        }
        $humedad = $humAnterior + self::azar(-1.5, 1.5) + ($centroHum - $humAnterior) * 0.2;
        $temperatura = round($temperatura, 2);
        $humedad = round(max(0, min(100, $humedad)), 2);

        $this->ejecutar("INSERT INTO Lectura_Sensor (id_sensor, temperatura, humedad, fecha_hora) VALUES (?, ?, ?, NOW())",
            'idd', [(int)$sensor['id_sensor'], $temperatura, $humedad]);
        return ['temperatura' => $temperatura, 'humedad' => $humedad];
    }

    /* Últimas lecturas para el gráfico (de la más vieja a la más nueva) */
    public function serie($idSensor, $cantidad = 30)
    {
        return array_reverse($this->filas("SELECT temperatura, humedad, fecha_hora FROM Lectura_Sensor WHERE id_sensor = ?
                                           ORDER BY fecha_hora DESC, id_lectura DESC LIMIT ?", 'ii', [$idSensor, $cantidad]));
    }

    /* Lecturas fuera de rango en las últimas 24 horas */
    public function alertas24Horas(array $sensor)
    {
        return (int)$this->valor("SELECT COUNT(*) FROM Lectura_Sensor
                                  WHERE id_sensor = ? AND fecha_hora >= NOW() - INTERVAL 24 HOUR
                                    AND (temperatura NOT BETWEEN ? AND ? OR humedad NOT BETWEEN ? AND ?)",
            'idddd', [(int)$sensor['id_sensor'], $sensor['temp_min'], $sensor['temp_max'], $sensor['hum_min'], $sensor['hum_max']]);
    }

    /* ---------- Reporte de lecturas ----------
       Filtros: sensor (0 = todos), rango de fechas [desde, hasta) y si se quieren
       solo las lecturas fuera de rango. El estado se calcula en la misma consulta. */
    const ESTADO_SQL = "CASE WHEN l.temperatura NOT BETWEEN s.temp_min AND s.temp_max THEN 'ALERTA: temperatura'
                             WHEN l.humedad NOT BETWEEN s.hum_min AND s.hum_max THEN 'ALERTA: humedad'
                             ELSE 'Normal' END";

    public function lecturas($idSensor, $desde, $hasta, $soloAlertas, $limite = 5000)
    {
        return $this->filas("SELECT l.fecha_hora, s.codigo, z.nombre AS zona, z.tipo AS tipo_zona,
                                    l.temperatura, l.humedad, " . self::ESTADO_SQL . " AS estado
                             FROM Lectura_Sensor l
                             INNER JOIN Sensor s ON s.id_sensor = l.id_sensor
                             INNER JOIN Zona z ON z.id_zona = s.id_zona
                             WHERE l.fecha_hora >= ? AND l.fecha_hora < ? AND (? = 0 OR s.id_sensor = ?)
                             HAVING ? = 0 OR estado <> 'Normal'
                             ORDER BY l.fecha_hora DESC, l.id_lectura DESC
                             LIMIT ?", 'ssiiii', [$desde, $hasta, $idSensor, $idSensor, $soloAlertas ? 1 : 0, $limite]);
    }

    /* Resumen por sensor en el mismo período: cantidad, mínimo, promedio, máximo y lecturas fuera de rango */
    public function resumen($idSensor, $desde, $hasta)
    {
        return $this->filas("SELECT s.codigo, z.nombre AS zona, z.tipo AS tipo_zona, s.temp_min, s.temp_max, s.hum_min, s.hum_max,
                                    COUNT(l.id_lectura) AS lecturas,
                                    MIN(l.temperatura) AS temp_minima, ROUND(AVG(l.temperatura), 2) AS temp_promedio, MAX(l.temperatura) AS temp_maxima,
                                    MIN(l.humedad) AS hum_minima, ROUND(AVG(l.humedad), 2) AS hum_promedio, MAX(l.humedad) AS hum_maxima,
                                    SUM(" . self::ESTADO_SQL . " <> 'Normal') AS fuera_rango
                             FROM Sensor s
                             INNER JOIN Zona z ON z.id_zona = s.id_zona
                             LEFT JOIN Lectura_Sensor l ON l.id_sensor = s.id_sensor AND l.fecha_hora >= ? AND l.fecha_hora < ?
                             WHERE ? = 0 OR s.id_sensor = ?
                             GROUP BY s.id_sensor, s.codigo, z.nombre, z.tipo, s.temp_min, s.temp_max, s.hum_min, s.hum_max
                             ORDER BY z.tipo = 'Refrigerada' DESC, z.nombre", 'ssii', [$desde, $hasta, $idSensor, $idSensor]);
    }

    /* Número decimal al azar entre $min y $max */
    private static function azar($min, $max)
    {
        return $min + mt_rand() / mt_getrandmax() * ($max - $min);
    }
}
