/* =====================================================
   PICKFLOW - REPORTES (DataTables + Buttons)
   A cada tabla de la pantalla de reportes le agrega los
   botones para bajarla en Excel, PDF o CSV. Se respeta el
   orden de la consulta SQL (sin ordenar ni paginar).
   ===================================================== */
$(function () {
    var ahora = new Date();
    var fechaTexto = ahora.toLocaleDateString('es-UY') + ' ' + ahora.toLocaleTimeString('es-UY', { hour: '2-digit', minute: '2-digit' });
    var hoy = ahora.toISOString().slice(0, 10);

    $('.tabla-reporte').each(function () {
        var $tabla = $(this);
        var titulo = $tabla.data('titulo');
        var subtitulo = 'PICKFLOW - Sistema de Picking y Logística. Generado el ' + fechaTexto + ' por ' + $tabla.data('usuario') + '.';
        var archivo = $tabla.data('archivo') + '_' + hoy;
        var columnas = $tabla.find('thead th').length;

        $tabla.DataTable({
            language: { url: 'https://cdn.datatables.net/plug-ins/2.1.8/i18n/es-ES.json' },
            paging: false,
            searching: false,
            ordering: false,
            info: false,
            columnDefs: [{ targets: '_all', className: 'dt-left' }],
            layout: {
                topStart: {
                    buttons: [
                        { extend: 'excelHtml5', text: '<i class="bi bi-file-earmark-excel"></i> Excel', className: 'btn btn-success btn-sm',
                          title: titulo, messageTop: subtitulo, filename: archivo },
                        { extend: 'pdfHtml5', text: '<i class="bi bi-file-earmark-pdf"></i> PDF', className: 'btn btn-danger btn-sm',
                          title: titulo, messageTop: subtitulo, filename: archivo,
                          orientation: columnas > 5 ? 'landscape' : 'portrait', pageSize: 'A4',
                          customize: function (doc) {
                              // Tabla a todo el ancho y encabezado con el verde de PICKFLOW
                              var tabla = doc.content[doc.content.length - 1].table;
                              tabla.widths = Array(tabla.body[0].length).fill('*');
                              doc.styles.tableHeader.fillColor = '#20C95A';
                              doc.styles.tableHeader.color = '#142544';
                              doc.styles.tableHeader.bold = true;
                              doc.styles.tableHeader.alignment = 'left';
                              doc.defaultStyle.fontSize = 10;
                          } },
                        { extend: 'csvHtml5', text: '<i class="bi bi-filetype-csv"></i> CSV', className: 'btn btn-secondary btn-sm',
                          filename: archivo }
                    ]
                },
                topEnd: null,
                bottomStart: null,
                bottomEnd: null
            }
        });
    });
});
