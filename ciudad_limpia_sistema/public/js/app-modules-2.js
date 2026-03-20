// ================= RUTAS =================
async function cargarRutas(page = 1) {
    pagination.rutas = page;
    const data = await apiFetch('/rutas');
    currentData.rutas = data;
    renderRutas();
}

function renderRutas() {
    let data = currentData.rutas || [];
    const search = document.getElementById('search-rutas')?.value.toLowerCase();
    const estado = document.getElementById('filter-estado-ruta')?.value;
    const prioridad = document.getElementById('filter-prioridad-ruta')?.value;

    if (search) data = data.filter(r => r.nombre.toLowerCase().includes(search) || r.zona.toLowerCase().includes(search));
    if (estado) data = data.filter(r => r.estado === estado);
    if (prioridad) data = data.filter(r => r.prioridad === prioridad);

    const start = (pagination.rutas - 1) * ITEMS_PER_PAGE;
    const paginated = data.slice(start, start + ITEMS_PER_PAGE);

    const tbody = document.getElementById('rutas-table');
    if (tbody) {
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
}

function calcularProgresoRuta(ruta) {
    if (!ruta.contenedores_ids || ruta.contenedores_ids.length === 0) return 0;
    if (ruta.estado === 'completada') return 100;
    if (ruta.estado === 'pendiente') return 0;
    return 50; 
}

function cargarRutasPage(page) { cargarRutas(page); }
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
    await apiFetch(`/rutas/${id}`, { method: 'PUT', body: JSON.stringify({ estado: 'en_progreso' }) });
    cargarRutas(); cargarMetricas();
}

async function completarRuta(id) {
    await apiFetch(`/rutas/${id}`, { method: 'PUT', body: JSON.stringify({ estado: 'completada' }) });
    cargarRutas(); cargarMetricas();
}

function editarRuta(id) { openModal('ruta', id); }

function renderModalRuta(title, body, id) {
    title.textContent = id ? 'Editar Ruta' : 'Nueva Ruta';
    Promise.all([apiFetch('/camiones'), apiFetch('/contenedores')]).then(([camiones, contenedores]) => {
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
                    ${id ? `<div class="form-group"><label>Estado:</label><select id="form-ruta-estado"><option value="pendiente">Pendiente</option><option value="en_progreso">En Progreso</option><option value="completada">Completada</option></select></div>` : ''}
                </div>
                <div>
                    <label>Seleccionar Contenedores (${contenedoresConCoords.length}):</label>
                    <div class="contenedores-list" id="contenedores-list">
                        ${contenedoresConCoords.map(c => `
                            <div class="contenedor-item" onclick="toggleContenedor(${c.id})">
                                <input type="checkbox" class="contenedor-checkbox" id="contenedor-${c.id}" value="${c.id}" onchange="actualizarContenedoresSeleccionados()">
                                <span><strong>#${c.id}</strong> - ${c.ubicacion} <span class="badge badge-${c.estado.replace(' ', '-')}">${c.estado}</span></span>
                            </div>
                        `).join('')}
                    </div>
                    <div class="form-group"><small>Seleccionados: <span id="contenedores-count">0</span></small></div>
                </div>
            </div>
            <div class="map-container"><label>Vista previa:</label><div id="mapa-ruta"></div></div>
            <button class="btn btn-primary" onclick="guardarRuta()">Guardar</button>
        `;

        // Lógica de mapa y checkboxes (simplificada para este archivo)
        setTimeout(() => initMapaRuta(contenedoresConCoords), 100);
        
        if (id) {
            apiFetch(`/rutas/${id}`).then(r => {
                document.getElementById('form-ruta-nombre').value = r.nombre;
                document.getElementById('form-ruta-zona').value = r.zona;
                document.getElementById('form-ruta-camion').value = r.camion_id;
                document.getElementById('form-ruta-prioridad').value = r.prioridad;
                if(r.estado) document.getElementById('form-ruta-estado').value = r.estado;
                if (r.contenedores_ids) {
                    r.contenedores_ids.forEach(cid => {
                        const cb = document.getElementById(`contenedor-${cid}`);
                        if (cb) cb.checked = true;
                    });
                    actualizarContenedoresSeleccionados();
                }
            });
        }
    });
}

function renderModalVerRuta(title, body, ruta) {
    title.textContent = 'Detalle de Ruta';
    apiFetch('/contenedores').then(contenedores => {
        const contenedoresRuta = ruta.contenedores_ids ? contenedores.filter(c => ruta.contenedores_ids.includes(c.id)) : [];
        const progreso = calcularProgresoRuta(ruta);
        
        body.innerHTML = `
            <div class="two-columns">
                <div>
                    <div class="ruta-info">
                        <div class="ruta-info-item"><span>Nombre:</span><strong>${ruta.nombre}</strong></div>
                        <div class="ruta-info-item"><span>Zona:</span><span>${ruta.zona}</span></div>
                        <div class="ruta-info-item"><span>Camión:</span><span>${ruta.camion ? ruta.camion.placa : 'No asignado'}</span></div>
                        <div class="ruta-info-item"><span>Estado:</span><span class="badge badge-${ruta.estado}">${ruta.estado}</span></div>
                    </div>
                    <div style="margin: 15px 0;">
                        <div style="background: #ecf0f1; border-radius: 10px; height: 25px; overflow: hidden;">
                            <div style="background: ${progreso === 100 ? '#27ae60' : '#3498db'}; width: ${progreso}%; height: 100%; text-align: center; color: white; font-size: 14px; line-height: 25px;">${progreso}%</div>
                        </div>
                    </div>
                    ${ruta.estado === 'pendiente' ? `<button class="btn btn-success" onclick="iniciarRuta(${ruta.id}); closeModal();" style="width: 100%; margin: 10px 0;">🚀 Iniciar Ruta</button>` : ''}
                    ${ruta.estado === 'en_progreso' ? `<button class="btn btn-success" onclick="completarRuta(${ruta.id}); closeModal();" style="width: 100%; margin: 10px 0;">✅ Completar Ruta</button>` : ''}
                </div>
                <div>
                    <label>Contenedores:</label>
                    <div class="contenedores-list">
                        ${contenedoresRuta.map(c => `<div class="contenedor-item"><span><strong>#${c.id}</strong> - ${c.ubicacion}</span></div>`).join('')}
                    </div>
                </div>
            </div>
            <div class="map-container"><label>Mapa:</label><div id="mapa-ruta"></div></div>
        `;
        setTimeout(() => mostrarMapaRuta(contenedoresRuta, ruta.camion), 100);
    });
}

function renderModalOptimizar(title, body) {
    title.textContent = 'Optimizar Ruta (IA)';
    apiFetch('/camiones').then(camiones => {
        // Filtrar camiones disponibles o en ruta
        const camionesDisponibles = camiones.filter(c => c.estado === 'disponible' || c.estado === 'en_ruta');
        
        body.innerHTML = `
            <div style="background: #e1f5fe; padding: 15px; border-radius: 8px; margin-bottom: 20px; color: #0277bd;">
                <i class="fas fa-robot"></i> <strong>Asistente Inteligente:</strong><br>
                Selecciona un camión y generaré automáticamente la ruta más eficiente basándome en su ubicación GPS y los contenedores más llenos cercanos.
            </div>
            
            <div class="form-group"><label>Seleccionar Camión:</label>
                <select id="opt-camion" class="w-full p-2 border rounded">
                    ${camionesDisponibles.map(c => `<option value="${c.id}">${c.placa} - ${c.conductor} (${c.estado})</option>`).join('')}
                </select>
            </div>
            
            <div class="form-group">
                <label>Zona o Distrito (Opcional):</label>
                <input type="text" id="opt-zona" placeholder="Ej: Zona Norte (Dejar vacío para usar GPS del camión)" class="w-full p-2 border rounded">
                <small style="color: #666;">Si lo dejas vacío, buscaré contenedores en un radio de 5km del camión.</small>
            </div>
            
            <button class="btn btn-primary" id="btn-optimizar" onclick="optimizarRuta()" style="width: 100%; padding: 15px; font-weight: bold; font-size: 16px;">
                ⚡ Generar Ruta Óptima
            </button>
        `;
    });
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
        contenedores_ids: contenedoresIds,
        estado: document.getElementById('form-ruta-estado') ? document.getElementById('form-ruta-estado').value : undefined
    };
    await apiFetch(id ? `/rutas/${id}` : '/rutas', { method: id ? 'PUT' : 'POST', body: JSON.stringify(data) });
    closeModal(); cargarRutas();
}

async function optimizarRuta() {
    const btn = document.getElementById('btn-optimizar');
    const originalText = btn.innerHTML;
    
    // Feedback visual
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-cog fa-spin"></i> Calculando ruta óptima...';
    
    try {
        const data = { 
            camion_id: parseInt(document.getElementById('opt-camion').value), 
            zona: document.getElementById('opt-zona').value 
        };
        
        await apiFetch('/rutas/optimizar', { method: 'POST', body: JSON.stringify(data) });
        
        // Éxito
        closeModal(); 
        cargarRutas();
        alert("¡Ruta generada con éxito! Se ha añadido a la tabla.");
        
    } catch (error) {
        console.error(error);
        alert("No se pudo generar una ruta. Verifica que haya contenedores llenos cerca.");
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

// Funciones auxiliares para el mapa de rutas
function toggleContenedor(id) {
    const cb = document.getElementById(`contenedor-${id}`);
    if (cb) { cb.checked = !cb.checked; actualizarContenedoresSeleccionados(); }
}

function actualizarContenedoresSeleccionados() {
    const checkboxes = document.querySelectorAll('.contenedor-checkbox:checked');
    document.getElementById('contenedores-count').textContent = checkboxes.length;
}

function initMapaRuta(contenedores) {
    if (!document.getElementById('mapa-ruta')) return;
    // Cochabamba
    mapaRuta = L.map('mapa-ruta').setView([-17.3895, -66.1568], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(mapaRuta);
    
    contenedores.forEach(c => {
        L.circleMarker([c.latitud, c.longitud], { radius: 5, color: 'blue' }).addTo(mapaRuta).bindPopup(c.ubicacion);
    });
}

function mostrarMapaRuta(contenedores, camion) {
    if (!document.getElementById('mapa-ruta')) return;
    // Cochabamba
    mapaRuta = L.map('mapa-ruta').setView([-17.3895, -66.1568], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(mapaRuta);
    
    const latlngs = [];
    contenedores.forEach(c => {
        if(c.latitud && c.longitud) {
            latlngs.push([c.latitud, c.longitud]);
            L.marker([c.latitud, c.longitud]).addTo(mapaRuta).bindPopup(`Contenedor #${c.id}`);
        }
    });
    
    if (latlngs.length > 0) {
        L.polyline(latlngs, {color: 'red'}).addTo(mapaRuta);
        mapaRuta.fitBounds(latlngs);
    }
}

// ================= REPORTES =================
async function cargarReportes(page = 1) {
    pagination.reportes = page;
    const data = await apiFetch('/reportes');
    currentData.reportes = data;
    renderReportes();
}

function renderReportes() {
    let data = currentData.reportes || [];
    const search = document.getElementById('search-reportes')?.value.toLowerCase();
    const tipo = document.getElementById('filter-tipo-reporte')?.value;
    const estado = document.getElementById('filter-estado-reporte')?.value;

    if (search) data = data.filter(r => r.descripcion.toLowerCase().includes(search));
    if (tipo) data = data.filter(r => r.tipo === tipo);
    if (estado) data = data.filter(r => r.estado === estado);

    const start = (pagination.reportes - 1) * ITEMS_PER_PAGE;
    const paginated = data.slice(start, start + ITEMS_PER_PAGE);

    const tbody = document.getElementById('reportes-table');
    if(tbody) {
        tbody.innerHTML = paginated.map(r => `
            <tr>
                <td>${r.id}</td>
                <td><span class="badge badge-${r.tipo}">${r.tipo.replace(/_/g, ' ')}</span></td>
                <td>${r.descripcion.substring(0, 50)}...</td>
                <td>${r.ubicacion}</td>
                <td><span class="badge badge-${r.estado}">${r.estado}</span></td>
                <td>${new Date(r.created_at).toLocaleDateString()}</td>
                <td class="actions">
                    <button class="btn btn-info" onclick="verReporte(${r.id})">Ver</button>
                    <button class="btn btn-primary" onclick="editarReporte(${r.id})">Editar</button>
                    ${r.estado === 'pendiente' ? `<button class="btn btn-warning" onclick="cambiarEstadoReporte(${r.id}, 'en_proceso')">En Proceso</button>` : ''}
                    ${r.estado !== 'resuelto' ? `<button class="btn btn-success" onclick="resolverReporte(${r.id})">Resolver</button>` : ''}
                    <button class="btn btn-danger" onclick="eliminarReporte(${r.id})">Eliminar</button>
                </td>
            </tr>
        `).join('');
        renderPagination('reportes', data.length, pagination.reportes, 'cargarReportesPage');
    }
}

function cargarReportesPage(page) { cargarReportes(page); }
function searchReportes() { cargarReportes(1); }
function filterReportes() { cargarReportes(1); }

async function cambiarEstadoReporte(id, estado) {
    await apiFetch(`/reportes/${id}`, { method: 'PUT', body: JSON.stringify({ estado }) });
    cargarReportes();
}

async function resolverReporte(id) {
    await apiFetch(`/reportes/${id}/resolver`, { method: 'POST' });
    cargarReportes();
}

async function eliminarReporte(id) {
    if (confirm('¿Eliminar reporte?')) {
        await apiFetch(`/reportes/${id}`, { method: 'DELETE' });
        cargarReportes();
    }
}

function verReporte(id) { openModal('reporte', id); }
function editarReporte(id) { openModal('reporte-edit', id); }

function renderModalReporte(title, body, id) {
    if (id) {
        title.textContent = 'Detalle del Reporte';
        apiFetch(`/reportes/${id}`).then(r => {
            renderVerReporte(body, r);
        });
    } else {
        title.textContent = 'Nuevo Reporte Interno';
        body.innerHTML = `
            <div class="two-columns">
                <div>
                    <div class="form-group"><label>Tipo:</label><select id="form-reporte-tipo"><option value="basura_en_calle">Basura en Calle</option><option value="contenedor_danado">Contenedor Dañado</option></select></div>
                    <div class="form-group"><label>Descripción:</label><textarea id="form-reporte-descripcion" rows="4"></textarea></div>
                    <div class="form-group"><label>Ubicación:</label><input type="text" id="form-reporte-ubicacion"></div>
                </div>
                <div>
                    <div class="form-group"><label>Latitud:</label><input type="number" step="any" id="form-reporte-latitud"></div>
                    <div class="form-group"><label>Longitud:</label><input type="number" step="any" id="form-reporte-longitud"></div>
                </div>
            </div>
            <button class="btn btn-primary" onclick="guardarReporte()">Guardar Reporte</button>
        `;
    }
}

function renderVerReporte(body, r) {
    const mapaId = `mapa-reporte-${r.id}`;
    
    // Prefer computed URL (backend) and fallback to raw field
    const rawFoto = r.foto;
    const imagenUrl = r.foto_url || (rawFoto ? (rawFoto.startsWith('http') || rawFoto.startsWith('/') ? rawFoto : `/${rawFoto}`) : null);
    
    const fotoHtml = imagenUrl 
        ? `<div style="text-align:center; margin: 15px 0;"><img src="${imagenUrl}" class="reporte-foto" style="max-height:300px; border-radius:8px; box-shadow:0 4px 6px rgba(0,0,0,0.1);" alt="Evidencia"></div>`
        : '<div style="background:#f0f0f0; padding:20px; text-align:center; color:#888; border-radius:8px; margin:10px 0;">Sin fotografía adjunta</div>';

    body.innerHTML = `
        <div class="two-columns">
            <div>
                <div class="reporte-info">
                    <div class="reporte-info-item"><span>ID:</span><strong>#${r.id}</strong></div>
                    <div class="reporte-info-item"><span>Tipo:</span><span class="badge badge-${r.tipo}">${r.tipo.replace(/_/g, ' ')}</span></div>
                    <div class="reporte-info-item"><span>Estado:</span><span class="badge badge-${r.estado}">${r.estado}</span></div>
                    <div class="reporte-info-item"><span>Fecha:</span><span>${new Date(r.created_at).toLocaleString()}</span></div>
                    ${r.ciudadano_nombre ? `<div class="reporte-info-item"><span>Ciudadano:</span><span>${r.ciudadano_nombre}</span></div>` : ''}
                    ${r.ciudadano_contacto ? `<div class="reporte-info-item"><span>Contacto:</span><span>${r.ciudadano_contacto}</span></div>` : ''}
                </div>
                <div class="form-group" style="margin-top:15px;">
                    <label>Descripción del Problema:</label>
                    <div style="background:#f8f9fa; padding:15px; border-radius:5px; border:1px solid #eee;">${r.descripcion}</div>
                </div>
                <div class="form-group">
                    <label>Ubicación (Referencia):</label>
                    <div style="font-weight:bold;">📍 ${r.ubicacion}</div>
                </div>
            </div>
            <div>
                <label>Evidencia Fotográfica:</label>
                ${fotoHtml}
            </div>
        </div>
        
        <div class="map-container" style="margin-top:20px;">
            <label>Ubicación GPS:</label>
            ${r.latitud && r.longitud ? `<div id="${mapaId}" style="height: 300px; border-radius: 10px; border:1px solid #ccc;"></div>` : '<div style="padding:20px; background:#f8d7da; color:#721c24; border-radius:5px;">⚠️ No hay coordenadas GPS registradas</div>'}
        </div>

        <div class="estado-cambio" style="margin-top:20px; border-top:1px solid #eee; padding-top:20px; display:flex; justify-content:flex-end; gap:10px;">
             <button class="btn btn-primary" onclick="editarReporte(${r.id})">Editar</button>
             ${r.estado === 'pendiente' ? `<button class="btn btn-warning" onclick="cambiarEstadoReporte(${r.id}, 'en_proceso'); closeModal();">🚀 Marcar En Proceso</button>` : ''}
             ${r.estado !== 'resuelto' ? `<button class="btn btn-success" onclick="resolverReporte(${r.id}); closeModal();">✅ Marcar Resuelto</button>` : ''}
             <button class="btn btn-secondary" onclick="closeModal()">Cerrar</button>
        </div>
    `;

    // Inicializar mapa si hay coordenadas
    if (r.latitud && r.longitud) {
        setTimeout(() => {
            if(document.getElementById(mapaId)) {
                const map = L.map(mapaId).setView([r.latitud, r.longitud], 16);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
                L.marker([r.latitud, r.longitud]).addTo(map)
                    .bindPopup('<b>Lugar del Reporte</b>').openPopup();
            }
        }, 300); // Pequeño delay para asegurar que el modal renderizó
    }
}

function renderModalReporteEdit(title, body, id) {
    title.textContent = 'Editar Reporte';
    apiFetch(`/reportes/${id}`).then(r => {
        body.innerHTML = `
            <input type="hidden" id="form-reporte-id" value="${r.id}">
            <div class="two-columns">
                <div>
                    <div class="form-group">
                        <label>Tipo:</label>
                        <select id="form-reporte-tipo">
                            <option value="basura_en_calle">Basura en Calle</option>
                            <option value="contenedor_danado">Contenedor Dañado</option>
                            <option value="punto_critico">Punto Crítico</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Descripción:</label><textarea id="form-reporte-descripcion" rows="4"></textarea></div>
                    <div class="form-group"><label>Ubicación:</label><input type="text" id="form-reporte-ubicacion"></div>
                    <div class="form-group">
                        <label>Estado:</label>
                        <select id="form-reporte-estado">
                            <option value="pendiente">Pendiente</option>
                            <option value="en_proceso">En Proceso</option>
                            <option value="resuelto">Resuelto</option>
                        </select>
                    </div>
                </div>
                <div>
                    <div class="form-group"><label>Latitud:</label><input type="number" step="any" id="form-reporte-latitud"></div>
                    <div class="form-group"><label>Longitud:</label><input type="number" step="any" id="form-reporte-longitud"></div>
                    <div class="form-group"><label>Ciudadano:</label><input type="text" id="form-reporte-ciudadano"></div>
                    <div class="form-group"><label>Contacto:</label><input type="text" id="form-reporte-contacto"></div>
                    <div class="form-group"><label>Foto (URL o ruta):</label><input type="text" id="form-reporte-foto"></div>
                </div>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:10px;">
                <button class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
                <button class="btn btn-primary" onclick="guardarReporteEdicion()">Guardar Cambios</button>
            </div>
        `;

        document.getElementById('form-reporte-tipo').value = r.tipo || 'basura_en_calle';
        document.getElementById('form-reporte-descripcion').value = r.descripcion || '';
        document.getElementById('form-reporte-ubicacion').value = r.ubicacion || '';
        document.getElementById('form-reporte-estado').value = r.estado || 'pendiente';
        document.getElementById('form-reporte-latitud').value = r.latitud ?? '';
        document.getElementById('form-reporte-longitud').value = r.longitud ?? '';
        document.getElementById('form-reporte-ciudadano').value = r.ciudadano_nombre || '';
        document.getElementById('form-reporte-contacto').value = r.ciudadano_contacto || '';
        document.getElementById('form-reporte-foto').value = r.foto || '';
    });
}

async function guardarReporteEdicion() {
    const id = document.getElementById('form-reporte-id').value;
    const lat = document.getElementById('form-reporte-latitud').value;
    const lng = document.getElementById('form-reporte-longitud').value;
    const ciudadano = document.getElementById('form-reporte-ciudadano').value.trim();
    const contacto = document.getElementById('form-reporte-contacto').value.trim();
    const foto = document.getElementById('form-reporte-foto').value.trim();

    const data = {
        tipo: document.getElementById('form-reporte-tipo').value,
        descripcion: document.getElementById('form-reporte-descripcion').value,
        ubicacion: document.getElementById('form-reporte-ubicacion').value,
        estado: document.getElementById('form-reporte-estado').value
    };

    if (lat !== '') data.latitud = parseFloat(lat);
    if (lng !== '') data.longitud = parseFloat(lng);
    if (ciudadano !== '') data.ciudadano_nombre = ciudadano;
    if (contacto !== '') data.ciudadano_contacto = contacto;
    if (foto !== '') data.foto = foto;

    await apiFetch(`/reportes/${id}`, { method: 'PUT', body: JSON.stringify(data) });
    closeModal();
    cargarReportes();
}

async function guardarReporte() {
    const data = {
        tipo: document.getElementById('form-reporte-tipo').value,
        descripcion: document.getElementById('form-reporte-descripcion').value,
        ubicacion: document.getElementById('form-reporte-ubicacion').value,
        latitud: parseFloat(document.getElementById('form-reporte-latitud').value) || null,
        longitud: parseFloat(document.getElementById('form-reporte-longitud').value) || null
    };
    await apiFetch('/reportes', { method: 'POST', body: JSON.stringify(data) });
    closeModal(); cargarReportes();
}
