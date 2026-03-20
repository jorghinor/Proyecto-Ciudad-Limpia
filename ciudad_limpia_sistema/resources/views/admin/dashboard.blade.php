<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Ciudad Limpia</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <!-- Gráficas 3D (Highcharts - CDNJS) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highcharts/11.4.0/highcharts.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highcharts/11.4.0/highcharts-3d.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highcharts/11.4.0/modules/exporting.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highcharts/11.4.0/modules/export-data.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highcharts/11.4.0/modules/accessibility.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; }
        .sidebar { width: 250px; background: #2c3e50; color: white; position: fixed; height: 100vh; padding: 20px; overflow-y: auto; transition: transform 0.3s ease; z-index: 1100; }
        .sidebar h2 { margin-bottom: 16px; text-align: center; }
        .sidebar .brand-media { display: flex; justify-content: center; margin-bottom: 18px; }
        .sidebar .brand-video { width: 160px; max-width: 100%; border-radius: 16px; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.2); box-shadow: 0 10px 20px rgba(0,0,0,0.25); }
        .sidebar nav a { display: block; color: white; text-decoration: none; padding: 15px; margin: 5px 0; border-radius: 5px; transition: background 0.3s; }
        .sidebar nav a:hover, .sidebar nav a.active { background: #34495e; }
        .main-content { margin-left: 250px; padding: 30px; transition: margin-left 0.3s ease; }
        .header { background: white; padding: 20px; border-radius: 10px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap; }
        .header-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .menu-toggle { display: none; }
        .pdf-logo { display: none; height: 36px; width: auto; vertical-align: middle; }
        .pdf-watermark { position: relative; }
        .pdf-watermark::before {
            content: "";
            position: absolute;
            inset: 0;
            background: url('/images/carrobasurero.jpg') center center / 60% no-repeat;
            opacity: 0.08;
            pointer-events: none;
            z-index: 0;
        }
        .pdf-watermark > * { position: relative; z-index: 1; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .stat-card h3 { color: #7f8c8d; font-size: 14px; text-transform: uppercase; }
        .stat-card .number { font-size: 36px; font-weight: bold; color: #2c3e50; margin: 10px 0; }
        .stat-card.success { border-left: 4px solid #27ae60; }
        .stat-card.warning { border-left: 4px solid #f39c12; }
        .stat-card.danger { border-left: 4px solid #e74c3c; }
        .stat-card.info { border-left: 4px solid #3498db; }
        .section { background: white; padding: 25px; border-radius: 10px; margin-bottom: 20px; }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px; }
        .search-box { display: flex; gap: 10px; margin-bottom: 20px; }
        .search-box input { flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ecf0f1; }
        th { background: #34495e; color: white; }
        tr:hover { background: #f8f9fa; }
        .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; margin: 2px; }
        .btn-primary { background: #3498db; color: white; }
        .btn-success { background: #27ae60; color: white; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-warning { background: #f39c12; color: white; }
        .btn-secondary { background: #95a5a6; color: white; }
        .btn-info { background: #17a2b8; color: white; }
        .badge { padding: 5px 10px; border-radius: 15px; font-size: 12px; }
        .badge-vacio { background: #27ae60; color: white; }
        .badge-medio { background: #f39c12; color: white; }
        .badge-casi { background: #e67e22; color: white; }
        .badge-lleno { background: #e74c3c; color: white; }
        .badge-danado { background: #8e44ad; color: white; }
        .badge-admin { background: #e74c3c; color: white; }
        .badge-operador { background: #3498db; color: white; }
        .badge-ciudadano { background: #95a5a6; color: white; }
        .badge-pendiente { background: #f39c12; color: white; }
        .badge-en_progreso { background: #3498db; color: white; }
        .badge-completada { background: #27ae60; color: white; }
        .badge-activa { background: #e74c3c; color: white; }
        .badge-atendida { background: #27ae60; color: white; }
        .badge-baja { background: #95a5a6; color: white; }
        .badge-normal { background: #3498db; color: white; }
        .badge-alta { background: #e67e22; color: white; }
        .badge-urgente { background: #e74c3c; color: white; }
        #loginForm { max-width: 400px; margin: 100px auto; background: white; padding: 40px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        #loginForm h2 { text-align: center; margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .form-group textarea { resize: vertical; min-height: 80px; }
        .hidden { display: none !important; }
        .modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; justify-content: center; align-items: center; z-index: 1000; }
        .modal-content { background: white; padding: 30px; border-radius: 10px; max-width: 800px; width: 90%; max-height: 90vh; overflow-y: auto; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .close-btn { font-size: 24px; cursor: pointer; color: #7f8c8d; }
        .close-btn:hover { color: #2c3e50; }
        .pagination { display: flex; justify-content: center; gap: 5px; margin-top: 20px; }
        .pagination button { padding: 8px 15px; border: 1px solid #ddd; background: white; cursor: pointer; border-radius: 5px; }
        .pagination button.active { background: #3498db; color: white; }
        .pagination button:disabled { opacity: 0.5; cursor: not-allowed; }
        .actions { display: flex; gap: 5px; flex-wrap: wrap; }
        .empty-state { text-align: center; padding: 40px; color: #7f8c8d; }
        .filter-bar { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
        .filter-bar select { padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        #map { height: 300px; border-radius: 5px; margin: 10px 0; }
        #mapa-camiones { height: 400px; border-radius: 10px; }
        #mapa-ruta { height: 350px; border-radius: 10px; margin: 15px 0; }
        .map-container { margin: 15px 0; }
        .coord-display { background: #f8f9fa; padding: 10px; border-radius: 5px; margin-top: 10px; font-family: monospace; }
        .map-controls { display: flex; gap: 10px; margin-bottom: 15px; flex-wrap: wrap; align-items: center; }
        .last-update { font-size: 12px; color: #7f8c8d; margin-left: auto; }
        .map-legend { display: flex; gap: 15px; font-size: 12px; margin-top: 10px; }
        .legend-item { display: flex; align-items: center; gap: 5px; }
        .legend-color { width: 12px; height: 12px; border-radius: 50%; }
        .ruta-info { background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 10px 0; }
        .ruta-info-item { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #dee2e6; }
        .ruta-info-item:last-child { border-bottom: none; }
        .contenedores-list { max-height: 200px; overflow-y: auto; border: 1px solid #ddd; border-radius: 5px; padding: 10px; margin: 10px 0; }
        .contenedor-item { display: flex; align-items: center; padding: 8px; border-bottom: 1px solid #eee; cursor: pointer; }
        .contenedor-item:hover { background: #f8f9fa; }
        .contenedor-item.selected { background: #d4edda; border-left: 3px solid #28a745; }
        .contenedor-checkbox { margin-right: 10px; }
        .modal-large { max-width: 900px; }
        .two-columns { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        @media (max-width: 768px) { .two-columns { grid-template-columns: 1fr; } }
        .reporte-foto { max-width: 100%; border-radius: 8px; margin: 10px 0; }
        .reporte-info { background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 10px 0; }
        .reporte-info-item { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #dee2e6; }
        .reporte-info-item:last-child { border-bottom: none; }
        .alerta-info { background: #fff3cd; padding: 15px; border-radius: 8px; margin: 10px 0; border-left: 4px solid #ffc107; }
        .estado-cambio { display: flex; gap: 10px; margin: 15px 0; }

        .charts-container { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        .chart-box { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); height: 350px; position: relative; }
        @media (max-width: 768px) { .charts-container { grid-template-columns: 1fr; } }
        .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .table-responsive table { min-width: 720px; }
        .sidebar-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,0.4); z-index: 1050; opacity: 0; pointer-events: none; transition: opacity 0.3s ease; }
        .sidebar-backdrop.active { opacity: 1; pointer-events: all; }
        @media (max-width: 992px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 20px; }
            .menu-toggle { display: inline-flex; align-items: center; justify-content: center; }
        }
        @media (max-width: 576px) {
            .header { padding: 15px; }
            .section { padding: 18px; }
            .btn { padding: 8px 14px; }
        }
    </style>
</head>
<body>
    <!-- Login Form -->
    <div id="loginForm">
        <h2>🔐 Iniciar Sesión</h2>
        <div class="form-group">
            <label>Email:</label>
            <input type="email" id="email" placeholder="admin@ciudadlimpia.com">
        </div>
        <div class="form-group">
            <label>Contraseña:</label>
            <input type="password" id="password" placeholder="********">
        </div>
        <button class="btn btn-primary" onclick="login()" style="width: 100%;">Ingresar</button>
        <p id="loginError" style="color: red; margin-top: 15px; text-align: center;"></p>
    </div>

    <!-- Admin Dashboard -->
    <div id="dashboard" class="hidden">
        <div class="sidebar" id="sidebar">
            <h2>🌆 Ciudad Limpia</h2>
            <div class="brand-media">
                <video class="brand-video" autoplay muted loop playsinline>
                    <source src="/images/logoanimado2.mp4" type="video/mp4">
                </video>
            </div>
            <nav>
                <a href="#" onclick="showSection('inicio')" id="nav-inicio">📊 Dashboard</a>
                <a href="#" onclick="showSection('contenedores')" id="nav-contenedores">🗑️ Contenedores</a>
                <a href="#" onclick="showSection('camiones')" id="nav-camiones">🚛 Camiones</a>
                <a href="#" onclick="showSection('rutas')" id="nav-rutas">🗺️ Rutas</a>
                <a href="#" onclick="showSection('reportes')" id="nav-reportes">📢 Reportes</a>
                <a href="#" onclick="showSection('alertas')" id="nav-alertas">🔔 Alertas</a>
                <a href="#" onclick="showSection('usuarios')" id="nav-usuarios">👥 Usuarios</a>
                <a href="#" onclick="logout()">🚪 Cerrar Sesión</a>
            </nav>
        </div>
        <div class="sidebar-backdrop" id="sidebar-backdrop" onclick="toggleSidebar(false)"></div>

        <div class="main-content" id="main-content-area">
            <div class="header">
                <button class="btn btn-secondary menu-toggle" type="button" onclick="toggleSidebar()">☰ Menú</button>
                <h1 id="page-title">Panel de Administración</h1>
                <div class="header-actions">
                    <button class="btn btn-secondary" onclick="descargarPDF()" style="margin-right: 10px;">📄 Exportar PDF</button>
                    <img id="pdf-logo" class="pdf-logo" src="/images/carrobasurero.jpg" alt="Ciudad Limpia">
                    <span id="userName">Usuario</span> |
                    <span id="userRole" class="badge">Rol</span>
                </div>
            </div>

            <!-- Dashboard Inicio -->
            <div id="section-inicio" class="section-content">
                <div class="stats-grid">
                    <div class="stat-card danger">
                        <h3>Contenedores Llenos</h3>
                        <div class="number" id="stat-llenos">0</div>
                    </div>
                    <div class="stat-card warning">
                        <h3>Casi Llenos</h3>
                        <div class="number" id="stat-casi">0</div>
                    </div>
                    <div class="stat-card info">
                        <h3>Rutas Activas</h3>
                        <div class="number" id="stat-rutas">0</div>
                    </div>
                    <div class="stat-card success">
                        <h3>Camiones Disponibles</h3>
                        <div class="number" id="stat-camiones">0</div>
                    </div>
                    <div class="stat-card danger">
                        <h3>Alertas Activas</h3>
                        <div class="number" id="stat-alertas">0</div>
                    </div>
                    <div class="stat-card warning">
                        <h3>Reportes Pendientes</h3>
                        <div class="number" id="stat-reportes">0</div>
                    </div>
                </div>

                <div class="charts-container">
                    <div class="chart-box">
                        <div id="chart-contenedores" style="width: 100%; height: 100%;"></div>
                    </div>
                    <div class="chart-box">
                        <div id="chart-eficiencia" style="width: 100%; height: 100%;"></div>
                    </div>
                </div>

                <div class="section">
                    <h3>🔔 Alertas Recientes</h3>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr><th>Tipo</th><th>Mensaje</th><th>Fecha</th><th>Acción</th></tr>
                            </thead>
                            <tbody id="alertas-recientes-table"></tbody>
                        </table>
                    </div>
                </div>

                <div class="section">
                    <div class="section-header">
                        <h3>🗺️ Camiones en Tiempo Real</h3>
                        <button class="btn btn-primary" onclick="actualizarMapaCamiones()">🔄 Actualizar</button>
                    </div>
                    <div class="map-controls">
                        <select id="filter-mapa-camiones" onchange="actualizarMapaCamiones()">
                            <option value="todos">Todos los camiones</option>
                            <option value="disponible">Disponibles</option>
                            <option value="en_ruta">En Ruta</option>
                            <option value="mantenimiento">Mantenimiento</option>
                        </select>
                        <button class="btn btn-secondary" onclick="centrarMapaEnCamiones()">📍 Centrar Todos</button>
                        <button class="btn btn-secondary" onclick="toggleAutoRefresh()" id="btn-auto-refresh">⏸️ Pausar</button>
                        <span class="last-update" id="last-update">Última actualización: --</span>
                    </div>
                    <div id="mapa-camiones" style="height: 400px; border-radius: 10px;"></div>
                    <div class="map-legend">
                        <div class="legend-item"><div class="legend-color" style="background: green;"></div> Disponible</div>
                        <div class="legend-item"><div class="legend-color" style="background: red;"></div> En Ruta</div>
                        <div class="legend-item"><div class="legend-color" style="background: orange;"></div> Mantenimiento</div>
                    </div>
                </div>
            </div>

            <!-- Contenedores -->
            <div id="section-contenedores" class="section-content hidden">
                <div class="section">
                    <div class="section-header">
                        <h3>🗑️ Gestión de Contenedores</h3>
                        <button class="btn btn-success" onclick="openModal('contenedor')">+ Nuevo Contenedor</button>
                    </div>
                    <div class="filter-bar">
                        <input type="text" id="search-contenedores" placeholder="Buscar contenedor..." onkeyup="searchContenedores()">
                        <select id="filter-estado-contenedor" onchange="filterContenedores()">
                            <option value="">Todos los estados</option>
                            <option value="vacio">Vacio</option>
                            <option value="medio">Medio</option>
                            <option value="casi lleno">Casi Lleno</option>
                            <option value="lleno">Lleno</option>
                            <option value="danado">Dañado</option>
                        </select>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr><th>ID</th><th>Ubicación</th><th>Nivel</th><th>Estado</th><th>Acciones</th></tr>
                            </thead>
                            <tbody id="contenedores-table"></tbody>
                        </table>
                    </div>
                    <div class="pagination" id="pagination-contenedores"></div>
                </div>
            </div>

            <!-- Camiones -->
            <div id="section-camiones" class="section-content hidden">
                <div class="section">
                    <div class="section-header">
                        <h3>🚛 Gestión de Camiones</h3>
                        <button class="btn btn-success" onclick="openModal('camion')">+ Nuevo Camión</button>
                    </div>
                    <div class="filter-bar">
                        <input type="text" id="search-camiones" placeholder="Buscar camión..." onkeyup="searchCamiones()">
                        <select id="filter-estado-camion" onchange="filterCamiones()">
                            <option value="">Todos los estados</option>
                            <option value="disponible">Disponible</option>
                            <option value="en_ruta">En Ruta</option>
                            <option value="mantenimiento">Mantenimiento</option>
                        </select>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr><th>ID</th><th>Placa</th><th>Conductor</th><th>Estado</th><th>Capacidad</th><th>Acciones</th></tr>
                            </thead>
                            <tbody id="camiones-table"></tbody>
                        </table>
                    </div>
                    <div class="pagination" id="pagination-camiones"></div>
                </div>
            </div>

            <!-- Rutas -->
            <div id="section-rutas" class="section-content hidden">
                <div class="section">
                    <div class="section-header">
                        <h3>🗺️ Gestión de Rutas</h3>
                        <button class="btn btn-success" onclick="openModal('ruta')">+ Nueva Ruta</button>
                        <button class="btn btn-primary" onclick="openModal('optimizar-ruta')">⚡ Optimizar Ruta</button>
                    </div>
                    <div class="filter-bar">
                        <input type="text" id="search-rutas" placeholder="Buscar ruta..." onkeyup="searchRutas()">
                        <select id="filter-estado-ruta" onchange="filterRutas()">
                            <option value="">Todos los estados</option>
                            <option value="pendiente">Pendiente</option>
                            <option value="en_progreso">En Progreso</option>
                            <option value="completada">Completada</option>
                        </select>
                        <select id="filter-prioridad-ruta" onchange="filterRutas()">
                            <option value="">Todas las prioridades</option>
                            <option value="baja">Baja</option>
                            <option value="normal">Normal</option>
                            <option value="alta">Alta</option>
                            <option value="urgente">Urgente</option>
                        </select>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr><th>ID</th><th>Nombre</th><th>Zona</th><th>Camión</th><th>Estado</th><th>Prioridad</th><th>Progreso</th><th>Acciones</th></tr>
                            </thead>
                            <tbody id="rutas-table"></tbody>
                        </table>
                    </div>
                    <div class="pagination" id="pagination-rutas"></div>
                </div>
            </div>

            <!-- Reportes -->
            <div id="section-reportes" class="section-content hidden">
                <div class="section">
                    <div class="section-header">
                        <h3>📢 Reportes Ciudadanos</h3>
                        <button class="btn btn-success" onclick="openModal('reporte')">+ Nuevo Reporte</button>
                    </div>
                    <div class="filter-bar">
                        <input type="text" id="search-reportes" placeholder="Buscar reporte..." onkeyup="searchReportes()">
                        <select id="filter-tipo-reporte" onchange="filterReportes()">
                            <option value="">Todos los tipos</option>
                            <option value="basura_en_calle">Basura en Calle</option>
                            <option value="contenedor_danado">Contenedor Dañado</option>
                            <option value="punto_critico">Punto Crítico</option>
                        </select>
                        <select id="filter-estado-reporte" onchange="filterReportes()">
                            <option value="">Todos los estados</option>
                            <option value="pendiente">Pendiente</option>
                            <option value="en_proceso">En Proceso</option>
                            <option value="resuelto">Resuelto</option>
                        </select>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr><th>ID</th><th>Tipo</th><th>Descripción</th><th>Ubicación</th><th>Estado</th><th>Fecha</th><th>Acciones</th></tr>
                            </thead>
                            <tbody id="reportes-table"></tbody>
                        </table>
                    </div>
                    <div class="pagination" id="pagination-reportes"></div>
                </div>
            </div>

            <!-- Alertas -->
            <div id="section-alertas" class="section-content hidden">
                <div class="section">
                    <div class="section-header">
                        <h3>🔔 Todas las Alertas</h3>
                        <button class="btn btn-danger" onclick="eliminarTodasAlertasAtendidas()">🗑️ Limpiar Atendidas</button>
                    </div>
                    <div class="filter-bar">
                        <input type="text" id="search-alertas" placeholder="Buscar alerta..." onkeyup="searchAlertas()">
                        <select id="filter-tipo-alerta" onchange="filterAlertas()">
                            <option value="">Todos los tipos</option>
                            <option value="contenedor_lleno">Contenedor Lleno</option>
                            <option value="contenedor_danado">Contenedor Dañado</option>
                            <option value="zona_critica">Zona Crítica</option>
                        </select>
                        <select id="filter-estado-alerta" onchange="filterAlertas()">
                            <option value="">Todos los estados</option>
                            <option value="activa">Activa</option>
                            <option value="atendida">Atendida</option>
                        </select>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr><th>ID</th><th>Tipo</th><th>Mensaje</th><th>Estado</th><th>Fecha</th><th>Acciones</th></tr>
                            </thead>
                            <tbody id="alertas-table"></tbody>
                        </table>
                    </div>
                    <div class="pagination" id="pagination-alertas"></div>
                </div>
            </div>

            <!-- Usuarios -->
            <div id="section-usuarios" class="section-content hidden">
                <div class="section">
                    <div class="section-header">
                        <h3>👥 Gestión de Usuarios</h3>
                        <button class="btn btn-success" onclick="openModal('usuario')">+ Nuevo Usuario</button>
                    </div>
                    <div class="filter-bar">
                        <input type="text" id="search-usuarios" placeholder="Buscar usuario..." onkeyup="searchUsuarios()">
                        <select id="filter-rol-usuario" onchange="filterUsuarios()">
                            <option value="">Todos los roles</option>
                            <option value="admin">Admin</option>
                            <option value="operador">Operador</option>
                            <option value="ciudadano">Ciudadano</option>
                        </select>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr><th>ID</th><th>Nombre</th><th>Email</th><th>Rol</th><th>Acciones</th></tr>
                            </thead>
                            <tbody id="usuarios-table"></tbody>
                        </table>
                    </div>
                    <div class="pagination" id="pagination-usuarios"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div id="modal" class="modal hidden">
        <div class="modal-content" id="modal-content">
            <div class="modal-header">
                <h3 id="modal-title">Título</h3>
                <span class="close-btn" onclick="closeModal()">&times;</span>
            </div>
            <div id="modal-body"></div>
        </div>
    </div>

    <!-- Cargar Scripts Modulares -->
    <script src="{{ asset('js/app-core.js') }}"></script>
    <script src="{{ asset('js/app-modules.js') }}"></script>
    <script src="{{ asset('js/app-modules-2.js') }}"></script>
    <script>
        function toggleSidebar(forceOpen) {
            const sidebar = document.getElementById('sidebar');
            const backdrop = document.getElementById('sidebar-backdrop');
            const isOpen = sidebar.classList.contains('open');
            const nextOpen = typeof forceOpen === 'boolean' ? forceOpen : !isOpen;
            sidebar.classList.toggle('open', nextOpen);
            backdrop.classList.toggle('active', nextOpen);
        }
    </script>
</body>
</html>
