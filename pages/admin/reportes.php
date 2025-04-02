<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'administrador') {
    header('Location: ' . dirname($_SERVER['SCRIPT_NAME'], 2) . '/index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes de Asistencia</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <!-- DateRangePicker CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">Panel de Administración</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Inicio</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo dirname($_SERVER['SCRIPT_NAME'], 2); ?>/includes/logout.php">Cerrar Sesión</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>Reportes de Asistencia</h2>
        
        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-body">
                <form id="filtrosForm">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="grupo" class="form-label">Grupo</label>
                                <select class="form-select" id="grupo" name="grupo">
                                    <option value="">Seleccionar Grupo</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="fechas" class="form-label">Rango de Fechas</label>
                                <input type="text" class="form-control" id="fechas" name="fechas">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="profesor" class="form-label">Profesor</label>
                                <select class="form-select" id="profesor" name="profesor">
                                    <option value="">Todos los profesores</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Generar Reporte</button>
                </form>
            </div>
        </div>

        <!-- Tabla de Resultados -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="reporteTable" class="table table-striped">
                        <thead>
                            <tr>
                                <th>Estudiante</th>
                                <th>Documento</th>
                                <th>Grupo</th>
                                <th>Profesor</th>
                                <th>Fecha y Hora</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    <!-- Moment.js y DateRangePicker -->
    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    
    <script>
    // Función auxiliar para crear elementos XML
    function createElementWithInnerHTML(html) {
        var container = document.implementation.createHTMLDocument().createElement('div');
        container.innerHTML = html;
        return container.firstChild;
    }

    $(document).ready(function() {
        // Función para obtener la ruta base
        function getBasePath() {
            const path = window.location.pathname;
            return path.substring(0, path.indexOf('/pages'));
        }

        // Inicializar DateRangePicker
        $('#fechas').daterangepicker({
            locale: {
                format: 'YYYY-MM-DD',
                applyLabel: 'Aplicar',
                cancelLabel: 'Cancelar',
                fromLabel: 'Desde',
                toLabel: 'Hasta',
                customRangeLabel: 'Rango personalizado',
                daysOfWeek: ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa'],
                monthNames: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre']
            },
            startDate: moment().subtract(29, 'days'),
            endDate: moment()
        });

        // Cargar grupos
        $.get(getBasePath() + '/includes/reportes/get_grupos.php', function(data) {
            data.forEach(function(grupo) {
                $('#grupo').append(`<option value="${grupo.id}">${grupo.nombre}</option>`);
            });
        });

        // Cargar profesores
        $.get(getBasePath() + '/includes/reportes/get_profesores.php', function(data) {
            data.forEach(function(profesor) {
                $('#profesor').append(`<option value="${profesor.id}">${profesor.nombre} ${profesor.apellidos}</option>`);
            });
        });

        // Variable para almacenar la instancia de DataTable
        var table;

        // Estilos personalizados para Excel
        var createXLSLFormatObj = function() {
            return {
                green: {
                    fill: {
                        fgColor: { rgb: "E2EFDA" }
                    },
                    font: {
                        color: { rgb: "006100" },
                        bold: true
                    },
                    alignment: {
                        horizontal: "center",
                        vertical: "center"
                    }
                },
                red: {
                    fill: {
                        fgColor: { rgb: "FFC7CE" }
                    },
                    font: {
                        color: { rgb: "9C0006" },
                        bold: true
                    },
                    alignment: {
                        horizontal: "center",
                        vertical: "center"
                    }
                },
                header: {
                    font: {
                        bold: true
                    },
                    alignment: {
                        textRotation: 90,
                        horizontal: "center",
                        vertical: "bottom"
                    }
                }
            };
        };

        // Función para inicializar o reinicializar la tabla
        function initializeTable(columns) {
            if ($.fn.DataTable.isDataTable('#reporteTable')) {
                $('#reporteTable').DataTable().destroy();
                $('#reporteTable thead, #reporteTable tbody').empty();
            }

            // Crear los encabezados de la tabla dinámicamente
            let headerHtml = '<tr>';
            columns.forEach(function(column) {
                headerHtml += `<th>${column.title}</th>`;
            });
            headerHtml += '</tr>';
            $('#reporteTable thead').html(headerHtml);

            table = $('#reporteTable').DataTable({
                dom: 'Bfrtip',
                ordering: false,
                paging: false,
                buttons: [
                    {
                        extend: 'copy',
                        text: 'Copiar',
                        className: 'btn btn-primary'
                    },
                    {
                        extend: 'csv',
                        text: 'CSV',
                        className: 'btn btn-primary',
                        charset: 'utf-8',
                        bom: true,
                        title: function() {
                            var fecha = moment().format('DD-MM-YYYY_HH-mm');
                            return 'Reporte de Asistencia - ' + $('#grupo option:selected').text() + ' - ' + fecha;
                        },
                        filename: function() {
                            var fecha = moment().format('DD-MM-YYYY_HH-mm');
                            return 'Reporte_' + $('#grupo option:selected').text().replace(/ /g, '_') + '_' + fecha;
                        }
                    },
                    {
                        extend: 'excel',
                        text: 'Excel',
                        className: 'btn btn-primary',
                        title: function() {
                            var fecha = moment().format('DD-MM-YYYY_HH-mm');
                            return 'Reporte de Asistencia - ' + $('#grupo option:selected').text() + ' - ' + fecha;
                        },
                        filename: function() {
                            var fecha = moment().format('DD-MM-YYYY_HH-mm');
                            return 'Reporte_' + $('#grupo option:selected').text().replace(/ /g, '_') + '_' + fecha;
                        },
                        customize: function(xlsx) {
                            var sheet = xlsx.xl.worksheets['sheet1.xml'];
                            
                            // Ajustar ancho de columnas
                            var widths = [30, 15];
                            var dateColumns = $('row:first-child c', sheet).length - 2;
                            for(var i = 0; i < dateColumns; i++) {
                                widths.push(8);
                            }
                            
                            $('col', sheet).each(function(i) {
                                $(this).attr('width', widths[i]);
                            });

                            // Definir estilos en styles.xml
                            var styles = xlsx.xl['styles.xml'];
                            
                            // Agregar nuevos fills para los colores
                            var fills = $('fills', styles);
                            // Fill para asistencia (verde)
                            fills.append('<fill><patternFill patternType="solid"><fgColor rgb="E2EFDA"/></patternFill></fill>');
                            var greenFillId = fills.find('fill').length - 1;
                            
                            // Fill para inasistencia (rojo)
                            fills.append('<fill><patternFill patternType="solid"><fgColor rgb="FFC7CE"/></patternFill></fill>');
                            var redFillId = fills.find('fill').length - 1;
                            
                            // Agregar nuevos estilos
                            var cellXfs = $('cellXfs', styles);
                            
                            // Estilo para asistencia
                            cellXfs.append('<xf numFmtId="0" fontId="0" fillId="' + greenFillId + '" borderId="0" applyFill="1" applyAlignment="1"><alignment horizontal="center"/></xf>');
                            var greenStyleId = cellXfs.find('xf').length - 1;
                            
                            // Estilo para inasistencia
                            cellXfs.append('<xf numFmtId="0" fontId="0" fillId="' + redFillId + '" borderId="0" applyFill="1" applyAlignment="1"><alignment horizontal="center"/></xf>');
                            var redStyleId = cellXfs.find('xf').length - 1;

                            // Aplicar estilos a las celdas
                            $('row c', sheet).each(function() {
                                var cell = $(this);
                                var value = cell.find('t').text();
                                
                                if (value === '✓') {
                                    cell.attr('s', greenStyleId);
                                } else if (value === 'X') {
                                    cell.attr('s', redStyleId);
                                }
                            });
                        }
                    },
                    {
                        extend: 'print',
                        text: 'Imprimir',
                        className: 'btn btn-primary',
                        title: function() {
                            var fecha = moment().format('DD-MM-YYYY_HH-mm');
                            return 'Reporte de Asistencia - ' + $('#grupo option:selected').text() + ' - ' + fecha;
                        },
                        customize: function(win) {
                            var body = $(win.document.body);
                            
                            // Aplicar estilos a la impresión
                            body.find('table thead th').each(function(index) {
                                if(index >= 2) {
                                    $(this).css({
                                        'white-space': 'nowrap',
                                        'writing-mode': 'vertical-rl',
                                        'transform': 'rotate(180deg)',
                                        'vertical-align': 'bottom',
                                        'height': '150px',
                                        'min-width': '50px',
                                        'text-align': 'center',
                                        'padding': '0',
                                        'position': 'relative'
                                    });
                                    
                                    // Envolver el texto en un div centrado
                                    var text = $(this).text();
                                    $(this).html('<div style="position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%) rotate(180deg); writing-mode: vertical-rl;">' + text + '</div>');
                                }
                            });

                            // Agregar estilos globales para la impresión
                            var style = '@media print { ' +
                                'table thead th { padding: 0 !important; } ' +
                                'table thead th[style*="writing-mode: vertical-rl"] { height: 150px !important; } ' +
                                '}';
                            
                            // Agregar los estilos al documento de impresión
                            $(win.document.head).append('<style>' + style + '</style>');

                            body.find('table tbody td').each(function(index) {
                                var text = $(this).text();
                                if(text === '✓') {
                                    $(this).html('✓').css({
                                        'background-color': '#E2EFDA',
                                        'color': '#006100',
                                        'text-align': 'center',
                                        'font-weight': 'bold',
                                        'vertical-align': 'middle',
                                        'min-width': '50px'
                                    });
                                } else if(text === 'X') {
                                    $(this).html('X').css({
                                        'background-color': '#FFC7CE',
                                        'color': '#9C0006',
                                        'text-align': 'center',
                                        'font-weight': 'bold',
                                        'vertical-align': 'middle',
                                        'min-width': '50px'
                                    });
                                }
                            });
                        }
                    }
                ],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
                },
                columns: columns,
                data: []
            });
        }

        // Inicializar tabla con columnas por defecto
        initializeTable([
            { title: 'Estudiante', data: 'estudiante' },
            { title: 'Documento', data: 'documento' }
        ]);

        // Manejar envío del formulario
        $('#filtrosForm').on('submit', function(e) {
            e.preventDefault();
            
            var grupo = $('#grupo').val();
            var profesor = $('#profesor').val();
            var fechas = $('#fechas').val();

            if (!grupo) {
                alert('Por favor seleccione un grupo');
                return;
            }

            $.ajax({
                url: '../../includes/reportes/get_asistencias.php',
                method: 'POST',
                data: {
                    grupo: grupo,
                    profesor: profesor,
                    fechas: fechas
                },
                success: function(response) {
                    if (response.error) {
                        alert(response.message);
                        if ($.fn.DataTable.isDataTable('#reporteTable')) {
                            $('#reporteTable').DataTable().clear().destroy();
                        }
                        $('#reporteTable').empty();
                        $('.dt-buttons').hide();
                        return;
                    }

                    // Inicializar la tabla con los datos
                    initializeTable(response.columns);
                    
                    // Agregar los datos a la tabla
                    table.clear().rows.add(response.data).draw();

                    // Mostrar los botones de exportación
                    $('.dt-buttons').show();

                    // Aplicar estilos a las celdas
                    $('#reporteTable tbody tr').each(function() {
                        $(this).find('td').each(function(index) {
                            if(index >= 2) { // Solo para columnas de fechas
                                var text = $(this).text().trim();
                                if(text === '✓') {
                                    $(this).html('✓').css({
                                        'background-color': '#E2EFDA',
                                        'color': '#006100',
                                        'text-align': 'center',
                                        'font-weight': 'bold'
                                    });
                                } else if(text === 'X') {
                                    $(this).html('X').css({
                                        'background-color': '#FFC7CE',
                                        'color': '#9C0006',
                                        'text-align': 'center',
                                        'font-weight': 'bold'
                                    });
                                }
                            }
                        });
                    });

                    // Rotar y estilizar encabezados de fechas
                    $('#reporteTable thead th').each(function(index) {
                        if(index >= 2) {
                            $(this).css({
                                'height': '120px',
                                'padding': '0',
                                'vertical-align': 'bottom',
                                'position': 'relative',
                                'min-width': '40px',
                                'max-width': '40px',
                                'background-color': 'transparent'
                            }).html(`
                                <div style="
                                    position: absolute;
                                    left: 0;
                                    right: 0;
                                    bottom: 0;
                                    top: 0;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    width: 100%;
                                ">
                                    <div style="
                                        writing-mode: tb-rl;
                                        transform: rotate(180deg);
                                        text-align: left;
                                        white-space: nowrap;
                                        width: 20px;
                                        line-height: 20px;
                                    ">
                                        ${$(this).text()}
                                    </div>
                                </div>
                            `);
                        }
                    });

                    // Actualizar los estilos CSS globales
                    if (!$('#customTableStyles').length) {
                        $('head').append(`
                            <style id="customTableStyles">
                                .container {
                                    margin-top: 0 !important;
                                }
                                .card {
                                    margin-bottom: 0 !important;
                                    border: none !important;
                                }
                                .card-body {
                                    padding: 0.5rem !important;
                                }
                                #reporteTable {
                                    margin: 0 !important;
                                }
                                #reporteTable thead th {
                                    min-width: 40px !important;
                                    max-width: 40px !important;
                                    background-color: transparent !important;
                                }
                                #reporteTable thead th:first-child,
                                #reporteTable thead th:nth-child(2) {
                                    min-width: 150px !important;
                                    max-width: none !important;
                                }
                                #reporteTable tbody td {
                                    min-width: 40px !important;
                                    max-width: 40px !important;
                                    text-align: center !important;
                                    vertical-align: middle !important;
                                    padding: 8px 4px !important;
                                }
                                #reporteTable tbody td:first-child,
                                #reporteTable tbody td:nth-child(2) {
                                    min-width: 150px !important;
                                    max-width: none !important;
                                    text-align: left !important;
                                }
                                .dataTables_scrollHead {
                                    overflow: visible !important;
                                }
                                .dt-buttons {
                                    margin: 0.5rem 0 !important;
                                }
                                .table-responsive {
                                    margin: 0 !important;
                                    padding: 0 !important;
                                    border: none !important;
                                }
                                #filtrosForm {
                                    margin-bottom: 0.5rem !important;
                                }
                                .mb-3 {
                                    margin-bottom: 0.5rem !important;
                                }
                                .mb-4 {
                                    margin-bottom: 0.5rem !important;
                                }
                                #reporteTable thead th[style*="position: relative"] > div > div {
                                    font-size: 12px !important;
                                }
                            </style>
                        `);
                    }

                    // Configurar la tabla para el scroll horizontal
                    $('#reporteTable').wrap('<div class="table-responsive"></div>');
                },
                error: function(xhr, status, error) {
                    alert('Error al generar el reporte: ' + error);
                    if ($.fn.DataTable.isDataTable('#reporteTable')) {
                        $('#reporteTable').DataTable().clear().destroy();
                    }
                    $('#reporteTable').empty();
                    $('.dt-buttons').hide();
                }
            });
        });
    });
    </script>
</body>
</html> 