
        const API_URL = 'http://localhost:8000/api';
        let token = localStorage.getItem('token');
        let user = JSON.parse(localStorage.getItem('user') || '{}');
        let currentData = {};
        let pagination = { contenedores: 1, camiones: 1, rutas: 1, reportes: 1, alertas: 1, usuarios: 1 };
        const ITEMS_PER_PAGE = 10;

        if (token) showDashboard();

        async function login() {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            try {
                const response = await fetch(`${API_URL}/login`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ email, password })
                });
                const data = await response.json();
                if (response.ok) {
                    token = data.token;
                    user = data.user;
                    localStorage.setItem('token', token);
                    localStorage.setItem('user', JSON.stringify(user));
                    showDashboard();
                } else {
                    document.getElementById('loginError').textContent = data.message || 'Error de autenticación';
                }
            } catch (error) {
                document.getElementById('loginError').textContent = 'Error de conexión';
            }
        }

        function logout() {
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            location.reload();
        }

        function showDashboard() {
            document.getElementById('loginForm').classList.add('hidden');
            document.getElementById('dashboard').classList.remove('hidden');
            document.getElementById('userName').textContent = user.name;
            document.getElementById('userRole').textContent = user.rol;
            document.getElementById('userRole').className = 'badge badge-' + user.rol;
            cargarMetricas();
            cargarAlertasRecientes();
        }

        function showSection(section) {
            document.querySelectorAll('.section-content').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('.sidebar nav a').forEach(el => el.classList.remove('active'));
            document.getElementById('section-' + section).classList.remove('hidden');
            document.getElementById('nav-' + section).classList.add('active');
            document.getElementById('page-title').textContent = document.getElementById('nav-' + section).textContent.trim();

            if (section === 'contenedores') cargarContenedores();
            if (section === 'camiones') cargarCamiones();
            if (section === 'rutas') cargarRutas();
            if (section === 'reportes') cargarReportes();
            if (section === 'alertas') cargarAlertas();
            if (section === 'usuarios' && user.rol === 'admin') cargarUsuarios();
        }

        async function apiFetch(endpoint, options = {}) {
            options.headers = { ...options.headers, 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json', 'Accept': 'application/json' };
            const response = await fetch(`${API_URL}${endpoint}`, options);
            if (response.status === 401) { logout(); return; }
            if (response.status === 422) {
                const err = await response.json();
                console.error("Error de validación", err);
                alert("Error de validación: " + err.message);
                throw new Error("Validation Error");
            }
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        }

        // ========== DASHBOARD ==========
        async function cargarMetricas() {
            try {
                const data = await apiFetch('/dashboard/metricas');
                document.getElementById('stat-llenos').textContent = data.contenedores.llenos;
                document.getElementById('stat-casi').textContent = data.contenedores.casi_llenos;
                document.getElementById('stat-rutas').textContent = data.rutas.activas;
                document.getElementById('stat-camiones').textContent = data.camiones.disponibles;
                document.getElementById('stat-alertas').textContent = data.alertas.activas;
                document.getElementById('stat-reportes').textContent = data.reportes.pendientes;
            } catch (e) { console.error("Error cargando métricas", e); }
        }

        async function cargarAlertasRecientes() {
            try {
                const alertas = await apiFetch('/alertas/activas');
                const tbody = document.getElementById('alertas-recientes-table');
                tbody.innerHTML = alertas.slice(0, 5).map(a => `
                    <tr>
                        <td><span class="badge badge-${a.tipo === 'contenedor_lleno' ? 'danger' : 'warning'}">${a.tipo}</span></td>
                        <td>${a.mensaje}</td>
                        <td>${new Date(a.created_at).toLocaleString()}</td>
                        <td><button class="btn btn-success" onclick="atenderAlerta(${a.id})">Atender</button></td>
                    </tr>
                `).join('');
            } catch (e) { console.error("Error cargando alertas recientes", e); }
        }

        // ========== PAGINACIÓN ==========
        function renderPagination(entity, totalItems, currentPage, callback) {
            const totalPages = Math.ceil(totalItems / ITEMS_PER_PAGE);
            const container = document.getElementById('pagination-' + entity);
            let html = `<button onclick="${callback}(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>Anterior</button>`;
            for (let i = 1; i <= totalPages; i++) {
                html += `<button onclick="${callback}(${i})" class="${i === currentPage ? 'active' : ''}">${i}</button>`;
            }
            html += `<button onclick="${callback}(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''}>Siguiente</button>`;
            container.innerHTML = html;
        }

        // ========== CONTENEDORES ==========
        async function cargarContenedores(page = 1) {
            pagination.contenedores = page;
            const data = await apiFetch('/contenedores');
            currentData.contenedores = data;
            renderContenedores();
        }

        function renderContenedores() {
            let data = currentData.contenedores || [];
            const search = document.getElementById('search-contenedores').value.toLowerCase();
            const estado = document.getElementById('filter-estado-contenedor').value;

            if (search) data = data.filter(c => c.ubicacion.toLowerCase().includes(search));
            if (estado) data = data.filter(c => c.estado === estado);

            const start = (pagination.contenedores - 1) * ITEMS_PER_PAGE;
            const paginated = data.slice(start, start + ITEMS_PER_PAGE);

            const tbody = document.getElementById('contenedores-table');
            tbody.innerHTML = paginated.map(c => `
                <tr>
                    <td>${c.id}</td>
                    <td>${c.ubicacion}</td>
                    <td>${c.nivel_llenado}%</td>
                    <td><span class="badge badge-${c.estado.replace(' ', '-')}">${c.estado}</span></td>
                    <td class="actions">
                        <button class="btn btn-warning" onclick="cambiarNivel(${c.id})">Nivel</button>
                        <button class="btn btn-primary" onclick="editarContenedor(${c.id})">Editar</button>
                        <button class="btn btn-danger" onclick="eliminarContenedor(${c.id})">Eliminar</button>
                    </td>
                </tr>
            `).join('');
            renderPagination('contenedores', data.length, pagination.contenedores, 'cargarContenedoresPage');
        }

        function cargarContenedoresPage(page) { pagination.contenedores = page; renderContenedores(); }
        function searchContenedores() { cargarContenedores(1); }
        function filterContenedores() { cargarContenedores(1); }

        async function cambiarNivel(id) {
            const nivel = prompt('Nuevo nivel (0-100):');
            if (nivel !== null) {
                await apiFetch(`/contenedores/${id}/nivel`, { method: 'POST', body: JSON.stringify({ nivel_llenado: parseInt(nivel) }) });
                cargarContenedores();
                cargarMetricas();
            }
        }

        async function eliminarContenedor(id) {
            if (confirm('¿Eliminar este contenedor?')) {
                await apiFetch(`/contenedores/${id}`, { method: 'DELETE' });
                cargarContenedores();
            }
        }

        // ========== CAMIONES ==========
        async function cargarCamiones(page = 1) {
            pagination.camiones = page;
            const data = await apiFetch('/camiones');
            currentData.camiones = data;
            renderCamiones();
        }

        function renderCamiones() {
            let data = currentData.camiones || [];
            const search = document.getElementById('search-camiones').value.toLowerCase();
            const estado = document.getElementById('filter-estado-camion').value;

            if (search) data = data.filter(c => c.placa.toLowerCase().includes(search) || c.conductor.toLowerCase().includes(search));
            if (estado) data = data.filter(c => c.estado === estado);

            const start = (pagination.camiones - 1) * ITEMS_PER_PAGE;
            const paginated = data.slice(start, start + ITEMS_PER_PAGE);

            const tbody = document.getElementById('camiones-table');
            tbody.innerHTML = paginated.map(c => `
                <tr>
                    <td>${c.id}</td>
                    <td>${c.placa}</td>
                    <td>${c.conductor}</td>
                    <td><span class="badge badge-${c.estado}">${c.estado}</span></td>
                    <td>${c.capacidad} kg</td>
                    <td class="actions">
                        <button class="btn btn-primary" onclick="editarCamion(${c.id})">Editar</button>
                        <button class="btn btn-danger" onclick="eliminarCamion(${c.id})">Eliminar</button>
                    </td>
                </tr>
            `).join('');
            renderPagination('camiones', data.length, pagination.camiones, 'cargarCamionesPage');
        }

        function cargarCamionesPage(page) { pagination.camiones = page; renderCamiones(); }
        function searchCamiones() { cargarCamiones(1); }
        function filterCamiones() { cargarCamiones(1); }

        async function eliminarCamion(id) {
            if (confirm('¿Eliminar este camión?')) {
                await apiFetch(`/camiones/${id}`, { method: 'DELETE' });
                cargarCamiones();
            }
        }

        // ========== RUTAS ==========
        async function cargarRutas(page = 1) {
            pagination.rutas = page;
            const data = await apiFetch('/rutas');
            currentData.rutas = data;
            renderRutas();
        }

        function renderRutas() {
            let data = currentData.rutas || [];
            const search = document.getElementById('search-rutas').value.toLowerCase();
            const estado = document.getElementById('filter-estado-ruta').value;
            const prioridad = document.getElementById('filter-prioridad-ruta').value;

            if (search) data = data.filter(r => r.nombre.toLowerCase().includes(search) || r.zona.toLowerCase().includes(search));
            if (estado) data = data.filter(r => r.estado === estado);
            if (prioridad) data = data.filter(r => r.prioridad === prioridad);

            const start = (pagination.rutas - 1) * ITEMS_PER_PAGE;
            const paginated = data.slice(start, start + ITEMS_PER_PAGE);

            const tbody = document.getElementById('rutas-table');
            tbody.innerHTML = paginated.map(r => {
                const progreso = calcularProgresoRuta(r);
                return `
                <tr>
                    <td>${r.id}</td>
                    <td>${r.nombre}</td>
                    <td>${r.zona}</td>
                    <td>${r.camion ? r.camion.placa : '-'}</td>
                    <td><span class="badge badge-${r.estado}">${r.estado}</span></td>
                    <td><span class="badge badge-${r.prioridad}">${r.prioridad}</span></td>
                    <td>
                        <div style="background: #ecf0f1; border-radius: 10px; height: 20px; overflow: hidden;">
                            <div style="background: ${progreso === 100 ? '#27ae60' : '#3498db'}; width: ${progreso}%; height: 100%; text-align: center; color: white; font-size: 12px; line-height: 20px;">${progreso}%</div>
                        </div>
                    </td>
                    <td class="actions">
                        <button class="btn btn-info" onclick="verRuta(${r.id})">Ver</button>
                        <button class="btn btn-primary" onclick="editarRuta(${r.id})">Editar</button>
                        <button class="btn btn-danger" onclick="eliminarRuta(${r.id})">Eliminar</button>
                    </td>
                </tr>
            `}).join('');
            renderPagination('rutas', data.length, pagination.rutas, 'cargarRutasPage');
        }

        function calcularProgresoRuta(ruta) {
            if (!ruta.contenedores_ids || ruta.contenedores_ids.length === 0) return 0;
            if (ruta.estado === 'completada') return 100;
            if (ruta.estado === 'pendiente') return 0;
            return 50;
        }

        function cargarRutasPage(page) { pagination.rutas = page; renderRutas(); }
        function searchRutas() { cargarRutas(1); }
        function filterRutas() { cargarRutas(1); }

        async function eliminarRuta(id) {
            if (confirm('¿Eliminar esta ruta?')) {
                await apiFetch(`/rutas/${id}`, { method: 'DELETE' });
                cargarRutas();
            }
        }

        async function verRuta(id) {
            const ruta = await apiFetch(`/rutas/${id}`);
            openModal('ver-ruta', ruta);
        }

        async function iniciarRuta(id) {
            await apiFetch(`/rutas/${id}`, {
                method: 'PUT',
                body: JSON.stringify({ estado: 'en_progreso' })
            });
            cargarRutas();
            cargarMetricas();
        }

        async function completarRuta(id) {
            await apiFetch(`/rutas/${id}`, {
                method: 'PUT',
                body: JSON.stringify({ estado: 'completada' })
            });
            cargarRutas();
            cargarMetricas();
        }

        // ========== REPORTES ==========
        async function cargarReportes(page = 1) {
            pagination.reportes = page;
            const data = await apiFetch('/reportes');
            currentData.reportes = data;
            renderReportes();
        }

        function renderReportes() {
                    let data = currentData.reportes || [];
                    const search = document.getElementById('search-reportes').value.toLowerCase();
                    const tipo = document.getElementById('filter-tipo-reporte').value;
                    const estado = document.getElementById('filter-estado-reporte').value;

                    if (search) data = data.filter(r => r.descripcion.toLowerCase().includes(search) || r.ubicacion.toLowerCase().includes(search));
                    if (tipo) data = data.filter(r => r.tipo === tipo);
                    if (estado) data = data.filter(r => r.estado === estado);

                    const start = (pagination.reportes - 1) * ITEMS_PER_PAGE;
                    const paginated = data.slice(start, start + ITEMS_PER_PAGE);

                    const tbody = document.getElementById('reportes-table');
                    tbody.innerHTML = paginated.map(r => `                <tr>
                            <td>${r.id}</td>
                            <td><span class="badge badge-${r.tipo}">${r.tipo.replace(/_/g, ' ')}</span></td>
                            <td>${r.descripcion.substring(0, 50)}${r.descripcion.length > 50 ? '...' : ''}</td>
                            <td>${r.ubicacion}</td>
                            <td><span class="badge badge-${r.estado}">${r.estado.replace(/_/g, ' ')}</span></td>
                            <td>${new Date(r.created_at).toLocaleDateString()}</td>
                            <td class="actions">
                                <button class="btn btn-info" onclick="verReporte(${r.id})">Ver</button>
                                ${r.estado === 'pendiente' ? `<button class="btn btn-warning" onclick="cambiarEstadoReporte(${r.id}, 'en_proceso')">En Proceso</button>` : ''}                        ${r.estado !== 'resuelto' ? `<button class="btn btn-success" onclick="resolverReporte(${r.id})">Resolver</button>` : ''}                        <button class="btn btn-danger" onclick="eliminarReporte(${r.id})">Eliminar</button>
                            </td>
                        </tr>
                    `).join('');
                    renderPagination('reportes', data.length, pagination.reportes, 'cargarReportesPage');
                }

        function cargarReportesPage(page) { pagination.reportes = page; renderReportes(); }
        function searchReportes() { cargarReportes(1); }
        function filterReportes() { cargarReportes(1); }

         async function cambiarEstadoReporte(id, estado) {
                    await apiFetch(`/reportes/${id}`, { method: 'PUT', body: JSON.stringify({ estado }) });
                    cargarReportes();
                }

                async function eliminarReporte(id) {
                    if (confirm('¿Eliminar este reporte permanentemente?')) {
                        await apiFetch(`/reportes/${id}`, { method: 'DELETE' });
                        cargarReportes();
                    }
                }

                async function guardarReporte() {
                    const data = {
                        tipo: document.getElementById('form-reporte-tipo').value,
                        descripcion: document.getElementById('form-reporte-descripcion').value,
                        ubicacion: document.getElementById('form-reporte-ubicacion').value,
                        ciudadano_nombre: document.getElementById('form-reporte-nombre').value,
                        ciudadano_contacto: document.getElementById('form-reporte-contacto').value,
                        foto: document.getElementById('form-reporte-foto').value,
                        latitud: parseFloat(document.getElementById('form-reporte-latitud').value) || null,
                        longitud: parseFloat(document.getElementById('form-reporte-longitud').value) || null
                    };
                    await apiFetch('/reportes', { method: 'POST', body: JSON.stringify(data) });
                    closeModal();
                    cargarReportes();
                }

                function verReporte(id) {
                    openModal('reporte', id);
                }

                function verAlerta(id) {
                    const alerta = currentData.alertas.find(a => a.id === id);
                    if (alerta) {
                        openModal('alerta', alerta);
                    }
                }

                async function eliminarAlerta(id) {
                    if (confirm('¿Eliminar esta alerta permanentemente?')) {
                        await apiFetch(`/alertas/${id}`, { method: 'DELETE' });
                        cargarAlertas();
                        cargarAlertasRecientes();
                        cargarMetricas();
                    }
                }

                async function eliminarTodasAlertasAtendidas() {
                    if (confirm('¿Eliminar todas las alertas atendidas?')) {
                        const alertasAtendidas = currentData.alertas.filter(a => a.estado === 'atendida');
                        for (const alerta of alertasAtendidas) {
                            await apiFetch(`/alertas/${alerta.id}`, { method: 'DELETE' });
                        }
                        cargarAlertas();
                        cargarMetricas();
                    }
                }

        async function resolverReporte(id) {
            await apiFetch(`/reportes/${id}/resolver`, { method: 'POST' });
            cargarReportes();
        }

        // ========== ALERTAS ==========
        async function cargarAlertas(page = 1) {
            pagination.alertas = page;
            const data = await apiFetch('/alertas');
            currentData.alertas = data;
            renderAlertas();
        }

        function renderAlertas() {
                    let data = currentData.alertas || [];
                    const search = document.getElementById('search-alertas').value.toLowerCase();
                    const tipo = document.getElementById('filter-tipo-alerta').value;
                    const estado = document.getElementById('filter-estado-alerta').value;

                    if (search) data = data.filter(a => a.mensaje.toLowerCase().includes(search));
                    if (tipo) data = data.filter(a => a.tipo === tipo);
                    if (estado) data = data.filter(a => a.estado === estado);

                    const start = (pagination.alertas - 1) * ITEMS_PER_PAGE;
                    const paginated = data.slice(start, start + ITEMS_PER_PAGE);

                    const tbody = document.getElementById('alertas-table');
                    tbody.innerHTML = paginated.map(a => `                <tr>
                            <td>${a.id}</td>
                            <td><span class="badge badge-${a.tipo}">${a.tipo.replace(/_/g, ' ')}</span></td>
                            <td>${a.mensaje.substring(0, 60)}${a.mensaje.length > 60 ? '...' : ''}</td>
                            <td><span class="badge badge-${a.estado}">${a.estado}</span></td>
                            <td>${new Date(a.created_at).toLocaleString()}</td>
                            <td class="actions">
                                <button class="btn btn-info" onclick="verAlerta(${a.id})">Ver</button>
                                ${a.estado === 'activa' ? `<button class="btn btn-success" onclick="atenderAlerta(${a.id})">Atender</button>` : ''}                        <button class="btn btn-danger" onclick="eliminarAlerta(${a.id})">Eliminar</button>
                            </td>
                        </tr>
                    `).join('');
                    renderPagination('alertas', data.length, pagination.alertas, 'cargarAlertasPage');
                }

        function cargarAlertasPage(page) { pagination.alertas = page; renderAlertas(); }
        function searchAlertas() { cargarAlertas(1); }
        function filterAlertas() { cargarAlertas(1); }

        async function atenderAlerta(id) {
            await apiFetch(`/alertas/${id}/atender`, { method: 'POST' });
            cargarAlertas();
            cargarAlertasRecientes();
            cargarMetricas();
        }

        // ========== USUARIOS ==========
        async function cargarUsuarios(page = 1) {
            if (user.rol !== 'admin') {
                document.getElementById('usuarios-table').innerHTML = '<tr><td colspan="5" class="empty-state">No tienes permisos para ver usuarios</td></tr>';
                return;
            }
            pagination.usuarios = page;
            try {
                const data = await apiFetch('/usuarios');
                currentData.usuarios = data;
                renderUsuarios();
            } catch (e) {
                console.error('Error al cargar usuarios:', e);
            }
        }

        function renderUsuarios() {
            let data = currentData.usuarios || [];
            const search = document.getElementById('search-usuarios').value.toLowerCase();
            const rol = document.getElementById('filter-rol-usuario').value;

            if (search) data = data.filter(u => u.name.toLowerCase().includes(search) || u.email.toLowerCase().includes(search));
            if (rol) data = data.filter(u => u.rol === rol);

            const start = (pagination.usuarios - 1) * ITEMS_PER_PAGE;
            const paginated = data.slice(start, start + ITEMS_PER_PAGE);

            const tbody = document.getElementById('usuarios-table');
            tbody.innerHTML = paginated.map(u => `
                <tr>
                    <td>${u.id}</td>
                    <td>${u.name}</td>
                    <td>${u.email}</td>
                    <td><span class="badge badge-${u.rol}">${u.rol}</span></td>
                    <td class="actions">
                        <button class="btn btn-primary" onclick="editarUsuario(${u.id})">Editar</button>
                        <button class="btn btn-warning" onclick="cambiarRolUsuario(${u.id}, '${u.rol}')">Rol</button>
                        ${u.id !== user.id ? `<button class="btn btn-danger" onclick="eliminarUsuario(${u.id})">Eliminar</button>` : ''}
                    </td>
                </tr>
            `).join('');
            renderPagination('usuarios', data.length, pagination.usuarios, 'cargarUsuariosPage');
        }

        function cargarUsuariosPage(page) { pagination.usuarios = page; renderUsuarios(); }
        function searchUsuarios() { cargarUsuarios(1); }
        function filterUsuarios() { cargarUsuarios(1); }

        async function eliminarUsuario(id) {
            if (confirm('¿Eliminar este usuario?')) {
                await apiFetch(`/usuarios/${id}`, { method: 'DELETE' });
                cargarUsuarios();
            }
        }

        async function cambiarRolUsuario(id, rolActual) {
            // Protección: No permitir que el usuario cambie su propio rol
            if (id === user.id) {
                alert("No puedes cambiar tu propio rol para evitar perder el acceso de administrador.");
                return;
            }

            const roles = ['admin', 'operador', 'ciudadano'];
            const nuevoRol = roles[(roles.indexOf(rolActual) + 1) % roles.length];

            // Confirmación antes de cambiar
            if (confirm(`¿Estás seguro de cambiar el rol de este usuario de ${rolActual} a ${nuevoRol}?`)) {
                await apiFetch(`/usuarios/${id}/rol`, { method: 'POST', body: JSON.stringify({ rol: nuevoRol }) });
                cargarUsuarios();
            }
        }

        // ========== MODALES ==========
        let mapaRuta = null;
        let marcadoresContenedores = {};

        function openModal(type, data = null) {
            const modal = document.getElementById('modal');
            const modalContent = document.getElementById('modal-content');
            const title = document.getElementById('modal-title');
            const body = document.getElementById('modal-body');
            modal.classList.remove('hidden');
            modalContent.className = 'modal-content';

            if (type === 'contenedor') {
                const id = data;
                title.textContent = id ? 'Editar Contenedor' : 'Nuevo Contenedor';
                if (id) {
                    apiFetch(`/contenedores/${id}`).then(c => {
                        renderContenedorForm(body, id, c.ubicacion, c.latitud, c.longitud);
                    });
                } else {
                    renderContenedorForm(body, null, '', -34.6037, -58.3816);
                }
            } else if (type === 'camion') {
                const id = data;
                title.textContent = id ? 'Editar Camión' : 'Nuevo Camión';
                body.innerHTML = `
                    <input type="hidden" id="form-camion-id" value="${id || ''}">
                    <div class="form-group"><label>Placa:</label><input type="text" id="form-camion-placa"></div>
                    <div class="form-group"><label>Conductor:</label><input type="text" id="form-camion-conductor"></div>
                    <div class="form-group"><label>Capacidad (kg):</label><input type="number" id="form-camion-capacidad"></div>
                    <div class="form-group"><label>Latitud:</label><input type="number" step="any" id="form-camion-latitud" placeholder="-17.7833"></div>
                    <div class="form-group"><label>Longitud:</label><input type="number" step="any" id="form-camion-longitud" placeholder="-63.1821"></div>
                    <button class="btn btn-primary" onclick="guardarCamion()">Guardar</button>
                `;
                if (id) {
                    apiFetch(`/camiones/${id}`).then(c => {
                        document.getElementById('form-camion-placa').value = c.placa;
                        document.getElementById('form-camion-conductor').value = c.conductor;
                        document.getElementById('form-camion-capacidad').value = c.capacidad;
                        document.getElementById('form-camion-latitud').value = c.latitud || '';
                        document.getElementById('form-camion-longitud').value = c.longitud || '';
                    });
                }
            } else if (type === 'ruta') {
                const id = data;
                title.textContent = id ? 'Editar Ruta' : 'Nueva Ruta';
                modalContent.classList.add('modal-large');
                Promise.all([apiFetch('/camiones'), apiFetch('/contenedores')]).then(([camiones, contenedores]) => {
                    renderRutaForm(body, id, camiones, contenedores);
                    if (id) {
                        apiFetch(`/rutas/${id}`).then(r => {
                            document.getElementById('form-ruta-nombre').value = r.nombre;
                            document.getElementById('form-ruta-zona').value = r.zona;
                            document.getElementById('form-ruta-camion').value = r.camion_id;
                            document.getElementById('form-ruta-prioridad').value = r.prioridad;
                            document.getElementById('form-ruta-estado').value = r.estado;
                            if (r.contenedores_ids) {
                                r.contenedores_ids.forEach(cid => {
                                    const checkbox = document.getElementById(`contenedor-${cid}`);
                                    if (checkbox) checkbox.checked = true;
                                });
                                actualizarContenedoresSeleccionados();
                            }
                        });
                    } else {
                        setTimeout(() => initMapaRuta(contenedores), 100);
                    }
                });
            } else if (type === 'ver-ruta') {
                const ruta = data;
                title.textContent = 'Detalle de Ruta';
                modalContent.classList.add('modal-large');
                Promise.all([apiFetch('/contenedores')]).then(([contenedores]) => {
                    renderVerRuta(body, ruta, contenedores);
                });
            } else if (type === 'optimizar-ruta') {
                title.textContent = 'Optimizar Ruta';
                apiFetch('/camiones').then(camiones => {
                    body.innerHTML = `
                        <div class="form-group"><label>Camión:</label>
                            <select id="opt-camion">${camiones.filter(c => c.estado === 'disponible').map(c => `<option value="${c.id}">${c.placa} - ${c.conductor}</option>`).join('')}</select>
                        </div>
                        <div class="form-group"><label>Zona:</label><input type="text" id="opt-zona" placeholder="Ej: Centro, Norte, Sur"></div>
                        <button class="btn btn-primary" onclick="optimizarRuta()">Optimizar</button>
                    `;
                });
             } else if (type === 'reporte') {
                            const id = data;
                            title.textContent = id ? 'Ver Reporte' : 'Nuevo Reporte';
                            modalContent.classList.add('modal-large');
                            if (id) {
                                apiFetch(`/reportes/${id}`).then(r => {
                                    renderVerReporte(body, r);
                                });
                            } else {
                                body.innerHTML = `
                                    <input type="hidden" id="form-reporte-id" value="">
                                    <div class="two-columns">
                                        <div>
                                            <div class="form-group"><label>Tipo:</label>
                                                <select id="form-reporte-tipo">
                                                    <option value="basura_en_calle">Basura en Calle</option>
                                                    <option value="contenedor_danado">Contenedor Dañado</option>
                                                    <option value="punto_critico">Punto Crítico</option>
                                                </select>
                                            </div>
                                            <div class="form-group"><label>Descripción:</label><textarea id="form-reporte-descripcion" rows="4"></textarea></div>
                                            <div class="form-group"><label>Ubicación:</label><input type="text" id="form-reporte-ubicacion" placeholder="Ej: Av. Principal 123"></div>
                                        </div>
                                        <div>
                                            <div class="form-group"><label>Ciudadano Nombre:</label><input type="text" id="form-reporte-nombre" placeholder="Nombre del reportante"></div>
                                            <div class="form-group"><label>Ciudadano Contacto:</label><input type="text" id="form-reporte-contacto" placeholder="Teléfono o email"></div>
                                            <div class="form-group"><label>Foto (URL):</label><input type="text" id="form-reporte-foto" placeholder="https://ejemplo.com/foto.jpg"></div>
                                            <div class="form-group"><label>Latitud:</label><input type="number" step="any" id="form-reporte-latitud" placeholder="-17.7833"></div>
                                            <div class="form-group"><label>Longitud:</label><input type="number" step="any" id="form-reporte-longitud" placeholder="-63.1821"></div>
                                        </div>
                                    </div>
                                    <button class="btn btn-primary" onclick="guardarReporte()">Guardar Reporte</button>
                                `;
                            }
                        } else if (type === 'alerta') {
                            const alerta = data;
                            title.textContent = 'Detalle de Alerta';
                            modalContent.classList.add('modal-large');
                            renderVerAlerta(body, alerta);
            } else if (type === 'usuario') {
                const id = data;
                title.textContent = id ? 'Editar Usuario' : 'Nuevo Usuario';
                body.innerHTML = `
                    <input type="hidden" id="form-usuario-id" value="${id || ''}">
                    <div class="form-group"><label>Nombre:</label><input type="text" id="form-usuario-nombre"></div>
                    <div class="form-group"><label>Email:</label><input type="email" id="form-usuario-email"></div>
                    <div class="form-group"><label>Contraseña:</label><input type="password" id="form-usuario-password" placeholder="${id ? 'Dejar en blanco para no cambiar' : ''}"></div>
                    <div class="form-group"><label>Rol:</label>
                        <select id="form-usuario-rol"><option value="admin">Admin</option><option value="operador">Operador</option><option value="ciudadano">Ciudadano</option></select>
                    </div>
                    <button class="btn btn-primary" onclick="guardarUsuario()">Guardar</button>
                `;
                if (id) {
                    apiFetch(`/usuarios/${id}`).then(u => {
                        document.getElementById('form-usuario-nombre').value = u.name;
                        document.getElementById('form-usuario-email').value = u.email;
                        document.getElementById('form-usuario-rol').value = u.rol;
                    });
                }
            }
        }

        function renderRutaForm(body, id, camiones, contenedores) {
            const contenedoresConCoords = contenedores.filter(c => c.latitud && c.longitud);

            body.innerHTML = `
                <input type="hidden" id="form-ruta-id" value="${id || ''}">
                <div class="two-columns">
                    <div>
                        <div class="form-group"><label>Nombre:</label><input type="text" id="form-ruta-nombre" placeholder="Ej: Ruta Centro Mañana"></div>
                        <div class="form-group"><label>Zona:</label><input type="text" id="form-ruta-zona" placeholder="Ej: Centro, Norte, Sur"></div>
                        <div class="form-group"><label>Camión:</label>
                            <select id="form-ruta-camion">${camiones.map(c => `<option value="${c.id}">${c.placa} - ${c.conductor} (${c.estado})</option>`).join('')}</select>
                        </div>
                        <div class="form-group"><label>Prioridad:</label>
                            <select id="form-ruta-prioridad">
                                <option value="baja">Baja</option>
                                <option value="normal" selected>Normal</option>
                                <option value="alta">Alta</option>
                                <option value="urgente">Urgente</option>
                            </select>
                        </div>
                        ${id ? `
                        <div class="form-group"><label>Estado:</label>
                            <select id="form-ruta-estado">
                                <option value="pendiente">Pendiente</option>
                                <option value="en_progreso">En Progreso</option>
                                <option value="completada">Completada</option>
                            </select>
                        </div>
                        ` : ''}
                    </div>
                    <div>
                        <label>Seleccionar Contenedores (${contenedoresConCoords.length} disponibles):</label>
                        <div class="contenedores-list" id="contenedores-list">
                            ${contenedoresConCoords.map(c => `
                                <div class="contenedor-item" onclick="toggleContenedor(${c.id})">
                                    <input type="checkbox" class="contenedor-checkbox" id="contenedor-${c.id}" value="${c.id}" onchange="actualizarContenedoresSeleccionados()">
                                    <span><strong>#${c.id}</strong> - ${c.ubicacion} <span class="badge badge-${c.estado.replace(' ', '-')}">${c.estado}</span> ${c.nivel_llenado}%</span>
                                </div>
                            `).join('')}
                        </div>
                        <div class="form-group">
                            <small>Contenedores seleccionados: <span id="contenedores-count">0</span></small>
                        </div>
                    </div>
                </div>
                <div class="map-container">
                    <label>Vista previa de la ruta:</label>
                    <div id="mapa-ruta"></div>
                </div>
                <button class="btn btn-primary" onclick="guardarRuta()">Guardar</button>
            `;

            setTimeout(() => initMapaRuta(contenedoresConCoords), 100);
        }

        function renderVerRuta(body, ruta, contenedores) {
            const contenedoresRuta = ruta.contenedores_ids ? contenedores.filter(c => ruta.contenedores_ids.includes(c.id)) : [];
            const progreso = calcularProgresoRuta(ruta);

            body.innerHTML = `
                <div class="two-columns">
                    <div>
                        <div class="ruta-info">
                            <div class="ruta-info-item"><span>Nombre:</span><strong>${ruta.nombre}</strong></div>
                            <div class="ruta-info-item"><span>Zona:</span><span>${ruta.zona}</span></div>
                            <div class="ruta-info-item"><span>Camión:</span><span>${ruta.camion ? ruta.camion.placa : 'No asignado'}</span></div>
                            <div class="ruta-info-item"><span>Conductor:</span><span>${ruta.camion ? ruta.camion.conductor : '-'}</span></div>
                            <div class="ruta-info-item"><span>Estado:</span><span class="badge badge-${ruta.estado}">${ruta.estado}</span></div>
                            <div class="ruta-info-item"><span>Prioridad:</span><span class="badge badge-${ruta.prioridad}">${ruta.prioridad}</span></div>
                            <div class="ruta-info-item"><span>Distancia:</span><span>${ruta.distancia_total ? ruta.distancia_total + ' km' : 'Calculando...'}</span></div>
                            <div class="ruta-info-item"><span>Tiempo Estimado:</span><span>${ruta.tiempo_estimado ? ruta.tiempo_estimado + ' min' : 'Calculando...'}</span></div>
                        </div>
                        <div style="margin: 15px 0;">
                            <div style="background: #ecf0f1; border-radius: 10px; height: 25px; overflow: hidden;">
                                <div style="background: ${progreso === 100 ? '#27ae60' : '#3498db'}; width: ${progreso}%; height: 100%; text-align: center; color: white; font-size: 14px; line-height: 25px;">${progreso}% Completado</div>
                            </div>
                        </div>
                        ${ruta.estado === 'pendiente' ? `<button class="btn btn-success" onclick="iniciarRuta(${ruta.id}); closeModal();" style="width: 100%; margin: 10px 0;">🚀 Iniciar Ruta</button>` : ''}
                        ${ruta.estado === 'en_progreso' ? `<button class="btn btn-success" onclick="completarRuta(${ruta.id}); closeModal();" style="width: 100%; margin: 10px 0;">✅ Completar Ruta</button>` : ''}
                    </div>
                    <div>
                        <label>Contenedores en esta ruta (${contenedoresRuta.length}):</label>
                        <div class="contenedores-list">
                            ${contenedoresRuta.length > 0 ? contenedoresRuta.map(c => `
                                <div class="contenedor-item">
                                    <span><strong>#${c.id}</strong> - ${c.ubicacion} <span class="badge badge-${c.estado.replace(' ', '-')}">${c.estado}</span></span>
                                </div>
                            `).join('') : '<div class="empty-state">No hay contenedores asignados</div>'}
                        </div>
                    </div>
                </div>
                <div class="map-container">
                    <label>Mapa de la ruta:</label>
                    <div id="mapa-ruta"></div>
                </div>
            `;

            setTimeout(() => mostrarMapaRuta(contenedoresRuta, ruta.camion), 100);
        }

        function toggleContenedor(id) {
            const checkbox = document.getElementById(`contenedor-${id}`);
            if (checkbox) {
                checkbox.checked = !checkbox.checked;
                actualizarContenedoresSeleccionados();
            }
        }

        function actualizarContenedoresSeleccionados() {
            const checkboxes = document.querySelectorAll('.contenedor-checkbox:checked');
            document.getElementById('contenedores-count').textContent = checkboxes.length;

            document.querySelectorAll('.contenedor-item').forEach(item => {
                const checkbox = item.querySelector('.contenedor-checkbox');
                if (checkbox.checked) {
                    item.classList.add('selected');
                } else {
                    item.classList.remove('selected');
                }
            });

            const contenedoresIds = Array.from(checkboxes).map(cb => parseInt(cb.value));
            const contenedores = currentData.contenedores || [];
            actualizarMapaRuta(contenedores, contenedoresIds);
        }

        function initMapaRuta(contenedores) {
            if (!document.getElementById('mapa-ruta')) return;

            mapaRuta = L.map('mapa-ruta').setView([-17.7833, -63.1821], 12);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(mapaRuta);

            marcadoresContenedores = {};

            contenedores.forEach(c => {
                if (c.latitud && c.longitud) {
                    const color = c.estado === 'lleno' ? 'red' : c.estado === 'casi lleno' ? 'orange' : 'green';
                    const marker = L.circleMarker([c.latitud, c.longitud], {
                        radius: 8,
                        fillColor: color,
                        color: '#fff',
                        weight: 2,
                        opacity: 1,
                        fillOpacity: 0.8
                    }).addTo(mapaRuta).bindPopup(`<b>Contenedor #${c.id}</b><br>${c.ubicacion}<br>${c.estado} - ${c.nivel_llenado}%`);

                    marcadoresContenedores[c.id] = marker;
                }
            });
        }

        function actualizarMapaRuta(contenedores, seleccionadosIds) {
            if (!mapaRuta) return;

            mapaRuta.eachLayer(layer => {
                if (layer instanceof L.Polyline && !(layer instanceof L.CircleMarker)) {
                    mapaRuta.removeLayer(layer);
                }
            });

            if (seleccionadosIds.length < 2) return;

            const puntos = seleccionadosIds.map(id => {
                const c = contenedores.find(cont => cont.id === id);
                return c ? [c.latitud, c.longitud] : null;
            }).filter(p => p !== null);

            if (puntos.length > 1) {
                const polyline = L.polyline(puntos, { color: '#3498db', weight: 4, opacity: 0.8 }).addTo(mapaRuta);
                mapaRuta.fitBounds(polyline.getBounds(), { padding: [50, 50] });
            }
        }

        function mostrarMapaRuta(contenedores, camion) {
            if (!document.getElementById('mapa-ruta')) return;

            mapaRuta = L.map('mapa-ruta').setView([-17.7833, -63.1821], 12);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(mapaRuta);

            const puntos = [];

            if (camion && camion.latitud && camion.longitud) {
                L.marker([camion.latitud, camion.longitud], {
                    icon: L.divIcon({
                        className: 'custom-div-icon',
                        html: '<div style="background: blue; width: 25px; height: 25px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; color: white; font-size: 12px;">🚛</div>',
                        iconSize: [25, 25],
                        iconAnchor: [12, 12]
                    })
                }).addTo(mapaRuta).bindPopup('<b>Camión</b><br>' + camion.placa);
                puntos.push([camion.latitud, camion.longitud]);
            }

            contenedores.forEach((c, index) => {
                if (c.latitud && c.longitud) {
                    const color = c.estado === 'lleno' ? 'red' : c.estado === 'casi lleno' ? 'orange' : 'green';
                    const numero = index + 1;
                    const marker = L.marker([c.latitud, c.longitud], {
                        icon: L.divIcon({
                            className: 'custom-div-icon',
                            html: `<div style="background: ${color}; width: 24px; height: 24px; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 12px;">${numero}</div>`,
                            iconSize: [24, 24],
                            iconAnchor: [12, 12]
                        })
                    }).addTo(mapaRuta).bindPopup(`<b>#${numero} - Contenedor ${c.id}</b><br>${c.ubicacion}<br>${c.estado}`);
                    puntos.push([c.latitud, c.longitud]);
                }
            });

            if (puntos.length > 1) {
                const polyline = L.polyline(puntos, { color: '#3498db', weight: 4, opacity: 0.8, dashArray: '10, 10' }).addTo(mapaRuta);
                mapaRuta.fitBounds(polyline.getBounds(), { padding: [50, 50] });
            }
        }

        function renderVerReporte(body, r) {
                    const tipoLabels = { 'basura_en_calle': 'Basura en Calle', 'contenedor_danado': 'Contenedor Dañado', 'punto_critico': 'Punto Crítico' };
                    const estadoLabels = { 'pendiente': 'Pendiente', 'en_proceso': 'En Proceso', 'resuelto': 'Resuelto' };

                    body.innerHTML = `
                        <div class="two-columns">
                            <div>
                                <div class="reporte-info">
                                    <div class="reporte-info-item"><span>ID:</span><strong>#${r.id}</strong></div>
                                    <div class="reporte-info-item"><span>Tipo:</span><span class="badge badge-${r.tipo}">${tipoLabels[r.tipo] || r.tipo}</span></div>
                                    <div class="reporte-info-item"><span>Estado:</span><span class="badge badge-${r.estado}">${estadoLabels[r.estado] || r.estado}</span></div>
                                    <div class="reporte-info-item"><span>Fecha:</span><span>${new Date(r.created_at).toLocaleString()}</span></div>
                                    <div class="reporte-info-item"><span>Ubicación:</span><span>${r.ubicacion}</span></div>
                                </div>
                                <div class="form-group">
                                    <label>Descripción:</label>
                                    <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; min-height: 80px;">${r.descripcion}</div>
                                </div>
                                ${r.ciudadano_nombre ? `
                                <div class="reporte-info">
                                    <div class="reporte-info-item"><span>Reportante:</span><span>${r.ciudadano_nombre}</span></div>
                                    ${r.ciudadano_contacto ? `<div class="reporte-info-item"><span>Contacto:</span><span>${r.ciudadano_contacto}</span></div>` : ''}
                                </div>
                                ` : ''}
                                <div class="estado-cambio">
                                    ${r.estado === 'pendiente' ? `<button class="btn btn-warning" onclick="cambiarEstadoReporte(${r.id}, 'en_proceso'); closeModal();">Marcar En Proceso</button>` : ''}
                                    ${r.estado !== 'resuelto' ? `<button class="btn btn-success" onclick="resolverReporte(${r.id}); closeModal();">Marcar Resuelto</button>` : ''}
                                </div>
                            </div>
                            <div>
                                ${r.foto ? `<div class="form-group"><label>Foto:</label><img src="${r.foto}" class="reporte-foto" alt="Foto del reporte" onerror="this.style.display='none'"></div>` : ''}
                                <div class="form-group"><label>Ubicación en Mapa:</label><div id="mapa-reporte" style="height: 300px; border-radius: 8px;"></div></div>
                            </div>
                        </div>
                    `;

                    setTimeout(() => {
                        const map = L.map('mapa-reporte').setView([r.latitud || -17.7833, r.longitud || -63.1821], 15);
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '© OpenStreetMap contributors'
                        }).addTo(map);
                        if (r.latitud && r.longitud) {
                            L.marker([r.latitud, r.longitud]).addTo(map).bindPopup(`<b>${r.ubicacion}</b><br>${r.descripcion.substring(0, 50)}...`);
                        }
                    }, 100);
                }

                function renderVerAlerta(body, alerta) {
                    const tipoLabels = { 'contenedor_lleno': 'Contenedor Lleno', 'contenedor_danado': 'Contenedor Dañado', 'zona_critica': 'Zona Crítica' };

                    body.innerHTML = `
                        <div class="alerta-info">
                            <div class="reporte-info-item"><span>ID:</span><strong>#${alerta.id}</strong></div>
                            <div class="reporte-info-item"><span>Tipo:</span><span class="badge badge-${alerta.tipo}">${tipoLabels[alerta.tipo] || alerta.tipo}</span></div>
                            <div class="reporte-info-item"><span>Estado:</span><span class="badge badge-${alerta.estado}">${alerta.estado}</span></div>
                            <div class="reporte-info-item"><span>Fecha:</span><span>${new Date(alerta.created_at).toLocaleString()}</span></div>
                            ${alerta.entidad_id ? `<div class="reporte-info-item"><span>Entidad ID:</span><span>${alerta.entidad_id} (${alerta.entidad_tipo || 'N/A'})</span></div>` : ''}
                        </div>
                        <div class="form-group">
                            <label>Mensaje:</label>
                            <div style="background: #fff3cd; padding: 15px; border-radius: 5px; font-size: 16px;">${alerta.mensaje}</div>
                        </div>
                        ${alerta.canal ? `
                        <div class="form-group">
                            <label>Canales de notificación:</label>
                            <div>${alerta.canal.map(c => `<span class="badge badge-info">${c}</span>`).join(' ')}</div>
                        </div>
                        ` : ''}
                        <div class="estado-cambio">
                            ${alerta.estado === 'activa' ? `<button class="btn btn-success" onclick="atenderAlerta(${alerta.id}); closeModal();">Marcar como Atendida</button>` : ''}
                            <button class="btn btn-danger" onclick="eliminarAlerta(${alerta.id}); closeModal();">Eliminar Alerta</button>
                        </div>
                    `;
                }

        function renderContenedorForm(body, id, ubicacion, lat, lng) {
            const latNum = parseFloat(lat) || -34.6037;
            const lngNum = parseFloat(lng) || -58.3816;

            body.innerHTML = `
                <input type="hidden" id="form-contenedor-id" value="${id || ''}">
                <div class="form-group"><label>Ubicación (dirección):</label><input type="text" id="form-contenedor-ubicacion" value="${ubicacion}" placeholder="Ej: Av. Principal 123"></div>
                <div class="map-container">
                    <label>Haz clic en el mapa para seleccionar la ubicación:</label>
                    <div id="map"></div>
                    <div class="coord-display">
                        📍 Latitud: <span id="display-lat">${latNum.toFixed(6)}</span> | Longitud: <span id="display-lng">${lngNum.toFixed(6)}</span>
                    </div>
                </div>
                <input type="hidden" id="form-contenedor-latitud" value="${latNum.toFixed(6)}">
                <input type="hidden" id="form-contenedor-longitud" value="${lngNum.toFixed(6)}">
                <button class="btn btn-primary" onclick="guardarContenedor()">Guardar</button>
            `;

            setTimeout(() => {
                const map = L.map('map').setView([latNum, lngNum], 15);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors'
                }).addTo(map);

                let marker = L.marker([latNum, lngNum], { draggable: true }).addTo(map);

                marker.on('dragend', function(e) {
                    const pos = marker.getLatLng();
                    updateCoords(pos.lat, pos.lng);
                });

                map.on('click', function(e) {
                    marker.setLatLng(e.latlng);
                    updateCoords(e.latlng.lat, e.latlng.lng);
                });

                function updateCoords(lat, lng) {
                    document.getElementById('form-contenedor-latitud').value = lat.toFixed(6);
                    document.getElementById('form-contenedor-longitud').value = lng.toFixed(6);
                    document.getElementById('display-lat').textContent = lat.toFixed(6);
                    document.getElementById('display-lng').textContent = lng.toFixed(6);
                }
            }, 100);
        }

        function closeModal() {
            document.getElementById('modal').classList.add('hidden');
            if (mapaRuta) {
                mapaRuta.remove();
                mapaRuta = null;
            }
        }

        async function guardarContenedor() {
            const id = document.getElementById('form-contenedor-id').value;
            const data = {
                ubicacion: document.getElementById('form-contenedor-ubicacion').value,
                latitud: parseFloat(document.getElementById('form-contenedor-latitud').value),
                longitud: parseFloat(document.getElementById('form-contenedor-longitud').value)
            };
            await apiFetch(id ? `/contenedores/${id}` : '/contenedores', { method: id ? 'PUT' : 'POST', body: JSON.stringify(data) });
            closeModal();
            cargarContenedores();
        }

        async function guardarCamion() {
                    const id = document.getElementById('form-camion-id').value;
                    const data = {
                        placa: document.getElementById('form-camion-placa').value,
                        conductor: document.getElementById('form-camion-conductor').value,
                        capacidad: parseInt(document.getElementById('form-camion-capacidad').value),
                        latitud: parseFloat(document.getElementById('form-camion-latitud').value) || null,
                        longitud: parseFloat(document.getElementById('form-camion-longitud').value) || null
                    };
                    await apiFetch(id ? `/camiones/${id}` : '/camiones', { method: id ? 'PUT' : 'POST', body: JSON.stringify(data) });
                    closeModal();
                    cargarCamiones();
                }

                async function guardarRuta() {
                    const id = document.getElementById('form-ruta-id').value;
                    const checkboxes = document.querySelectorAll('.contenedor-checkbox:checked');
                    const contenedoresIds = Array.from(checkboxes).map(cb => parseInt(cb.value));

                    const data = {
                        nombre: document.getElementById('form-ruta-nombre').value,
                        zona: document.getElementById('form-ruta-zona').value,
                        camion_id: parseInt(document.getElementById('form-ruta-camion').value),
                        prioridad: document.getElementById('form-ruta-prioridad').value,
                        contenedores_ids: contenedoresIds
                    };

                    if (document.getElementById('form-ruta-estado')) {
                        data.estado = document.getElementById('form-ruta-estado').value;
                    }

                    await apiFetch(id ? `/rutas/${id}` : '/rutas', { method: id ? 'PUT' : 'POST', body: JSON.stringify(data) });
                    closeModal();
                    cargarRutas();
                    cargarMetricas();
                }

                async function optimizarRuta() {
                    const data = {
                        camion_id: parseInt(document.getElementById('opt-camion').value),
                        zona: document.getElementById('opt-zona').value
                    };
                    await apiFetch('/rutas/optimizar', { method: 'POST', body: JSON.stringify(data) });
                    closeModal();
                    cargarRutas();
                }

                async function guardarUsuario() {
                    const id = document.getElementById('form-usuario-id').value;
                    const data = {
                        name: document.getElementById('form-usuario-nombre').value,
                        email: document.getElementById('form-usuario-email').value,
                        rol: document.getElementById('form-usuario-rol').value
                    };
                    const password = document.getElementById('form-usuario-password').value;
                    if (password) data.password = password;

                    await apiFetch(id ? `/usuarios/${id}` : '/usuarios', { method: id ? 'PUT' : 'POST', body: JSON.stringify(data) });
                    closeModal();
                    cargarUsuarios();
                }

                // ========== EDITAR (abrir modal con ID) ==========
                function editarContenedor(id) { openModal('contenedor', id); }
                function editarCamion(id) { openModal('camion', id); }
                function editarRuta(id) { openModal('ruta', id); }
                function editarUsuario(id) { openModal('usuario', id); }

                let mapaCamiones;
                let marcadoresCamiones = {};
                let rutasCamiones = {};
                let autoRefreshInterval = null;
                let autoRefreshActivo = true;

                async function initMapaCamiones() {
                    if (!document.getElementById('mapa-camiones')) return;

                    mapaCamiones = L.map('mapa-camiones').setView([-17.7833, -63.1821], 12);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '© OpenStreetMap contributors'
                    }).addTo(mapaCamiones);

                    await actualizarMapaCamiones();
                    iniciarAutoRefresh();
                }

                function iniciarAutoRefresh() {
                    if (autoRefreshInterval) clearInterval(autoRefreshInterval);
                    autoRefreshInterval = setInterval(() => {
                        if (autoRefreshActivo && document.getElementById('section-inicio') &&
                            !document.getElementById('section-inicio').classList.contains('hidden')) {
                            actualizarMapaCamiones();
                        }
                    }, 30000); // 30 segundos
                }

                function toggleAutoRefresh() {
                    autoRefreshActivo = !autoRefreshActivo;
                    const btn = document.getElementById('btn-auto-refresh');
                    btn.textContent = autoRefreshActivo ? '⏸️ Pausar' : '▶️ Reanudar';
                    btn.className = autoRefreshActivo ? 'btn btn-secondary' : 'btn btn-warning';
                }

                function centrarMapaEnCamiones() {
                    if (!mapaCamiones || Object.keys(marcadoresCamiones).length === 0) return;

                    const bounds = L.featureGroup(Object.values(marcadoresCamiones)).getBounds();
                    if (bounds.isValid()) {
                        mapaCamiones.fitBounds(bounds, { padding: [50, 50] });
                    }
                }

                async function actualizarMapaCamiones() {
                    try {
                        if (!mapaCamiones) {
                            await initMapaCamiones();
                            return;
                        }

                        const filtro = document.getElementById('filter-mapa-camiones')?.value || 'todos';
                        let camiones = await apiFetch('/camiones');

                        // Aplicar filtro
                        if (filtro !== 'todos') {
                            camiones = camiones.filter(c => c.estado === filtro);
                        }

                        // Limpiar marcadores y rutas anteriores
                        Object.values(marcadoresCamiones).forEach(m => mapaCamiones.removeLayer(m));
                        Object.values(rutasCamiones).forEach(r => mapaCamiones.removeLayer(r));
                        marcadoresCamiones = {};
                        rutasCamiones = {};

                        camiones.forEach(camion => {
                            if (camion.latitud && camion.longitud) {
                                const color = camion.estado === 'en_ruta' ? 'red' :
                                             camion.estado === 'disponible' ? 'green' : 'orange';

                                const icon = L.divIcon({
                                    className: 'custom-div-icon',
                                    html: `<div style="background-color: ${color}; width: 20px; height: 20px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3);"></div>`,
                                    iconSize: [20, 20],
                                    iconAnchor: [10, 10]
                                });

                                const marker = L.marker([camion.latitud, camion.longitud], { icon })
                                    .addTo(mapaCamiones)
                                    .bindPopup(`                                <b>🚛 ${camion.placa}</b><br>
                                        Conductor: ${camion.conductor}<br>
                                        Estado: ${camion.estado}<br>
                                        Capacidad: ${camion.capacidad} kg<br>
                                        <small>Actualizado: ${new Date().toLocaleTimeString()}</small>
                                    `);

                                marcadoresCamiones[camion.id] = marker;

                                // Si el camión tiene historial de posiciones, dibujar ruta
                                if (camion.historial_posiciones && camion.historial_posiciones.length > 1) {
                                    const puntos = camion.historial_posiciones.map(p => [p.latitud, p.longitud]);
                                    const polyline = L.polyline(puntos, { color: color, opacity: 0.6, weight: 3 }).addTo(mapaCamiones);
                                    rutasCamiones[camion.id] = polyline;
                                }
                            }
                        });

                        // Actualizar timestamp
                        const lastUpdate = document.getElementById('last-update');
                        if (lastUpdate) {
                            lastUpdate.textContent = 'Última actualización: ' + new Date().toLocaleTimeString();
                        }

                    } catch (error) {
                        console.error('Error cargando camiones:', error);
                        const lastUpdate = document.getElementById('last-update');
                        if (lastUpdate) {
                            lastUpdate.textContent = 'Error al actualizar - ' + new Date().toLocaleTimeString();
                        }
                    }
                }

                // Inicializar mapa cuando se muestra la sección de inicio
                const originalShowSection = showSection;
                showSection = function(section) {
                    originalShowSection(section);
                    if (section === 'inicio') {
                        setTimeout(initMapaCamiones, 100);
                    }
                };
