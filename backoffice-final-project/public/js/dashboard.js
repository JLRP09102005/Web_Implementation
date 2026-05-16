'use strict';

function setCookie(name, value, days) {
    const d = new Date();
    d.setTime(d.getTime() + (days * 24 * 60 * 60 * 1000));
    document.cookie = name + '=' + encodeURIComponent(value) + ';path=/;expires=' + d.toUTCString() + ';SameSite=Lax';
}

function getCookie(name) {
    const cookies = document.cookie.split(';');
    for (let c of cookies) {
        c = c.trim();
        if (c.startsWith(name + '=')) {
            return decodeURIComponent(c.substring(name.length + 1));
        }
    }
    return null;
}

(function () {

// ── Estado ───────────────────────────────────────────────────────────────────
const WEC = window.WEC || { userId: 0, role: '', visible: [] };
const loaded = new Set();
let adminEntity    = 'pilots';
let adminRows      = [];
let currentSection = 'panel';

// Listas maestras para filtros
let _pilots  = [];
let _teams   = [];
let _races   = [];
let _penalties = [];
let _results = [];
let _vehicles = [];
let _inscriptions = [];

// ── Init ─────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
    initNav();
    initSidebar();
    initLogout();

    if (WEC.visible.includes('administracion')) {
        initAdminTabs();
        initAdminModal();
    }

    // Sin timeout, DOMContentLoaded ya garantiza que el DOM está listo
    let savedSection = getCookie('wec_section');
    if (!savedSection || !WEC.visible.includes(savedSection)) {
        savedSection = 'panel';
    }
    // Primero activa visualmente, luego carga datos
    activateSection(savedSection);
    currentSection = savedSection;
    loadSection(savedSection);
    loaded.add(savedSection);
});

// ── Navegación ───────────────────────────────────────────────────────────────
function initNav() {
    document.querySelectorAll('.nav-item').forEach(btn => {
        btn.addEventListener('click', () => {
            showSection(btn.dataset.section);
            closeSidebar();
        });
    });
}

// Solo activa el DOM visualmente, sin disparar carga de datos
function activateSection(id) {
    document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.nav-item').forEach(b => {
        b.classList.toggle('active', b.dataset.section === id);
        b.setAttribute('aria-current', b.dataset.section === id ? 'page' : 'false');
    });
    const sec = document.getElementById('sec-' + id);
    if (sec) sec.classList.add('active');
    const labels = {
        panel: 'Panel', carreras: 'Carreras', pilotos: 'Pilotos',
        equipos: 'Equipos', vehiculos: 'Vehículos', penalizaciones: 'Penalizaciones',
        resultados: 'Resultados', inscripciones: 'Inscripciones',
        estadisticas: 'Estadísticas', fabricante: 'Mi Fabricante', administracion: 'Administración'
    };
    const title = document.getElementById('topbarTitle');
    if (title) title.textContent = labels[id] || id;
}

function showSection(id) {
    currentSection = id;
    activateSection(id);
    if (!loaded.has(id)) {
        loadSection(id);
        loaded.add(id);
    }
    setCookie('wec_section', id, 7);  // añadir esta línea
}

/** Fuerza recarga de una sección aunque ya esté en loaded (tras CRUD) */
function reloadSection(id) {
    activateSection(id);
    loadSection(id);
}

// ── Carga de datos por sección ───────────────────────────────────────────────
function loadSection(id) {
    switch (id) {
        case 'panel':          loadOverview();   break;
        case 'carreras':       loadRaces();      break;
        case 'pilotos':        loadPilots();     break;
        case 'equipos':        loadTeams();      break;
        case 'vehiculos':      loadVehicles();   break;
        case 'penalizaciones': loadPenalties();  break;
        case 'resultados':     loadResults();    break;
        case 'inscripciones':  loadInscriptions(); break;
        case 'estadisticas':   loadStats();      break;
        case 'fabricante':     loadManufacturer(); break;
        case 'administracion': loadAdminEntity(adminEntity); break;
    }
}

// ── Loader genérico de tablas (sin filtros) ──────────────────────────────────
async function loadTable(endpoint, tbodyId, renderFn) {
    const tbody = document.getElementById(tbodyId);
    if (!tbody) return [];
    try {
        const rows = await apiFetch(endpoint);
        const list = Array.isArray(rows) ? rows : [];
        if (!list.length) {
            tbody.innerHTML = `<tr><td colspan="99" class="empty-state-cell">Sin datos disponibles</td></tr>`;
            return [];
        }
        tbody.innerHTML = list.map(renderFn).join('');
        if (window.lucide) lucide.createIcons();
        return list;
    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="99" class="empty-state-cell error-cell">Error al cargar datos</td></tr>`;
        console.error(endpoint, e);
        return [];
    }
}

// ══════════════════════════════════════════════════════════════════════════════
// SECCIONES CON FILTROS
// ══════════════════════════════════════════════════════════════════════════════

// ── PANEL ────────────────────────────────────────────────────────────────────
async function loadOverview() {
    try {
        const data = await apiFetch('/api/overview');
        if (data.error) return;
        const kpis = [
            { label: 'Carreras',       value: data.total_races,    cls: 'kpi-accent'  },
            { label: 'Pilotos',        value: data.total_pilots,   cls: ''            },
            { label: 'Equipos',        value: data.total_teams,    cls: ''            },
            { label: 'Vehículos',      value: data.total_vehicles, cls: ''            },
            { label: 'Penalizaciones', value: data.total_penalties,cls: 'kpi-warning' },
        ].filter(k => k.value !== undefined && k.value !== null && k.value !== '');
        const grid = document.getElementById('kpiGrid');
        if (grid) {
            grid.innerHTML = kpis.map(k =>
                `<div class="kpi-card"><span class="kpi-label">${k.label}</span><span class="kpi-value ${k.cls}">${k.value}</span></div>`
            ).join('');
        }
        const races = Array.isArray(data.races) ? data.races : [];
        const now   = new Date();
        const upcoming = races
            .filter(r => { const d = new Date(r.event_date ?? r.date ?? r.eventdate); return !isNaN(d) && d > now; })
            .sort((a,b) => new Date(a.event_date ?? a.date ?? a.eventdate) - new Date(b.event_date ?? b.date ?? b.eventdate))
            .slice(0, 8);
        const tbody = document.getElementById('overviewRacesBody');
        if (tbody) {
            tbody.innerHTML = upcoming.length
                ? upcoming.map(r => renderRaceRow(r)).join('')
                : `<tr><td colspan="5" class="empty-state-cell">No hay próximas carreras</td></tr>`;
        }
        if (window.lucide) lucide.createIcons();
    } catch (e) { console.error('overview error', e); }
}

// ── CARRERAS ─────────────────────────────────────────────────────────────────
async function loadRaces() {
    _races = await loadTable('/api/races', 'racesBody', renderRaceRow);
    initRaceFilters();
}

function renderRaceRow(r) {
    return `<tr>
        <td>${esc(r.event_name ?? r.eventname ?? r.race_name ?? '')}</td>
        <td>${esc(r.circuit_name ?? r.circuitname ?? '')}</td>
        <td>${esc(r.country ?? '')}</td>
        <td>${formatDate(r.event_date ?? r.eventdate ?? r.date ?? '')}</td>
        <td>${esc(r.event_duration ?? r.eventduration ?? r.duration ?? '')}</td>
    </tr>`;
}

// ── Filtro genérico con selector de columna ───────────────────────────────
function initColumnFilter(searchId, selectId, data, columns, tbodyId, renderFn) {
    const searchEl = document.getElementById(searchId);
    const selectEl = document.getElementById(selectId);
    if (!searchEl || !selectEl) return;

    // Llenar el select con las columnas disponibles
    selectEl.innerHTML = `<option value="all">Todas las columnas</option>`
        + columns.map(c => `<option value="${c.key}">${c.label}</option>`).join('');

    function apply() {
        const q   = searchEl.value.toLowerCase().trim();
        const col = selectEl.value;
        if (!q) {
            document.getElementById(tbodyId).innerHTML = data.map(renderFn).join('');
            if (window.lucide) lucide.createIcons();
            return;
        }
        const filtered = data.filter(row => {
            if (col === 'all') {
                return columns.some(c => String(row[c.key] ?? '').toLowerCase().includes(q));
            }
            return String(row[col] ?? '').toLowerCase().includes(q);
        });
        const tbody = document.getElementById(tbodyId);
        if (tbody) {
            tbody.innerHTML = filtered.length
                ? filtered.map(renderFn).join('')
                : `<tr><td colspan="99" class="empty-state-cell">Sin resultados</td></tr>`;
            if (window.lucide) lucide.createIcons();
        }
    }

    searchEl.addEventListener('input',  apply);
    selectEl.addEventListener('change', apply);
}

function initRaceFilters() {
    const filterEl = document.getElementById('raceStatusFilter');
    filterEl?.addEventListener('change', applyRaceFilters);

    initColumnFilter('raceSearch', 'raceColFilter', _races, [
        { key: 'event_name',    label: 'Carrera'   },
        { key: 'circuit_name',  label: 'Circuito'  },
        { key: 'country',       label: 'País'      },
    ], 'racesBody', renderRaceRow);
}

function applyRaceFilters() {
    const q      = document.getElementById('raceSearch')?.value.toLowerCase() ?? '';
    const col    = document.getElementById('raceColFilter')?.value ?? 'all';
    const status = document.getElementById('raceStatusFilter')?.value ?? 'all';
    const now    = new Date();
    const filtered = _races.filter(r => {
        const d = new Date(r.event_date ?? r.eventdate ?? r.date ?? '');
        const matchStatus = status === 'all'
            || (status === 'upcoming' && d > now)
            || (status === 'past'     && d <= now);
        if (!matchStatus) return false;
        if (!q) return true;
        const cols = [
            { key: 'event_name' }, { key: 'circuit_name' }, { key: 'country' }
        ];
        if (col === 'all') return cols.some(c => String(r[c.key] ?? '').toLowerCase().includes(q));
        return String(r[col] ?? '').toLowerCase().includes(q);
    });
    const tbody = document.getElementById('racesBody');
    if (tbody) {
        tbody.innerHTML = filtered.length
            ? filtered.map(renderRaceRow).join('')
            : `<tr><td colspan="5" class="empty-state-cell">Sin resultados</td></tr>`;
    }
}

// ── PILOTOS ──────────────────────────────────────────────────────────────────
async function loadPilots() {
    _pilots = await loadTable('/api/pilots', 'pilotsBody', renderPilotRow);
    initPilotFilters();
}

function renderPilotRow(r) {
    return `<tr>
        <td>${esc(r.pilot_name ?? r.pilotname ?? r.name ?? '')}</td>
        <td>${esc(r.pilot_age  ?? r.pilotage  ?? r.age  ?? '')}</td>
        <td>${esc(r.pilot_category_name ?? r.pilotcategoryname ?? r.category_name ?? r.category ?? '')}</td>
    </tr>`;
}

function initPilotFilters() {
    const catEl = document.getElementById('pilotCategoryFilter');
    if (catEl) {
        const cats = [...new Set(_pilots.map(p =>
            p.pilot_category_name ?? p.pilotcategoryname ?? p.category_name ?? p.category
        ).filter(Boolean))].sort();
        catEl.innerHTML = `<option value="all">Todas las categorías</option>`
            + cats.map(c => `<option value="${esc(c)}">${esc(c)}</option>`).join('');
        catEl.addEventListener('change', applyPilotFilters);
    }
    document.getElementById('pilotSearch')?.addEventListener('input', applyPilotFilters);
    document.getElementById('pilotColFilter')?.addEventListener('change', applyPilotFilters);
}

function applyPilotFilters() {
    const q   = document.getElementById('pilotSearch')?.value.toLowerCase() ?? '';
    const col = document.getElementById('pilotColFilter')?.value ?? 'all';
    const cat = document.getElementById('pilotCategoryFilter')?.value ?? 'all';
    const cols = [
        { key: 'pilot_name' }, { key: 'pilot_age' }, { key: 'pilot_category_name' }
    ];
    const filtered = _pilots.filter(p => {
        const pcat = p.pilot_category_name ?? p.pilotcategoryname ?? p.category_name ?? p.category ?? '';
        if (cat !== 'all' && pcat !== cat) return false;
        if (!q) return true;
        if (col === 'all') return cols.some(c => String(p[c.key] ?? '').toLowerCase().includes(q));
        return String(p[col] ?? '').toLowerCase().includes(q);
    });
    const tbody = document.getElementById('pilotsBody');
    if (tbody) {
        tbody.innerHTML = filtered.length
            ? filtered.map(renderPilotRow).join('')
            : `<tr><td colspan="3" class="empty-state-cell">Sin resultados</td></tr>`;
    }
}

// ── EQUIPOS ──────────────────────────────────────────────────────────────────
async function loadTeams() {
    _teams = await loadTable('/api/teams', 'teamsBody', renderTeamRow);
    initTeamFilters();
}

function renderTeamRow(r) {
    return `<tr>
        <td>${esc(r.team_name ?? r.teamname ?? r.name ?? '')}</td>
        <td>${esc(r.manufacturer_name ?? r.manufacturername ?? r.manufacturer ?? '')}</td>
        <td>${esc(r.mechanics_num ?? r.mechanicsnum ?? r.mechanics ?? '')}</td>
    </tr>`;
}

function initTeamFilters() {
    const mfrEl = document.getElementById('teamManufacturerFilter');
    if (mfrEl) {
        const mfrs = [...new Set(_teams.map(t =>
            t.manufacturer_name ?? t.manufacturername ?? t.manufacturer
        ).filter(Boolean))].sort();
        mfrEl.innerHTML = `<option value="all">Todos los fabricantes</option>`
            + mfrs.map(m => `<option value="${esc(m)}">${esc(m)}</option>`).join('');
        mfrEl.addEventListener('change', applyTeamFilters);
    }
    document.getElementById('teamSearch')?.addEventListener('input', applyTeamFilters);
    document.getElementById('teamColFilter')?.addEventListener('change', applyTeamFilters);
}

function applyTeamFilters() {
    const q   = document.getElementById('teamSearch')?.value.toLowerCase() ?? '';
    const col = document.getElementById('teamColFilter')?.value ?? 'all';
    const mfr = document.getElementById('teamManufacturerFilter')?.value ?? 'all';
    const cols = [
        { key: 'team_name' }, { key: 'manufacturer_name' }, { key: 'mechanics_num' }
    ];
    const filtered = _teams.filter(t => {
        const tm = t.manufacturer_name ?? t.manufacturername ?? t.manufacturer ?? '';
        if (mfr !== 'all' && tm !== mfr) return false;
        if (!q) return true;
        if (col === 'all') return cols.some(c => String(t[c.key] ?? '').toLowerCase().includes(q));
        return String(t[col] ?? '').toLowerCase().includes(q);
    });
    const tbody = document.getElementById('teamsBody');
    if (tbody) {
        tbody.innerHTML = filtered.length
            ? filtered.map(renderTeamRow).join('')
            : `<tr><td colspan="3" class="empty-state-cell">Sin resultados</td></tr>`;
    }
}

// ── VEHÍCULOS ─────────────────────────────────────────────────────────────────
async function loadVehicles() {
    _vehicles = await loadTable('/api/vehicles', 'vehiclesBody', renderVehicleRow);
    initVehicleFilters();
}

function renderVehicleRow(r) {
    const specs = r.specifications_url ?? r.specificationsurl
        ? `<a href="${esc(r.specifications_url ?? r.specificationsurl)}" target="_blank" rel="noopener" class="link-specs">Ver specs</a>`
        : '—';
    return `<tr><td>${esc(r.model ?? '')}</td><td>${specs}</td></tr>`;
}

function initVehicleFilters() {
    document.getElementById('vehicleSearch')?.addEventListener('input', applyVehicleFilters);
    document.getElementById('vehicleColFilter')?.addEventListener('change', applyVehicleFilters);
}

function applyVehicleFilters() {
    const q   = document.getElementById('vehicleSearch')?.value.toLowerCase() ?? '';
    const col = document.getElementById('vehicleColFilter')?.value ?? 'all';
    const cols = [{ key: 'model' }, { key: 'specifications_url' }];
    const filtered = _vehicles.filter(v => {
        if (!q) return true;
        if (col === 'all') return cols.some(c => String(v[c.key] ?? '').toLowerCase().includes(q));
        return String(v[col] ?? '').toLowerCase().includes(q);
    });
    const tbody = document.getElementById('vehiclesBody');
    if (tbody) {
        tbody.innerHTML = filtered.length
            ? filtered.map(renderVehicleRow).join('')
            : `<tr><td colspan="2" class="empty-state-cell">Sin resultados</td></tr>`;
    }
}

// ── PENALIZACIONES ───────────────────────────────────────────────────────────
async function loadPenalties() {
    _penalties = await loadTable('/api/penalties', 'penaltiesBody', renderPenaltyRow);
    initPenaltyFilters();
}

function renderPenaltyRow(r) {
    const type = r.penalty_type ?? r.penaltytype ?? '';
    const typeClsMap = { POINTS: 'badge-yellow', TIME: 'badge-gray', DSQ: 'badge-red', DNF: 'badge-red' };
    const typeCls = typeClsMap[type] ?? 'badge-gray';
    const appliesTo = r.penalty_applies_to ?? r.penaltyappliesto ?? '';
    return `<tr>
        <td><span class="badge ${typeCls}">${esc(type)}</span></td>
        <td>${esc(r.reason ?? '')}</td>
        <td>${esc(r.penalty_value ?? r.penaltyvalue ?? '')}</td>
        <td>${esc(appliesTo)}</td>
        <td>${esc(r.team_name  ?? r.teamname  ?? '')}</td>
        <td>${appliesTo === 'PILOT' ? esc(r.pilot_name ?? r.pilotname ?? '') : '—'}</td>
        <td>${esc(r.event_name ?? r.eventname ?? '')}</td>
    </tr>`;
}

function initPenaltyFilters() {
    const typeEl = document.getElementById('penaltyTypeFilter');
    typeEl?.addEventListener('change', applyPenaltyFilters);
    document.getElementById('penaltySearch')?.addEventListener('input', applyPenaltyFilters);
    document.getElementById('penaltyColFilter')?.addEventListener('change', applyPenaltyFilters);
}

function applyPenaltyFilters() {
    const q    = document.getElementById('penaltySearch')?.value.toLowerCase() ?? '';
    const col  = document.getElementById('penaltyColFilter')?.value ?? 'all';
    const type = document.getElementById('penaltyTypeFilter')?.value ?? 'all';
    const cols = [
        { key: 'reason' }, { key: 'team_name' }, { key: 'pilot_name' },
        { key: 'event_name' }, { key: 'penalty_value' }
    ];
    const filtered = _penalties.filter(p => {
        const ptype = p.penalty_type ?? p.penaltytype ?? '';
        if (type !== 'all' && ptype !== type) return false;
        if (!q) return true;
        if (col === 'all') return cols.some(c => String(p[c.key] ?? '').toLowerCase().includes(q));
        return String(p[col] ?? '').toLowerCase().includes(q);
    });
    const tbody = document.getElementById('penaltiesBody');
    if (tbody) {
        tbody.innerHTML = filtered.length
            ? filtered.map(renderPenaltyRow).join('')
            : `<tr><td colspan="7" class="empty-state-cell">Sin resultados</td></tr>`;
    }
}

// ── RESULTADOS ───────────────────────────────────────────────────────────────
async function loadResults() {
    _results = await loadTable('/api/results', 'resultsBody', renderResultRow);
    initResultFilters();
}

function renderResultRow(r) {
    const pos    = r.position ?? r.pos ?? '';
    const posCls = Number(pos) === 1 ? 'kpi-accent' : '';
    return `<tr>
        <td><strong class="${posCls}">${esc(String(pos))}</strong></td>
        <td>${esc(r.event_name    ?? r.eventname    ?? r.race_name ?? '')}</td>
        <td>${esc(r.team_name     ?? r.teamname     ?? r.id_team   ?? '')}</td>
        <td>${esc(r.model         ?? r.vehiclemodel ?? '')}</td>
        <td>${esc(r.final_time    ?? r.finaltime    ?? '')}</td>
        <td>${esc(r.penalty_time  ?? r.penaltytime  ?? '')}</td>
        <td>${esc(r.base_points_team  ?? r.basepointsteam  ?? '')}</td>
        <td>${esc(r.base_points_pilot ?? r.basepointspilot ?? '')}</td>
    </tr>`;
}

function initResultFilters() {
    const raceEl = document.getElementById('resultRaceFilter');
    if (raceEl) {
        const races = [...new Set(_results.map(r =>
            r.event_name ?? r.eventname ?? r.race_name
        ).filter(Boolean))].sort();
        raceEl.innerHTML = `<option value="all">Todas las carreras</option>`
            + races.map(r => `<option value="${esc(r)}">${esc(r)}</option>`).join('');
        raceEl.addEventListener('change', applyResultFilters);
    }
    document.getElementById('resultSearch')?.addEventListener('input', applyResultFilters);
    document.getElementById('resultColFilter')?.addEventListener('change', applyResultFilters);
}

function applyResultFilters() {
    const q    = document.getElementById('resultSearch')?.value.toLowerCase() ?? '';
    const col  = document.getElementById('resultColFilter')?.value ?? 'all';
    const race = document.getElementById('resultRaceFilter')?.value ?? 'all';
    const cols = [
        { key: 'team_name' }, { key: 'model' }, { key: 'event_name' },
        { key: 'position'  }, { key: 'final_time' }
    ];
    const filtered = _results.filter(r => {
        const rname = r.event_name ?? r.eventname ?? r.race_name ?? '';
        if (race !== 'all' && rname !== race) return false;
        if (!q) return true;
        if (col === 'all') return cols.some(c => String(r[c.key] ?? '').toLowerCase().includes(q));
        return String(r[col] ?? '').toLowerCase().includes(q);
    });
    const tbody = document.getElementById('resultsBody');
    if (tbody) {
        tbody.innerHTML = filtered.length
            ? filtered.map(renderResultRow).join('')
            : `<tr><td colspan="8" class="empty-state-cell">Sin resultados</td></tr>`;
    }
}

// ── INSCRIPCIONES ─────────────────────────────────────────────────────────────
async function loadInscriptions() {
    _inscriptions = await loadTable('/api/inscriptions', 'inscriptionsBody', renderInscriptionRow);
}

function renderInscriptionRow(r) {
    return `<tr>
        <td>${esc(r.team_name  ?? r.teamname  ?? r.team  ?? '')}</td>
        <td>${esc(r.event_name ?? r.eventname ?? r.race  ?? '')}</td>
        <td>${esc(r.model      ?? r.vehicle   ?? '')}</td>
    </tr>`;
}

// ── ESTADÍSTICAS ─────────────────────────────────────────────────────────────
async function loadStats() {
    try {
        const data = await apiFetch('/api/stats');
        if (data.error) return;

        const teamPoints      = data.team_points         ?? {};
        const penTypes        = data.penalty_types        ?? {};
        const raceParticip    = data.race_participations  ?? {};
        const penByTeam       = data.penalty_by_team      ?? {};
        const ageGroups       = data.age_groups           ?? {};
        const pilotPoints     = data.pilot_points         ?? {};
        const penPointsByTeam = data.penalty_points_team  ?? {};

        renderBarChart('chartTeamPoints',     Object.keys(teamPoints),      Object.values(teamPoints),      'Puntos');
        renderDoughnutChart('chartPenaltyTypes', Object.keys(penTypes),     Object.values(penTypes));
        renderBarChart('chartRaceParticip',   Object.keys(raceParticip),    Object.values(raceParticip),    'Equipos');
        renderBarChart('chartPenByTeam',      Object.keys(penByTeam),       Object.values(penByTeam),       'Valor');
        renderDoughnutChart('chartAgeGroups', Object.keys(ageGroups),       Object.values(ageGroups));
        renderBarChart('chartPilotPoints',    Object.keys(pilotPoints),     Object.values(pilotPoints),     'Puntos');
        renderBarChart('chartPenPointsByTeam',Object.keys(penPointsByTeam), Object.values(penPointsByTeam), 'Pts pen.');

    } catch (e) { console.error('stats error', e); }
}

function renderBarChart(canvasId, labels, values, label) {
    const ctx = document.getElementById(canvasId);
    if (!ctx || !window.Chart) return;
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label,
                data: values,
                backgroundColor: 'rgba(225,6,0,0.7)',
                borderColor: '#e10600',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: {
                    ticks: { color: '#7a7a85', maxRotation: 30, font: { size: 11 } },
                    grid:  { color: 'rgba(255,255,255,0.05)' }
                },
                y: {
                    ticks: { color: '#7a7a85', font: { size: 11 } },
                    grid:  { color: 'rgba(255,255,255,0.05)' },
                    beginAtZero: true
                }
            }
        }
    });
}

function renderDoughnutChart(canvasId, labels, values) {
    const ctx = document.getElementById(canvasId);
    if (!ctx || !window.Chart) return;
    const colors = ['#e10600','#ff6b6b','#ff9a5c','#ffd166','#06d6a0','#118ab2','#7b2d8b','#c77dff'];
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{
                data: values,
                backgroundColor: colors.slice(0, labels.length),
                borderColor: '#18181c',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { color: '#7a7a85', padding: 12, font: { size: 11 } }
                }
            }
        }
    });
}

// ── MI FABRICANTE ─────────────────────────────────────────────────────────────
async function loadManufacturer() {
    try {
        const data = await apiFetch('/api/manufacturer');
        const info = document.getElementById('manufacturerInfo');
        if (info) {
            if (data.error) {
                info.innerHTML = `<p class="text-muted">Sin datos de fabricante</p>`;
            } else {
                info.innerHTML = `<div class="mfr-info">
                    <div class="mfr-name">${esc(data.manufacturer_name ?? data.manufacturername ?? data.name ?? '')}</div>
                    <div class="mfr-country text-muted">${esc(data.manufacturer_country ?? data.manufacturercountry ?? data.country ?? '')}</div>
                </div>`;
            }
        }
        const teams = data.teams ?? [];
        const tbody = document.getElementById('manufacturerTeamsBody');
        if (tbody) {
            tbody.innerHTML = teams.length
                ? teams.map(t => `<tr><td>${esc(t.team_name ?? t.teamname ?? '')}</td><td>${esc(t.mechanics_num ?? t.mechanicsnum ?? '')}</td></tr>`).join('')
                : `<tr><td colspan="2" class="empty-state-cell">Sin equipos asociados</td></tr>`;
        }
    } catch (e) { console.error('manufacturer error', e); }
}

// ══════════════════════════════════════════════════════════════════════════════
// ADMINISTRACIÓN
// ══════════════════════════════════════════════════════════════════════════════
const entityConfig = {
    pilots:        { label: 'Pilotos',         cols: ['pilot_name','pilot_age','pilot_category_name'],                       keys: ['pilot_name','pilot_age','id_pilot_category'],                                                                          idKey: 'id_pilot' },
    teams:         { label: 'Equipos',         cols: ['team_name','mechanics_num','manufacturer_name'],                      keys: ['team_name','mechanics_num','manufacturer_name'],                                                                       idKey: 'id_team' },
    vehicles:      { label: 'Vehículos',       cols: ['model','specifications_url'],                                         keys: ['model','specifications_url'],                                                                                          idKey: 'id_vehicle' },
    races:         { label: 'Carreras',        cols: ['event_name','circuit_name','event_date','event_duration'],            keys: ['event_name','event_date','event_duration','id_circuit'],                                                               idKey: 'id_race' },
    circuits:      { label: 'Circuitos',       cols: ['circuit_name','country','length_km','direction'],                     keys: ['circuit_name','country','length_km','direction'],                                                                      idKey: 'id_circuit' },
    manufacturers: { label: 'Fabricantes',     cols: ['manufacturer_name','manufacturer_country'],                           keys: ['manufacturer_name','manufacturer_country'],                                                                            idKey: 'id_manufacturer' },
    penalties:     { label: 'Penalizaciones',  cols: ['penalty_type','reason','penalty_value','penalty_applies_to','team_name','pilot_name','event_name'], keys: ['penalty_type','reason','penalty_value','penalty_applies_to'],                            idKey: 'id_penalty' },
    results:       { label: 'Resultados',      cols: ['position','final_time','team_name','model','event_name'],             keys: ['position','final_time','penalty_time','base_points_team','base_points_pilot','penalty_points_team','penalty_points_pilot','id_vehicle','id_race','id_team'], idKey: 'id_result' },
    users:         { label: 'Usuarios',        cols: ['username', 'email', 'role', 'team_id', 'password_hash'],              keys: ['username', 'email', 'password_hash', 'team_id', 'role'],                                                               idKey: 'id_user' },
};

function initAdminTabs() {
    // Restaurar tab guardado
    const savedTab = getCookie('wec_admin_tab');
    if (savedTab && entityConfig[savedTab]) {
        adminEntity = savedTab;
        document.querySelectorAll('.admin-tab').forEach(t => {
            const isActive = t.dataset.entity === savedTab;
            t.classList.toggle('active', isActive);
            t.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });
    }

    document.querySelectorAll('.admin-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.admin-tab').forEach(t => {
                t.classList.remove('active');
                t.setAttribute('aria-selected', 'false');
            });
            tab.classList.add('active');
            tab.setAttribute('aria-selected', 'true');
            adminEntity = tab.dataset.entity;
            adminRows = [];
            setCookie('wec_admin_tab', adminEntity, 7); // ← guardar
            loadAdminEntity(adminEntity);
        });
    });
}

async function loadAdminEntity(entity) {
    const cfg   = entityConfig[entity];
    if (!cfg) return;
    const thead = document.getElementById('adminTableHead');
    const tbody = document.getElementById('adminTableBody');
    const title = document.getElementById('adminTableTitle');
    if (!thead || !tbody) return;
    if (title) title.innerHTML = `<i data-lucide="database" width="15" height="15" aria-hidden="true"></i> ${cfg.label}`;

    // Filtro de búsqueda admin
    const adminSearchEl = document.getElementById('adminSearch');
    if (adminSearchEl) { adminSearchEl.value = ''; adminSearchEl.oninput = () => applyAdminFilter(cfg); }

    thead.innerHTML = `<tr>${cfg.cols.map(c => `<th>${colLabel(c)}</th>`).join('')}<th style="width:80px">Acciones</th></tr>`;
    tbody.innerHTML = `<tr><td colspan="${cfg.cols.length + 1}" class="empty-state-cell">Cargando...</td></tr>`;
    if (window.lucide) lucide.createIcons();
    try {
        const rows  = await apiFetch(`/api/admin/list?entity=${entity}`);
        adminRows   = Array.isArray(rows) ? rows : [];
        renderAdminRows(entity, adminRows);
    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="${entityConfig[entity].cols.length + 1}" class="empty-state-cell error-cell">Error al cargar</td></tr>`;
    }
}

function revealHashPrompt(userId) {
    const password = prompt("Ingresa tu contraseña de administrador para ver el hash:");
    if (!password) return;
    
    fetch('/api/admin/reveal-hash', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ target_user_id: userId, admin_password: password })
    })
    .then(res => { if (!res.ok) throw new Error('Error'); return res.json(); })
    .then(data => {
        if (data.hash) {
            document.getElementById(`hash-${userId}`).textContent = data.hash;
        } else {
            alert(data.message || 'Error al obtener el hash');
        }
    })
    .catch(() => alert('Error de conexión'));
}

window.revealHashPrompt = revealHashPrompt; 

function renderAdminRows(entity, rows) {
    const cfg   = entityConfig[entity];
    const tbody = document.getElementById('adminTableBody');
    if (!tbody) return;
    if (!rows.length) {
        tbody.innerHTML = `<tr><td colspan="${cfg.cols.length + 1}" class="empty-state-cell">Sin registros</td></tr>`;
        return;
    }
    // FIX 1: usamos el idKey del registro como data-id, no el índice del array.
    // Así editar/eliminar funciona correctamente aunque haya un filtro activo.
    tbody.innerHTML = rows.map(row => `<tr>
        ${cfg.cols.map(c => {
            if (c === 'password_hash') {
                return `<td>
                    <span id="hash-${row[cfg.idKey]}">********</span>
                    <button class="btn-action" onclick="revealHashPrompt(${row[cfg.idKey]})" title="Ver hash">
                        <i data-lucide="eye" width="13" height="13"></i>
                    </button>
                </td>`;
            }
            return `<td>${esc(row[c] ?? '')}</td>`;
        }).join('')}
        <td><div class="action-btns">
            <button class="btn-action btn-edit"   data-id="${esc(String(row[cfg.idKey] ?? ''))}" aria-label="Editar">  <i data-lucide="pencil"  width="13" height="13" aria-hidden="true"></i></button>
            <button class="btn-action btn-delete" data-id="${esc(String(row[cfg.idKey] ?? ''))}" aria-label="Eliminar"><i data-lucide="trash-2" width="13" height="13" aria-hidden="true"></i></button>
        </div></td>
    </tr>`).join('');
    if (window.lucide) lucide.createIcons();
    tbody.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', () => {
            const row = adminRows.find(r => String(r[cfg.idKey]) === btn.dataset.id);
            if (row) openEditModal(entity, row);
        });
    });
    tbody.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', () => {
            const row = adminRows.find(r => String(r[cfg.idKey]) === btn.dataset.id);
            if (row) confirmDelete(entity, row);
        });
    });
}

function applyAdminFilter(cfg) {
    const q = document.getElementById('adminSearch')?.value.toLowerCase() ?? '';
    if (!q) { renderAdminRows(adminEntity, adminRows); return; }
    const filtered = adminRows.filter(row =>
        cfg.cols.some(c => String(row[c] ?? '').toLowerCase().includes(q))
    );
    renderAdminRows(adminEntity, filtered);
}

function initAdminModal() {
    const btnAdd    = document.getElementById('btnAdminAdd');
    const overlay   = document.getElementById('crudModalOverlay');
    const btnClose  = document.getElementById('crudModalClose');
    const btnCancel = document.getElementById('crudModalCancel');
    const form      = document.getElementById('crudModalForm');
    if (btnAdd)    btnAdd.addEventListener('click', () => openInsertModal(adminEntity));
    if (btnClose)  btnClose.addEventListener('click', closeModal);
    if (btnCancel) btnCancel.addEventListener('click', closeModal);
    if (overlay)   overlay.addEventListener('click', e => { if (e.target === overlay) closeModal(); });
    if (form) form.addEventListener('submit', async e => {
        e.preventDefault();
        const action = form.dataset.action;
        const entity = form.dataset.entity;
        const data   = {};
        new FormData(form).forEach((v, k) => data[k] = v);
        // Validación pilot_name
        if (entity === 'pilots' && data.pilot_name && !data.pilot_name.trim().includes(' ')) {
            showToast('El nombre del piloto debe incluir nombre y apellido', 'error');
            return;
        }
        try {
            const res = await apiFetch('/api/admin/crud', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action, entity, data }) });
            if (res.success) {
                closeModal();
                showToast(action === 'insert' ? 'Registro añadido' : 'Registro actualizado', 'success');
                loadAdminEntity(entity);
                const entityToSection = {
                    pilots: 'pilotos', teams: 'equipos', vehicles: 'vehiculos',
                    races: 'carreras', penalties: 'penalizaciones', results: 'resultados',
                };
                const relatedSection = entityToSection[entity];
                if (relatedSection && loaded.has(relatedSection)) {
                    loadSection(relatedSection); // solo datos, sin tocar el DOM de navegación
                }
            } else {
                showToast(res.message || 'Error al guardar', 'error');
            }
        } catch (err) { showToast('Error de conexión', 'error'); }
    });
}

function openInsertModal(entity) {
    const cfg  = entityConfig[entity];
    if (!cfg) return;
    const form = document.getElementById('crudModalForm');
    form.dataset.action = 'insert';
    form.dataset.entity = entity;
    document.getElementById('crudModalTitle').textContent = `Añadir ${cfg.label}`;
    document.getElementById('crudModalFields').innerHTML = buildModalFields(entity, cfg);
    openModal();
}
function openEditModal(entity, row) {
    const cfg  = entityConfig[entity];
    if (!cfg) return;
    const form = document.getElementById('crudModalForm');
    form.dataset.action = 'update';
    form.dataset.entity = entity;
    document.getElementById('crudModalTitle').textContent = `Editar ${cfg.label}`;
    document.getElementById('crudModalFields').innerHTML =
        `<input type="hidden" name="${cfg.idKey}" value="${esc(row[cfg.idKey] ?? '')}">` +
        buildModalFields(entity, cfg, row);
    openModal();
}
function buildModalFields(entity, cfg, row = null) {
    let html = '';
    cfg.keys.forEach(k => {
        const label = colLabel(k);
        const type = inputType(k);
        const value = row ? esc(row[k] ?? '') : '';
        const placeholder = (k === 'role') ? 'Ej: team-manager, pilot' : label;
        html += `
        <div class="field-group">
            <label class="field-label" for="f_${k}">${label}</label>
            <input class="field-input" id="f_${k}" name="${k}" type="${type}" value="${value}" placeholder="${placeholder}" required>
        </div>`;
    });

    if (entity === 'users') {
        html += `
        <div class="field-group">
            <label class="field-label" for="f_admin_password">Tu contraseña de administrador</label>
            <input class="field-input" id="f_admin_password" name="admin_password" type="password" placeholder="Requerida para guardar" required>
        </div>`;
    }

    return html;
}

async function confirmDelete(entity, row) {
    const cfg = entityConfig[entity];
    if (!cfg) return;
    
    let adminPassword = null;
    if (entity === 'users') {
        adminPassword = prompt("Para eliminar el usuario, ingresa tu contraseña de administrador:");
        if (!adminPassword) return;
    }
    
    if (!confirm(`¿Eliminar este registro de ${cfg.label}?`)) return;
    
    const body = { action: 'delete', entity, data: { [cfg.idKey]: row[cfg.idKey] } };
    if (adminPassword) body.data.admin_password = adminPassword;
    try {
        const res = await apiFetch('/api/admin/crud', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) });
        if (res.success) {
            showToast('Registro eliminado', 'success');
            loadAdminEntity(entity);
            const entityToSection = {
                pilots: 'pilotos', teams: 'equipos', vehicles: 'vehiculos',
                races: 'carreras', penalties: 'penalizaciones', results: 'resultados',
            };
            const relatedSection = entityToSection[entity];
            if (relatedSection && loaded.has(relatedSection)) {
                loadSection(relatedSection); // solo datos, sin tocar el DOM de navegación
            }
        } else {
            showToast(res.message || 'Error al eliminar', 'error');
        }
    } catch (e) { showToast('Error de conexión', 'error'); }
}

function openModal() {
    const overlay = document.getElementById('crudModalOverlay');
    overlay.hidden = false;
    setTimeout(() => overlay.classList.add('visible'), 10);
    if (window.lucide) lucide.createIcons();
    overlay.querySelector('.modal')?.querySelector('input')?.focus();
}

function closeModal() {
    const overlay = document.getElementById('crudModalOverlay');
    overlay.classList.remove('visible');
    setTimeout(() => { overlay.hidden = true; }, 200);
}

// ── Logout ────────────────────────────────────────────────────────────────────
function initLogout() {
    const btn = document.getElementById('logoutBtn');
    if (!btn) return;
    btn.addEventListener('click', async () => {
        try {
            const res  = await fetch('/logout', { method: 'POST' });
            const data = await res.json();
            if (data.success) window.location.href = data.redirect || '/login';
        } catch { window.location.href = '/login'; }
    });
}

// ── Sidebar móvil ─────────────────────────────────────────────────────────────
function initSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    const btnMenu = document.getElementById('btnMenu');
    const btnClose = document.getElementById('sidebarClose');
    if (btnMenu)  btnMenu.addEventListener('click',  openSidebar);
    if (btnClose) btnClose.addEventListener('click', closeSidebar);
    if (overlay)  overlay.addEventListener('click',  closeSidebar);
}
function openSidebar()  { document.getElementById('sidebar')?.classList.add('open'); document.getElementById('overlay')?.classList.add('visible'); document.getElementById('btnMenu')?.setAttribute('aria-expanded','true'); }
function closeSidebar() { document.getElementById('sidebar')?.classList.remove('open'); document.getElementById('overlay')?.classList.remove('visible'); document.getElementById('btnMenu')?.setAttribute('aria-expanded','false'); }

// ── Toast ─────────────────────────────────────────────────────────────────────
function showToast(msg, type = 'info') {
    const container = document.getElementById('toastContainer');
    if (!container) return;
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = msg;
    container.appendChild(toast);
    requestAnimationFrame(() => toast.classList.add('show'));
    setTimeout(() => { toast.classList.remove('show'); setTimeout(() => toast.remove(), 300); }, 3500);
}

// ── API fetch ─────────────────────────────────────────────────────────────────
async function apiFetch(url, options) {
    const res = await fetch(url, options);
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    return res.json();
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function esc(str) {
    if (str === null || str === undefined) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function formatDate(d) {
    if (!d) return '';
    try { return new Intl.DateTimeFormat('es-ES', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(d)); }
    catch { return d; }
}

function colLabel(key) {
    const map = {
        pilot_category_name: 'Categoría', pilot_name: 'Piloto', pilot_age: 'Edad',
        id_pilot_category: 'ID Categoría', category_name: 'Categoría',
        team_name: 'Equipo', mechanics_num: 'Mecánicos', manufacturer_name: 'Fabricante',
        manufacturer_country: 'País', model: 'Modelo', specifications_url: 'Especificaciones',
        event_name: 'Carrera', event_date: 'Fecha', event_duration: 'Duración',
        id_circuit: 'ID Circuito', circuit_name: 'Circuito', country: 'País',
        length_km: 'Longitud (km)', direction: 'Dirección',
        username: 'Usuario', email: 'Email', password_hash: 'Contraseña', team_id: 'ID Equipo',
        penalty_type: 'Tipo', reason: 'Motivo', penalty_value: 'Valor', penalty_applies_to: 'Aplica a',
        position: 'Posición', final_time: 'Tiempo', penalty_time: 'T. Penalización',
        base_points_team: 'Pts Equipo', base_points_pilot: 'Pts Piloto',
        penalty_points_team: 'Pts Pen. Equipo', penalty_points_pilot: 'Pts Pen. Piloto',
        id_vehicle: 'ID Vehículo', circuit_id: 'ID Circuito', id_race: 'ID Carrera',
        id_team: 'ID Equipo', id_pilot: 'ID Piloto', id_manufacturer: 'ID Fabricante',
        id_penalty: 'ID Penalización', id_result: 'ID Resultado', role: 'Rol', id_user: 'ID Usuario',
    };
    return map[key] || key.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
}

function inputType(key) {
    if (key.includes('date'))              return 'datetime-local';
    if (key.includes('age') || key.includes('num') || key === 'position' || key.includes('id') || key.includes('points') || key.includes('value')) return 'number';
    if (key.includes('url'))               return 'url';
    if (key === 'email')                   return 'email';
    if (key === 'password_hash')           return 'password';
    if (key.includes('km'))               return 'number';
    return 'text';
}

})();