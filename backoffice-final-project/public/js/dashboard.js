'use strict';

(function () {

const { userId, role } = window.WEC;

// ── Navegación entre secciones ────────────────────────────
const navItems  = document.querySelectorAll('.nav-item');
const sections  = document.querySelectorAll('.section');
const topbarTitle = document.getElementById('topbarTitle');

const sectionTitles = {
    overview:     'Panel',
    pilots:       'Pilotos',
    vehicles:     'Vehículos',
    races:        'Carreras',
    teams:        'Equipos',
    penalties:    'Penalizaciones',
    results:      'Resultados',
    stats:        'Estadísticas',
    manufacturer: 'Mi Fabricante',
};

function navigateTo(sectionId) {
    navItems.forEach(btn => {
        btn.classList.toggle('active', btn.dataset.section === sectionId);
    });
    sections.forEach(sec => {
        sec.classList.toggle('active', sec.dataset.section === sectionId);
    });
    topbarTitle.textContent = sectionTitles[sectionId] ?? sectionId;
    closeSidebar();
    lazyLoad(sectionId);
}

navItems.forEach(btn => {
    btn.addEventListener('click', () => navigateTo(btn.dataset.section));
});

// ── Sidebar mobile ────────────────────────────────────────
const sidebar   = document.getElementById('sidebar');
const overlay   = document.getElementById('overlay');
const menuBtn   = document.getElementById('menuBtn');
const closeBtn  = document.getElementById('sidebarClose');

function openSidebar() {
    sidebar.classList.add('open');
    overlay.classList.add('visible');
    overlay.removeAttribute('aria-hidden');
    menuBtn.setAttribute('aria-expanded', 'true');
}
function closeSidebar() {
    sidebar.classList.remove('open');
    overlay.classList.remove('visible');
    overlay.setAttribute('aria-hidden', 'true');
    menuBtn.setAttribute('aria-expanded', 'false');
}

menuBtn.addEventListener('click', openSidebar);
closeBtn.addEventListener('click', closeSidebar);
overlay.addEventListener('click', closeSidebar);

// ── Logout ────────────────────────────────────────────────
document.getElementById('logoutBtn').addEventListener('click', async () => {
    try {
        const res  = await fetch('/logout', { method: 'POST' });
        const data = await res.json();
        if (data.success) window.location.href = data.redirect ?? '/login';
    } catch {
        window.location.href = '/login';
    }
});

// ── Lucide icons ──────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
});

// ── Lazy load por sección ─────────────────────────────────
const loaded = new Set();

function lazyLoad(sectionId) {
    if (loaded.has(sectionId)) return;
    loaded.add(sectionId);

    switch (sectionId) {
        case 'overview':     loadOverview();     break;
        case 'pilots':       loadPilots();       break;
        case 'vehicles':     loadVehicles();     break;
        case 'races':        loadRaces();        break;
        case 'teams':        loadTeams();        break;
        case 'penalties':    loadPenalties();    break;
        case 'results':      loadResults();      break;
        case 'manufacturer': loadManufacturer(); break;
    }
}

// ── Helpers ───────────────────────────────────────────────
async function apiFetch(url) {
    const res = await fetch(url);
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    return res.json();
}

function buildTable(headers, rows, colMap) {
    if (!rows || rows.length === 0) {
        return `<div class="empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/>
            </svg>
            <p>Sin datos disponibles</p>
        </div>`;
    }
    const ths = headers.map(h => `<th>${h}</th>`).join('');
    const trs = rows.map(row => {
        const tds = colMap.map(key => `<td>${row[key] ?? '—'}</td>`).join('');
        return `<tr>${tds}</tr>`;
    }).join('');
    return `<table><thead><tr>${ths}</tr></thead><tbody>${trs}</tbody></table>`;
}

function setError(el, msg) {
    el.innerHTML = `<div class="empty-state"><p style="color:var(--error)">${msg}</p></div>`;
}

// ── KPI card builder ──────────────────────────────────────
function kpiCard(label, value, sub = '', colorClass = '') {
    return `<div class="kpi-card">
        <span class="kpi-label">${label}</span>
        <span class="kpi-value ${colorClass}">${value}</span>
        ${sub ? `<span class="kpi-sub">${sub}</span>` : ''}
    </div>`;
}

// ── OVERVIEW ──────────────────────────────────────────────
async function loadOverview() {
    const kpiGrid = document.getElementById('kpiGrid');
    const upcomingEl = document.getElementById('upcomingRaces');

    try {
        const calendar = await apiFetch('/api/public/race-calendar');
        const now = new Date();
        const upcoming = (calendar || [])
            .filter(r => new Date(r.event_date) >= now)
            .slice(0, 5);

        // KPIs dinámicos según rol
        const kpis = buildKpis(calendar);
        kpiGrid.innerHTML = kpis;

        // Tabla próximas carreras
        upcomingEl.innerHTML = buildTable(
            ['Evento', 'Circuito', 'Fecha', 'Duración'],
            upcoming,
            ['event_name', 'circuit_name', 'event_date', 'event_duration']
        );
    } catch (e) {
        kpiGrid.innerHTML = kpiCard('Carreras', '—', 'Error al cargar', 'kpi-accent');
        setError(upcomingEl, 'No se pudo cargar el calendario.');
    }

    if (window.lucide) lucide.createIcons();
}

function buildKpis(calendar) {
    const total = calendar?.length ?? 0;
    const now   = new Date();
    const done  = (calendar || []).filter(r => new Date(r.event_date) < now).length;
    const left  = total - done;

    const base = [
        kpiCard('Carreras Totales',    total,  'en el campeonato',  'kpi-accent'),
        kpiCard('Carreras Disputadas', done,   'resultados disponibles', 'kpi-success'),
        kpiCard('Pendientes',          left,   'por disputar',      'kpi-warning'),
    ];

    if (['software-administrator','administratorDB'].includes(role)) {
        return base.join('');
    }
    if (role === 'pilot') {
        return kpiCard('Próximas Carreras', left, 'en tu calendario', 'kpi-accent')
             + kpiCard('Carreras Completadas', done, 'resultados registrados', 'kpi-success');
    }
    return base.join('');
}

// ── PILOTS ───────────────────────────────────────────────
async function loadPilots() {
    const el = document.getElementById('pilotsTable');
    try {
        const data = await apiFetch(`/api/pilots?userId=${userId}`);
        el.innerHTML = buildTable(
            ['ID', 'Nombre', 'Edad', 'Categoría'],
            data,
            ['id_pilot', 'pilot_name', 'pilot_age', 'pilot_category_name']
        );
    } catch { setError(el, 'No se pudieron cargar los pilotos.'); }
    if (window.lucide) lucide.createIcons();
}

// ── VEHICLES ─────────────────────────────────────────────
async function loadVehicles() {
    const el = document.getElementById('vehiclesTable');
    try {
        const data = await apiFetch(`/api/vehicles?userId=${userId}`);
        el.innerHTML = buildTable(
            ['ID', 'Modelo', 'Especificaciones'],
            data,
            ['id_vehicle', 'model', 'specifications_url']
        );
    } catch { setError(el, 'No se pudieron cargar los vehículos.'); }
    if (window.lucide) lucide.createIcons();
}

// ── RACES ────────────────────────────────────────────────
async function loadRaces() {
    const el = document.getElementById('racesTable');
    try {
        const data = await apiFetch(`/api/races?userId=${userId}`);
        el.innerHTML = buildTable(
            ['ID', 'Evento', 'Fecha', 'Circuito', 'Duración'],
            data,
            ['id_race', 'event_name', 'event_date', 'circuit_name', 'event_duration']
        );
    } catch { setError(el, 'No se pudieron cargar las carreras.'); }
    if (window.lucide) lucide.createIcons();
}

// ── TEAMS ────────────────────────────────────────────────
async function loadTeams() {
    const el = document.getElementById('teamsTable');
    try {
        const data = await apiFetch(`/api/teams?userId=${userId}`);
        el.innerHTML = buildTable(
            ['ID', 'Equipo', 'Fabricante', 'Mecánicos'],
            data,
            ['id_team', 'team_name', 'manufacturer_name', 'mechanics_num']
        );
    } catch { setError(el, 'No se pudieron cargar los equipos.'); }
    if (window.lucide) lucide.createIcons();
}

// ── PENALTIES ────────────────────────────────────────────
async function loadPenalties() {
    const el = document.getElementById('penaltiesTable');
    try {
        const data = await apiFetch(`/api/penalties?userId=${userId}`);
        el.innerHTML = buildTable(
            ['ID', 'Tipo', 'Motivo', 'Valor', 'Aplica a'],
            data,
            ['id_penalty', 'penalty_type', 'reason', 'penalty_value', 'penalty_applies_to']
        );
    } catch { setError(el, 'No se pudieron cargar las penalizaciones.'); }
    if (window.lucide) lucide.createIcons();
}

// ── RESULTS ──────────────────────────────────────────────
async function loadResults() {
    const el = document.getElementById('resultsTable');
    try {
        const data = await apiFetch(`/api/results?userId=${userId}`);
        el.innerHTML = buildTable(
            ['Pos.', 'Evento', 'Tiempo Final', 'T. Penaliz.', 'Pts. Piloto', 'Pts. Equipo'],
            data,
            ['position', 'event_name', 'final_time', 'penalty_time', 'base_points_pilot', 'base_points_team']
        );
    } catch { setError(el, 'No se pudieron cargar los resultados.'); }
    if (window.lucide) lucide.createIcons();
}

// ── MANUFACTURER ─────────────────────────────────────────
async function loadManufacturer() {
    const el = document.getElementById('manufacturerCard');
    try {
        const data = await apiFetch(`/api/manufacturer?userId=${userId}`);
        if (!data) { setError(el, 'Sin datos de fabricante.'); return; }
        el.innerHTML = `
            <div style="padding:1.25rem;display:flex;flex-direction:column;gap:0.75rem">
                <div class="kpi-grid">
                    ${kpiCard('Fabricante', data.manufacturer_name ?? '—', '', 'kpi-accent')}
                    ${kpiCard('País', data.manufacturer_country ?? '—')}
                </div>
            </div>`;
    } catch { setError(el, 'No se pudieron cargar los datos del fabricante.'); }
    if (window.lucide) lucide.createIcons();
}

// ── Carga inicial ─────────────────────────────────────────
lazyLoad('overview');

})();