
async function loadContainers() {
    const res = await fetch('/api/containers');
    const data = await res.json();

    const div = document.getElementById('containers');
    div.innerHTML = '';

    data.forEach(c => {
        const el = document.createElement('div');
        el.className = 'card';
        el.innerHTML = `
        <h3>Contenedor ${c.id}</h3>
        <p>Ubicación: ${c.location}</p>
        <p>Nivel: ${c.fill_level}%</p>
        `;
        div.appendChild(el);
    });
}

loadContainers();
