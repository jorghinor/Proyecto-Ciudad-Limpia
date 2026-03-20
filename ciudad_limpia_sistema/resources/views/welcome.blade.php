<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ciudad Limpia - Portal Ciudadano</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap');
        body { font-family: 'Poppins', sans-serif; }
        .hero-wrapper { position: relative; overflow: hidden; min-height: 100vh; }
        .hero-slider { position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: -2; padding: 0; margin: 0; list-style: none; }
        .hero-slider li { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-size: cover; background-position: center; opacity: 0; transform: scale(1); animation: slideAnimation 24s linear infinite; }
        .hero-slider li:nth-child(1) { background-image: url('https://images.unsplash.com/photo-1532996122724-e3c354a0b15b?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80'); animation-delay: 0s; }
        .hero-slider li:nth-child(2) { background-image: url('https://images.unsplash.com/photo-1611284446314-60a58ac0deb9?q=80&w=2070&auto=format&fit=crop'); animation-delay: 8s; }
        .hero-slider li:nth-child(3) { background-image: url('/images/camion.jpg'); background-position: center; background-color: #2d3748; animation-delay: 16s; }
        @keyframes slideAnimation { 0% { opacity: 0; transform: scale(1); } 4% { opacity: 1; } 33% { opacity: 1; transform: scale(1.1); } 37% { opacity: 0; } 100% { opacity: 0; } }
        .hero-overlay { position: absolute; inset: 0; background: linear-gradient(135deg, rgba(6, 78, 59, 0.75), rgba(30, 58, 138, 0.75)); z-index: -1; animation: gradientChange 12s ease infinite alternate; }
        .hero-media { display: inline-flex; align-items: center; justify-content: center; }
        .hero-video { width: 160px; max-width: 40vw; height: auto; border-radius: 9999px; box-shadow: 0 20px 40px rgba(15, 23, 42, 0.25); background: rgba(255, 255, 255, 0.12); backdrop-filter: blur(6px); border: 1px solid rgba(255, 255, 255, 0.35); }
        @media (min-width: 768px) { .hero-video { width: 200px; } }
        @media (max-width: 480px) { .hero-video { width: 130px; } }
        @keyframes gradientChange { 0% { background: linear-gradient(135deg, rgba(6, 78, 59, 0.8), rgba(30, 58, 138, 0.8)); } 50% { background: linear-gradient(135deg, rgba(21, 128, 61, 0.8), rgba(88, 28, 135, 0.8)); } 100% { background: linear-gradient(135deg, rgba(13, 148, 136, 0.8), rgba(30, 64, 175, 0.8)); } }
        .glass { background: rgba(255, 255, 255, 0.75); backdrop-filter: blur(16px); border: 1px solid rgba(255, 255, 255, 0.5); box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.1); }
        .glass-nav { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); border-bottom: 1px solid rgba(229, 231, 235, 0.5); }
        .text-gradient-hero { background: linear-gradient(to right, #4ade80, #60a5fa, #c084fc); background-size: 200% auto; -webkit-background-clip: text; -webkit-text-fill-color: transparent; animation: shine 5s linear infinite; }
        @keyframes shine { to { background-position: 200% center; } }
        @keyframes float { 0%, 100% { transform: translateY(0px); } 50% { transform: translateY(-15px); } }
        .animate-float { animation: float 6s ease-in-out infinite; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        .reveal { opacity: 0; transform: translateY(30px); transition: all 0.8s ease-out; }
        .reveal.active { opacity: 1; transform: translateY(0); }
        .map-container { height: 550px; width: 100%; border-radius: 1.5rem; z-index: 1; }
        .marker-cluster-small { background-color: rgba(181, 226, 140, 0.6); }
        .marker-cluster-small div { background-color: rgba(110, 204, 57, 0.6); }
        .marker-cluster-medium { background-color: rgba(241, 211, 87, 0.6); }
        .marker-cluster-medium div { background-color: rgba(240, 194, 12, 0.6); }
        .marker-cluster-large { background-color: rgba(253, 156, 115, 0.6); }
        .marker-cluster-large div { background-color: rgba(241, 128, 23, 0.6); }
        ::-webkit-scrollbar { width: 10px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #16a34a; border-radius: 5px; }
        ::-webkit-scrollbar-thumb:hover { background: #15803d; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 overflow-x-hidden">

    <!-- Navbar (Igual) -->
    <nav class="glass-nav fixed w-full z-50 transition-all duration-300 shadow-sm" id="navbar">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20 items-center">
                <div class="flex items-center group cursor-pointer">
                    <div class="bg-gradient-to-br from-green-400 to-green-600 p-2.5 rounded-xl mr-3 shadow-lg shadow-green-500/30 transform group-hover:rotate-12 transition duration-300">
                        <i class="fas fa-leaf text-white text-xl"></i>
                    </div>
                    <span class="font-extrabold text-2xl tracking-tight text-gray-800">Ciudad<span class="text-green-600">Limpia</span></span>
                </div>
                <button id="mobile-menu-btn" class="md:hidden inline-flex items-center justify-center h-10 w-10 rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-100 transition" aria-label="Abrir menú" aria-expanded="false">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="hidden md:flex items-center space-x-8">
                    <a href="#inicio" class="text-gray-600 hover:text-green-600 font-medium transition duration-300">Inicio</a>
                    <a href="#mapa" class="text-gray-600 hover:text-green-600 font-medium transition duration-300">Mapa</a>
                    <a href="#reportar" class="text-gray-600 hover:text-green-600 font-medium transition duration-300">Reportar</a>
                    <a href="/admin" class="bg-gray-900 hover:bg-black text-white px-6 py-2.5 rounded-full font-bold shadow-lg transition duration-300 flex items-center border border-gray-700">
                        <i class="fas fa-user-shield mr-2"></i> Admin
                    </a>
                </div>
            </div>
            <div id="mobile-menu" class="md:hidden hidden mt-3 pb-4 border-t border-gray-100">
                <div class="flex flex-col gap-3 pt-4">
                    <a href="#inicio" class="text-gray-700 hover:text-green-600 font-medium transition duration-300">Inicio</a>
                    <a href="#mapa" class="text-gray-700 hover:text-green-600 font-medium transition duration-300">Mapa</a>
                    <a href="#reportar" class="text-gray-700 hover:text-green-600 font-medium transition duration-300">Reportar</a>
                    <a href="/admin" class="bg-gray-900 hover:bg-black text-white px-4 py-2 rounded-full font-bold shadow-lg transition duration-300 inline-flex items-center w-fit border border-gray-700">
                        <i class="fas fa-user-shield mr-2"></i> Admin
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section (Igual) -->
    <div id="inicio" class="hero-wrapper flex items-center justify-center pt-20">
        <ul class="hero-slider"><li></li><li></li><li></li></ul>
        <div class="hero-overlay"></div>
        <div class="absolute top-32 left-10 w-40 h-40 bg-white opacity-10 rounded-full blur-3xl animate-float"></div>
        <div class="absolute bottom-40 right-20 w-80 h-80 bg-blue-400 opacity-20 rounded-full blur-3xl animate-float" style="animation-delay: 2s"></div>
        <div class="text-center text-white px-4 max-w-5xl mx-auto z-10">
            <div class="inline-flex items-center bg-white/10 backdrop-blur-md px-5 py-2 rounded-full text-sm font-bold mb-8 border border-white/20 shadow-lg animate-[fadeInUp_0.8s_ease-out]">
                <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse mr-3"></span> Sistema de Gestión Inteligente
            </div>
            <div class="flex flex-col items-center gap-6">
                <div class="hero-media animate-[fadeInUp_0.9s_ease-out]">
                    <video class="hero-video" autoplay muted loop playsinline>
                        <source src="/images/logoanimado1.mp4" type="video/mp4">
                    </video>
                </div>
                <h1 class="text-5xl md:text-7xl font-extrabold mb-8 leading-tight animate-[fadeInUp_1s_ease-out]">Tu ciudad, más limpia<br/><span class="text-gradient-hero">más sostenible</span></h1>
            </div>
            <p class="text-lg md:text-2xl mb-12 text-gray-100 max-w-3xl mx-auto font-light leading-relaxed animate-[fadeInUp_1.2s_ease-out] drop-shadow-md">Únete a la red ciudadana. Monitorea rutas y reporta incidencias.</p>
            <div class="flex flex-col sm:flex-row justify-center gap-6 animate-[fadeInUp_1.4s_ease-out]">
                <a href="#mapa" class="group bg-green-500 hover:bg-green-600 text-white font-bold py-4 px-10 rounded-full shadow-lg transition-all duration-300 border-t border-green-400 flex items-center justify-center">Ver Mapa</a>
                <a href="#reportar" class="group bg-white/10 hover:bg-white/20 text-white border border-white/40 font-bold py-4 px-10 rounded-full shadow-lg transition-all duration-300 flex items-center justify-center backdrop-blur-sm">Reportar</a>
            </div>
            <!-- Stats -->
            <div class="mt-24 grid grid-cols-1 md:grid-cols-3 gap-6 max-w-5xl mx-auto animate-[fadeInUp_1.6s_ease-out]">
                <div class="glass p-6 rounded-2xl text-left border-l-4 border-green-500">
                    <p class="text-gray-500 text-sm font-bold uppercase">Contenedores</p>
                    <h3 class="text-4xl font-extrabold text-gray-800 mt-1" id="stat-contenedores">--</h3>
                </div>
                <div class="glass p-6 rounded-2xl text-left border-l-4 border-blue-500">
                    <p class="text-gray-500 text-sm font-bold uppercase">Flota</p>
                    <h3 class="text-4xl font-extrabold text-gray-800 mt-1" id="stat-camiones">--</h3>
                </div>
                <div class="glass p-6 rounded-2xl text-left border-l-4 border-purple-500">
                    <p class="text-gray-500 text-sm font-bold uppercase">Reportes</p>
                    <h3 class="text-4xl font-extrabold text-gray-800 mt-1" id="stat-reportes">--</h3>
                </div>
            </div>
        </div>
        <div class="absolute bottom-0 w-full leading-none z-10">
            <svg class="relative block w-full h-16 md:h-24" viewBox="0 0 1200 120" preserveAspectRatio="none"><path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z" fill="#ffffff"></path></svg>
        </div>
    </div>

    <!-- Mapa -->
    <section id="mapa" class="py-24 bg-white relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-4xl font-extrabold text-gray-900 mb-10 text-center">Mapa de Operaciones</h2>
            <div class="relative p-3 bg-white rounded-3xl shadow-xl border border-gray-100 reveal delay-100">
                <div id="leaflet-map" class="map-container shadow-inner"></div>
            </div>
        </div>
    </section>

    <!-- Reportes (CORREGIDO) -->
    <section id="reportar" class="py-24 bg-gray-50 relative overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                <div class="lg:col-span-5 reveal">
                    <h2 class="text-4xl font-extrabold text-gray-900 mb-6">¿Ves algo fuera de lugar? <span class="text-green-600">¡Repórtalo!</span></h2>
                    <p class="text-gray-600 mb-8 text-lg">Ayúdanos a mantener la ciudad limpia enviando un reporte con foto.</p>
                </div>

                <div class="lg:col-span-7 reveal delay-200">
                    <div class="bg-white rounded-3xl shadow-xl p-8 border border-gray-100 relative">
                        <!-- Formulario -->
                        <form id="reporte-form" onsubmit="enviarReporte(event)" class="space-y-6 relative z-10">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Tipo de Incidencia</label>
                                    <select id="tipo" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl" required>
                                        <option value="basura_en_calle">🗑️ Basura en la calle</option>
                                        <option value="contenedor_danado">🔧 Contenedor dañado</option>
                                        <option value="punto_critico">⚠️ Punto crítico</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Referencia</label>
                                    <input type="text" id="ubicacion" placeholder="Ej: Esquina del parque" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl" required>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Descripción</label>
                                <textarea id="descripcion" rows="3" placeholder="Detalle..." class="w-full p-4 bg-gray-50 border border-gray-200 rounded-xl" required></textarea>
                            </div>

                            <!-- CAMPO DE FOTO AGREGADO -->
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Evidencia (Foto)</label>
                                <input type="file" id="foto" accept="image/*" class="w-full p-2 bg-gray-50 border border-gray-200 rounded-xl">
                                <p class="text-xs text-gray-500 mt-1">Sube una foto clara del problema (Opcional).</p>
                            </div>

                            <div class="bg-blue-50 p-5 rounded-xl border border-blue-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                                <div>
                                    <span class="block text-xs font-bold text-blue-800 uppercase">Geolocalización</span>
                                    <span id="gps-status" class="text-sm text-gray-600">Requerido para el reporte</span>
                                </div>
                                <button type="button" onclick="obtenerUbicacion()" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg text-sm font-bold shadow-md transition">
                                    <i class="fas fa-map-marker-alt mr-2"></i> Activar GPS
                                </button>
                                <input type="hidden" id="latitud">
                                <input type="hidden" id="longitud">
                            </div>

                            <button type="submit" class="w-full bg-gray-900 hover:bg-black text-white font-bold py-4 rounded-xl shadow-lg transition transform hover:-translate-y-1 text-lg">
                                Enviar Reporte
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer (Igual) -->
    <footer class="bg-gray-900 text-white pt-16 pb-8 border-t-4 border-green-500 text-center">
        <p class="text-gray-400 text-sm">&copy; 2026 Ciudad Limpia Sistema.</p>
    </footer>

    <script>
        const API_URL = '/api/public';
        let map, markers;

        // ... (Funciones reveal, scroll, animateValue, initMap, actualizarMapa, obtenerUbicacion iguales) ...
        function reveal() { var reveals = document.querySelectorAll(".reveal"); for (var i = 0; i < reveals.length; i++) { var windowHeight = window.innerHeight; var elementTop = reveals[i].getBoundingClientRect().top; if (elementTop < windowHeight - 150) reveals[i].classList.add("active"); } }
        window.addEventListener("scroll", reveal);
        window.addEventListener("scroll", () => { const navbar = document.getElementById("navbar"); if (window.scrollY > 50) { navbar.classList.add("shadow-md"); navbar.classList.replace("h-20", "h-16"); } else { navbar.classList.remove("shadow-md"); navbar.classList.replace("h-16", "h-20"); } });
        document.addEventListener('DOMContentLoaded', () => {
            reveal();
            initMap();
            cargarEstadisticas();
            setInterval(actualizarMapa, 10000);
            const mobileBtn = document.getElementById('mobile-menu-btn');
            const mobileMenu = document.getElementById('mobile-menu');
            if (mobileBtn && mobileMenu) {
                mobileBtn.addEventListener('click', () => {
                    const isOpen = !mobileMenu.classList.contains('hidden');
                    mobileMenu.classList.toggle('hidden', isOpen);
                    mobileBtn.setAttribute('aria-expanded', String(!isOpen));
                });
            }
        });

        async function cargarEstadisticas() { try { const res = await fetch(`${API_URL}/estadisticas`); if(res.ok) { const d = await res.json(); animateValue("stat-contenedores", 0, d.contenedores_activos, 2000); animateValue("stat-camiones", 0, d.camiones_ruta, 2000); animateValue("stat-reportes", 0, d.reportes_resueltos, 2000); } } catch(e){} }
        function animateValue(id, start, end, duration) { const obj = document.getElementById(id); if(!obj) return; let startTimestamp = null; const step = (timestamp) => { if (!startTimestamp) startTimestamp = timestamp; const progress = Math.min((timestamp - startTimestamp) / duration, 1); obj.innerHTML = Math.floor(progress * (end - start) + start); if (progress < 1) window.requestAnimationFrame(step); }; window.requestAnimationFrame(step); }
        function initMap() { map = L.map('leaflet-map', { zoomControl: false }).setView([-17.3938, -66.1570], 13); L.control.zoom({ position: 'bottomright' }).addTo(map); L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map); markers = L.markerClusterGroup(); map.addLayer(markers); actualizarMapa(); }
        async function actualizarMapa() { try { markers.clearLayers(); const cRes = await fetch(`${API_URL}/contenedores`); const kRes = await fetch(`${API_URL}/camiones`); if(cRes.ok) (await cRes.json()).forEach(c => { if(c.lat && c.lng || c.latitud) { const lat = c.lat || c.latitud; const lng = c.lng || c.longitud; const color = c.estado === 'lleno' ? '#ef4444' : '#22c55e'; markers.addLayer(L.circleMarker([lat, lng], { radius: 8, fillColor: color, color: '#fff', weight: 2, fillOpacity: 0.8 }).bindPopup(c.ubicacion)); } }); if(kRes.ok) (await kRes.json()).forEach(k => { if(k.lat && k.lng || k.latitud) { const lat = k.lat || k.latitud; const lng = k.lng || k.longitud; L.marker([lat, lng], { icon: L.divIcon({ className: '', html: '<div style="background:#2563eb;width:20px;height:20px;border-radius:50%;border:2px solid white;"></div>' }) }).addTo(map).bindPopup(k.placa); } }); } catch(e){} }
        function obtenerUbicacion() { if(!navigator.geolocation) return Swal.fire('Error', 'GPS no soportado', 'error'); document.getElementById('gps-status').innerText = "Localizando..."; navigator.geolocation.getCurrentPosition(p => { document.getElementById('latitud').value = p.coords.latitude; document.getElementById('longitud').value = p.coords.longitude; document.getElementById('gps-status').innerText = "Ubicación lista ✅"; Swal.fire({ icon: 'success', title: 'GPS Activo', timer: 1000, showConfirmButton: false }); }, () => { Swal.fire('Error', 'No se pudo obtener ubicación', 'error'); }); }

        // ==========================================
        //  LÓGICA DE ENVÍO CORREGIDA (FORMDATA)
        // ==========================================
        async function enviarReporte(e) {
            e.preventDefault();
            const btn = e.target.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;

            const lat = document.getElementById('latitud').value;
            if(!lat) {
                Swal.fire('Falta GPS', 'Por favor activa el GPS antes de enviar.', 'warning');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Enviando...';

            // USAR FORMDATA PARA ENVIAR ARCHIVOS
            const formData = new FormData();
            formData.append('tipo', document.getElementById('tipo').value);
            formData.append('descripcion', document.getElementById('descripcion').value);
            formData.append('ubicacion', document.getElementById('ubicacion').value);
            formData.append('latitud', document.getElementById('latitud').value);
            formData.append('longitud', document.getElementById('longitud').value);

            const fileInput = document.getElementById('foto');
            if (fileInput.files.length > 0) {
                formData.append('foto', fileInput.files[0]);
            }

            try {
                // NOTA: Al usar FormData, NO se debe poner Content-Type manual, el navegador lo pone
                const response = await fetch(`${API_URL}/reportes`, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' },
                    body: formData
                });

                if (response.ok) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Recibido!',
                        text: 'Tu reporte ha sido enviado con éxito.',
                        confirmButtonColor: '#16a34a'
                    });
                    e.target.reset();
                    document.getElementById('gps-status').innerText = "Requerido para el reporte";
                } else {
                    const errorData = await response.json();
                    Swal.fire('Error', errorData.message || 'Hubo un problema.', 'error');
                }
            } catch (error) {
                Swal.fire('Error de Conexión', 'Intenta nuevamente más tarde.', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        }
    </script>
</body>
</html>
