'use strict';
(function () {
    const { userId, role } = window.WEC;

    // ── Navegación ──────────────────────────────────────────
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
        navItems.forEach(btn => btn.classList.toggle('active', btn.dataset.section === sectionId));
        sections.forEach(sec => sec.classList.toggle('active', sec.dataset.section === sectionId));
        topbarTitle.textContent = sectionTitles[sectionId] ?? sectionId;
        closeSidebar();
        lazyLoad(sectionId);
    }

    navItems.forEach(btn => btn.addEventListener('click', () => navigateTo(btn.dataset.section)));

    // ── Sidebar mobile ──────────────────────────────────────
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    const menuBtn = document.getElementById('menuBtn');
    const closeBtn = document.getElementById('sidebarClose');

    function openSidebar()  {
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

    // ── Logout ──────────────────────────────────────────────
    document.getElementById('logoutBtn').addEventListener('click', async () => {
        try {
            const res  = await fetch('/logout', { method: 'POST' });
            const data = await res.json();
            if (data.success) window.location.href = data.redirect ?? '/login';
        } catch {
            window.location.href = '/login';
        }
    });

    // ── Lucide ──────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide) lucide.createIcons();
    });

    // ── Lazy load ───────────────────────────────────────────
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

    // ── Helpers ─────────────────────────────────────────────
    async function apiFetch(url) {
        const res = await fetch(url);
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return res.json();
    }

    function buildTable(headers, rows, colMap) {
        if (!rows || rows.length === 0) {
            return `<div class="empty-state"><p>Sin datos disponibles.</p></div>`;
        }
        const ths = headers.map(h => `<th>${h}</th>`).join('');
        const trs = rows.map(row => {
            const tds = colMap.map(key => {
                const val = row[key] ?? '—';
                return `<td>${escHtml(String(val))}</td>`;
            }).join('');
            return `<tr>${tds}</tr>`;
        }).join('');
        return `<table><thead><tr>${ths}</tr></thead><tbody>${trs}</tbody></table>`;
    }

    function escHtml(str) {
        return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function renderError(container, msg = 'Error cargando datos.') {
        container.innerHTML = `<div class="empty-state"><p>${msg}</p></div>`;
    }

    function kpiCard(label, value, cls = '') {
        return `<div class="kpi-card">
            <span class="kpi-label">${label}</span>
            <span class="kpi-value ${cls}">${value}</span>
        </div>`;
    }

    function formatDate(dateStr) {
        if (!dateStr) return '—';
        const d = new Date(dateStr);
        return isNaN(d) ? dateStr : d.toLocaleDateString('es-ES', { day:'2-digit', month:'short', year:'numeric' });
    }

    // ── Overview ────────────────────────────────────────────
    async function loadOverview() {
        const kpiGrid      = document.getElementById('kpiGrid');
        const upcomingRaces = document.getElementById('upcomingRaces');

        try {
            const data = await apiFetch(`/api/overview`);

            kpiGrid.innerHTML = [
                kpiCard('Total Carreras',  data.total_races  ?? 0, 'kpi-accent'),
                kpiCard('Total Pilotos',   data.total_pilots ?? 0),
                kpiCard('Total Equipos',   data.total_teams  ?? 0),
            ].join('');

            const upcoming = (data.races ?? []).filter(r => new Date(r.event_date) >= new Date()).slice(0, 5);
            if (upcoming.length === 0) {
                upcomingRaces.innerHTML = `<div class="empty-state"><p>No hay carreras próximas.</p></div>`;
            } else {
                upcomingRaces.innerHTML = buildTable(
                    ['Evento', 'Fecha', 'Circuito', 'País', 'Duración'],
                    upcoming,
                    ['event_name', 'event_date', 'circuit_name', 'country', 'event_duration']
                );
                // Formatear fechas en la tabla ya renderizada
                upcomingRaces.querySelectorAll('tbody td:nth-child(2)').forEach(td => {
                    td.textContent = formatDate(td.textContent);
                });
            }

            if (window.lucide) lucide.createIcons();
        } catch (e) {
            kpiGrid.innerHTML = '';
            renderError(upcomingRaces, 'No se pudo cargar el resumen.');
        }
    }

    // ── Pilotos ─────────────────────────────────────────────
    async function loadPilots() {
        const el = document.getElementById('pilotsTable');
        try {
            const data = await apiFetch(`/api/pilots`);
            el.innerHTML = buildTable(
                ['Nombre', 'Categoría'],
                data,
                ['pilot_name', 'pilot_category_name']
            );
            if (window.lucide) lucide.createIcons();
        } catch { renderError(el); }
    }

    // ── Vehículos ────────────────────────────────────────────
    async function loadVehicles() {
        const el = document.getElementById('vehiclesTable');
        try {
            const data = await apiFetch(`/api/vehicles`);
            const headers = role === 'readonly-public'
                ? ['Modelo', 'Equipo']
                : ['ID', 'Modelo', 'Equipo', 'Especificaciones'];
            const colMap = role === 'readonly-public'
                ? ['model', 'team_name']
                : ['id_vehicle', 'model', 'team_name', 'specifications_url'];
            el.innerHTML = buildTable(headers, data, colMap);
            if (window.lucide) lucide.createIcons();
        } catch { renderError(el); }
    }

    // ── Carreras ─────────────────────────────────────────────
    async function loadRaces() {
        const el = document.getElementById('racesTable');
        try {
            const data = await apiFetch(`/api/races`);
            el.innerHTML = buildTable(
                ['Evento', 'Fecha', 'Circuito', 'País', 'Duración'],
                data,
                ['event_name', 'event_date', 'circuit_name', 'country', 'event_duration']
            );
            el.querySelectorAll('tbody td:nth-child(2)').forEach(td => {
                td.textContent = formatDate(td.textContent);
            });
            if (window.lucide) lucide.createIcons();
        } catch { renderError(el); }
    }

    // ── Equipos ──────────────────────────────────────────────
    async function loadTeams() {
        const el = document.getElementById('teamsTable');
        try {
            const data = await apiFetch(`/api/teams`);
            el.innerHTML = buildTable(
                ['Equipo', 'Fabricante', 'Mecánicos'],
                data,
                ['team_name', 'manufacturer_name', 'mechanics_num']
            );
            if (window.lucide) lucide.createIcons();
        } catch { renderError(el); }
    }

    // ── Penalizaciones ───────────────────────────────────────
    async function loadPenalties() {
        const el = document.getElementById('penaltiesTable');
        try {
            const data = await apiFetch(`/api/penalties`);
            el.innerHTML = buildTable(
                ['Tipo', 'Descripción', 'Puntos', 'Tiempo (s)', 'Fecha'],
                data,
                ['penalty_type', 'description', 'points_deduction', 'time_deduction', 'created_at']
            );
            el.querySelectorAll('tbody td:nth-child(5)').forEach(td => {
                td.textContent = formatDate(td.textContent);
            });
            if (window.lucide) lucide.createIcons();
        } catch { renderError(el); }
    }

    // ── Resultados ───────────────────────────────────────────
    async function loadResults() {
        const el = document.getElementById('resultsTable');
        try {
            const data = await apiFetch(`/api/results`);
            el.innerHTML = buildTable(
                ['Posición', 'Piloto', 'Equipo', 'Vehículo', 'Tiempo Final', 'Penalización', 'Puntos'],
                data,
                ['position', 'pilot_name', 'team_name', 'vehicle_model', 'final_time', 'penalty_time', 'total_points']
            );
            if (window.lucide) lucide.createIcons();
        } catch { renderError(el); }
    }

    // ── Stats ────────────────────────────────────────────────
    async function loadStats() {
        const el = document.getElementById('statsKpi');
        try {
            const data = await apiFetch(`/api/overview`);
            el.innerHTML = [
                kpiCard('Total Carreras',    data.total_races    ?? 0, 'kpi-accent'),
                kpiCard('Total Pilotos',     data.total_pilots   ?? 0),
                kpiCard('Total Equipos',     data.total_teams    ?? 0),
                kpiCard('Total Vehículos',   data.total_vehicles ?? 0),
                kpiCard('Penalizaciones',    data.total_penalties ?? 0, 'kpi-warning'),
            ].join('');
        } catch { renderError(el, 'No se pudieron cargar estadísticas.'); }
    }

    // ── Fabricante ───────────────────────────────────────────
    async function loadManufacturer() {
        const el = document.getElementById('manufacturerCard');
        try {
            const data = await apiFetch(`/api/manufacturer`);
            if (!data || data.error) {
                renderError(el, data?.message ?? 'Sin datos de fabricante.');
                return;
            }
            el.innerHTML = `
                <div style="padding:1.25rem;">
                    <h3 style="font-size:1.125rem;font-weight:700;margin-bottom:1rem;">${escHtml(data.manufacturer_name ?? '—')}</h3>
                    <div class="kpi-grid">
                        ${kpiCard('País', data.country ?? '—')}
                        ${kpiCard('Fundado', data.founded_year ?? '—')}
                    </div>
                </div>`;
        } catch { renderError(el, 'No se pudo cargar el fabricante.'); }
    }

    // ── Arranque: cargar overview al inicio ─────────────────
    lazyLoad('overview');

})();