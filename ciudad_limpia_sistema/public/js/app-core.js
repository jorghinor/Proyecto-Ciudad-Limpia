// Configuración Global
const API_URL = 'http://localhost:8000/api';
let token = localStorage.getItem('token');
let user = JSON.parse(localStorage.getItem('user') || '{}');
let currentData = {};
let pagination = { contenedores: 1, camiones: 1, rutas: 1, reportes: 1, alertas: 1, usuarios: 1 };
const ITEMS_PER_PAGE = 10;

// Inicialización
if (token) showDashboard();

// ================= AUTENTICACIÓN =================
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

// ================= INTERFAZ GENERAL =================
function showDashboard() {
    const loginForm = document.getElementById('loginForm');
    const dashboard = document.getElementById('dashboard');
    
    if (loginForm) loginForm.classList.add('hidden');
    if (dashboard) dashboard.classList.remove('hidden');
    
    const userNameEl = document.getElementById('userName');
    const userRoleEl = document.getElementById('userRole');
    
    if (userNameEl) userNameEl.textContent = user.name;
    if (userRoleEl) {
        userRoleEl.textContent = user.rol;
        userRoleEl.className = 'badge badge-' + user.rol;
    }

    // Inicializar módulos cuando los scripts ya estén cargados
    initDashboardModules();
}

function initDashboardModules(attempt = 0) {
    const maxAttempts = 30;
    const retryDelayMs = 50;

    const hasMetricas = typeof cargarMetricas === 'function';
    const hasAlertas = typeof cargarAlertasRecientes === 'function';
    const hasMapa = typeof initMapaCamiones === 'function';

    if (hasMetricas) cargarMetricas();
    if (hasAlertas) cargarAlertasRecientes();
    if (hasMapa) setTimeout(initMapaCamiones, 100);

    if ((!hasMetricas || !hasAlertas || !hasMapa) && attempt < maxAttempts) {
        setTimeout(() => initDashboardModules(attempt + 1), retryDelayMs);
    }
}

function showSection(section) {
    document.querySelectorAll('.section-content').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.sidebar nav a').forEach(el => el.classList.remove('active'));
    
    const sectionEl = document.getElementById('section-' + section);
    const navEl = document.getElementById('nav-' + section);
    
    if (sectionEl) sectionEl.classList.remove('hidden');
    if (navEl) {
        navEl.classList.add('active');
        document.getElementById('page-title').textContent = navEl.textContent.trim();
    }

    // Router de carga de datos según la sección
    if (section === 'contenedores' && typeof cargarContenedores === 'function') cargarContenedores();
    if (section === 'camiones' && typeof cargarCamiones === 'function') cargarCamiones();
    if (section === 'rutas' && typeof cargarRutas === 'function') cargarRutas();
    if (section === 'reportes' && typeof cargarReportes === 'function') cargarReportes();
    if (section === 'alertas' && typeof cargarAlertas === 'function') cargarAlertas();
    if (section === 'usuarios' && typeof cargarUsuarios === 'function' && user.rol === 'admin') cargarUsuarios();
    
    // Manejo especial para el mapa de inicio
    if (section === 'inicio' && typeof initMapaCamiones === 'function') {
        setTimeout(initMapaCamiones, 100);
    }
}

async function apiFetch(endpoint, options = {}) {
    options.headers = { 
        ...options.headers, 
        'Authorization': `Bearer ${token}`, 
        'Content-Type': 'application/json',
        'Accept': 'application/json' 
    };
    
    const response = await fetch(`${API_URL}${endpoint}`, options);
    
    //if (response.status === 401) { logout(); return; }
    if (response.status === 401) {
        alert('Sesión expirada. Por favor, inicia sesión nuevamente.');
        logout();
        return;
    }

    if (response.status === 403) {
        const err = await response.json();
        alert('Acceso denegado: ' + (err.message || 'No tienes permisos'));
        throw new Error("Forbidden");
    }
    
    if (response.status === 422) {
        const err = await response.json();
        console.error("Error de validación", err);
        alert("Error de validación: " + (err.message || JSON.stringify(err.errors)));
        throw new Error("Validation Error");
    }
    
    if (!response.ok) {
        console.error(`HTTP Error: ${response.status}`);
        throw new Error(`HTTP error! Status: ${response.status}`);
    }
    
    // Si la respuesta es 204 No Content, retornar null
    if (response.status === 204) return null;
    
    return response.json();
}

// ================= UTILIDADES UI =================
function renderPagination(entity, totalItems, currentPage, callbackName) {
    const totalPages = Math.ceil(totalItems / ITEMS_PER_PAGE);
    const container = document.getElementById('pagination-' + entity);
    if (!container) return;

    let html = `<button onclick="${callbackName}(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>Anterior</button>`;
    for (let i = 1; i <= totalPages; i++) {
        html += `<button onclick="${callbackName}(${i})" class="${i === currentPage ? 'active' : ''}">${i}</button>`;
    }
    html += `<button onclick="${callbackName}(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''}>Siguiente</button>`;
    container.innerHTML = html;
}

// Variables globales para mapas, para poder limpiarlos al cerrar modales
let mapaRuta = null;

function openModal(type, data = null) {
    const modal = document.getElementById('modal');
    const modalContent = document.getElementById('modal-content');
    const title = document.getElementById('modal-title');
    const body = document.getElementById('modal-body');
    
    modal.classList.remove('hidden');
    // Resetear clases y contenido
    modalContent.className = 'modal-content'; 
    body.innerHTML = ''; 

    // Router de Modales
    if (type === 'contenedor' && typeof renderModalContenedor === 'function') renderModalContenedor(title, body, data);
    else if (type === 'camion' && typeof renderModalCamion === 'function') renderModalCamion(title, body, data);
    else if (type === 'ruta' && typeof renderModalRuta === 'function') {
        modalContent.classList.add('modal-large');
        renderModalRuta(title, body, data);
    }
    else if (type === 'ver-ruta' && typeof renderModalVerRuta === 'function') {
        modalContent.classList.add('modal-large');
        renderModalVerRuta(title, body, data);
    }
    else if (type === 'optimizar-ruta' && typeof renderModalOptimizar === 'function') renderModalOptimizar(title, body);
    else if (type === 'reporte' && typeof renderModalReporte === 'function') {
        modalContent.classList.add('modal-large');
        renderModalReporte(title, body, data);
    }
    else if (type === 'reporte-edit' && typeof renderModalReporteEdit === 'function') {
        modalContent.classList.add('modal-large');
        renderModalReporteEdit(title, body, data);
    }
    else if (type === 'alerta' && typeof renderModalAlerta === 'function') {
        modalContent.classList.add('modal-large');
        renderModalAlerta(title, body, data);
    }
    else if (type === 'usuario' && typeof renderModalUsuario === 'function') renderModalUsuario(title, body, data);
}

function closeModal() {
    document.getElementById('modal').classList.add('hidden');
    if (mapaRuta) {
        mapaRuta.remove();
        mapaRuta = null;
    }
}
