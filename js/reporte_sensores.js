/* =====================================================
   PICKFLOW - REPORTE DE SENSORES (DataTables + Buttons)
   Igual que el reporte de pedidos: convierte la tabla de
   lecturas en un reporte con búsqueda, orden, paginación
   y exportación a Excel, PDF, CSV o impresión.
   PHP arma la tabla con las lecturas guardadas en MySQL.
   ===================================================== */
$(function () {
    var $tabla = $('#tablaSensores');
    if (!$tabla.length) {
        return;
    }

    var titulo = $tabla.data('titulo');
    var ahora = new Date();
    var fechaTexto = ahora.toLocaleDateString('es-UY') + ' ' + ahora.toLocaleTimeString('es-UY', { hour: '2-digit', minute: '2-digit' });
    var subtitulo = 'PICKFLOW - Control ambiental. Período: ' + $tabla.data('periodo') + '. Generado el ' + fechaTexto + ' por ' + $tabla.data('usuario') + '.';
    var nombreArchivo = 'reporte_sensores_' + ahora.toISOString().slice(0, 10);

    /* La columna Estado se exporta como texto limpio (sin la píldora de color) */
    var exportar = {
        format: {
            body: function (dato, fila, columna, celda) {
                var limpio = $(celda).attr('data-exportar');
                return limpio !== undefined ? limpio : $(celda).text().replace(/\s+/g, ' ').trim();
            }
        }
    };

    $tabla.DataTable({
        language: { url: 'https://cdn.datatables.net/plug-ins/2.1.8/i18n/es-ES.json', emptyTable: 'No hay lecturas en este período.' },
        order: [[0, 'desc']],                     // lecturas más nuevas primero
        pageLength: 25,
        columnDefs: [{ targets: '_all', className: 'dt-left' }],
        layout: {
            topStart: {
                buttons: [
                    { extend: 'excelHtml5', text: '<i class="bi bi-file-earmark-excel"></i> Excel', className: 'btn btn-success btn-sm',
                      title: titulo, messageTop: subtitulo, filename: nombreArchivo, exportOptions: exportar },
                    { extend: 'pdfHtml5', text: '<i class="bi bi-file-earmark-pdf"></i> PDF', className: 'btn btn-danger btn-sm',
                      title: titulo, messageTop: subtitulo, filename: nombreArchivo, exportOptions: exportar,
                      orientation: 'portrait', pageSize: 'A4',
                      customize: function (doc) {
                          // Tabla a todo el ancho y encabezado con el verde de PICKFLOW
                          var tabla = doc.content[doc.content.length - 1].table;
                          tabla.widths = '*'.repeat(tabla.body[0].length).split('');
                          doc.styles.tableHeader.fillColor = '#20C95A';
                          doc.styles.tableHeader.color = '#142544';
                          doc.styles.tableHeader.bold = true;
                          doc.styles.tableHeader.alignment = 'left';
                          doc.defaultStyle.fontSize = 9;
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
