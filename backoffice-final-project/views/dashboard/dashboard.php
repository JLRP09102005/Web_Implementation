<?php
// views/dashboard/dashboard.php
// Seguridad: esta vista solo se sirve desde el router con ROLE_AUTHENTICATED,
// pero hacemos una segunda comprobación por si acaso.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user'])) {
    header('Location: /login');
    exit;
}

$user     = $_SESSION['user'];
$userId   = (int) ($user['id']       ?? 0);
$username = htmlspecialchars($user['username'] ?? 'Usuario', ENT_QUOTES, 'UTF-8');
$role     = htmlspecialchars($user['role']     ?? 'guest',   ENT_QUOTES, 'UTF-8');
$initial  = strtoupper(mb_substr($username, 0, 1));

// Secciones visibles por rol
// Formato: [ section_id => label ]
$allSections = [
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

// Control de visibilidad por rol
$visibilityMap = [
    'software-administrator' => ['overview','pilots','vehicles','races','teams','penalties','results','stats'],
    'administratorDB'        => ['overview','pilots','vehicles','races','teams','penalties','results','stats'],
    'race-director'          => ['overview','races','penalties','results'],
    'commissioner-boss'      => ['overview','races','penalties','results'],
    'data-analyst'           => ['overview','pilots','vehicles','races','teams','penalties','results','stats'],
    'team-manager'           => ['overview','races','results'],
    'mechanical-boss'        => ['overview','vehicles','races'],
    'manufacturer-representative' => ['overview','vehicles','races','manufacturer'],
    'pilot'                  => ['overview','races','results'],
    'guest'                  => ['overview','races'],
];

$visible = $visibilityMap[$role] ?? ['overview'];

// Label del rol para el badge
$roleLabels = [
    'software-administrator'      => 'Administrador',
    'administratorDB'             => 'Admin DB',
    'race-director'               => 'Director de carrera',
    'commissioner-boss'           => 'Jefe comisario',
    'data-analyst'                => 'Analista de datos',
    'team-manager'                => 'Team Manager',
    'mechanical-boss'             => 'Jefe mecánico',
    'manufacturer-representative' => 'Rep. Fabricante',
    'pilot'                       => 'Piloto',
    'guest'                       => 'Invitado',
];
$roleLabel = htmlspecialchars($roleLabels[$role] ?? $role, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WEC · Panel</title>
    <link rel="stylesheet" href="/public/css/dashboard.css">
    <!-- Lucide icons -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js" defer></script>
</head>
<body>

<!-- ── Sidebar ──────────────────────────────────────────────── -->
<aside class="sidebar" id="sidebar" role="navigation" aria-label="Navegación principal">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <svg width="28" height="28" viewBox="0 0 48 48" fill="none" style="color:var(--accent)" aria-hidden="true">
                <circle cx="24" cy="24" r="22" stroke="currentColor" stroke-width="2.5"/>
                <path d="M12 24 L20 14 L28 24 L36 14" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M12 30 L20 20 L28 30 L36 20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" opacity="0.5"/>
            </svg>
            <span class="logo-text">WEC</span>
        </div>
        <button class="sidebar-close" id="sidebarClose" aria-label="Cerrar menú">
            <i data-lucide="x" width="18" height="18"></i>
        </button>
    </div>

    <nav class="sidebar-nav" aria-label="Secciones">
        <?php foreach ($allSections as $id => $cfg): ?>
        <?php if (in_array($id, $visible, true)): ?>
        <button
            class="nav-item<?= $id === 'overview' ? ' active' : '' ?>"
            data-section="<?= $id ?>"
            aria-current="<?= $id === 'overview' ? 'page' : 'false' ?>"
        >
            <i data-lucide="<?= $cfg['icon'] ?>" width="16" height="16" aria-hidden="true"></i>
            <?= $cfg['label'] ?>
        </button>
        <?php endif; ?>
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

<!-- ── Overlay mobile ────────────────────────────────────────── -->
<div class="overlay" id="overlay" aria-hidden="true"></div>

<!-- ── Main ──────────────────────────────────────────────────── -->
<main class="main" id="mainContent">

    <!-- Topbar -->
    <header class="topbar">
        <button class="btn-menu" id="menuBtn" aria-label="Abrir menú" aria-expanded="false" aria-controls="sidebar">
            <i data-lucide="menu" aria-hidden="true"></i>
        </button>
        <h1 class="topbar-title" id="topbarTitle">Panel</h1>
        <span class="topbar-role-badge" aria-label="Rol: <?= $roleLabel ?>"><?= $roleLabel ?></span>
    </header>

    <!-- Contenido -->
    <div class="content">

        <!-- ── PANEL (overview) ──────────────────────────── -->
        <?php if (in_array('overview', $visible)): ?>
        <section class="section active" data-section="overview" aria-labelledby="hOverview">
            <div class="section-header">
                <h2 id="hOverview">Panel</h2>
                <p class="section-sub">Resumen general del campeonato</p>
            </div>
            <div id="overviewContent">
                <!-- KPIs -->
                <div class="kpi-grid" id="kpiGrid">
                    <div class="kpi-card skeleton">
                        <div class="sk-line sk-s"></div>
                        <div class="sk-line sk-l"></div>
                    </div>
                    <div class="kpi-card skeleton">
                        <div class="sk-line sk-s"></div>
                        <div class="sk-line sk-l"></div>
                    </div>
                    <div class="kpi-card skeleton">
                        <div class="sk-line sk-s"></div>
                        <div class="sk-line sk-l"></div>
                    </div>
                    <div class="kpi-card skeleton">
                        <div class="sk-line sk-s"></div>
                        <div class="sk-line sk-l"></div>
                    </div>
                </div>

                <!-- Próximas carreras -->
                <div class="card mt-6">
                    <div class="card-header">
                        <h3><i data-lucide="calendar" aria-hidden="true"></i> Próximas carreras</h3>
                    </div>
                    <div class="table-wrap" id="upcomingRacesWrap">
                        <div style="padding:1.5rem">
                            <div class="sk-line sk-l"></div>
                            <div class="sk-line sk-s"></div>
                        </div>
                    </div>
                </div>

                <!-- Gráfica de puntos por equipo -->
                <div class="card mt-6">
                    <div class="card-header">
                        <h3><i data-lucide="bar-chart-2" aria-hidden="true"></i> Puntos por equipo</h3>
                    </div>
                    <div style="padding:1rem; position:relative; height:260px;">
                        <canvas id="chartTeamPoints" aria-label="Gráfica de puntos por equipo"></canvas>
                    </div>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- ── PILOTOS ────────────────────────────────────── -->
        <?php if (in_array('pilots', $visible)): ?>
        <section class="section" data-section="pilots" aria-labelledby="hPilots">
            <div class="section-header">
                <h2 id="hPilots">Pilotos</h2>
                <p class="section-sub">Listado de pilotos del campeonato</p>
            </div>
            <div class="card">
                <div class="card-header">
                    <h3><i data-lucide="user" aria-hidden="true"></i> Pilotos registrados</h3>
                    <input type="search" id="pilotSearch" placeholder="Buscar piloto…"
                        style="background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius-sm);padding:0.35rem 0.625rem;font-size:0.8125rem;color:var(--text);outline:none;width:200px;"
                        aria-label="Buscar piloto">
                </div>
                <div class="table-wrap" id="pilotsTableWrap">
                    <div style="padding:1.5rem"><div class="sk-line sk-l"></div><div class="sk-line sk-s"></div></div>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- ── VEHÍCULOS ──────────────────────────────────── -->
        <?php if (in_array('vehicles', $visible)): ?>
        <section class="section" data-section="vehicles" aria-labelledby="hVehicles">
            <div class="section-header">
                <h2 id="hVehicles">Vehículos</h2>
                <p class="section-sub">Vehículos registrados en el campeonato</p>
            </div>
            <div class="card">
                <div class="card-header">
                    <h3><i data-lucide="car" aria-hidden="true"></i> Flota de vehículos</h3>
                </div>
                <div class="table-wrap" id="vehiclesTableWrap">
                    <div style="padding:1.5rem"><div class="sk-line sk-l"></div><div class="sk-line sk-s"></div></div>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- ── CARRERAS ───────────────────────────────────── -->
        <?php if (in_array('races', $visible)): ?>
        <section class="section" data-section="races" aria-labelledby="hRaces">
            <div class="section-header">
                <h2 id="hRaces">Carreras</h2>
                <p class="section-sub">Calendario completo de carreras</p>
            </div>
            <div class="card">
                <div class="card-header">
                    <h3><i data-lucide="flag" aria-hidden="true"></i> Calendario</h3>
                </div>
                <div class="table-wrap" id="racesTableWrap">
                    <div style="padding:1.5rem"><div class="sk-line sk-l"></div><div class="sk-line sk-s"></div></div>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- ── EQUIPOS ────────────────────────────────────── -->
        <?php if (in_array('teams', $visible)): ?>
        <section class="section" data-section="teams" aria-labelledby="hTeams">
            <div class="section-header">
                <h2 id="hTeams">Equipos</h2>
                <p class="section-sub">Equipos participantes en el campeonato</p>
            </div>
            <div class="card">
                <div class="card-header">
                    <h3><i data-lucide="users" aria-hidden="true"></i> Equipos</h3>
                </div>
                <div class="table-wrap" id="teamsTableWrap">
                    <div style="padding:1.5rem"><div class="sk-line sk-l"></div><div class="sk-line sk-s"></div></div>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- ── PENALIZACIONES ─────────────────────────────── -->
        <?php if (in_array('penalties', $visible)): ?>
        <section class="section" data-section="penalties" aria-labelledby="hPenalties">
            <div class="section-header">
                <h2 id="hPenalties">Penalizaciones</h2>
                <p class="section-sub">Historial de penalizaciones aplicadas</p>
            </div>
            <div class="card">
                <div class="card-header">
                    <h3><i data-lucide="alert-triangle" aria-hidden="true"></i> Penalizaciones</h3>
                </div>
                <div class="table-wrap" id="penaltiesTableWrap">
                    <div style="padding:1.5rem"><div class="sk-line sk-l"></div><div class="sk-line sk-s"></div></div>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- ── RESULTADOS ─────────────────────────────────── -->
        <?php if (in_array('results', $visible)): ?>
        <section class="section" data-section="results" aria-labelledby="hResults">
            <div class="section-header">
                <h2 id="hResults">Resultados</h2>
                <p class="section-sub">Clasificaciones y resultados de las carreras</p>
            </div>
            <div class="card">
                <div class="card-header">
                    <h3><i data-lucide="trophy" aria-hidden="true"></i> Resultados</h3>
                </div>
                <div class="table-wrap" id="resultsTableWrap">
                    <div style="padding:1.5rem"><div class="sk-line sk-l"></div><div class="sk-line sk-s"></div></div>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- ── ESTADÍSTICAS ───────────────────────────────── -->
        <?php if (in_array('stats', $visible)): ?>
        <section class="section" data-section="stats" aria-labelledby="hStats">
            <div class="section-header">
                <h2 id="hStats">Estadísticas</h2>
                <p class="section-sub">Métricas globales del campeonato</p>
            </div>
            <div id="statsContent">
                <div class="kpi-grid" id="statsKpiGrid">
                    <div class="kpi-card skeleton"><div class="sk-line sk-s"></div><div class="sk-line sk-l"></div></div>
                    <div class="kpi-card skeleton"><div class="sk-line sk-s"></div><div class="sk-line sk-l"></div></div>
                    <div class="kpi-card skeleton"><div class="sk-line sk-s"></div><div class="sk-line sk-l"></div></div>
                </div>
                <div class="card mt-6">
                    <div class="card-header">
                        <h3><i data-lucide="pie-chart" aria-hidden="true"></i> Distribución de penalizaciones</h3>
                    </div>
                    <div style="padding:1rem; max-width:360px; margin:0 auto; position:relative; height:280px;">
                        <canvas id="chartPenaltyTypes" aria-label="Distribución de tipos de penalización"></canvas>
                    </div>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- ── MI FABRICANTE ──────────────────────────────── -->
        <?php if (in_array('manufacturer', $visible)): ?>
        <section class="section" data-section="manufacturer" aria-labelledby="hManufacturer">
            <div class="section-header">
                <h2 id="hManufacturer">Mi Fabricante</h2>
                <p class="section-sub">Información de tu fabricante y equipos asociados</p>
            </div>
            <div id="manufacturerContent">
                <div class="sk-line sk-l" style="margin-bottom:1rem;height:2rem;border-radius:8px;"></div>
            </div>
        </section>
        <?php endif; ?>

    </div><!-- /.content -->
</main><!-- /.main -->

<!-- Datos de sesión para JS (sin datos sensibles) -->
<script>
window.WEC = {
    userId: <?= $userId ?>,
    role:   <?= json_encode($role) ?>,
    visibleSections: <?= json_encode($visible) ?>
};
</script>
<script src="/public/js/dashboard.js" defer></script>

</body>
</html>