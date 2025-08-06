<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
include_once("../config.php");
session_start();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Consulta de Docentes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <link rel="stylesheet" href="styles.css" />
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <div class="app-container">
        <header class="app-header">
            <div class="header-container">
                <div class="undav-container">
                    <img class="undav" src="../imagenes/undav.png" />
                </div>
                <div class="logo-container">
                    <img class="logo" src="../imagenes/logo.png" />
                </div>
                <button id="logoutBtn" class="btn btn--sm">
                    <i class="fas fa-sign-out-alt"></i> Salir
                </button>
            </div>
        </header>
        <nav class="navbar navbar-expand-lg custom-navbar">
            <div class="container-fluid">
                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link active" aria-current="page" href="principal.php">Home</a>
                        </li>
                        <li class="nav-item dropdown nav-color">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"
                                aria-expanded="false">Docentes</a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" data-value="guarani">Docentes con Asignación Aulica</a></li>
                                <li><a class="dropdown-item" href="#" data-value="mapuche">Docentes con Designación</a></li>
                                <li class="dropdown-submenu">
                                    <a class="dropdown-item dropdown-toggle" href="#" data-value="combinados">Docentes - Unificado</a>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item year-item" href="#" data-value="combinados-2011">2011</a></li>
                                        <li><a class="dropdown-item year-item" href="#" data-value="combinados-2012">2012</a></li>
                                        <li><a class="dropdown-item year-item" href="#" data-value="combinados-2013">2013</a></li>
                                        <li><a class="dropdown-item year-item" href="#" data-value="combinados-2014">2014</a></li>
                                        <li><a class="dropdown-item year-item" href="#" data-value="combinados-2015">2015</a></li>
                                        <li><a class="dropdown-item year-item" href="#" data-value="combinados-2016">2016</a></li>
                                        <li><a class="dropdown-item year-item" href="#" data-value="combinados-2017">2017</a></li>
                                        <li><a class="dropdown-item year-item" href="#" data-value="combinados-2018">2018</a></li>
                                        <li><a class="dropdown-item year-item" href="#" data-value="combinados-2019">2019</a></li>
                                        <li><a class="dropdown-item year-item" href="#" data-value="combinados-2020">2020</a></li>
                                        <li><a class="dropdown-item year-item" href="#" data-value="combinados-2021">2021</a></li>
                                        <li><a class="dropdown-item year-item" href="#" data-value="combinados-2022">2022</a></li>
                                        <li><a class="dropdown-item year-item" href="#" data-value="combinados-2023">2023</a></li>
                                        <li><a class="dropdown-item year-item" href="#" data-value="combinados-2024">2024</a></li>
                                        <li><a class="dropdown-item year-item" href="#" data-value="combinados-2025">2025</a></li>
                                    </ul>
                                </li>
                            </ul>
                        </li>
                    </ul>

                    <div class="filter-container">
                        <label for="filterInput" class="filter-label">Filtrar:</label>
                        <input type="text" id="filterInput" class="form-control filter-input" placeholder="Nombre/Apellido" />
                        <button type="button" id="filterBtn" class="btn btn-outline-primary">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    <p type="button" id="refreshBtn"></p>
                </div>
            </div>
        </nav>

        <div class="header-container">
            <div id="paginationText" class="pagination-text"></div>
            <div id="selectionTitle" class="selection-title text-center"></div>
            <div id="exportButtons" class="export-buttons" style="display:none;">
                <button id="excelBtn" class="btn btn-success btn-sm"><i class="fas fa-file-excel"></i> Excel</button>
                <button id="pdfBtn" class="btn btn-danger btn-sm"><i class="fas fa-file-pdf"></i> PDF</button>
            </div>
        </div>

        <main class="app-main">
            <div id="resultsContainer" class="results-container"></div>
            <div id="paginationContainer" class="pagination-container"></div>
        </main>
        <footer class="app-footer">
            <p>TINKUY v.1.0 &copy; 2025 - Desarrollado por el Área de Sistemas de la UNDAV.</p>
        </footer>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.min.js"></script>
    <!-- SheetJS para Excel -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <!-- jsPDF y autoTable para PDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

    <script>
        const baseURL = "<?php echo BASE_URL; ?>";
        let currentPage = 1;
        const perPage = 10;
        let currentQueryType = '';
        let currentSelectionText = 'Seleccione un grupo de docentes del menú desplegable';
        let currentSearchTerm = '';

        async function cargarResultados() {
            const resultsContainer = document.getElementById('resultsContainer');
            const paginationContainer = document.getElementById('paginationContainer');
            const selectionTitle = document.getElementById('selectionTitle');
            const exportButtons = document.getElementById('exportButtons');
            exportButtons.style.display = 'none';

            if (!currentQueryType) {
                resultsContainer.innerHTML = '<div class="error">Seleccione un tipo de docentes del menú</div>';
                paginationContainer.innerHTML = '';
                selectionTitle.textContent = currentSelectionText;
                return;
            }

            resultsContainer.innerHTML = '<div class="loading">Cargando datos...</div>';
            paginationContainer.innerHTML = '';
            selectionTitle.textContent = `${currentSelectionText}`;

            try {
                const url = `${baseURL}?action=getData&type=${currentQueryType}&page=${currentPage}&search=${encodeURIComponent(currentSearchTerm)}`;
                console.log("URL de solicitud:", url);
                
                const response = await fetch(url);
                
                if (!response.ok) {
                    const errorText = await response.text();
                    console.error("Detalles del error:", errorText);
                    throw new Error(`Error ${response.status}: ${errorText}`);
                }

                const data = await response.json();
                if (!data.success) throw new Error(data.error || 'Error desconocido');

                document.getElementById('paginationText').textContent = `Página ${data.pagination.current_page} de ${data.pagination.total_pages}`;
                let html = '';

                if (currentSearchTerm) {
                    html += `<p class="search-info">Filtrado por: <strong>${currentSearchTerm}</strong></p>`;
                }
                html += `
                <div class="table-scroll-container">
                    <div class="table-scroll-top" id="topScroll"></div>
                    <div class="table-wrapper" id="tableWrapper">
                        <table class="table table-striped table-bordered" style="width:100%; margin:0">
                            <thead><tr>`;
                if (data.data.length > 0) {
                    Object.keys(data.data[0]).forEach(key => {
                        html += `<th style="white-space: nowrap">${key}</th>`;
                    });
                    html += '</tr></thead><tbody>';
                    data.data.forEach(row => {
                        html += '<tr>';
                        Object.values(row).forEach(value => {
                            html += `<td style="white-space: nowrap">${value ?? ''}</td>`;
                        });
                        html += '</tr>';
                    });
                    html += '</tbody></table></div></div>';
                    resultsContainer.innerHTML = html;
                    exportButtons.style.display = 'flex';
                    exportButtons.style.gap = '10px';
                    setTimeout(() => {
                        const topScroll = document.getElementById('topScroll');
                        const tableWrapper = document.getElementById('tableWrapper');
                        if (topScroll && tableWrapper) {
                            topScroll.scrollLeft = 0;
                            if (!topScroll.querySelector('.ghost')) {
                                const ghostDiv = document.createElement('div');
                                ghostDiv.className = 'ghost';
                                ghostDiv.style.width = tableWrapper.scrollWidth + 'px';
                                ghostDiv.style.height = '1px';
                                topScroll.appendChild(ghostDiv);
                            }
                            topScroll.onscroll = () => {
                                tableWrapper.scrollLeft = topScroll.scrollLeft;
                            };
                            tableWrapper.onscroll = () => {
                                topScroll.scrollLeft = tableWrapper.scrollLeft;
                            };
                        }
                    }, 100);
                } else {
                    resultsContainer.innerHTML = '<div class="alert alert-info">No se encontraron resultados.</div>';
                }
                const { current_page, total_pages } = data.pagination;
                let pagHtml = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
                if (current_page > 1) {
                    pagHtml += `<li class="page-item"><a class="page-link" href="#" onclick="irPagina(${current_page - 1}); return false;">&laquo;</a></li>`;
                } else {
                    pagHtml += `<li class="page-item disabled"><span class="page-link">&laquo;</span></li>`;
                }

                const maxPagesToShow = 5;
                let startPage = Math.max(1, current_page - Math.floor(maxPagesToShow / 2));
                let endPage = startPage + maxPagesToShow - 1;

                if (endPage > total_pages) {
                    endPage = total_pages;
                    startPage = Math.max(1, endPage - maxPagesToShow + 1);
                }

                for (let i = startPage; i <= endPage; i++) {
                    pagHtml += `<li class="page-item ${i === current_page ? 'active' : ''}">
                        <a class="page-link" href="#" onclick="irPagina(${i}); return false;">${i}</a>
                    </li>`;
                }

                if (current_page < total_pages) {
                    pagHtml += `<li class="page-item"><a class="page-link" href="#" onclick="irPagina(${current_page + 1}); return false;">&raquo;</a></li>`;
                } else {
                    pagHtml += `<li class="page-item disabled"><span class="page-link">&raquo;</span></li>`;
                }

                pagHtml += '</ul></nav>';
                paginationContainer.innerHTML = pagHtml;

            } catch (error) {
                console.error('Error:', error);
                resultsContainer.innerHTML = `<div class="error"><strong>Error:</strong> ${error.message}</div>`;
            }
        }

        function irPagina(pagina) {
            currentPage = pagina;
            cargarResultados();
            document.querySelector('.query-panel').scrollIntoView({ behavior: 'smooth' });
        }

        async function obtenerTodosLosDatos() {
            try {
                const response = await fetch(
                    `${baseURL}?action=getData&type=${currentQueryType}&search=${encodeURIComponent(currentSearchTerm)}&export=true`
                );
                const data = await response.json();
                return data.resultados || data.data || [];
            } catch (error) {
                console.error("Error al obtener todos los datos:", error);
                return [];
            }
        }

        async function exportarAExcel() {
            const datos = await obtenerTodosLosDatos();
            if (datos.length === 0) {
                alert("No hay datos para exportar.");
                return;
            }

            const wsData = [Object.keys(datos[0]), ...datos.map(row => Object.values(row))];
            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.aoa_to_sheet(wsData);
            XLSX.utils.book_append_sheet(wb, ws, "Resultados");
            XLSX.writeFile(wb, "resultados.xlsx");
        }

        async function exportarAPDF() {
            const datos = await obtenerTodosLosDatos();
            if (datos.length === 0) {
                alert("No hay datos para exportar.");
                return;
            }

            const jsPDF = window.jspdf?.jsPDF || window.jspdf;
            const doc = new jsPDF({
                orientation: "landscape",
                unit: "pt",
                format: "a4"
            });

            const fecha = new Date().toLocaleString('es-AR');
            doc.setFontSize(14);
            doc.text("Listado de Docentes - Completo", 40, 40);
            doc.setFontSize(10);
            doc.text(`Exportado el ${fecha}`, 40, 60);

            const headers = [Object.keys(datos[0])];
            const rows = datos.map(row => Object.values(row));

            doc.autoTable({
                head: headers,
                body: rows,
                startY: 80,
                margin: { top: 40, left: 40, right: 40 },
                styles: { fontSize: 9, cellPadding: 4 },
                headStyles: { fillColor: [41, 128, 185], textColor: 255, halign: 'center', fontStyle: 'bold' },
                alternateRowStyles: { fillColor: [240, 240, 240] },
                theme: 'striped'
            });

            doc.save("resultados.pdf");
        }

        async function secureLogout() {
    try {
        // Limpiar datos sensibles del cliente
        currentQueryType = '';
        currentSearchTerm = '';
        
        // Forzar limpieza de caché
        const response = await fetch('logout.php', {
            method: 'POST',
            headers: {
                'Cache-Control': 'no-cache',
                'Pragma': 'no-cache'
            },
            cache: 'no-store'
        });

        // Verificar si la respuesta es una redirección
        if (response.redirected) {
            // Forzar recarga completa sin caché
            window.location.replace(response.url + '&nocache=' + Date.now());
            return;
        }

        // Mostrar confirmación y redirigir
        await Swal.fire({
            title: '¡Sesión cerrada!',
            text: 'Vuelve pronto 😊',
            icon: 'success',
            confirmButtonColor: '#2c3e50',
            background: '#fff',
            timer: 2000,
            willClose: () => {
                // Redirección forzada con parámetros anti-caché
                window.location.href = '../login/index.html?logout=1&nocache=' + Date.now();
                // Limpiar el historial de navegación
                if (window.history) {
                    window.history.replaceState(null, '', window.location.href);
                }
            }
        });

    } catch (error) {
        Swal.fire('Error', 'No se pudo cerrar sesión', 'error');
        // Forzar redirección en caso de error
        window.location.href = '../login/index.html?error=1&nocache=' + Date.now();
    }
}
// Evitar que se pueda volver atrás después de logout
if (window.location.search.includes('logout=1')) {
    // Limpiar el historial de navegación
    if (window.history && window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
    
    // Deshabilitar caché
    window.addEventListener('pageshow', function(event) {
        if (event.persisted) {
            window.location.reload();
        }
    });
}

        document.addEventListener('DOMContentLoaded', function () {
    // Event listeners principales (sin cambios)
    document.getElementById('logoutBtn').addEventListener('click', secureLogout);
    document.getElementById('refreshBtn').addEventListener('click', () => {
        currentPage = 1;
        cargarResultados();
    });
    document.getElementById('filterBtn').addEventListener('click', function () {
        currentSearchTerm = document.getElementById('filterInput').value.trim();
        currentPage = 1;
        cargarResultados();
    });
    document.getElementById('filterInput').addEventListener('keyup', function (e) {
        if (e.key === 'Enter') {
            currentSearchTerm = this.value.trim();
            currentPage = 1;
            cargarResultados();
        }
    });
    
    // Event listeners para los items del dropdown (versión minimalista)
    document.querySelectorAll('.dropdown-item').forEach(item => {
        item.addEventListener('click', function (e) {
            e.preventDefault();
            currentQueryType = this.dataset.value;
            currentSelectionText = this.textContent;
            currentPage = 1;
            currentSearchTerm = '';
            document.getElementById('filterInput').value = '';
            cargarResultados();
            
            // Cierra el menú inmediatamente después de la selección
            const dropdown = bootstrap.Dropdown.getInstance(this.closest('.dropdown').querySelector('.dropdown-toggle'));
            if (dropdown) dropdown.hide();
        });
    });
    
    // Event listeners para los años (sin efectos)
    document.querySelectorAll('.year-item').forEach(item => {
        item.addEventListener('click', function (e) {
            e.preventDefault();
            currentQueryType = this.dataset.value;
            currentSelectionText = `Docentes - Unificado ${this.textContent}`;
            currentPage = 1;
            currentSearchTerm = '';
            document.getElementById('filterInput').value = '';
            cargarResultados();
        });
    });

    // Eliminamos completamente los event listeners de mouseleave/mouseenter
    // que causaban los efectos no deseados

    document.getElementById('excelBtn').addEventListener('click', exportarAExcel);
    document.getElementById('pdfBtn').addEventListener('click', exportarAPDF);
});
    </script>
</body>
</html>