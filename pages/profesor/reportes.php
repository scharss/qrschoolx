<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'profesor') {
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
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Sistema de Asistencia</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="scanner.php">Escanear QR</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reportes.php">Reportes</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="../../includes/logout.php">Cerrar Sesión</a>
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

        // Cargar grupos del profesor
        $.get(getBasePath() + '/includes/reportes/get_grupos_profesor.php', function(data) {
            data.forEach(function(grupo) {
                $('#grupo').append(`<option value="${grupo.id}">${grupo.nombre}</option>`);
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
            console.log('Columnas inicializadas:', columns.length);
            console.log('Columnas:', columns.map(c => c.title).join(', '));

            // Ajustar configuración de DataTables
            table = $('#reporteTable').DataTable({
                dom: 'Bfrtip',
                ordering: false,
                paging: false,
                data: [],
                columns: columns,
                columnDefs: [
                    {
                        targets: '_all',
                        render: function(data, type, row, meta) {
                            if (type === 'display' && data && (typeof data === 'string') && data.includes('<span')) {
                                return data;
                            }
                            return data;
                        }
                    }
                ],
                language: {
                    search: "Buscar:",
                    zeroRecords: "No se encontraron registros",
                    info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
                    infoEmpty: "Mostrando 0 a 0 de 0 registros",
                    infoFiltered: "(filtrado de _MAX_ registros totales)"
                },
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
                                var value = cell.find('v').text();
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
                        className: 'btn btn-primary'
                    }
                ]
            });
        }

        // Manejar el envío del formulario
        $('#filtrosForm').on('submit', function(e) {
            e.preventDefault();
            
            var grupo = $('#grupo').val();
            var fechas = $('#fechas').val();
            
            if (!grupo) {
                alert('Por favor seleccione un grupo');
                return;
            }

            // Mostrar un indicador de carga
            $('#reporteTable tbody').html('<tr><td colspan="5" class="text-center">Cargando datos...</td></tr>');

            $.ajax({
                url: '../../includes/reportes/get_asistencias_profesor.php',
                method: 'POST',
                data: {
                    grupo: grupo,
                    fechas: fechas
                },
                dataType: 'json',
                success: function(response) {
                    if (response.error) {
                        alert(response.message);
                        return;
                    }
                    
                    console.log('Respuesta recibida:', response);
                    console.log('Columnas:', response.columns);
                    console.log('Datos:', response.data ? response.data.length : 0);
                    
                    // Procesar los datos para mejorar la visualización
                    if (response.data && response.data.length > 0) {
                        response.data.forEach(function(row) {
                            Object.keys(row).forEach(function(key) {
                                if (key !== 'estudiante' && key !== 'documento') {
                                    if (row[key] === '✓') {
                                        row[key] = '<span class="check-icon">✓</span>';
                                    } else if (row[key] === 'X') {
                                        row[key] = '<span class="x-icon">X</span>';
                                    }
                                }
                            });
                        });
                    }
                    
                    initializeTable(response.columns);
                    table.clear().rows.add(response.data).draw();
                },
                error: function(xhr, status, error) {
                    let errorMsg = 'Error al generar el reporte';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg += ': ' + xhr.responseJSON.message;
                    } else {
                        errorMsg += ': ' + error;
                    }
                    alert(errorMsg);
                    console.error('Error details:', xhr.responseText);
                    if ($.fn.DataTable.isDataTable('#reporteTable')) {
                        $('#reporteTable').DataTable().clear().destroy();
                        $('#reporteTable').empty();
                    }
                }
            });
        });
    });
    </script>

    <style>
    #reporteTable {
        width: 100% !important;
        margin: 0 !important;
    }

    #reporteTable thead th {
        white-space: nowrap;
        position: relative;
        vertical-align: bottom;
        padding: 10px 5px;
        font-size: 0.9em;
        text-align: center;
    }

    #reporteTable thead th:not(:first-child):not(:nth-child(2)) {
        min-width: 80px;
        max-width: 100px;
        writing-mode: vertical-tb;
    }

    #reporteTable thead th:first-child,
    #reporteTable thead th:nth-child(2) {
        min-width: 150px;
    }

    #reporteTable tbody td {
        padding: 5px;
        text-align: center;
        font-size: 0.9em;
    }

    #reporteTable tbody td:first-child,
    #reporteTable tbody td:nth-child(2) {
        text-align: left;
    }

    .dt-buttons {
        margin-bottom: 15px;
    }

    .dt-button {
        margin-right: 5px;
    }

    #reporteTable thead th[style*="position: relative"] > div > div {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        text-align: center;
    }

    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .check-icon {
        color: green;
        font-weight: bold;
    }
    
    .x-icon {
        color: red;
    }
    </style>
</body>
</html> 