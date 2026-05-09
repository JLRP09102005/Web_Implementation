'use strict';

(function () {

// ── Estado ───────────────────────────────────────────────────
const WEC      = window.WEC || { userId: 0, role: '', visible: [] };
const loaded   = new Set();
let   adminEntity = 'pilots';
let   adminRows   = [];

// ── Init ─────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();

    initNav();
    initSidebar();
    initLogout();

    // Cargar panel al inicio
    loadSection('panel');

    // Admin tabs
    if (WEC.visible.includes('administracion')) {
        initAdminTabs();
        initAdminModal();
    }
});

// ── Navegación ───────────────────────────────────────────────
function initNav() {
    document.querySelectorAll('.nav-item').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.section;
            showSection(id);
            closeSidebar();
        });
    });
}

function showSection(id) {
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

    if (!loaded.has(id)) {
        loadSection(id);
        loaded.add(id);
    }
}

// ── Carga de datos por sección ────────────────────────────────
function loadSection(id) {
    switch (id) {
        case 'panel':          loadOverview();      break;
        case 'carreras':       loadTable('/api/races',       'racesBody',        renderRaceRow);         break;
        case 'pilotos':        loadTable('/api/pilots',      'pilotsBody',       renderPilotRow);        break;
        case 'equipos':        loadTable('/api/teams',       'teamsBody',        renderTeamRow);         break;
        case 'vehiculos':      loadTable('/api/vehicles',    'vehiclesBody',     renderVehicleRow);      break;
        case 'penalizaciones': loadTable('/api/penalties',   'penaltiesBody',    renderPenaltyRow);      break;
        case 'resultados':     loadTable('/api/results',     'resultsBody',      renderResultRow);       break;
        case 'inscripciones':  loadTable('/api/inscriptions','inscriptionsBody', renderInscriptionRow);  break;
        case 'estadisticas':   loadStats();          break;
        case 'fabricante':     loadManufacturer();   break;
        case 'administracion': loadAdminEntity(adminEntity); break;
    }
}

// ── Overview / Panel ─────────────────────────────────────────
async function loadOverview() {
    try {
        const data = await apiFetch('/api/overview');
        if (data.error) return;

        const kpis = [
            { label: 'Carreras',       value: data.total_races,     cls: 'kpi-accent' },
            { label: 'Pilotos',        value: data.total_pilots,    cls: '' },
            { label: 'Equipos',        value: data.total_teams,     cls: '' },
            { label: 'Vehículos',      value: data.total_vehicles,  cls: '' },
            { label: 'Penalizaciones', value: data.total_penalties, cls: 'kpi-warning' },
        ].filter(k => k.value !== undefined && k.value !== null && k.value !== '');

        const grid = document.getElementById('kpiGrid');
        if (grid) {
            grid.innerHTML = kpis.map(k => `
                <div class="kpi-card">
                    <span class="kpi-label">${k.label}</span>
                    <span class="kpi-value ${k.cls}">${k.value}</span>
                </div>`).join('');
        }

        const races = data.races || [];
        const tbody = document.getElementById('overviewRacesBody');
        if (tbody) {
            tbody.innerHTML = races.length
                ? races.map(r => renderRaceRow(r)).join('')
                : '<tr><td colspan="5" class="empty-state-cell">Sin carreras registradas</td></tr>';
        }
    } catch (e) {
        console.error('overview error', e);
    }
}

// ── Loader genérico de tablas ─────────────────────────────────
async function loadTable(endpoint, tbodyId, renderFn) {
    const tbody = document.getElementById(tbodyId);
    if (!tbody) return;

    try {
        const rows = await apiFetch(endpoint);
        if (!Array.isArray(rows) || rows.length === 0) {
            tbody.innerHTML = `<tr><td colspan="99" class="empty-state-cell">Sin datos disponibles</td></tr>`;
            return;
        }
        tbody.innerHTML = rows.map(renderFn).join('');
        if (window.lucide) lucide.createIcons();
    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="99" class="empty-state-cell error-cell">Error al cargar datos</td></tr>`;
        console.error(endpoint, e);
    }
}

// ── Row renderers ─────────────────────────────────────────────
function renderRaceRow(r) {
    return `<tr>
        <td>${esc(r.event_name ?? r.race_name ?? '—')}</td>
        <td>${esc(r.circuit_name ?? '—')}</td>
        <td>${esc(r.country ?? '—')}</td>
        <td>${formatDate(r.event_date ?? r.date)}</td>
        <td>${esc(r.event_duration ?? r.duration ?? '—')}</td>
    </tr>`;
}

function renderPilotRow(r) {
    return `<tr>
        <td>${esc(r.pilot_name ?? r.name ?? '—')}</td>
        <td>${esc(r.pilot_age ?? r.age ?? '—')}</td>
        <td>${esc(r.category_name ?? r.pilot_category ?? r.category ?? '—')}</td>
    </tr>`;
}

function renderTeamRow(r) {
    return `<tr>
        <td>${esc(r.team_name ?? r.name ?? '—')}</td>
        <td>${esc(r.manufacturer_name ?? r.manufacturer ?? '—')}</td>
        <td>${esc(r.mechanic_num ?? r.mechanics ?? '—')}</td>
    </tr>`;
}

function renderVehicleRow(r) {
    const specs = r.specifications_url
        ? `<a href="${esc(r.specifications_url)}" target="_blank" rel="noopener" class="link-specs">Ver specs</a>`
        : '—';
    return `<tr>
        <td>${esc(r.model ?? '—')}</td>
        <td>${specs}</td>
    </tr>`;
}

function renderPenaltyRow(r) {
    return `<tr>
        <td><span class="badge badge-red">${esc(r.penalty_type ?? '—')}</span></td>
        <td>${esc(r.reason ?? '—')}</td>
        <td>${esc(r.penalty_value ?? '—')}</td>
        <td>${esc(r.penalty_applies_to ?? '—')}</td>
    </tr>`;
}

function renderResultRow(r) {
    const pos = r.position ?? r.pos ?? '—';
    const posCls = pos === 1 ? 'kpi-accent' : '';
    return `<tr>
        <td><strong class="${posCls}">${pos}</strong></td>
        <td>${esc(r.vehicle_model ?? r.model ?? r.id_vehicle ?? '—')}</td>
        <td>${esc(r.final_time ?? '—')}</td>
        <td>${esc(r.base_points_team ?? '—')}</td>
        <td>${esc(r.base_points_pilot ?? '—')}</td>
    </tr>`;
}

function renderInscriptionRow(r) {
    return `<tr>
        <td>${esc(r.team_name ?? r.team ?? '—')}</td>
        <td>${esc(r.event_name ?? r.race ?? '—')}</td>
        <td>${esc(r.model ?? r.vehicle ?? '—')}</td>
    </tr>`;
}

// ── Estadísticas ─────────────────────────────────────────────
async function loadStats() {
    try {
        const data = await apiFetch('/api/stats');
        if (data.error) return;

        const teamPoints  = data.team_points  || {};
        const penTypes    = data.penalty_types || {};

        renderBarChart('chartTeamPoints', Object.keys(teamPoints), Object.values(teamPoints), 'Puntos');
        renderDoughnutChart('chartPenaltyTypes', Object.keys(penTypes), Object.values(penTypes));
    } catch (e) {
        console.error('stats error', e);
    }
}

function renderBarChart(canvasId, labels, values, label) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return;
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
                borderRadius: 4,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                x: { ticks: { color: '#7a7a85' }, grid: { color: 'rgba(255,255,255,0.05)' } },
                y: { ticks: { color: '#7a7a85' }, grid: { color: 'rgba(255,255,255,0.05)' }, beginAtZero: true }
            }
        }
    });
}

function renderDoughnutChart(canvasId, labels, values) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return;
    const colors = ['#e10600','#ff6b6b','#ff9a5c','#ffd166','#06d6a0','#118ab2','#7b2d8b','#c77dff'];
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{
                data: values,
                backgroundColor: colors.slice(0, labels.length),
                borderColor: '#18181c',
                borderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom', labels: { color: '#7a7a85', padding: 16 } }
            }
        }
    });
}

// ── Mi Fabricante ─────────────────────────────────────────────
async function loadManufacturer() {
    try {
        const data = await apiFetch('/api/manufacturer');
        const info = document.getElementById('manufacturerInfo');
        if (info) {
            if (data.error) {
                info.innerHTML = `<p class="text-muted">Sin datos de fabricante</p>`;
            } else {
                info.innerHTML = `
                    <div class="mfr-info">
                        <div class="mfr-name">${esc(data.manufacturer_name ?? data.name ?? '—')}</div>
                        <div class="mfr-country text-muted">${esc(data.manufacturer_country ?? data.country ?? '—')}</div>
                    </div>`;
            }
        }
        const teams = data.teams || [];
        const tbody = document.getElementById('manufacturerTeamsBody');
        if (tbody) {
            tbody.innerHTML = teams.length
                ? teams.map(t => `<tr><td>${esc(t.team_name??'—')}</td><td>${esc(t.mechanic_num??'—')}</td></tr>`).join('')
                : '<tr><td colspan="2" class="empty-state-cell">Sin equipos asociados</td></tr>';
        }
    } catch (e) {
        console.error('manufacturer error', e);
    }
}

// ── Administración ────────────────────────────────────────────
const entityConfig = {
    pilots:        { label: 'Pilotos',       cols: ['pilot_name','pilot_age','category_name'],     keys: ['pilot_name','pilot_age','category_name'],     idKey: 'id_pilot' },
    teams:         { label: 'Equipos',       cols: ['team_name','manufacturer_name','mechanic_num'], keys: ['team_name','mechanic_num','manufacturer_name'], idKey: 'id_team' },
    vehicles:      { label: 'Vehículos',     cols: ['model','specifications_url'],                 keys: ['model','specifications_url'],                 idKey: 'id_vehicle' },
    races:         { label: 'Carreras',      cols: ['event_name','circuit_name','event_date','event_duration'], keys: ['event_name','event_date','event_duration','id_circuit'], idKey: 'id_race' },
    circuits:      { label: 'Circuitos',     cols: ['circuit_name','country','length_km','direction'], keys: ['circuit_name','country','length_km','direction'], idKey: 'circuit_id' },
    manufacturers: { label: 'Fabricantes',   cols: ['manufacturer_name','manufacturer_country'],   keys: ['manufacturer_name','manufacturer_country'],   idKey: 'id_manufacturer' },
    penalties:     { label: 'Penalizaciones',cols: ['penalty_type','reason','penalty_value','penalty_applies_to'], keys: ['penalty_type','reason','penalty_value','penalty_applies_to'], idKey: 'id_penalty' },
    results:       { label: 'Resultados',    cols: ['position','final_time','id_vehicle'],         keys: ['position','final_time','penalty_time','base_points_team','base_points_pilot','penalty_points_team','penalty_points_pilot','id_vehicle'], idKey: 'id_result' },
};

function initAdminTabs() {
    document.querySelectorAll('.admin-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.admin-tab').forEach(t => {
                t.classList.remove('active');
                t.setAttribute('aria-selected', 'false');
            });
            tab.classList.add('active');
            tab.setAttribute('aria-selected', 'true');
            adminEntity = tab.dataset.entity;
            adminRows   = [];
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

    thead.innerHTML = `<tr>${cfg.cols.map(c => `<th>${colLabel(c)}</th>`).join('')}<th style="width:80px">Acciones</th></tr>`;
    tbody.innerHTML = `<tr><td colspan="${cfg.cols.length + 1}" class="empty-state-cell">Cargando...</td></tr>`;

    if (window.lucide) lucide.createIcons();

    try {
        const rows = await apiFetch(`/api/admin/list?entity=${entity}`);
        adminRows = Array.isArray(rows) ? rows : [];

        if (!adminRows.length) {
            tbody.innerHTML = `<tr><td colspan="${cfg.cols.length + 1}" class="empty-state-cell">Sin registros</td></tr>`;
            return;
        }

        tbody.innerHTML = adminRows.map((row, idx) => `
            <tr>
                ${cfg.cols.map(c => `<td>${esc(row[c] ?? '—')}</td>`).join('')}
                <td>
                    <div class="action-btns">
                        <button class="btn-action btn-edit" data-idx="${idx}" aria-label="Editar">
                            <i data-lucide="pencil" width="13" height="13" aria-hidden="true"></i>
                        </button>
                        <button class="btn-action btn-delete" data-idx="${idx}" aria-label="Eliminar">
                            <i data-lucide="trash-2" width="13" height="13" aria-hidden="true"></i>
                        </button>
                    </div>
                </td>
            </tr>`).join('');

        if (window.lucide) lucide.createIcons();

        tbody.querySelectorAll('.btn-edit').forEach(btn =>
            btn.addEventListener('click', () => openEditModal(entity, adminRows[+btn.dataset.idx]))
        );
        tbody.querySelectorAll('.btn-delete').forEach(btn =>
            btn.addEventListener('click', () => confirmDelete(entity, adminRows[+btn.dataset.idx]))
        );
    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="${entityConfig[entity].cols.length + 1}" class="empty-state-cell error-cell">Error al cargar</td></tr>`;
    }
}

function initAdminModal() {
    const btnAdd  = document.getElementById('btnAdminAdd');
    const overlay = document.getElementById('crudModalOverlay');
    const btnClose  = document.getElementById('crudModalClose');
    const btnCancel = document.getElementById('crudModalCancel');
    const form    = document.getElementById('crudModalForm');

    if (btnAdd) btnAdd.addEventListener('click', () => openInsertModal(adminEntity));
    if (btnClose)  btnClose.addEventListener('click',  closeModal);
    if (btnCancel) btnCancel.addEventListener('click', closeModal);
    if (overlay) overlay.addEventListener('click', e => { if (e.target === overlay) closeModal(); });

    if (form) {
        form.addEventListener('submit', async e => {
            e.preventDefault();
            const action = form.dataset.action;
            const entity = form.dataset.entity;
            const data   = {};
            new FormData(form).forEach((v, k) => data[k] = v);

            try {
                const res = await apiFetch('/api/admin/crud', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action, entity, data })
                });

                if (res.success) {
                    closeModal();
                    showToast(action === 'insert' ? 'Registro añadido' : 'Registro actualizado', 'success');
                    adminRows = [];
                    loadAdminEntity(entity);
                } else {
                    showToast(res.message || 'Error al guardar', 'error');
                }
            } catch (err) {
                showToast('Error de conexión', 'error');
            }
        });
    }
}

function openInsertModal(entity) {
    const cfg = entityConfig[entity];
    if (!cfg) return;
    const form = document.getElementById('crudModalForm');
    form.dataset.action = 'insert';
    form.dataset.entity = entity;
    document.getElementById('crudModalTitle').textContent = `Añadir ${cfg.label}`;
    document.getElementById('crudModalFields').innerHTML = cfg.keys.map(k =>
        `<div class="field-group">
            <label class="field-label" for="f_${k}">${colLabel(k)}</label>
            <input class="field-input" id="f_${k}" name="${k}" type="${inputType(k)}" placeholder="${colLabel(k)}" required>
        </div>`
    ).join('');
    openModal();
}

function openEditModal(entity, row) {
    const cfg = entityConfig[entity];
    if (!cfg) return;
    const form = document.getElementById('crudModalForm');
    form.dataset.action  = 'update';
    form.dataset.entity  = entity;
    document.getElementById('crudModalTitle').textContent = `Editar ${cfg.label}`;
    document.getElementById('crudModalFields').innerHTML =
        `<input type="hidden" name="${cfg.idKey}" value="${esc(row[cfg.idKey] ?? '')}">` +
        cfg.keys.map(k =>
            `<div class="field-group">
                <label class="field-label" for="f_${k}">${colLabel(k)}</label>
                <input class="field-input" id="f_${k}" name="${k}" type="${inputType(k)}" value="${esc(row[k] ?? '')}" required>
            </div>`
        ).join('');
    openModal();
}

async function confirmDelete(entity, row) {
    const cfg = entityConfig[entity];
    if (!cfg) return;
    if (!confirm(`¿Eliminar este registro de ${cfg.label}?`)) return;

    try {
        const res = await apiFetch('/api/admin/crud', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', entity, data: { [cfg.idKey]: row[cfg.idKey] } })
        });
        if (res.success) {
            showToast('Registro eliminado', 'success');
            adminRows = [];
            loadAdminEntity(entity);
        } else {
            showToast(res.message || 'Error al eliminar', 'error');
        }
    } catch (e) {
        showToast('Error de conexión', 'error');
    }
}

function openModal() {
    const overlay = document.getElementById('crudModalOverlay');
    overlay.hidden = false;
    setTimeout(() => overlay.classList.add('visible'), 10);
    if (window.lucide) lucide.createIcons();
    overlay.querySelector('.modal').querySelector('input')?.focus();
}

function closeModal() {
    const overlay = document.getElementById('crudModalOverlay');
    overlay.classList.remove('visible');
    setTimeout(() => { overlay.hidden = true; }, 200);
}

// ── Logout ───────────────────────────────────────────────────
function initLogout() {
    const btn = document.getElementById('logoutBtn');
    if (!btn) return;
    btn.addEventListener('click', async () => {
        try {
            const res = await fetch('/logout', { method: 'POST' });
            const data = await res.json();
            if (data.success) window.location.href = data.redirect || '/login';
        } catch {
            window.location.href = '/login';
        }
    });
}

// ── Sidebar móvil ─────────────────────────────────────────────
function initSidebar() {
    const sidebar  = document.getElementById('sidebar');
    const overlay  = document.getElementById('overlay');
    const btnMenu  = document.getElementById('btnMenu');
    const btnClose = document.getElementById('sidebarClose');

    if (btnMenu)  btnMenu.addEventListener('click',  openSidebar);
    if (btnClose) btnClose.addEventListener('click', closeSidebar);
    if (overlay)  overlay.addEventListener('click',  closeSidebar);
}

function openSidebar() {
    document.getElementById('sidebar')?.classList.add('open');
    document.getElementById('overlay')?.classList.add('visible');
    document.getElementById('btnMenu')?.setAttribute('aria-expanded', 'true');
}

function closeSidebar() {
    document.getElementById('sidebar')?.classList.remove('open');
    document.getElementById('overlay')?.classList.remove('visible');
    document.getElementById('btnMenu')?.setAttribute('aria-expanded', 'false');
}

// ── Toast ─────────────────────────────────────────────────────
function showToast(msg, type = 'info') {
    const container = document.getElementById('toastContainer');
    if (!container) return;
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = msg;
    container.appendChild(toast);
    requestAnimationFrame(() => toast.classList.add('show'));
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3500);
}

// ── API fetch ─────────────────────────────────────────────────
async function apiFetch(url, options = {}) {
    const res = await fetch(url, options);
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    return res.json();
}

// ── Helpers ───────────────────────────────────────────────────
function esc(str) {
    if (str === null || str === undefined) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function formatDate(d) {
    if (!d) return '—';
    try {
        return new Intl.DateTimeFormat('es-ES', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(d));
    } catch { return d; }
}

function colLabel(key) {
    const map = {
        pilot_name: 'Nombre', pilot_age: 'Edad', id_pilot_category: 'ID Categoría', category_name: 'Categoría',
        team_name: 'Equipo', mechanic_num: 'Mecánicos', id_manufacturer: 'ID Fabricante', manufacturer_name: 'Fabricante',
        manufacturer_country: 'País', model: 'Modelo', specifications_url: 'Especificaciones',
        event_name: 'Nombre', event_date: 'Fecha', event_duration: 'Duración', id_circuit: 'ID Circuito',
        circuit_name: 'Circuito', country: 'País', length_km: 'Longitud (km)', direction: 'Dirección',
        username: 'Usuario', email: 'Email', password_hash: 'Contraseña', team_id: 'ID Equipo',
        penalty_type: 'Tipo', reason: 'Motivo', penalty_value: 'Valor', penalty_applies_to: 'Aplica a',
        position: 'Posición', final_time: 'Tiempo', penalty_time: 'T. Penalización',
        base_points_team: 'Pts Equipo', base_points_pilot: 'Pts Piloto',
        penalty_points_team: 'Pts Pen. Equipo', penalty_points_pilot: 'Pts Pen. Piloto', id_vehicle: 'ID Vehículo',
        circuit_id: 'ID Circuito', id_race: 'ID Carrera', id_team: 'ID Equipo', id_pilot: 'ID Piloto',
        id_manufacturer: 'ID Fabricante', id_penalty: 'ID Penalización', id_result: 'ID Resultado',
    };
    return map[key] || key.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
}

function inputType(key) {
    if (key.includes('date'))  return 'datetime-local';
    if (key.includes('age') || key.includes('num') || key.includes('id_') || key === 'position') return 'number';
    if (key.includes('url'))   return 'url';
    if (key === 'email')       return 'email';
    if (key === 'password_hash') return 'password';
    if (key.includes('km') || key.includes('value')) return 'number';
    return 'text';
}

})();