'use strict';
(function () {

const { userId, role, visible } = window.WEC;

// ═══════════════════════════════════════════════════
// NAVEGACIÓN
// ═══════════════════════════════════════════════════
const navItems    = document.querySelectorAll('.nav-item');
const sections    = document.querySelectorAll('.section');
const topbarTitle = document.getElementById('topbarTitle');
const titles = {
    overview:'Panel', pilots:'Pilotos', vehicles:'Vehículos',
    races:'Carreras', teams:'Equipos', penalties:'Penalizaciones',
    results:'Resultados', stats:'Estadísticas', manufacturer:'Mi Fabricante'
};

function navigateTo(id) {
    navItems.forEach(b => {
        const on = b.dataset.section === id;
        b.classList.toggle('active', on);
        b.setAttribute('aria-current', on ? 'page' : 'false');
    });
    sections.forEach(s => s.classList.toggle('active', s.dataset.section === id));
    topbarTitle.textContent = titles[id] ?? id;
    closeSidebar();
    lazyLoad(id);
}
navItems.forEach(b => b.addEventListener('click', () => navigateTo(b.dataset.section)));

// ═══════════════════════════════════════════════════
// SIDEBAR MOBILE
// ═══════════════════════════════════════════════════
const sidebar  = document.getElementById('sidebar');
const overlay  = document.getElementById('overlay');
const menuBtn  = document.getElementById('menuBtn');
const closeBtn = document.getElementById('sidebarClose');

function openSidebar()  {
    sidebar.classList.add('open');
    overlay.classList.add('visible');
    overlay.removeAttribute('aria-hidden');
    menuBtn.setAttribute('aria-expanded','true');
}
function closeSidebar() {
    sidebar.classList.remove('open');
    overlay.classList.remove('visible');
    overlay.setAttribute('aria-hidden','true');
    menuBtn.setAttribute('aria-expanded','false');
}
menuBtn.addEventListener('click', openSidebar);
closeBtn.addEventListener('click', closeSidebar);
overlay.addEventListener('click', closeSidebar);

// ═══════════════════════════════════════════════════
// LOGOUT
// ═══════════════════════════════════════════════════
document.getElementById('logoutBtn').addEventListener('click', async () => {
    try {
        const res  = await fetch('/logout', { method:'POST' });
        const data = await res.json();
        if (data.success) window.location.href = data.redirect ?? '/login';
    } catch { window.location.href = '/login'; }
});

// ═══════════════════════════════════════════════════
// LUCIDE
// ═══════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
});

// ═══════════════════════════════════════════════════
// LAZY LOAD
// ═══════════════════════════════════════════════════
const loaded = new Set();
function lazyLoad(id) {
    if (loaded.has(id)) return;
    loaded.add(id);
    const fn = {
        overview: loadOverview, pilots: loadPilots, vehicles: loadVehicles,
        races: loadRaces, teams: loadTeams, penalties: loadPenalties,
        results: loadResults, stats: loadStats, manufacturer: loadManufacturer
    }[id];
    if (fn) fn();
}
lazyLoad('overview');

// ═══════════════════════════════════════════════════
// HELPERS
// ═══════════════════════════════════════════════════
async function api(url) {
    const res = await fetch(url);
    if (!res.ok) throw new Error(`HTTP ${res.status} — ${url}`);
    return res.json();
}

function esc(v) {
    if (v == null) return '—';
    return String(v)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function rows(data) { return Array.isArray(data) ? data : (data?.data ?? []); }

function table(headers, data, cols) {
    if (!data.length) return emptyState('Sin datos disponibles');
    const ths = headers.map(h => `<th scope="col">${esc(h)}</th>`).join('');
    const trs = data.map(r =>
        `<tr>${cols.map(c => `<td>${r.__html?.[c] ?? esc(r[c])}</td>`).join('')}</tr>`
    ).join('');
    return `<table><thead><tr>${ths}</tr></thead><tbody>${trs}</tbody></table>`;
}

function emptyState(msg) {
    return `<div class="empty-state">
        <i data-lucide="inbox"></i><span>${esc(msg)}</span>
    </div>`;
}
function errState(msg) {
    return `<div class="empty-state" style="color:var(--error)">
        <i data-lucide="alert-circle"></i><span>${esc(msg)}</span>
    </div>`;
}
function setWrap(id, html) {
    const el = document.getElementById(id);
    if (el) { el.innerHTML = html; reIcons(el); }
}
function reIcons(el) { if (window.lucide) lucide.createIcons({ nodes: [el] }); }

function kpiCard(label, value, cls = '') {
    return `<div class="kpi-card">
        <span class="kpi-label">${esc(label)}</span>
        <span class="kpi-value ${cls}">${value}</span>
    </div>`;
}

function badge(text, cls) {
    return `<span class="badge badge-${cls}">${esc(text)}</span>`;
}

function penaltyBadge(type) {
    const map = { POINTS:'yellow', TIME:'gray', DSQ:'red', DNF:'red' };
    return badge(type, map[type] ?? 'gray');
}

function formatDate(d) {
    if (!d) return '—';
    try { return new Date(d).toLocaleDateString('es-ES',{day:'2-digit',month:'short',year:'numeric'}); }
    catch { return d; }
}

const CHART_COLORS = [
    'rgba(225,6,0,.8)', 'rgba(245,158,11,.8)', 'rgba(34,197,94,.8)',
    'rgba(59,130,246,.8)', 'rgba(168,85,247,.8)', 'rgba(236,72,153,.8)',
    'rgba(20,184,166,.8)', 'rgba(249,115,22,.8)'
];
const chartDefaults = {
    plugins: { legend: { labels: { color:'#7a7a85', font:{ size:11 } } } },
    scales: {
        x: { ticks:{ color:'#7a7a85', font:{size:11} }, grid:{ color:'rgba(255,255,255,.04)' } },
        y: { ticks:{ color:'#7a7a85', font:{size:11} }, grid:{ color:'rgba(255,255,255,.06)' } }
    }
};
function mkChart(id, cfg) {
    const el = document.getElementById(id);
    if (!el || !window.Chart) return;
    return new Chart(el, cfg);
}

// ═══════════════════════════════════════════════════
// OVERVIEW
// ═══════════════════════════════════════════════════
async function loadOverview() {
    try {
        const [ov, penalties, results] = await Promise.all([
            api('/api/overview'),
            visible.includes('penalties') ? api('/api/penalties') : Promise.resolve([]),
            visible.includes('results')   ? api('/api/results')   : Promise.resolve([]),
        ]);

        // KPIs
        const g = document.getElementById('kpiGrid');
        if (g) {
            g.innerHTML =
                kpiCard('Carreras totales',  ov.total_races    ?? '—', 'kpi-accent')  +
                kpiCard('Pilotos',           ov.total_pilots   ?? '—')                 +
                kpiCard('Equipos',           ov.total_teams    ?? '—', 'kpi-success') +
                kpiCard('Penalizaciones',    ov.total_penalties ?? '—', 'kpi-warning');
            reIcons(g);
        }

        // Próximas carreras
        const raceList = rows(ov.races ?? []);
        const now = Date.now();
        const upcoming = raceList
            .filter(r => new Date(r.event_date).getTime() >= now)
            .sort((a,b) => new Date(a.event_date) - new Date(b.event_date))
            .slice(0,5);
        setWrap('upcomingRacesWrap', upcoming.length
            ? table(['Carrera','Circuito','País','Fecha','Duración'],
                upcoming.map(r => ({ ...r, event_date: formatDate(r.event_date) })),
                ['event_name','circuit_name','country','event_date','event_duration'])
            : emptyState('No hay carreras próximas')
        );

        // Chart: puntos por equipo (de results)
        const res = rows(results);
        if (res.length) {
            const tp = {};
            res.forEach(r => {
                const k = r.id_team ?? '?';
                tp[k] = (tp[k]??0) + (parseInt(r.base_points_team,10)||0);
            });
            const labels = Object.keys(tp).slice(0,10);
            mkChart('chartTeamPoints', {
                type:'bar',
                data:{ labels, datasets:[{
                    label:'Puntos', data: labels.map(k=>tp[k]),
                    backgroundColor:'rgba(225,6,0,.7)',
                    borderColor:'rgba(225,6,0,1)', borderWidth:1, borderRadius:4
                }]},
                options:{ responsive:true, maintainAspectRatio:false,
                    plugins:{ legend:{display:false} }, scales: chartDefaults.scales }
            });
        }

        // Chart: tipos penalización (doughnut)
        const pen = rows(penalties);
        if (pen.length) {
            const tc = {};
            pen.forEach(p => { tc[p.penalty_type] = (tc[p.penalty_type]??0)+1; });
            const labels = Object.keys(tc);
            mkChart('chartPenaltyTypes',{
                type:'doughnut',
                data:{ labels, datasets:[{
                    data: labels.map(k=>tc[k]),
                    backgroundColor: CHART_COLORS.slice(0,labels.length),
                    borderColor:'var(--surface)', borderWidth:3
                }]},
                options:{ responsive:true, maintainAspectRatio:false,
                    plugins:{ legend:{ position:'bottom', labels:{color:'#7a7a85',font:{size:11},padding:12} } } }
            });
        }

        // Chart: carreras por país (bar horizontal)
        const raceData = rows(ov.races ?? []);
        if (raceData.length) {
            const cp = {};
            raceData.forEach(r => { const c = r.country??'?'; cp[c]=(cp[c]??0)+1; });
            const labels = Object.keys(cp);
            mkChart('chartRacesByCountry',{
                type:'bar',
                data:{ labels, datasets:[{
                    label:'Carreras', data: labels.map(k=>cp[k]),
                    backgroundColor:'rgba(59,130,246,.7)', borderRadius:4
                }]},
                options:{ indexAxis:'y', responsive:true, maintainAspectRatio:false,
                    plugins:{ legend:{display:false} }, scales:{
                        x:{ ticks:{color:'#7a7a85',font:{size:11}}, grid:{color:'rgba(255,255,255,.04)'} },
                        y:{ ticks:{color:'#7a7a85',font:{size:11}}, grid:{color:'rgba(255,255,255,.04)'} }
                    } }
            });
        }

        reIcons(document.body);
    } catch(e) { setWrap('kpiGrid', errState('Error cargando el panel: '+e.message)); }
}

// ═══════════════════════════════════════════════════
// PILOTOS
// ═══════════════════════════════════════════════════
async function loadPilots() {
    try {
        const data = await api('/api/pilots');
        let list = rows(data);

        // KPIs
        const cats = {};
        list.forEach(p => { cats[p.pilot_category_name??'?'] = (cats[p.pilot_category_name??'?']??0)+1; });
        const catNames = Object.keys(cats);
        const g = document.getElementById('pilotsKpiGrid');
        if (g) {
            g.innerHTML =
                kpiCard('Total pilotos', list.length) +
                kpiCard('Categorías', catNames.length, 'kpi-accent') +
                kpiCard('Edad media', list.length
                    ? Math.round(list.reduce((s,p)=>s+(parseInt(p.pilot_age,10)||0),0)/list.length)
                    : '—');
            reIcons(g);
        }

        // Tabla
        function renderPilots(arr) {
            setWrap('pilotsTableWrap',
                table(['ID','Nombre','Edad','Categoría'], arr,
                    ['id_pilot','pilot_name','pilot_age','pilot_category_name'])
            );
        }
        renderPilots(list);

        // Búsqueda
        const s = document.getElementById('pilotSearch');
        if (s) s.addEventListener('input', () => {
            const q = s.value.toLowerCase();
            renderPilots(list.filter(p =>
                (p.pilot_name??'').toLowerCase().includes(q) ||
                (p.pilot_category_name??'').toLowerCase().includes(q)
            ));
        });

        // Chart categorías
        mkChart('chartPilotCategories',{
            type:'doughnut',
            data:{ labels: catNames, datasets:[{
                data: catNames.map(k=>cats[k]),
                backgroundColor: CHART_COLORS.slice(0,catNames.length),
                borderColor:'var(--surface)', borderWidth:3
            }]},
            options:{ responsive:true, maintainAspectRatio:false,
                plugins:{ legend:{ position:'bottom', labels:{color:'#7a7a85',font:{size:11},padding:12} } } }
        });
    } catch(e) { setWrap('pilotsTableWrap', errState('Error: '+e.message)); }
}

// ═══════════════════════════════════════════════════
// VEHÍCULOS
// ═══════════════════════════════════════════════════
async function loadVehicles() {
    try {
        const data  = await api('/api/vehicles');
        const list  = rows(data);
        const enhanced = list.map(r => ({
            ...r,
            __html: {
                specifications_url: r.specifications_url
                    ? `<a href="${esc(r.specifications_url)}" target="_blank" rel="noopener"
                           style="color:var(--accent);text-decoration:underline">Ver ficha</a>`
                    : '—'
            }
        }));
        setWrap('vehiclesTableWrap',
            table(['ID','Modelo','Ficha técnica'], enhanced,
                ['id_vehicle','model','specifications_url'])
        );
    } catch(e) { setWrap('vehiclesTableWrap', errState('Error: '+e.message)); }
}

// ═══════════════════════════════════════════════════
// CARRERAS
// ═══════════════════════════════════════════════════
async function loadRaces() {
    try {
        const data = await api('/api/races');
        let list = rows(data);
        const now = Date.now();

        // KPIs
        const upcoming = list.filter(r => new Date(r.event_date).getTime() >= now);
        const past     = list.filter(r => new Date(r.event_date).getTime() <  now);
        const g = document.getElementById('racesKpiGrid');
        if (g) {
            g.innerHTML =
                kpiCard('Total carreras', list.length) +
                kpiCard('Próximas', upcoming.length, 'kpi-success') +
                kpiCard('Disputadas', past.length, 'kpi-accent');
            reIcons(g);
        }

        function renderRaces(arr) {
            const mapped = arr.map(r => ({ ...r, event_date: formatDate(r.event_date) }));
            setWrap('racesTableWrap',
                table(['ID','Evento','Circuito','País','Fecha','Duración'], mapped,
                    ['id_race','event_name','circuit_name','country','event_date','event_duration'])
            );
        }
        renderRaces(list);

        // Filtro
        const f = document.getElementById('raceFilter');
        if (f) f.addEventListener('change', () => {
            const v = f.value;
            renderRaces(
                v === 'upcoming' ? upcoming :
                v === 'past'     ? past     : list
            );
        });

        // Chart duración por nombre de carrera
        if (list.length) {
            function timeToMin(t) {
                if (!t) return 0;
                const p = t.split(':').map(Number);
                return p[0]*60 + (p[1]||0);
            }
            const labels = list.slice(0,10).map(r => r.event_name ?? r.id_race);
            const vals   = list.slice(0,10).map(r => timeToMin(r.event_duration));
            mkChart('chartRacesDuration',{
                type:'bar',
                data:{ labels, datasets:[{
                    label:'Duración (min)', data: vals,
                    backgroundColor:'rgba(168,85,247,.7)', borderRadius:4
                }]},
                options:{ responsive:true, maintainAspectRatio:false,
                    plugins:{ legend:{display:false} }, scales: chartDefaults.scales }
            });
        }
    } catch(e) { setWrap('racesTableWrap', errState('Error: '+e.message)); }
}

// ═══════════════════════════════════════════════════
// EQUIPOS
// ═══════════════════════════════════════════════════
async function loadTeams() {
    try {
        const data = await api('/api/teams');
        let list = rows(data);

        // KPIs
        const g = document.getElementById('teamsKpiGrid');
        if (g) {
            const totalMech = list.reduce((s,t)=>s+(parseInt(t.mechanics_num,10)||0),0);
            g.innerHTML =
                kpiCard('Total equipos', list.length, 'kpi-accent') +
                kpiCard('Total mecánicos', totalMech);
            reIcons(g);
        }

        function renderTeams(arr) {
            setWrap('teamsTableWrap',
                table(['ID','Equipo','Mecánicos','Fabricante'], arr,
                    ['id_team','team_name','mechanics_num','manufacturer_name'])
            );
        }
        renderTeams(list);

        // Búsqueda
        const s = document.getElementById('teamSearch');
        if (s) s.addEventListener('input', () => {
            const q = s.value.toLowerCase();
            renderTeams(list.filter(t =>
                (t.team_name??'').toLowerCase().includes(q) ||
                (t.manufacturer_name??'').toLowerCase().includes(q)
            ));
        });

        // Chart mecánicos
        if (list.length) {
            const top = list.slice(0,12);
            mkChart('chartMechanics',{
                type:'bar',
                data:{ labels: top.map(t=>t.team_name), datasets:[{
                    label:'Mecánicos', data: top.map(t=>parseInt(t.mechanics_num,10)||0),
                    backgroundColor:'rgba(34,197,94,.7)', borderRadius:4
                }]},
                options:{ responsive:true, maintainAspectRatio:false,
                    plugins:{ legend:{display:false} }, scales: chartDefaults.scales }
            });
        }
    } catch(e) { setWrap('teamsTableWrap', errState('Error: '+e.message)); }
}

// ═══════════════════════════════════════════════════
// PENALIZACIONES
// ═══════════════════════════════════════════════════
async function loadPenalties() {
    try {
        const data = await api('/api/penalties');
        let list = rows(data);

        // KPIs por tipo
        const tc = { POINTS:0, TIME:0, DSQ:0, DNF:0 };
        list.forEach(p => { if (tc[p.penalty_type]!==undefined) tc[p.penalty_type]++; });
        const g = document.getElementById('penaltiesKpiGrid');
        if (g) {
            const cls = { POINTS:'kpi-warning', TIME:'', DSQ:'kpi-accent', DNF:'kpi-accent' };
            g.innerHTML = Object.entries(tc).map(([k,v]) => kpiCard(k, v, cls[k]??'')).join('');
            reIcons(g);
        }

        const enhanced = list.map(r => ({
            ...r, __html:{ penalty_type: penaltyBadge(r.penalty_type) }
        }));

        function renderPenalties(arr) {
            const mapped = arr.map(r => ({
                ...r, __html:{ penalty_type: penaltyBadge(r.penalty_type) }
            }));
            setWrap('penaltiesTableWrap',
                table(['ID','Tipo','Razón','Valor','Aplica a','Fecha'],
                    mapped, ['id_penalty','penalty_type','reason','penalty_value','penalty_applies_to','created_at'])
            );
        }
        renderPenalties(list);

        // Filtro por tipo
        const f = document.getElementById('penaltyTypeFilter');
        if (f) f.addEventListener('change', () => {
            renderPenalties(f.value === 'all' ? list : list.filter(p=>p.penalty_type===f.value));
        });

        // Chart detalle
        const labels = Object.keys(tc);
        mkChart('chartPenaltiesDetail',{
            type:'doughnut',
            data:{ labels, datasets:[{
                data: labels.map(k=>tc[k]),
                backgroundColor: CHART_COLORS.slice(0,labels.length),
                borderColor:'var(--surface)', borderWidth:3
            }]},
            options:{ responsive:true, maintainAspectRatio:false,
                plugins:{ legend:{ position:'bottom', labels:{color:'#7a7a85',font:{size:11},padding:12} } } }
        });
    } catch(e) { setWrap('penaltiesTableWrap', errState('Error: '+e.message)); }
}

// ═══════════════════════════════════════════════════
// RESULTADOS
// ═══════════════════════════════════════════════════
async function loadResults() {
    try {
        const data = await api('/api/results');
        const list = rows(data);

        // KPIs
        const totalPtsTeam  = list.reduce((s,r)=>s+(parseInt(r.base_points_team,10)||0),0);
        const totalPtsPilot = list.reduce((s,r)=>s+(parseInt(r.base_points_pilot,10)||0),0);
        const g = document.getElementById('resultsKpiGrid');
        if (g) {
            g.innerHTML =
                kpiCard('Resultados', list.length) +
                kpiCard('Pts equipos totales', totalPtsTeam.toLocaleString(), 'kpi-success') +
                kpiCard('Pts pilotos totales', totalPtsPilot.toLocaleString(), 'kpi-accent');
            reIcons(g);
        }

        setWrap('resultsTableWrap',
            table(['Pos.','Carrera','Equipo','Tiempo','Pen.','Pts Eq.','Pts Pil.'], list,
                ['position','event_name','id_team','final_time','penalty_time',
                 'base_points_team','base_points_pilot'])
        );

        // Chart puntos por equipo
        if (list.length) {
            const tp = {};
            list.forEach(r => {
                const k = r.id_team ?? '?';
                tp[k] = (tp[k]??0) + (parseInt(r.base_points_team,10)||0);
            });
            const sorted = Object.entries(tp).sort((a,b)=>b[1]-a[1]).slice(0,10);
            mkChart('chartResultsPoints',{
                type:'bar',
                data:{ labels: sorted.map(e=>e[0]), datasets:[{
                    label:'Puntos equipo', data: sorted.map(e=>e[1]),
                    backgroundColor: CHART_COLORS.slice(0,sorted.length), borderRadius:4
                }]},
                options:{ responsive:true, maintainAspectRatio:false,
                    plugins:{ legend:{display:false} }, scales: chartDefaults.scales }
            });
        }
    } catch(e) { setWrap('resultsTableWrap', errState('Error: '+e.message)); }
}

// ═══════════════════════════════════════════════════
// ESTADÍSTICAS
// ═══════════════════════════════════════════════════
async function loadStats() {
    try {
        const [ov, pen, res] = await Promise.all([
            api('/api/overview'),
            api('/api/penalties'),
            api('/api/results'),
        ]);

        const penList = rows(pen);
        const resList = rows(res);

        const totalPts    = resList.reduce((s,r)=>s+(parseInt(r.base_points_team,10)||0),0);
        const avgPos      = resList.length
            ? (resList.reduce((s,r)=>s+(parseInt(r.position,10)||0),0)/resList.length).toFixed(1)
            : '—';
        const penPerRace  = ov.total_races
            ? (penList.length / ov.total_races).toFixed(2)
            : '—';
        const dsnq        = penList.filter(p=>p.penalty_type==='DSQ'||p.penalty_type==='DNF').length;
        const pct         = penList.length ? Math.round(dsnq/penList.length*100) : 0;

        const g = document.getElementById('statsKpiGrid');
        if (g) {
            g.innerHTML =
                kpiCard('Total carreras',     ov.total_races    ?? '—', 'kpi-accent')  +
                kpiCard('Total pilotos',      ov.total_pilots   ?? '—')                 +
                kpiCard('Total equipos',      ov.total_teams    ?? '—', 'kpi-success') +
                kpiCard('Pts totales equipo', totalPts.toLocaleString(), 'kpi-success') +
                kpiCard('Pos. media',         avgPos)                                   +
                kpiCard(`DSQ/DNF (${pct}%)`,  dsnq, 'kpi-accent');
            reIcons(g);
        }

        // Chart puntos top 10
        if (resList.length) {
            const tp = {};
            resList.forEach(r => {
                const k = r.id_team??'?';
                tp[k] = (tp[k]??0)+(parseInt(r.base_points_team,10)||0);
            });
            const sorted = Object.entries(tp).sort((a,b)=>b[1]-a[1]).slice(0,10);
            mkChart('chartStatsTeams',{
                type:'bar',
                data:{ labels: sorted.map(e=>e[0]), datasets:[{
                    label:'Puntos', data: sorted.map(e=>e[1]),
                    backgroundColor: CHART_COLORS.slice(0,sorted.length), borderRadius:4
                }]},
                options:{ responsive:true, maintainAspectRatio:false,
                    plugins:{ legend:{display:false} }, scales: chartDefaults.scales }
            });
        }

        // Chart penalizaciones doughnut
        if (penList.length) {
            const tc = {};
            penList.forEach(p=>{ tc[p.penalty_type]=(tc[p.penalty_type]??0)+1; });
            const labels = Object.keys(tc);
            mkChart('chartStatsPenalties',{
                type:'doughnut',
                data:{ labels, datasets:[{
                    data: labels.map(k=>tc[k]),
                    backgroundColor: CHART_COLORS.slice(0,labels.length),
                    borderColor:'var(--surface)', borderWidth:3
                }]},
                options:{ responsive:true, maintainAspectRatio:false,
                    plugins:{ legend:{ position:'bottom', labels:{color:'#7a7a85',font:{size:11},padding:12} } } }
            });
        }

        // Leaderboard top 10
        if (resList.length) {
            const tp = {};
            resList.forEach(r => {
                const k = r.id_team??'?';
                if (!tp[k]) tp[k] = { id_team:k, points:0, races:new Set() };
                tp[k].points += (parseInt(r.base_points_team,10)||0);
                tp[k].races.add(r.id_race??r.event_name);
            });
            const sorted = Object.values(tp)
                .sort((a,b)=>b.points-a.points)
                .slice(0,10)
                .map((t,i)=>({ pos:i+1, id_team:t.id_team, races:t.races.size, points:t.points }));
            setWrap('statsLeaderboard',
                table(['#','Equipo','Carreras','Puntos'], sorted,
                    ['pos','id_team','races','points'])
            );
        }
    } catch(e) { setWrap('statsKpiGrid', errState('Error cargando estadísticas: '+e.message)); }
}

// ═══════════════════════════════════════════════════
// MI FABRICANTE
// ═══════════════════════════════════════════════════
async function loadManufacturer() {
    const wrap = document.getElementById('manufacturerContent');
    try {
        const data = await api('/api/manufacturer');
        const info = Array.isArray(data) ? data[0] : data;
        if (!info || info.error) {
            wrap.innerHTML = `<div class="empty-state">
                <i data-lucide="factory"></i>
                <span>No se encontró información del fabricante.</span>
            </div>`;
            reIcons(wrap);
            return;
        }
        wrap.innerHTML = `
        <div class="kpi-grid kpi-grid--sm">
            ${kpiCard('Fabricante', esc(info.manufacturer_name??'—'), 'kpi-accent')}
            ${kpiCard('País', esc(info.manufacturer_country??'—'))}
            ${kpiCard('ID', esc(info.id_manufacturer??'—'))}
        </div>
        <div class="card mt-4">
            <div class="card-header">
                <h3><i data-lucide="factory" aria-hidden="true"></i>
                    ${esc(info.manufacturer_name??'Fabricante')}</h3>
                <span class="badge badge-gray">${esc(info.manufacturer_country??'—')}</span>
            </div>
            <div style="padding:1.25rem;color:var(--text-muted);font-size:.875rem">
                <p>Fabricante oficial del campeonato registrado con ID
                <strong style="color:var(--text)">${esc(info.id_manufacturer??'—')}</strong>,
                originario de <strong style="color:var(--text)">${esc(info.manufacturer_country??'—')}</strong>.</p>
            </div>
        </div>`;
        reIcons(wrap);
    } catch(e) {
        wrap.innerHTML = errState('Error cargando fabricante: '+e.message);
        reIcons(wrap);
    }
}

})();