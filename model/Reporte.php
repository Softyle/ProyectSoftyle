<?php
/* =====================================================
   MODELO - REPORTES PARA EL CLIENTE
   Diez consultas avanzadas sobre el depósito (tercera entrega
   y entrega final). Cada reporte guarda su SQL para que la
   pantalla pueda mostrar la consulta que se ejecutó.
   Usan JOIN, subconsultas, NOT EXISTS, agregación condicional,
   la vista Vista_Estadisticas_Operario y la función RANK().
===================================================== */
class Reporte extends Modelo
{
    /* clave => [título, descripción, ícono, consulta SQL] */
    const REPORTES = [
        'mas-pedidos' => [
            'Productos más pedidos',
            'Los 10 productos con más unidades solicitadas y en cuántos pedidos aparecen.',
            'bi-graph-up-arrow',
            "SELECT p.Nombre AS `Producto`, c.nombre AS `Categoría`,
       COUNT(DISTINCT t.id_pedido) AS `Pedidos`,
       SUM(t.Cantidad) AS `Unidades`
FROM Tiene t
INNER JOIN Producto p ON p.id_producto = t.id_producto
INNER JOIN Categoria c ON c.id_categoria = p.id_categoria
GROUP BY p.id_producto, p.Nombre, c.nombre
ORDER BY `Unidades` DESC
LIMIT 10",
        ],
        'ranking' => [
            'Ranking de operarios',
            'Puesto de cada operario según pedidos completados y precisión (vista Vista_Estadisticas_Operario + RANK).',
            'bi-trophy',
            "SELECT RANK() OVER (ORDER BY e.pedidos_completados DESC, e.precision_pct DESC) AS `Puesto`,
       e.operario AS `Operario`,
       e.pedidos_completados AS `Pedidos completados`,
       e.unidades_recogidas AS `Unidades recogidas`,
       CONCAT(COALESCE(e.precision_pct, 0), ' %') AS `Precisión`,
       COALESCE(CONCAT(e.minutos_promedio, ' min'), '—') AS `Tiempo promedio`
FROM Vista_Estadisticas_Operario e
ORDER BY `Puesto`, e.operario",
        ],
        'reposicion' => [
            'Reposición necesaria',
            'Productos cuyo stock disponible (descontando lo reservado por pedidos sin recoger) ya está en el mínimo o por debajo.',
            'bi-exclamation-triangle',
            "SELECT p.Nombre AS `Producto`, z.nombre AS `Zona`,
       p.stock_actual AS `Stock`,
       COALESCE(r.reservado, 0) AS `Reservado`,
       p.stock_actual - COALESCE(r.reservado, 0) AS `Disponible`,
       p.stock_minimo AS `Mínimo`
FROM Producto p
INNER JOIN Ubicacion u ON u.id_ubicacion = p.id_ubicacion
INNER JOIN Zona z ON z.id_zona = u.id_zona
LEFT JOIN (SELECT t.id_producto, SUM(t.Cantidad) AS reservado
           FROM Tiene t
           INNER JOIN Pedido pe ON pe.id_pedido = t.id_pedido
           WHERE pe.estado IN ('Pendiente', 'En Picking') AND t.recogido = 0
           GROUP BY t.id_producto) r ON r.id_producto = p.id_producto
WHERE p.stock_actual - COALESCE(r.reservado, 0) <= p.stock_minimo
ORDER BY `Disponible`, p.Nombre",
        ],
        'ocupacion' => [
            'Ocupación por zona',
            'Ubicaciones, productos y unidades guardadas en cada zona, comparadas con su capacidad.',
            'bi-grid-3x3-gap',
            "SELECT z.nombre AS `Zona`, z.tipo AS `Tipo`,
       COUNT(DISTINCT u.id_ubicacion) AS `Ubicaciones`,
       COUNT(DISTINCT p.id_producto) AS `Productos`,
       COALESCE(SUM(p.stock_actual), 0) AS `Unidades`,
       z.capacidad_total AS `Capacidad`,
       COALESCE(CONCAT(ROUND(100 * SUM(p.stock_actual) / NULLIF(z.capacidad_total, 0), 1), ' %'), '—') AS `Ocupación`
FROM Zona z
LEFT JOIN Ubicacion u ON u.id_zona = z.id_zona
LEFT JOIN Producto p ON p.id_ubicacion = u.id_ubicacion
GROUP BY z.id_zona, z.nombre, z.tipo, z.capacidad_total
ORDER BY z.nombre",
        ],
        'estados' => [
            'Pedidos por estado',
            'Cantidad de pedidos en cada etapa y, para los completados, el tiempo promedio desde que se crearon hasta su entrega.',
            'bi-diagram-3',
            "SELECT pe.estado AS `Estado`,
       COUNT(*) AS `Pedidos`,
       COALESCE(SUM(t.unidades), 0) AS `Unidades`,
       COALESCE(CONCAT(ROUND(AVG(CASE WHEN pe.estado = 'Completado'
                    THEN TIMESTAMPDIFF(MINUTE, pe.fecha_creacion, pe.fecha_entrega) END) / 60, 1), ' h'), '—') AS `Tiempo promedio hasta la entrega`
FROM Pedido pe
LEFT JOIN (SELECT id_pedido, SUM(Cantidad) AS unidades FROM Tiene GROUP BY id_pedido) t ON t.id_pedido = pe.id_pedido
GROUP BY pe.estado
ORDER BY FIELD(pe.estado, 'Pendiente', 'En Picking', 'Por validar', 'Completado')",
        ],
        'errores' => [
            'Errores de picking por zona',
            'Retiros correctos frente a errores de ubicación y de cantidad en cada zona: muestra dónde se equivocan más los operarios.',
            'bi-bullseye',
            "SELECT z.nombre AS `Zona`,
       SUM(r.Tipo_Registro = 'Retiro') AS `Retiros correctos`,
       SUM(r.Tipo_Registro = 'Error de ubicación') AS `Errores de ubicación`,
       SUM(r.Tipo_Registro = 'Error de cantidad') AS `Errores de cantidad`,
       CONCAT(ROUND(100 * SUM(r.Tipo_Registro = 'Retiro') / COUNT(*), 1), ' %') AS `Precisión`
FROM Registra r
INNER JOIN Producto p ON p.id_producto = r.id_producto
INNER JOIN Ubicacion u ON u.id_ubicacion = p.id_ubicacion
INNER JOIN Zona z ON z.id_zona = u.id_zona
WHERE r.Tipo_Registro = 'Retiro' OR r.Tipo_Registro LIKE 'Error%'
GROUP BY z.id_zona, z.nombre
ORDER BY SUM(r.Tipo_Registro LIKE 'Error%') DESC, z.nombre",
        ],
        'clientes' => [
            'Clientes con más pedidos',
            'Los 10 clientes que más pedidos hicieron, con las unidades solicitadas y la fecha de su último pedido.',
            'bi-people',
            "SELECT pc.usuario AS `Cliente`,
       COUNT(DISTINCT pe.id_pedido) AS `Pedidos`,
       COALESCE(SUM(t.Cantidad), 0) AS `Unidades`,
       MAX(pe.fecha_creacion) AS `Último pedido`
FROM Pedido pe
INNER JOIN Persona pc ON pc.CI = pe.ci_cliente
LEFT JOIN Tiene t ON t.id_pedido = pe.id_pedido
GROUP BY pe.ci_cliente, pc.usuario
ORDER BY `Pedidos` DESC, `Unidades` DESC
LIMIT 10",
        ],
        'movimientos' => [
            'Movimientos de stock por día',
            'Actividad de los últimos 30 días: retiros, unidades retiradas, devoluciones al picking y errores.',
            'bi-calendar3',
            "SELECT DATE(r.fecha_hora) AS `Día`,
       SUM(r.Tipo_Registro = 'Retiro') AS `Retiros`,
       SUM(CASE WHEN r.Tipo_Registro = 'Retiro' THEN r.cantidad ELSE 0 END) AS `Unidades retiradas`,
       SUM(r.Tipo_Registro = 'Devolución') AS `Devoluciones`,
       SUM(r.Tipo_Registro LIKE 'Error%') AS `Errores`
FROM Registra r
WHERE r.fecha_hora >= CURDATE() - INTERVAL 30 DAY
GROUP BY DATE(r.fecha_hora)
ORDER BY `Día` DESC",
        ],
        'categorias' => [
            'Despachos por categoría',
            'Stock actual y unidades ya despachadas (pedidos completados) de cada categoría de productos.',
            'bi-tags',
            "SELECT c.nombre AS `Categoría`,
       COUNT(p.id_producto) AS `Productos`,
       SUM(p.stock_actual) AS `Stock total`,
       COALESCE(SUM(d.despachado), 0) AS `Unidades despachadas`
FROM Categoria c
INNER JOIN Producto p ON p.id_categoria = c.id_categoria
LEFT JOIN (SELECT t.id_producto, SUM(t.Cantidad) AS despachado
           FROM Tiene t
           INNER JOIN Pedido pe ON pe.id_pedido = t.id_pedido
           WHERE pe.estado = 'Completado'
           GROUP BY t.id_producto) d ON d.id_producto = p.id_producto
GROUP BY c.id_categoria, c.nombre
ORDER BY `Unidades despachadas` DESC, c.nombre",
        ],
        'sin-movimiento' => [
            'Productos sin pedidos',
            'Productos que nunca se pidieron: ocupan lugar en el depósito sin rotación (NOT EXISTS).',
            'bi-box2',
            "SELECT p.Nombre AS `Producto`, c.nombre AS `Categoría`, z.nombre AS `Zona`,
       CONCAT(u.pasillo, '-', u.estante, '-', u.posicion) AS `Ubicación`,
       p.stock_actual AS `Stock`
FROM Producto p
INNER JOIN Categoria c ON c.id_categoria = p.id_categoria
INNER JOIN Ubicacion u ON u.id_ubicacion = p.id_ubicacion
INNER JOIN Zona z ON z.id_zona = u.id_zona
WHERE NOT EXISTS (SELECT 1 FROM Tiene t WHERE t.id_producto = p.id_producto)
ORDER BY p.stock_actual DESC, p.Nombre",
        ],
    ];

    /* Ejecuta todos los reportes. Si uno falla, los demás se muestran igual. */
    public function todos()
    {
        $reportes = [];
        foreach (self::REPORTES as $clave => [$titulo, $descripcion, $icono, $sql]) {
            $reporte = ['clave' => $clave, 'titulo' => $titulo, 'descripcion' => $descripcion,
                        'icono' => $icono, 'sql' => $sql, 'filas' => [], 'error' => ''];
            try {
                $reporte['filas'] = $this->filas($sql);
            } catch (Throwable $ex) {
                error_log('PICKFLOW Reporte ' . $clave . ': ' . $ex->getMessage());
                $reporte['error'] = 'No se pudo generar este reporte.';
            }
            $reportes[] = $reporte;
        }
        return $reportes;
    }
}
