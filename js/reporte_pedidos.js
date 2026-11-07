/* =====================================================
   PICKFLOW - REPORTE DE PEDIDOS (DataTables + Buttons)
   Convierte la tabla de "Ver pedidos" en un reporte con:
     - búsqueda, orden por columna y paginación
     - exportación a Excel (JSZip), PDF (pdfmake), CSV
     - impresión
   PHP arma la tabla con los datos de MySQL; esta librería
   de JavaScript trabaja sobre esa tabla en el navegador.
   ===================================================== */
$(function () {
    var $tabla = $('#tablaPedidos');
    if (!$tabla.length) {
        return;
    }

    var titulo = $tabla.data('titulo');
    var ahora = new Date();
    var fechaTexto = ahora.toLocaleDateString('es-UY') + ' ' + ahora.toLocaleTimeString('es-UY', { hour: '2-digit', minute: '2-digit' });
    var subtitulo = 'PICKFLOW - Sistema de Picking y Logística. Generado el ' + fechaTexto + ' por ' + $tabla.data('usuario') + '.';
    var nombreArchivo = 'reporte_pedidos_' + ahora.toISOString().slice(0, 10);

    /* Qué se exporta: todas las columnas menos "Acción" (botones y formularios).
       Si la celda tiene data-exportar se usa ese texto limpio (por ejemplo, el
       nombre del operario sin la aclaración "Completado por este operario"). */
    var exportar = {
        columns: ':not(.no-exportar)',
        format: {
            body: function (dato, fila, columna, celda) {
                var limpio = $(celda).attr('data-exportar');
                return limpio !== undefined ? limpio : $(celda).text().replace(/\s+/g, ' ').trim();
            }
        }
    };

    $tabla.DataTable({
        language: { url: 'https://cdn.datatables.net/plug-ins/2.1.8/i18n/es-ES.json', emptyTable: 'No hay pedidos en este estado.' },
        order: [[0, 'desc']],                     // pedidos más nuevos primero
        pageLength: 10,
        columnDefs: [
            { targets: 'no-exportar', orderable: false, searchable: false },
            { targets: '_all', className: 'dt-left' }   // todo alineado a la izquierda, como el resto del sistema
        ],
        layout: {
            topStart: {
                buttons: [
                    { extend: 'excelHtml5', text: '<i class="bi bi-file-earmark-excel"></i> Excel', className: 'btn btn-success btn-sm',
                      title: titulo, messageTop: subtitulo, filename: nombreArchivo, exportOptions: exportar },
                    { extend: 'pdfHtml5', text: '<i class="bi bi-file-earmark-pdf"></i> PDF', className: 'btn btn-danger btn-sm',
                      title: titulo, messageTop: subtitulo, filename: nombreArchivo, exportOptions: exportar,
                      orientation: 'landscape', pageSize: 'A4',
                      customize: function (doc) {
                          // Tabla a todo el ancho y encabezado con el verde de PICKFLOW
                          // (texto azul oscuro, igual que los botones verdes del sistema)
                          doc.content[doc.content.length - 1].table.widths = '*'.repeat(doc.content[doc.content.length - 1].table.body[0].length).split('');
                          doc.styles.tableHeader.fillColor = '#20C95A';
                          doc.styles.tableHeader.color = '#142544';
                          doc.styles.tableHeader.bold = true;
                          doc.styles.tableHeader.alignment = 'left';
                          doc.defaultStyle.fontSize = 10;
                      } },
                    { extend: 'csvHtml5', text: '<i class="bi bi-filetype-csv"></i> CSV', className: 'btn btn-secondary btn-sm',
                      filename: nombreArchivo, exportOptions: exportar },
                    { extend: 'print', text: '<i class="bi bi-printer"></i> Imprimir', className: 'btn btn-primary btn-sm',
                      title: titulo, messageTop: subtitulo, exportOptions: exportar }
                ]
            },
            topEnd: 'search'
        }
    });
});
