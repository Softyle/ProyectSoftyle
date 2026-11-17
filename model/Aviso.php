<?php
/* =====================================================
   MODELO - AVISOS (campanita de la barra superior)
   No se guardan: se calculan en el momento con lo que hay
   que atender. Cada aviso es una cosa pendiente, con la
   cantidad y la pantalla donde se resuelve.

   Administrador: stock bajo, lotes vencidos o por vencer,
   pedidos por validar o sin asignar, conteos, órdenes de
   compra atrasadas y alertas de temperatura.
   Operario: sus pedidos y sus conteos.
===================================================== */
class Aviso extends Modelo
{
    /* Lista de avisos: [clave, icono, tono, texto, cantidad, url].
       tono: 'rojo' (urgente), 'ambar' (atención) o 'azul' (para hacer). */
    public function para($rol, $ci)
    {
        $avisos = [];
        $agregar = function ($clave, $icono, $tono, $cantidad, $texto, $url) use (&$avisos) {
            if ($cantidad > 0) {
                $avisos[] = ['clave' => $clave, 'icono' => $icono, 'tono' => $tono, 'cantidad' => (int)$cantidad, 'texto' => $texto, 'url' => $url];
            }
        };
        $plural = fn($n, $uno, $varios) => $n . ' ' . ($n === 1 ? $uno : $varios);

        if ($rol === 'admin') {
            $n = $this->contar("SELECT COUNT(*) FROM Producto p
                                LEFT JOIN (SELECT t.id_producto, SUM(t.Cantidad) AS reservado FROM Tiene t
                                           JOIN Pedido pe ON pe.id_pedido = t.id_pedido
                                           WHERE pe.estado IN ('Pendiente', 'En Picking') AND t.recogido = 0
                                           GROUP BY t.id_producto) r ON r.id_producto = p.id_producto
                                WHERE p.stock_actual - COALESCE(r.reservado, 0) <= p.stock_minimo");
            $agregar('stock', 'bi-exclamation-triangle', 'rojo', $n,
                $plural($n, 'producto quedó', 'productos quedaron') . ' en el stock mínimo o por debajo', url('compra', 'reposicion'));

            $lotes = (new Lote())->resumen();
            $agregar('vencidos', 'bi-calendar2-x', 'rojo', $lotes['vencidos'],
                $plural($lotes['vencidos'], 'lote vencido', 'lotes vencidos') . ' para dar de baja', url('recepcion', 'vencimientos', ['dias' => Lote::DIAS_AVISO]));
            $agregar('por-vencer', 'bi-calendar2-week', 'ambar', $lotes['por_vencer'],
                $plural($lotes['por_vencer'], 'lote vence', 'lotes vencen') . ' en los próximos ' . Lote::DIAS_AVISO . ' días', url('recepcion', 'vencimientos', ['dias' => Lote::DIAS_AVISO]));

            $n = $this->contar("SELECT COUNT(*) FROM Pedido WHERE estado = 'Por validar'");
            $agregar('por-validar', 'bi-clipboard-check', 'azul', $n,
                $plural($n, 'pedido espera', 'pedidos esperan') . ' que lo valides', url('pedido', 'index', ['estado' => 'Por validar']));

            $n = $this->contar("SELECT COUNT(*) FROM Pedido WHERE estado = 'Pendiente' AND ci_operario IS NULL");
            $agregar('sin-asignar', 'bi-person-plus', 'azul', $n,
                $plural($n, 'pedido pendiente', 'pedidos pendientes') . ' sin operario asignado', url('pedido', 'index', ['estado' => 'Pendiente']));

            // Conteos en curso: los que ya tienen todo contado están listos para cerrar
            $f = $this->filaSegura("SELECT COUNT(*) AS en_curso, COALESCE(SUM(faltan = 0), 0) AS listos FROM (
                                        SELECT c.id_conteo, SUM(d.cantidad_contada IS NULL) AS faltan
                                        FROM Conteo c JOIN Conteo_Detalle d ON d.id_conteo = c.id_conteo
                                        WHERE c.estado = 'En curso' GROUP BY c.id_conteo) x");
            $listos = (int)($f['listos'] ?? 0);
            $agregar('conteos-listos', 'bi-clipboard-data', 'ambar', $listos,
                $plural($listos, 'conteo terminado espera', 'conteos terminados esperan') . ' que lo cierres', url('conteo'));
            $enCurso = (int)($f['en_curso'] ?? 0) - $listos;
            $agregar('conteos', 'bi-clipboard-data', 'azul', $enCurso,
                $plural($enCurso, 'conteo de inventario', 'conteos de inventario') . ' en curso', url('conteo'));

            $n = $this->contar("SELECT COUNT(*) FROM Orden_Compra WHERE estado IN ('Pendiente', 'Parcial') AND fecha_esperada < CURDATE()");
            $agregar('ordenes-atrasadas', 'bi-truck', 'ambar', $n,
                $plural($n, 'orden de compra está atrasada', 'órdenes de compra están atrasadas'), url('compra', 'index', ['estado' => 'Abiertas']));

            $n = $this->contar("SELECT COUNT(*) FROM Notificacion WHERE tipo = 'Cadena de frío' AND fecha >= NOW() - INTERVAL 1 DAY");
            $agregar('frio', 'bi-thermometer-snow', 'rojo', $n,
                $plural($n, 'alerta de temperatura', 'alertas de temperatura') . ' en las últimas 24 horas', url('sensor'));
        } elseif ($rol === 'operario') {
            $n = $this->contar("SELECT COUNT(*) FROM Pedido WHERE ci_operario = ? AND estado = 'Pendiente'", 's', [$ci]);
            $agregar('mis-pedidos', 'bi-clipboard-check', 'azul', $n,
                'Tenés ' . $plural($n, 'pedido nuevo', 'pedidos nuevos') . ' para preparar', url('picking'));

            $n = $this->contar("SELECT COUNT(*) FROM Pedido WHERE ci_operario = ? AND estado = 'En Picking'", 's', [$ci]);
            $agregar('mis-pedidos-curso', 'bi-cart-check', 'ambar', $n,
                $plural($n, 'pedido tuyo está', 'pedidos tuyos están') . ' en preparación', url('picking'));

            $n = $this->contar("SELECT COUNT(*) FROM Conteo WHERE ci_asignado = ? AND estado = 'En curso'", 's', [$ci]);
            $agregar('mis-conteos', 'bi-clipboard-data', 'azul', $n,
                'Tenés ' . $plural($n, 'conteo de inventario', 'conteos de inventario') . ' para hacer', url('conteo'));
        }
        return $avisos;
    }

    /* Un número; si la consulta falla (por ejemplo, falta una tabla) el aviso no se muestra */
    private function contar($sql, $tipos = '', array $parametros = [])
    {
        try {
            return (int)$this->valor($sql, $tipos, $parametros);
        } catch (mysqli_sql_exception $ex) {
            error_log('PICKFLOW avisos: ' . $ex->getMessage());
            return 0;
        }
    }

    private function filaSegura($sql)
    {
        try {
            return $this->fila($sql) ?: [];
        } catch (mysqli_sql_exception $ex) {
            error_log('PICKFLOW avisos: ' . $ex->getMessage());
            return [];
        }
    }
}
