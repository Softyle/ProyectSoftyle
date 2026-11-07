<?php
/* =====================================================
   MODELO - LOGROS (gamificación)
   Usa la vista Vista_Estadisticas_Operario, las tablas
   Logro y Operario_Logro y el procedimiento AsignarLogros
   (softyleDEF.sql).
===================================================== */
class Logro extends Modelo
{
    /* Otorga al operario los logros que ya alcanzó (procedimiento almacenado).
       Si falla, no corta la página: los logros se vuelven a calcular la próxima vez. */
    public function actualizar($ci)
    {
        try {
            $this->ejecutar("CALL AsignarLogros(?)", 's', [$ci]);
            while ($this->db->more_results() && $this->db->next_result()) {
                // un CALL puede dejar resultados pendientes: se descartan
            }
        } catch (Throwable $ex) {
            error_log('PICKFLOW Logro::actualizar: ' . $ex->getMessage());
        }
    }

    /* Estadísticas de picking de un operario (precisión, velocidad, volumen) */
    public function estadisticas($ci)
    {
        return $this->fila("SELECT * FROM Vista_Estadisticas_Operario WHERE ci_operario = ?", 's', [$ci])
            ?: ['pedidos_completados' => 0, 'unidades_recogidas' => 0, 'retiros' => 0, 'errores' => 0,
                'minutos_promedio' => null, 'precision_pct' => null];
    }

    /* Galería: TODAS las insignias, marcando las obtenidas y cuánto le falta para cada una.
       'progreso' va de 0 a 100 y 'detalle' explica el avance en palabras. */
    public function galeria($ci, array $est)
    {
        $logros = $this->filas("SELECT l.*, ol.fecha_obtenido
                                FROM Logro l
                                LEFT JOIN Operario_Logro ol ON ol.id_logro = l.id_logro AND ol.ci_operario = ?
                                ORDER BY FIELD(l.categoria, 'Volumen', 'Precisión', 'Velocidad'), ol.id_logro IS NULL,
                                         FIELD(l.nivel, 'Bronce', 'Plata', 'Oro'), l.criterio,
                                         IF(l.criterio = 'minutos_promedio', -l.umbral, l.umbral)", 's', [$ci]);

        foreach ($logros as &$l) {
            $umbral = (float)$l['umbral'];
            switch ($l['criterio']) {
                case 'pedidos_completados':
                    $valor = (int)$est['pedidos_completados'];
                    $l['progreso'] = min(100, $umbral > 0 ? $valor * 100 / $umbral : 100);
                    $l['detalle'] = $valor . ' de ' . (int)$umbral . ' pedidos';
                    break;
                case 'unidades_recogidas':
                    $valor = (int)$est['unidades_recogidas'];
                    $l['progreso'] = min(100, $umbral > 0 ? $valor * 100 / $umbral : 100);
                    $l['detalle'] = $valor . ' de ' . (int)$umbral . ' unidades';
                    break;
                case 'retiros':
                    $valor = (int)$est['retiros'];
                    $l['progreso'] = min(100, $umbral > 0 ? $valor * 100 / $umbral : 100);
                    $l['detalle'] = $valor . ' de ' . (int)$umbral . ' retiros';
                    break;
                case 'precision_pct':
                    $valor = (float)($est['precision_pct'] ?? 0);
                    $l['progreso'] = min(100, $umbral > 0 ? $valor * 100 / $umbral : 100);
                    $l['detalle'] = 'Precisión actual: ' . ($est['precision_pct'] === null ? 'sin retiros' : $valor . '%');
                    break;
                case 'precision_perfecta':
                    $sinErrores = (int)$est['errores'] === 0;
                    $valor = $sinErrores ? (int)$est['retiros'] : 0;
                    $l['progreso'] = min(100, $umbral > 0 ? $valor * 100 / $umbral : 100);
                    $l['detalle'] = $sinErrores ? $valor . ' de ' . (int)$umbral . ' retiros sin errores'
                                                : 'Tenés ' . (int)$est['errores'] . ' error(es) registrados';
                    break;
                case 'minutos_promedio': // en velocidad, menos minutos es mejor
                    $valor = $est['minutos_promedio'];
                    $l['progreso'] = $valor === null ? 0 : min(100, (float)$valor <= 0 ? 100 : $umbral * 100 / (float)$valor);
                    $l['detalle'] = $valor === null ? 'Todavía sin pedidos medidos' : 'Promedio actual: ' . $valor . ' min';
                    break;
                default:
                    $l['progreso'] = 0;
                    $l['detalle'] = '';
            }
            $l['obtenido'] = $l['fecha_obtenido'] !== null;
            if ($l['obtenido']) {
                $l['progreso'] = 100;
            }
        }
        unset($l);
        return $logros;
    }

    /* Ranking por pedidos completados y precisión (función de ventana RANK() de MySQL) */
    public function ranking()
    {
        return $this->filas("SELECT RANK() OVER (ORDER BY e.pedidos_completados DESC, e.precision_pct DESC) AS posicion,
                                    e.*, o.Estado, p.foto_perfil
                             FROM Vista_Estadisticas_Operario e
                             INNER JOIN Operario o ON o.CI = e.ci_operario
                             INNER JOIN Persona p ON p.CI = e.ci_operario
                             ORDER BY posicion, e.operario");
    }

    /* Insignias obtenidas, agrupadas por operario */
    public function insigniasPorOperario()
    {
        $insignias = [];
        foreach ($this->filas("SELECT ol.ci_operario, l.nombre, l.nivel, l.icono, ol.fecha_obtenido
                               FROM Operario_Logro ol INNER JOIN Logro l ON l.id_logro = ol.id_logro
                               ORDER BY FIELD(l.nivel, 'Oro', 'Plata', 'Bronce'), l.nombre") as $i) {
            $insignias[$i['ci_operario']][] = $i;
        }
        return $insignias;
    }

    public function total()
    {
        return (int)$this->valor("SELECT COUNT(*) FROM Logro");
    }
}
