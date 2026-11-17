<?php
/* =====================================================
   MODELO - REPORTES PARA EL CLIENTE
   Diez consultas avanzadas sobre el depósito (tercera entrega
   y entrega final) y siete de abastecimiento (recepción, lotes,
   conteos y proveedores). Cada reporte guarda su SQL para que la
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
            'Retiros correctos frente a errores de ubicación, de cantidad y de producto (código de barras equivocado) en cada zona: muestra dónde se equivocan más los operarios.',
            'bi-bullseye',
            "SELECT z.nombre AS `Zona`,
       SUM(r.Tipo_Registro = 'Retiro') AS `Retiros correctos`,
       SUM(r.Tipo_Registro = 'Error de ubicación') AS `Errores de ubicación`,
       SUM(r.Tipo_Registro = 'Error de cantidad') AS `Errores de cantidad`,
       SUM(r.Tipo_Registro = 'Error de producto') AS `Errores de producto`,
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

        /* ---------- Abastecimiento: recepción, lotes, conteos y proveedores ---------- */
        'rotacion' => [
            'Productos que más se mueven',
            'Los 15 productos con más unidades retiradas en los últimos 30 días (descontando devoluciones), cuánto sale por día y para cuántos días alcanza el stock actual.',
            'bi-speedometer',
            "SELECT p.Nombre AS `Producto`, c.nombre AS `Categoría`,
       SUM(CASE r.Tipo_Registro WHEN 'Retiro' THEN r.cantidad ELSE -r.cantidad END) AS `Unidades retiradas`,
       ROUND(SUM(CASE r.Tipo_Registro WHEN 'Retiro' THEN r.cantidad ELSE -r.cantidad END) / 30, 1) AS `Por día`,
       p.stock_actual AS `Stock`,
       COALESCE(CONCAT(FLOOR(p.stock_actual / NULLIF(SUM(CASE r.Tipo_Registro WHEN 'Retiro' THEN r.cantidad ELSE -r.cantidad END) / 30, 0)), ' días'), '—') AS `Alcanza para`
FROM Registra r
INNER JOIN Producto p ON p.id_producto = r.id_producto
INNER JOIN Categoria c ON c.id_categoria = p.id_categoria
WHERE r.Tipo_Registro IN ('Retiro', 'Devolución') AND r.fecha_hora >= CURDATE() - INTERVAL 30 DAY
GROUP BY p.id_producto, p.Nombre, c.nombre, p.stock_actual
HAVING `Unidades retiradas` > 0
ORDER BY `Unidades retiradas` DESC
LIMIT 15",
        ],
        'recepciones-proveedor' => [
            'Recepciones por proveedor',
            'Mercadería recibida en los últimos 90 días agrupada por proveedor: cuántas veces entró, cuántos productos distintos y cuántas unidades.',
            'bi-box-arrow-in-down',
            "SELECT COALESCE(v.nombre, 'Sin proveedor') AS `Proveedor`,
       COUNT(DISTINCT rm.id_recepcion) AS `Recepciones`,
       COUNT(DISTINCT l.id_producto) AS `Productos distintos`,
       COALESCE(SUM(l.cantidad_recibida), 0) AS `Unidades recibidas`,
       MAX(rm.fecha) AS `Última recepción`
FROM Recepcion_Mercaderia rm
LEFT JOIN Proveedor v ON v.id_proveedor = rm.id_proveedor
LEFT JOIN Lote l ON l.id_recepcion = rm.id_recepcion
WHERE rm.fecha >= CURDATE() - INTERVAL 90 DAY
GROUP BY rm.id_proveedor, v.nombre
ORDER BY `Unidades recibidas` DESC",
        ],
        'cumplimiento-ordenes' => [
            'Cumplimiento de las órdenes de compra',
            'Por proveedor: órdenes recibidas, parciales y pendientes, las que ya pasaron su fecha esperada y qué porcentaje de lo pedido llegó (sin contar las canceladas).',
            'bi-receipt',
            "SELECT v.nombre AS `Proveedor`,
       COUNT(*) AS `Órdenes`,
       SUM(o.estado = 'Recibida') AS `Recibidas`,
       SUM(o.estado = 'Parcial') AS `Parciales`,
       SUM(o.estado = 'Pendiente') AS `Pendientes`,
       SUM(o.estado IN ('Pendiente', 'Parcial') AND o.fecha_esperada < CURDATE()) AS `Atrasadas`,
       CONCAT(ROUND(100 * SUM(d.recibido) / NULLIF(SUM(d.pedido), 0), 1), ' %') AS `Unidades que llegaron`
FROM Orden_Compra o
INNER JOIN Proveedor v ON v.id_proveedor = o.id_proveedor
LEFT JOIN (SELECT id_orden, SUM(cantidad_pedida) AS pedido, SUM(LEAST(cantidad_recibida, cantidad_pedida)) AS recibido
           FROM Orden_Compra_Detalle GROUP BY id_orden) d ON d.id_orden = o.id_orden
WHERE o.estado <> 'Cancelada'
GROUP BY v.id_proveedor, v.nombre
ORDER BY `Órdenes` DESC, v.nombre",
        ],
        'diferencias-conteo' => [
            'Diferencias en los conteos de inventario',
            'Cada conteo cerrado: cuántos productos se contaron, cuántos no coincidían con el sistema, las unidades de más y de menos, y la exactitud del inventario.',
            'bi-clipboard-data',
            "SELECT c.id_conteo AS `Conteo`, z.nombre AS `Zona`, c.fecha_cierre AS `Cerrado`,
       COUNT(d.cantidad_contada) AS `Productos contados`,
       SUM(d.cantidad_contada <> d.stock_sistema) AS `Con diferencia`,
       SUM(GREATEST(d.cantidad_contada - d.stock_sistema, 0)) AS `Unidades de más`,
       SUM(GREATEST(d.stock_sistema - d.cantidad_contada, 0)) AS `Unidades de menos`,
       CONCAT(ROUND(100 * SUM(d.cantidad_contada = d.stock_sistema) / NULLIF(COUNT(d.cantidad_contada), 0), 1), ' %') AS `Exactitud`
FROM Conteo c
INNER JOIN Zona z ON z.id_zona = c.id_zona
INNER JOIN Conteo_Detalle d ON d.id_conteo = c.id_conteo
WHERE c.estado = 'Cerrado'
GROUP BY c.id_conteo, z.nombre, c.fecha_cierre
ORDER BY c.fecha_cierre DESC",
        ],
        'lotes-por-vencer' => [
            'Lotes por vencer',
            'Lotes con mercadería que vencen en los próximos 30 días (o ya vencieron), del más urgente al menos urgente.',
            'bi-calendar2-week',
            "SELECT p.Nombre AS `Producto`, COALESCE(l.codigo_lote, '—') AS `Lote`,
       l.fecha_vencimiento AS `Vence`,
       CASE WHEN l.fecha_vencimiento < CURDATE() THEN 'Vencido'
            ELSE CONCAT(DATEDIFF(l.fecha_vencimiento, CURDATE()), ' días') END AS `Faltan`,
       l.cantidad_actual AS `Unidades`,
       CONCAT(z.nombre, ' / ', u.pasillo, '-', u.estante, '-', u.posicion) AS `Ubicación`
FROM Lote l
INNER JOIN Producto p ON p.id_producto = l.id_producto
INNER JOIN Ubicacion u ON u.id_ubicacion = p.id_ubicacion
INNER JOIN Zona z ON z.id_zona = u.id_zona
WHERE l.cantidad_actual > 0 AND l.fecha_vencimiento IS NOT NULL
  AND l.fecha_vencimiento <= CURDATE() + INTERVAL 30 DAY
ORDER BY l.fecha_vencimiento, p.Nombre",
        ],
        'bajas-vencimiento' => [
            'Mercadería dada de baja por vencimiento',
            'Lo que se sacó del stock por estar vencido en los últimos 90 días: qué producto, cuántas unidades y quién lo dio de baja.',
            'bi-trash3',
            "SELECT m.fecha AS `Fecha`, p.Nombre AS `Producto`,
       ABS(m.cantidad) AS `Unidades`,
       pe.usuario AS `Dio de baja`,
       m.detalle AS `Detalle`
FROM Movimiento_Stock m
INNER JOIN Producto p ON p.id_producto = m.id_producto
INNER JOIN Persona pe ON pe.CI = m.ci_persona
WHERE m.tipo = 'Baja por vencimiento' AND m.fecha >= CURDATE() - INTERVAL 90 DAY
ORDER BY m.fecha DESC",
        ],
        'ajustes-stock' => [
            'Cambios de stock por motivo',
            'Todo lo que cambió el stock fuera del picking en los últimos 30 días (recepciones, conteos, ajustes manuales y bajas), con las unidades que entraron y salieron.',
            'bi-arrow-left-right',
            "SELECT m.tipo AS `Motivo`,
       COUNT(*) AS `Movimientos`,
       COUNT(DISTINCT m.id_producto) AS `Productos`,
       SUM(GREATEST(m.cantidad, 0)) AS `Unidades que entraron`,
       SUM(GREATEST(-m.cantidad, 0)) AS `Unidades que salieron`
FROM Movimiento_Stock m
WHERE m.fecha >= CURDATE() - INTERVAL 30 DAY
GROUP BY m.tipo
ORDER BY `Movimientos` DESC",
        ],
    ];

    /* Reportes que van en el grupo "Abastecimiento"; el resto es "Pedidos y depósito" */
    const ABASTECIMIENTO = ['rotacion', 'recepciones-proveedor', 'cumplimiento-ordenes', 'diferencias-conteo',
                            'lotes-por-vencer', 'bajas-vencimiento', 'ajustes-stock'];

    /* Ejecuta todos los reportes. Si uno falla, los demás se muestran igual. */
    public function todos()
    {
        $reportes = [];
        foreach (self::REPORTES as $clave => [$titulo, $descripcion, $icono, $sql]) {
            $reporte = ['clave' => $clave, 'titulo' => $titulo, 'descripcion' => $descripcion,
                        'grupo' => in_array($clave, self::ABASTECIMIENTO, true) ? 'Abastecimiento' : 'Pedidos y depósito',
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
