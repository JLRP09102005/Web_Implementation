'use strict';
(function () {

    const { userId, role, visibleSections } = window.WEC;

    // ── Navegación ────────────────────────────────────────────
    const navItems    = document.querySelectorAll('.nav-item');
    const sections    = document.querySelectorAll('.section');
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
            const active = btn.dataset.section === sectionId;
            btn.classList.toggle('active', active);
            btn.setAttribute('aria-current', active ? 'page' : 'false');
        });
        sections.forEach(sec =>
            sec.classList.toggle('active', sec.dataset.section === sectionId)
        );
        topbarTitle.textContent = sectionTitles[sectionId] ?? sectionId;
        closeSidebar();
        lazyLoad(sectionId);
    }

    navItems.forEach(btn =>
        btn.addEventListener('click', () => navigateTo(btn.dataset.section))
    );

    // ── Sidebar mobile ────────────────────────────────────────
    const sidebar  = document.getElementById('sidebar');
    const overlay  = document.getElementById('overlay');
    const menuBtn  = document.getElementById('menuBtn');
    const closeBtn = document.getElementById('sidebarClose');

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

    // ── Lazy load ─────────────────────────────────────────────
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
            case 'stats':        loadStats();        break;
            case 'manufacturer': loadManufacturer(); break;
        }
    }

    // Cargar overview al inicio siempre
    lazyLoad('overview');

    // ── Helpers ───────────────────────────────────────────────
    async function apiFetch(url) {
        const res = await fetch(url);
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return res.json();
    }

    function esc(str) {
        if (str == null) return '—';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function buildTable(headers, rows, colMap) {
        if (!rows || rows.length === 0) {
            return `<div class="empty-state">
                <i data-lucide="inbox"></i>
                <span>Sin datos disponibles.</span>
            </div>`;
        }
        const ths = headers.map(h => `<th scope="col">${esc(h)}</th>`).join('');
        const trs = rows.map(row => {
            const tds = colMap.map(col => {
                const val = row[col] ?? '—';
                return `<td>${esc(val)}</td>`;
            }).join('');
            return `<tr>${tds}</tr>`;
        }).join('');
        return `<table><thead><tr>${ths}</tr></thead><tbody>${trs}</tbody></table>`;
    }

    function renderError(wrap, msg) {
        wrap.innerHTML = `<div class="empty-state" style="color:var(--error)">
            <i data-lucide="alert-circle"></i>
            <span>${esc(msg)}</span>
        </div>`;
        if (window.lucide) lucide.createIcons({ nodes: [wrap] });
    }

    function toastShow(msg, type = 'success') {
        let t = document.getElementById('wecToast');
        if (!t) {
            t = document.createElement('div');
            t.id = 'wecToast';
            Object.assign(t.style, {
                position: 'fixed', bottom: '1.25rem', right: '1.25rem',
                background: 'var(--surface-3)', border: '1px solid var(--border)',
                color: 'var(--text)', padding: '0.75rem 1rem',
                borderRadius: 'var(--radius)', fontSize: '0.875rem',
                zIndex: '9999', boxShadow: '0 8px 24px rgba(0,0,0,0.4)',
                transition: 'opacity 0.3s', opacity: '0', maxWidth: '320px',
            });
            document.body.appendChild(t);
        }
        t.textContent = msg;
        t.style.borderColor = type === 'error' ? 'var(--error)' : 'var(--border-strong)';
        t.style.opacity = '1';
        clearTimeout(t._tid);
        t._tid = setTimeout(() => { t.style.opacity = '0'; }, 3500);
    }

    // ── OVERVIEW ──────────────────────────────────────────────
    async function loadOverview() {
        try {
            const [overviewData, racesData, resultsData] = await Promise.all([
                apiFetch('/api/overview'),
                apiFetch('/api/races'),
                visibleSections.includes('results') ? apiFetch('/api/results') : Promise.resolve([]),
            ]);

            // KPIs
            const kpiGrid = document.getElementById('kpiGrid');
            const stats = overviewData.stats ?? overviewData ?? {};
            kpiGrid.innerHTML = `
                <div class="kpi-card">
                    <span class="kpi-label">Carreras totales</span>
                    <span class="kpi-value kpi-accent">${stats.total_races ?? (racesData.length ?? '—')}</span>
                </div>
                <div class="kpi-card">
                    <span class="kpi-label">Pilotos</span>
                    <span class="kpi-value">${stats.total_pilots ?? '—'}</span>
                </div>
                <div class="kpi-card">
                    <span class="kpi-label">Equipos</span>
                    <span class="kpi-value">${stats.total_teams ?? '—'}</span>
                </div>
                <div class="kpi-card">
                    <span class="kpi-label">Penalizaciones</span>
                    <span class="kpi-value kpi-warning">${stats.total_penalties ?? '—'}</span>
                </div>
            `;

            // Próximas carreras
            const now = new Date();
            const upcoming = (Array.isArray(racesData) ? racesData : [])
                .filter(r => new Date(r.event_date) >= now)
                .sort((a, b) => new Date(a.event_date) - new Date(b.event_date))
                .slice(0, 5);

            const upcomingWrap = document.getElementById('upcomingRacesWrap');
            if (upcoming.length === 0) {
                upcomingWrap.innerHTML = `<div class="empty-state">
                    <i data-lucide="calendar-x"></i>
                    <span>No hay carreras próximas.</span>
                </div>`;
            } else {
                upcomingWrap.innerHTML = buildTable(
                    ['Carrera', 'Circuito', 'Fecha', 'Duración'],
                    upcoming,
                    ['event_name', 'circuit_name', 'event_date', 'event_duration']
                );
            }

            // Chart: puntos por equipo
            if (resultsData && resultsData.length > 0 && window.Chart) {
                const teamPoints = {};
                resultsData.forEach(r => {
                    const t = r.id_team ?? r.team_name ?? 'Equipo';
                    teamPoints[t] = (teamPoints[t] ?? 0) + (parseInt(r.base_points_team, 10) || 0);
                });
                const labels = Object.keys(teamPoints).slice(0, 10);
                const data   = labels.map(k => teamPoints[k]);
                const ctx    = document.getElementById('chartTeamPoints');
                if (ctx) {
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels,
                            datasets: [{
                                label: 'Puntos',
                                data,
                                backgroundColor: 'rgba(225,6,0,0.7)',
                                borderColor:     'rgba(225,6,0,1)',
                                borderWidth: 1,
                                borderRadius: 4,
                            }],
                        },
                        options: {
                            responsive: true, maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                x: { ticks: { color: '#7a7a85', font: { size: 11 } }, grid: { color: 'rgba(255,255,255,0.04)' } },
                                y: { ticks: { color: '#7a7a85', font: { size: 11 } }, grid: { color: 'rgba(255,255,255,0.06)' } },
                            },
                        },
                    });
                }
            }

            if (window.lucide) lucide.createIcons();
        } catch (err) {
            renderError(document.getElementById('kpiGrid'), 'Error cargando el panel: ' + err.message);
        }
    }

    // ── PILOTOS ───────────────────────────────────────────────
    async function loadPilots() {
        const wrap = document.getElementById('pilotsTableWrap');
        try {
            const data = await apiFetch('/api/pilots');
            let rows = Array.isArray(data) ? data : (data.data ?? []);
            wrap.innerHTML = buildTable(
                ['ID', 'Nombre', 'Edad', 'Categoría'],
                rows,
                ['id_pilot', 'pilot_name', 'pilot_age', 'pilot_category_name']
            );
            if (window.lucide) lucide.createIcons({ nodes: [wrap] });

            // Búsqueda live
            const search = document.getElementById('pilotSearch');
            if (search) {
                search.addEventListener('input', function () {
                    const q = this.value.toLowerCase();
                    const filtered = rows.filter(r =>
                        (r.pilot_name ?? '').toLowerCase().includes(q) ||
                        (r.pilot_category_name ?? '').toLowerCase().includes(q)
                    );
                    wrap.innerHTML = buildTable(
                        ['ID', 'Nombre', 'Edad', 'Categoría'],
                        filtered,
                        ['id_pilot', 'pilot_name', 'pilot_age', 'pilot_category_name']
                    );
                    if (window.lucide) lucide.createIcons({ nodes: [wrap] });
                });
            }
        } catch (err) {
            renderError(wrap, 'Error cargando pilotos: ' + err.message);
        }
    }

    // ── VEHÍCULOS ─────────────────────────────────────────────
    async function loadVehicles() {
        const wrap = document.getElementById('vehiclesTableWrap');
        try {
            const data = await apiFetch('/api/vehicles');
            const rows = Array.isArray(data) ? data : (data.data ?? []);
            wrap.innerHTML = buildTable(
                ['ID', 'Modelo', 'Ficha técnica'],
                rows.map(r => ({
                    ...r,
                    specifications_url: r.specifications_url
                        ? `<a href="${esc(r.specifications_url)}" target="_blank" rel="noopener" style="color:var(--accent)">Ver ficha</a>`
                        : '—',
                })),
                ['id_vehicle', 'model', 'specifications_url']
            );
            // Permitir HTML en la columna de URL
            wrap.querySelectorAll('td:last-child').forEach((td, i) => {
                const row = rows[i];
                if (row && row.specifications_url) {
                    td.innerHTML = `<a href="${esc(row.specifications_url)}" target="_blank" rel="noopener" style="color:var(--accent)">Ver ficha</a>`;
                }
            });
            if (window.lucide) lucide.createIcons({ nodes: [wrap] });
        } catch (err) {
            renderError(wrap, 'Error cargando vehículos: ' + err.message);
        }
    }

    // ── CARRERAS ──────────────────────────────────────────────
    async function loadRaces() {
        const wrap = document.getElementById('racesTableWrap');
        try {
            const data = await apiFetch('/api/races');
            const rows = Array.isArray(data) ? data : (data.data ?? []);
            wrap.innerHTML = buildTable(
                ['ID', 'Evento', 'Circuito', 'Fecha', 'Duración'],
                rows,
                ['id_race', 'event_name', 'circuit_name', 'event_date', 'event_duration']
            );
            if (window.lucide) lucide.createIcons({ nodes: [wrap] });
        } catch (err) {
            renderError(wrap, 'Error cargando carreras: ' + err.message);
        }
    }

    // ── EQUIPOS ───────────────────────────────────────────────
    async function loadTeams() {
        const wrap = document.getElementById('teamsTableWrap');
        try {
            const data = await apiFetch('/api/teams');
            const rows = Array.isArray(data) ? data : (data.data ?? []);
            wrap.innerHTML = buildTable(
                ['ID', 'Equipo', 'Mecánicos', 'Fabricante'],
                rows,
                ['id_team', 'team_name', 'mechanics_num', 'manufacturer_name']
            );
            if (window.lucide) lucide.createIcons({ nodes: [wrap] });
        } catch (err) {
            renderError(wrap, 'Error cargando equipos: ' + err.message);
        }
    }

    // ── PENALIZACIONES ────────────────────────────────────────
    async function loadPenalties() {
        const wrap = document.getElementById('penaltiesTableWrap');
        try {
            const data = await apiFetch('/api/penalties');
            const rows = Array.isArray(data) ? data : (data.data ?? []);
            // Badge por tipo
            const rowsWithBadge = rows.map(r => {
                const badgeClass = {
                    POINTS: 'badge-yellow',
                    TIME:   'badge-gray',
                    DSQ:    'badge-red',
                    DNF:    'badge-red',
                }[r.penalty_type] ?? 'badge-gray';
                return {
                    ...r,
                    _type_badge: `<span class="badge ${badgeClass}">${esc(r.penalty_type)}</span>`,
                    _applies: r.penalty_applies_to ?? '—',
                };
            });
            wrap.innerHTML = buildTable(
                ['ID', 'Tipo', 'Razón', 'Valor', 'Aplica a', 'Fecha'],
                rowsWithBadge,
                ['id_penalty', '_type_badge', 'reason', 'penalty_value', '_applies', 'created_at']
            );
            // Inyectar HTML de badges
            wrap.querySelectorAll('tbody tr').forEach((tr, i) => {
                const badgeCell = tr.children[1];
                if (badgeCell && rowsWithBadge[i]) {
                    badgeCell.innerHTML = rowsWithBadge[i]._type_badge;
                }
            });
            if (window.lucide) lucide.createIcons({ nodes: [wrap] });
        } catch (err) {
            renderError(wrap, 'Error cargando penalizaciones: ' + err.message);
        }
    }

    // ── RESULTADOS ────────────────────────────────────────────
    async function loadResults() {
        const wrap = document.getElementById('resultsTableWrap');
        try {
            const data = await apiFetch('/api/results');
            const rows = Array.isArray(data) ? data : (data.data ?? []);
            wrap.innerHTML = buildTable(
                ['Pos.', 'Carrera', 'Equipo', 'Tiempo final', 'Tiempo pen.', 'Pts equipo', 'Pts piloto'],
                rows,
                ['position', 'event_name', 'id_team', 'final_time', 'penalty_time', 'base_points_team', 'base_points_pilot']
            );
            if (window.lucide) lucide.createIcons({ nodes: [wrap] });
        } catch (err) {
            renderError(wrap, 'Error cargando resultados: ' + err.message);
        }
    }

    // ── ESTADÍSTICAS ──────────────────────────────────────────
    async function loadStats() {
        try {
            const [overviewData, penaltiesData, resultsData] = await Promise.all([
                apiFetch('/api/overview'),
                apiFetch('/api/penalties'),
                apiFetch('/api/results'),
            ]);

            const stats = overviewData.stats ?? overviewData ?? {};
            const penalties = Array.isArray(penaltiesData) ? penaltiesData : [];
            const results   = Array.isArray(resultsData)   ? resultsData   : [];

            // KPIs estadísticas
            const totalPoints = results.reduce((s, r) => s + (parseInt(r.base_points_team, 10) || 0), 0);
            const avgPos      = results.length
                ? (results.reduce((s, r) => s + (parseInt(r.position, 10) || 0), 0) / results.length).toFixed(1)
                : '—';

            document.getElementById('statsKpiGrid').innerHTML = `
                <div class="kpi-card">
                    <span class="kpi-label">Total puntos acumulados</span>
                    <span class="kpi-value kpi-success">${totalPoints.toLocaleString()}</span>
                </div>
                <div class="kpi-card">
                    <span class="kpi-label">Posición media</span>
                    <span class="kpi-value">${avgPos}</span>
                </div>
                <div class="kpi-card">
                    <span class="kpi-label">Penalizaciones totales</span>
                    <span class="kpi-value kpi-warning">${penalties.length}</span>
                </div>
            `;

            // Gráfica de penalizaciones por tipo
            if (penalties.length > 0 && window.Chart) {
                const typeCounts = {};
                penalties.forEach(p => {
                    typeCounts[p.penalty_type] = (typeCounts[p.penalty_type] ?? 0) + 1;
                });
                const labels = Object.keys(typeCounts);
                const data   = labels.map(k => typeCounts[k]);
                const colors = ['rgba(225,6,0,0.8)', 'rgba(245,158,11,0.8)', 'rgba(100,100,120,0.8)', 'rgba(34,197,94,0.8)'];
                const ctx    = document.getElementById('chartPenaltyTypes');
                if (ctx) {
                    new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels,
                            datasets: [{
                                data,
                                backgroundColor: colors.slice(0, labels.length),
                                borderColor: 'var(--surface)',
                                borderWidth: 3,
                            }],
                        },
                        options: {
                            responsive: true, maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: { color: '#7a7a85', font: { size: 12 }, padding: 16 },
                                },
                            },
                        },
                    });
                }
            }

            if (window.lucide) lucide.createIcons();
        } catch (err) {
            const grid = document.getElementById('statsKpiGrid');
            if (grid) renderError(grid, 'Error cargando estadísticas: ' + err.message);
        }
    }

    // ── MI FABRICANTE ─────────────────────────────────────────
    async function loadManufacturer() {
        const wrap = document.getElementById('manufacturerContent');
        try {
            const data = await apiFetch('/api/manufacturer');
            const info = Array.isArray(data) ? data[0] : data;
            if (!info) {
                wrap.innerHTML = `<div class="empty-state"><i data-lucide="factory"></i><span>No se encontró información del fabricante.</span></div>`;
                if (window.lucide) lucide.createIcons({ nodes: [wrap] });
                return;
            }
            wrap.innerHTML = `
                <div class="card">
                    <div class="card-header">
                        <h3><i data-lucide="factory" aria-hidden="true"></i> ${esc(info.manufacturer_name ?? '—')}</h3>
                        <span class="badge badge-gray">${esc(info.manufacturer_country ?? '—')}</span>
                    </div>
                    <div style="padding:1.25rem; display:grid; grid-template-columns: repeat(auto-fill, minmax(200px,1fr)); gap:1rem;">
                        <div class="kpi-card">
                            <span class="kpi-label">Fabricante</span>
                            <span class="kpi-value" style="font-size:1.1rem;">${esc(info.manufacturer_name ?? '—')}</span>
                        </div>
                        <div class="kpi-card">
                            <span class="kpi-label">País</span>
                            <span class="kpi-value" style="font-size:1.1rem;">${esc(info.manufacturer_country ?? '—')}</span>
                        </div>
                        <div class="kpi-card">
                            <span class="kpi-label">ID</span>
                            <span class="kpi-value" style="font-size:1.1rem;">${esc(info.id_manufacturer ?? '—')}</span>
                        </div>
                    </div>
                </div>
            `;
            if (window.lucide) lucide.createIcons({ nodes: [wrap] });
        } catch (err) {
            renderError(wrap, 'Error cargando fabricante: ' + err.message);
        }
    }

})();