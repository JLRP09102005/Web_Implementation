<?php
// views/dashboard/dashboard.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user'])) { header('Location: /login'); exit; }

$user     = $_SESSION['user'];
$userId   = (int)($user['id']       ?? 0);
$username = htmlspecialchars($user['username'] ?? 'Usuario', ENT_QUOTES, 'UTF-8');
$role     = $user['role'] ?? 'guest';
$initial  = strtoupper(mb_substr($username, 0, 1));

$roleLabels = [
    'software-administrator'      => 'Administrador',
    'administratorDB'             => 'Admin DB',
    'race-director'               => 'Director de Carrera',
    'comissioner-boss'            => 'Jefe Comisario',
    'data-analyst'                => 'Analista de Datos',
    'team-manager'                => 'Team Manager',
    'mechanical-boss'             => 'Jefe Mecánico',
    'manufacturer-representative' => 'Rep. Fabricante',
    'pilot'                       => 'Piloto',
    'guest'                       => 'Invitado',
];
$roleLabel = htmlspecialchars($roleLabels[$role] ?? $role, ENT_QUOTES, 'UTF-8');
$roleSafe  = htmlspecialchars($role, ENT_QUOTES, 'UTF-8');

// Secciones visibles por rol
$visibilityMap = [
    'software-administrator'      => ['overview','pilots','vehicles','races','teams','penalties','results','stats'],
    'administratorDB'             => ['overview','pilots','vehicles','races','teams','penalties','results','stats'],
    'race-director'               => ['overview','races','penalties','results'],
    'comissioner-boss'            => ['overview','races','penalties','results'],
    'data-analyst'                => ['overview','pilots','vehicles','races','teams','penalties','results','stats'],
    'team-manager'                => ['overview','races','vehicles','results'],
    'mechanical-boss'             => ['overview','vehicles','races'],
    'manufacturer-representative' => ['overview','vehicles','races','teams','manufacturer'],
    'pilot'                       => ['overview','races','results'],
    'guest'                       => ['overview','races','pilots','teams'],
];
$visible = $visibilityMap[$role] ?? ['overview'];

$navDef = [
    'overview'     => ['icon' => 'layout-dashboard', 'label' => 'Panel'],
    'pilots'       => ['icon' => 'user',              'label' => 'Pilotos'],
    'vehicles'     => ['icon' => 'car',               'label' => 'Vehículos'],
    'races'        => ['icon' => 'flag',              'label' => 'Carreras'],
    'teams'        => ['icon' => 'users',             'label' => 'Equipos'],
    'penalties'    => ['icon' => 'alert-triangle',    'label' => 'Penalizaciones'],
    'results'      => ['icon' => 'trophy',            'label' => 'Resultados'],
    'stats'        => ['icon' => 'bar-chart-2',       'label' => 'Estadísticas'],
    'manufacturer' => ['icon' => 'factory',           'label' => 'Mi Fabricante'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WEC · Panel de Control</title>
    <link rel="stylesheet" href="/public/css/dashboard.css">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js" defer></script>
</head>
<body>

<!-- ════════════════════════════════════════
     SIDEBAR
════════════════════════════════════════ -->
<aside class="sidebar" id="sidebar" role="navigation" aria-label="Navegación principal">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <svg width="30" height="30" viewBox="0 0 48 48" fill="none"
                 style="color:var(--accent)" aria-hidden="true">
                <circle cx="24" cy="24" r="21" stroke="currentColor" stroke-width="2.5"/>
                <path d="M13 26 L19 16 L24 22 L29 16 L35 26"
                      stroke="currentColor" stroke-width="2.5"
                      stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="24" cy="32" r="2.5" fill="currentColor"/>
            </svg>
            <span class="logo-text">WEC</span>
        </div>
        <button class="sidebar-close" id="sidebarClose" aria-label="Cerrar menú">
            <i data-lucide="x" width="18" height="18"></i>
        </button>
    </div>

    <nav class="sidebar-nav" aria-label="Secciones">
        <?php foreach ($navDef as $id => $cfg): if (!in_array($id, $visible, true)) continue; ?>
        <button class="nav-item<?= $id === 'overview' ? ' active' : '' ?>"
                data-section="<?= $id ?>"
                aria-current="<?= $id === 'overview' ? 'page' : 'false' ?>">
            <i data-lucide="<?= $cfg['icon'] ?>" width="16" height="16" aria-hidden="true"></i>
            <?= $cfg['label'] ?>
        </button>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar" aria-hidden="true"><?= $initial ?></div>
            <div class="user-meta">
                <span class="user-name"><?= $username ?></span>
                <span class="user-role"><?= $roleLabel ?></span>
            </div>
        </div>
        <button class="btn-logout" id="logoutBtn" aria-label="Cerrar sesión" title="Cerrar sesión">
            <i data-lucide="log-out" width="16" height="16" aria-hidden="true"></i>
        </button>
    </div>
</aside>

<div class="overlay" id="overlay" aria-hidden="true"></div>

<!-- ════════════════════════════════════════
     MAIN
════════════════════════════════════════ -->
<main class="main" id="mainContent">

    <header class="topbar">
        <button class="btn-menu" id="menuBtn" aria-label="Abrir menú"
                aria-expanded="false" aria-controls="sidebar">
            <i data-lucide="menu" aria-hidden="true"></i>
        </button>
        <h1 class="topbar-title" id="topbarTitle">Panel</h1>
        <span class="topbar-role-badge"><?= $roleLabel ?></span>
    </header>

    <div class="content">

    <!-- ─── OVERVIEW ────────────────────────────────── -->
    <?php if (in_array('overview', $visible)): ?>
    <section class="section active" data-section="overview" aria-labelledby="h-overview">
        <div class="section-header">
            <h2 id="h-overview">Panel de control</h2>
            <p class="section-sub">Resumen global del campeonato</p>
        </div>

        <!-- KPIs -->
        <div class="kpi-grid" id="kpiGrid">
            <?php foreach (['races','pilots','teams','penalties'] as $k): ?>
            <div class="kpi-card skeleton">
                <div class="sk-line sk-s"></div>
                <div class="sk-line sk-l"></div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Gráficas Overview -->
        <div class="charts-row mt-6">
            <!-- Próximas carreras -->
            <div class="card chart-card">
                <div class="card-header">
                    <h3><i data-lucide="calendar" aria-hidden="true"></i> Próximas carreras</h3>
                </div>
                <div class="table-wrap" id="upcomingRacesWrap">
                    <div class="sk-block"><div class="sk-line sk-l"></div><div class="sk-line sk-s"></div></div>
                </div>
            </div>

            <!-- Gráfica puntos por equipo -->
            <div class="card chart-card">
                <div class="card-header">
                    <h3><i data-lucide="bar-chart-2" aria-hidden="true"></i> Puntos por equipo</h3>
                </div>
                <div class="chart-wrap">
                    <canvas id="chartTeamPoints" aria-label="Puntos por equipo"></canvas>
                </div>
            </div>
        </div>

        <!-- Segunda fila de gráficas -->
        <div class="charts-row mt-4">
            <!-- Distribución penalizaciones -->
            <div class="card chart-card">
                <div class="card-header">
                    <h3><i data-lucide="pie-chart" aria-hidden="true"></i> Tipos de penalización</h3>
                </div>
                <div class="chart-wrap chart-wrap--sm">
                    <canvas id="chartPenaltyTypes" aria-label="Tipos de penalización"></canvas>
                </div>
            </div>

            <!-- Carreras por país -->
            <div class="card chart-card">
                <div class="card-header">
                    <h3><i data-lucide="map-pin" aria-hidden="true"></i> Carreras por país</h3>
                </div>
                <div class="chart-wrap chart-wrap--sm">
                    <canvas id="chartRacesByCountry" aria-label="Carreras por país"></canvas>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- ─── PILOTOS ──────────────────────────────────── -->
    <?php if (in_array('pilots', $visible)): ?>
    <section class="section" data-section="pilots" aria-labelledby="h-pilots">
        <div class="section-header">
            <h2 id="h-pilots">Pilotos</h2>
            <p class="section-sub">Listado de pilotos del campeonato</p>
        </div>

        <!-- Stats pilotos -->
        <div class="kpi-grid kpi-grid--sm" id="pilotsKpiGrid">
            <div class="kpi-card skeleton"><div class="sk-line sk-s"></div><div class="sk-line sk-l"></div></div>
            <div class="kpi-card skeleton"><div class="sk-line sk-s"></div><div class="sk-line sk-l"></div></div>
            <div class="kpi-card skeleton"><div class="sk-line sk-s"></div><div class="sk-line sk-l"></div></div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h3><i data-lucide="user" aria-hidden="true"></i> Pilotos registrados</h3>
                <input type="search" id="pilotSearch" placeholder="Buscar…" class="search-input"
                       aria-label="Buscar piloto">
            </div>
            <div class="table-wrap" id="pilotsTableWrap">
                <div class="sk-block"><div class="sk-line sk-l"></div><div class="sk-line sk-s"></div></div>
            </div>
        </div>

        <!-- Gráfica categorías -->
        <div class="card mt-4">
            <div class="card-header">
                <h3><i data-lucide="pie-chart" aria-hidden="true"></i> Distribución por categoría</h3>
            </div>
            <div class="chart-wrap chart-wrap--sm" style="max-width:400px;margin:0 auto">
                <canvas id="chartPilotCategories" aria-label="Pilotos por categoría"></canvas>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- ─── VEHÍCULOS ───────────────────────────────── -->
    <?php if (in_array('vehicles', $visible)): ?>
    <section class="section" data-section="vehicles" aria-labelledby="h-vehicles">
        <div class="section-header">
            <h2 id="h-vehicles">Vehículos</h2>
            <p class="section-sub">Flota de vehículos registrados</p>
        </div>
        <div class="card">
            <div class="card-header">
                <h3><i data-lucide="car" aria-hidden="true"></i> Vehículos</h3>
            </div>
            <div class="table-wrap" id="vehiclesTableWrap">
                <div class="sk-block"><div class="sk-line sk-l"></div><div class="sk-line sk-s"></div></div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- ─── CARRERAS ─────────────────────────────────── -->
    <?php if (in_array('races', $visible)): ?>
    <section class="section" data-section="races" aria-labelledby="h-races">
        <div class="section-header">
            <h2 id="h-races">Carreras</h2>
            <p class="section-sub">Calendario completo del campeonato</p>
        </div>

        <div class="kpi-grid kpi-grid--sm" id="racesKpiGrid">
            <div class="kpi-card skeleton"><div class="sk-line sk-s"></div><div class="sk-line sk-l"></div></div>
            <div class="kpi-card skeleton"><div class="sk-line sk-s"></div><div class="sk-line sk-l"></div></div>
            <div class="kpi-card skeleton"><div class="sk-line sk-s"></div><div class="sk-line sk-l"></div></div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h3><i data-lucide="flag" aria-hidden="true"></i> Calendario</h3>
                <div style="display:flex;gap:.5rem;align-items:center">
                    <select id="raceFilter" class="search-input" style="width:auto" aria-label="Filtrar carreras">
                        <option value="all">Todas</option>
                        <option value="upcoming">Próximas</option>
                        <option value="past">Pasadas</option>
                    </select>
                </div>
            </div>
            <div class="table-wrap" id="racesTableWrap">
                <div class="sk-block"><div class="sk-line sk-l"></div><div class="sk-line sk-s"></div></div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h3><i data-lucide="clock" aria-hidden="true"></i> Duración media por circuito</h3>
            </div>
            <div class="chart-wrap">
                <canvas id="chartRacesDuration" aria-label="Duración de carreras"></canvas>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- ─── EQUIPOS ──────────────────────────────────── -->
    <?php if (in_array('teams', $visible)): ?>
    <section class="section" data-section="teams" aria-labelledby="h-teams">
        <div class="section-header">
            <h2 id="h-teams">Equipos</h2>
            <p class="section-sub">Equipos participantes en el campeonato</p>
        </div>

        <div class="kpi-grid kpi-grid--sm" id="teamsKpiGrid">
            <div class="kpi-card skeleton"><div class="sk-line sk-s"></div><div class="sk-line sk-l"></div></div>
            <div class="kpi-card skeleton"><div class="sk-line sk-s"></div><div class="sk-line sk-l"></div></div>
        </div>

        <div class="charts-row mt-4">
            <div class="card chart-card">
                <div class="card-header">
                    <h3><i data-lucide="users" aria-hidden="true"></i> Equipos</h3>
                    <input type="search" id="teamSearch" placeholder="Buscar…"
                           class="search-input" aria-label="Buscar equipo">
                </div>
                <div class="table-wrap" id="teamsTableWrap">
                    <div class="sk-block"><div class="sk-line sk-l"></div><div class="sk-line sk-s"></div></div>
                </div>
            </div>
            <div class="card chart-card">
                <div class="card-header">
                    <h3><i data-lucide="wrench" aria-hidden="true"></i> Mecánicos por equipo</h3>
                </div>
                <div class="chart-wrap">
                    <canvas id="chartMechanics" aria-label="Mecánicos por equipo"></canvas>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- ─── PENALIZACIONES ───────────────────────────── -->
    <?php if (in_array('penalties', $visible)): ?>
    <section class="section" data-section="penalties" aria-labelledby="h-penalties">
        <div class="section-header">
            <h2 id="h-penalties">Penalizaciones</h2>
            <p class="section-sub">Historial de penalizaciones aplicadas</p>
        </div>

        <div class="kpi-grid kpi-grid--sm" id="penaltiesKpiGrid">
            <?php foreach (['POINTS','TIME','DSQ','DNF'] as $t): ?>
            <div class="kpi-card skeleton"><div class="sk-line sk-s"></div><div class="sk-line sk-l"></div></div>
            <?php endforeach; ?>
        </div>

        <div class="charts-row mt-4">
            <div class="card chart-card">
                <div class="card-header">
                    <h3><i data-lucide="alert-triangle" aria-hidden="true"></i> Penalizaciones</h3>
                    <select id="penaltyTypeFilter" class="search-input" style="width:auto">
                        <option value="all">Todos los tipos</option>
                        <option value="POINTS">POINTS</option>
                        <option value="TIME">TIME</option>
                        <option value="DSQ">DSQ</option>
                        <option value="DNF">DNF</option>
                    </select>
                </div>
                <div class="table-wrap" id="penaltiesTableWrap">
                    <div class="sk-block"><div class="sk-line sk-l"></div><div class="sk-line sk-s"></div></div>
                </div>
            </div>
            <div class="card chart-card">
                <div class="card-header">
                    <h3><i data-lucide="pie-chart" aria-hidden="true"></i> Distribución</h3>
                </div>
                <div class="chart-wrap chart-wrap--sm">
                    <canvas id="chartPenaltiesDetail" aria-label="Distribución de penalizaciones"></canvas>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- ─── RESULTADOS ───────────────────────────────── -->
    <?php if (in_array('results', $visible)): ?>
    <section class="section" data-section="results" aria-labelledby="h-results">
        <div class="section-header">
            <h2 id="h-results">Resultados</h2>
            <p class="section-sub">Clasificaciones y tiempos de carrera</p>
        </div>

        <div class="kpi-grid kpi-grid--sm" id="resultsKpiGrid">
            <div class="kpi-card skeleton"><div class="sk-line sk-s"></div><div class="sk-line sk-l"></div></div>
            <div class="kpi-card skeleton"><div class="sk-line sk-s"></div><div class="sk-line sk-l"></div></div>
            <div class="kpi-card skeleton"><div class="sk-line sk-s"></div><div class="sk-line sk-l"></div></div>
        </div>

        <div class="charts-row mt-4">
            <div class="card chart-card chart-card--wide">
                <div class="card-header">
                    <h3><i data-lucide="trophy" aria-hidden="true"></i> Tabla de resultados</h3>
                </div>
                <div class="table-wrap" id="resultsTableWrap">
                    <div class="sk-block"><div class="sk-line sk-l"></div><div class="sk-line sk-s"></div></div>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h3><i data-lucide="trending-up" aria-hidden="true"></i> Puntos acumulados por equipo</h3>
            </div>
            <div class="chart-wrap">
                <canvas id="chartResultsPoints" aria-label="Puntos acumulados"></canvas>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- ─── ESTADÍSTICAS ─────────────────────────────── -->
    <?php if (in_array('stats', $visible)): ?>
    <section class="section" data-section="stats" aria-labelledby="h-stats">
        <div class="section-header">
            <h2 id="h-stats">Estadísticas</h2>
            <p class="section-sub">Métricas globales del campeonato</p>
        </div>

        <div class="kpi-grid" id="statsKpiGrid">
            <?php foreach (range(1,6) as $_): ?>
            <div class="kpi-card skeleton"><div class="sk-line sk-s"></div><div class="sk-line sk-l"></div></div>
            <?php endforeach; ?>
        </div>

        <div class="charts-row mt-6">
            <div class="card chart-card">
                <div class="card-header">
                    <h3><i data-lucide="bar-chart-2" aria-hidden="true"></i> Puntos equipos (top 10)</h3>
                </div>
                <div class="chart-wrap">
                    <canvas id="chartStatsTeams" aria-label="Puntos por equipo"></canvas>
                </div>
            </div>
            <div class="card chart-card">
                <div class="card-header">
                    <h3><i data-lucide="pie-chart" aria-hidden="true"></i> Penalizaciones por tipo</h3>
                </div>
                <div class="chart-wrap chart-wrap--sm">
                    <canvas id="chartStatsPenalties" aria-label="Penalizaciones"></canvas>
                </div>
            </div>
        </div>

        <!-- Tabla de líderes -->
        <div class="card mt-4">
            <div class="card-header">
                <h3><i data-lucide="medal" aria-hidden="true"></i> Top 10 equipos — clasificación general</h3>
            </div>
            <div class="table-wrap" id="statsLeaderboard">
                <div class="sk-block"><div class="sk-line sk-l"></div><div class="sk-line sk-s"></div></div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- ─── MI FABRICANTE ────────────────────────────── -->
    <?php if (in_array('manufacturer', $visible)): ?>
    <section class="section" data-section="manufacturer" aria-labelledby="h-manufacturer">
        <div class="section-header">
            <h2 id="h-manufacturer">Mi Fabricante</h2>
            <p class="section-sub">Información de tu fabricante y equipos asociados</p>
        </div>
        <div id="manufacturerContent">
            <div class="sk-block"><div class="sk-line sk-l" style="height:2.5rem"></div></div>
        </div>
    </section>
    <?php endif; ?>

    </div><!-- /.content -->
</main>

<!-- Datos para JS -->
<script>
window.WEC = {
    userId: <?= $userId ?>,
    role:   <?= json_encode($role) ?>,
    visible: <?= json_encode($visible) ?>
};
</script>
<script src="/public/js/dashboard.js" defer></script>
</body>
</html>