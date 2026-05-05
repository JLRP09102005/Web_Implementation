<?php
if (!isset($_SESSION['user'])) {
    header('Location: /login');
    exit;
}

$user     = $_SESSION['user'];
$role     = $user['role'];
$username = $user['username'];
$userId   = $user['id'];

$roleLabels = [
    'software-administrator'      => 'Administrador',
    'administratorDB'             => 'Administrador BD',
    'comissioner-boss'            => 'Comisario Jefe',
    'race-director'               => 'Director de Carrera',
    'data-analyst'                => 'Analista de Datos',
    'manufacturer-representative' => 'Representante Fabricante',
    'mechanical-boss'             => 'Jefe de Mecánicos',
    'team-manager'                => 'Team Manager',
    'pilot'                       => 'Piloto',
];

$navItems = [
    'software-administrator' => [
        ['icon'=>'layout-dashboard','label'=>'Panel',         'section'=>'overview'],
        ['icon'=>'users',           'label'=>'Pilotos',        'section'=>'pilots'],
        ['icon'=>'car',             'label'=>'Vehículos',      'section'=>'vehicles'],
        ['icon'=>'flag',            'label'=>'Carreras',       'section'=>'races'],
        ['icon'=>'building-2',      'label'=>'Equipos',        'section'=>'teams'],
        ['icon'=>'alert-triangle',  'label'=>'Penalizaciones', 'section'=>'penalties'],
    ],
    'administratorDB' => [
        ['icon'=>'layout-dashboard','label'=>'Panel',         'section'=>'overview'],
        ['icon'=>'users',           'label'=>'Pilotos',        'section'=>'pilots'],
        ['icon'=>'car',             'label'=>'Vehículos',      'section'=>'vehicles'],
        ['icon'=>'flag',            'label'=>'Carreras',       'section'=>'races'],
        ['icon'=>'building-2',      'label'=>'Equipos',        'section'=>'teams'],
        ['icon'=>'alert-triangle',  'label'=>'Penalizaciones', 'section'=>'penalties'],
    ],
    'comissioner-boss' => [
        ['icon'=>'layout-dashboard','label'=>'Panel',         'section'=>'overview'],
        ['icon'=>'flag',            'label'=>'Carreras',       'section'=>'races'],
        ['icon'=>'alert-triangle',  'label'=>'Penalizaciones', 'section'=>'penalties'],
        ['icon'=>'list-ordered',    'label'=>'Resultados',     'section'=>'results'],
    ],
    'race-director' => [
        ['icon'=>'layout-dashboard','label'=>'Panel',         'section'=>'overview'],
        ['icon'=>'flag',            'label'=>'Carreras',       'section'=>'races'],
        ['icon'=>'alert-triangle',  'label'=>'Penalizaciones', 'section'=>'penalties'],
        ['icon'=>'list-ordered',    'label'=>'Resultados',     'section'=>'results'],
    ],
    'data-analyst' => [
        ['icon'=>'layout-dashboard','label'=>'Panel',         'section'=>'overview'],
        ['icon'=>'bar-chart-2',     'label'=>'Estadísticas',   'section'=>'stats'],
        ['icon'=>'flag',            'label'=>'Carreras',       'section'=>'races'],
        ['icon'=>'users',           'label'=>'Pilotos',        'section'=>'pilots'],
        ['icon'=>'list-ordered',    'label'=>'Resultados',     'section'=>'results'],
    ],
    'manufacturer-representative' => [
        ['icon'=>'layout-dashboard','label'=>'Panel',         'section'=>'overview'],
        ['icon'=>'building-2',      'label'=>'Mi Fabricante',  'section'=>'manufacturer'],
        ['icon'=>'car',             'label'=>'Vehículos',      'section'=>'vehicles'],
    ],
    'mechanical-boss' => [
        ['icon'=>'layout-dashboard','label'=>'Panel',         'section'=>'overview'],
        ['icon'=>'car',             'label'=>'Mis Vehículos',  'section'=>'vehicles'],
        ['icon'=>'flag',            'label'=>'Carreras',       'section'=>'races'],
    ],
    'team-manager' => [
        ['icon'=>'layout-dashboard','label'=>'Panel',         'section'=>'overview'],
        ['icon'=>'users',           'label'=>'Pilotos',        'section'=>'pilots'],
        ['icon'=>'car',             'label'=>'Vehículos',      'section'=>'vehicles'],
        ['icon'=>'flag',            'label'=>'Carreras',       'section'=>'races'],
        ['icon'=>'list-ordered',    'label'=>'Resultados',     'section'=>'results'],
    ],
    'pilot' => [
        ['icon'=>'layout-dashboard','label'=>'Panel',         'section'=>'overview'],
        ['icon'=>'flag',            'label'=>'Mis Carreras',   'section'=>'races'],
        ['icon'=>'list-ordered',    'label'=>'Mis Resultados', 'section'=>'results'],
    ],
];

$currentNav = $navItems[$role] ?? $navItems['pilot'];
$roleLabel  = $roleLabels[$role] ?? $role;
?>
<!DOCTYPE html>
<html lang="es" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WEC Dashboard</title>
    <link rel="stylesheet" href="/public/css/dashboard.css">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
    <script>
        window.WEC = {
            userId:   <?= (int)$userId ?>,
            role:     <?= json_encode($role) ?>,
            username: <?= json_encode($username) ?>,
        };
    </script>
</head>
<body>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <svg viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg" aria-label="WEC Logo" width="40" height="40">
                <rect width="40" height="40" rx="8" fill="#e10600"/>
                <text x="50%" y="55%" dominant-baseline="middle" text-anchor="middle"
                      font-family="system-ui,sans-serif" font-size="16" font-weight="800" fill="#fff">WEC</text>
            </svg>
            <span class="logo-text">Dashboard</span>
        </div>
        <button class="sidebar-close" id="sidebarClose" aria-label="Cerrar menú">
            <i data-lucide="x"></i>
        </button>
    </div>

    <nav class="sidebar-nav" aria-label="Navegación principal">
        <?php foreach ($currentNav as $item): ?>
        <button class="nav-item <?= $item['section'] === 'overview' ? 'active' : '' ?>"
                data-section="<?= htmlspecialchars($item['section']) ?>"
                type="button">
            <i data-lucide="<?= htmlspecialchars($item['icon']) ?>"></i>
            <span><?= htmlspecialchars($item['label']) ?></span>
        </button>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar" aria-hidden="true">
                <?= htmlspecialchars(mb_strtoupper(mb_substr($username, 0, 1))) ?>
            </div>
            <div class="user-meta">
                <span class="user-name"><?= htmlspecialchars($username) ?></span>
                <span class="user-role"><?= htmlspecialchars($roleLabel) ?></span>
            </div>
        </div>
        <button class="btn-logout" id="logoutBtn" type="button" aria-label="Cerrar sesión">
            <i data-lucide="log-out"></i>
        </button>
    </div>
</aside>

<div class="overlay" id="overlay" aria-hidden="true"></div>

<main class="main" id="main">
    <header class="topbar">
        <button class="btn-menu" id="menuBtn" aria-label="Abrir menú" aria-expanded="false">
            <i data-lucide="menu"></i>
        </button>
        <h1 class="topbar-title" id="topbarTitle">Panel</h1>
        <span class="topbar-role-badge"><?= htmlspecialchars($roleLabel) ?></span>
    </header>

    <div class="content" id="content">

        <section class="section active" data-section="overview">
            <div class="section-header">
                <h2>Bienvenido, <?= htmlspecialchars($username) ?></h2>
                <p class="section-sub">Resumen del campeonato WEC</p>
            </div>
            <div class="kpi-grid" id="kpiGrid">
                <div class="kpi-card skeleton"><div class="sk-line sk-s"></div><div class="sk-line sk-l"></div></div>
                <div class="kpi-card skeleton"><div class="sk-line sk-s"></div><div class="sk-line sk-l"></div></div>
                <div class="kpi-card skeleton"><div class="sk-line sk-s"></div><div class="sk-line sk-l"></div></div>
            </div>
            <div class="card mt-6">
                <div class="card-header">
                    <h3><i data-lucide="calendar"></i> Próximas Carreras</h3>
                </div>
                <div class="table-wrap" id="upcomingRaces">
                    <div class="empty-state"><i data-lucide="flag"></i><p>Cargando calendario…</p></div>
                </div>
            </div>
        </section>

        <section class="section" data-section="pilots">
            <div class="section-header"><h2>Pilotos</h2><p class="section-sub">Listado de pilotos del campeonato</p></div>
            <div class="card"><div class="table-wrap" id="pilotsTable"><div class="empty-state"><i data-lucide="users"></i><p>Cargando pilotos…</p></div></div></div>
        </section>

        <section class="section" data-section="vehicles">
            <div class="section-header"><h2>Vehículos</h2><p class="section-sub">Vehículos inscritos</p></div>
            <div class="card"><div class="table-wrap" id="vehiclesTable"><div class="empty-state"><i data-lucide="car"></i><p>Cargando vehículos…</p></div></div></div>
        </section>

        <section class="section" data-section="races">
            <div class="section-header"><h2>Carreras</h2><p class="section-sub">Calendario de eventos</p></div>
            <div class="card"><div class="table-wrap" id="racesTable"><div class="empty-state"><i data-lucide="flag"></i><p>Cargando carreras…</p></div></div></div>
        </section>

        <section class="section" data-section="teams">
            <div class="section-header"><h2>Equipos</h2><p class="section-sub">Equipos participantes</p></div>
            <div class="card"><div class="table-wrap" id="teamsTable"><div class="empty-state"><i data-lucide="building-2"></i><p>Cargando equipos…</p></div></div></div>
        </section>

        <section class="section" data-section="penalties">
            <div class="section-header"><h2>Penalizaciones</h2><p class="section-sub">Registro de penalizaciones</p></div>
            <div class="card"><div class="table-wrap" id="penaltiesTable"><div class="empty-state"><i data-lucide="alert-triangle"></i><p>Cargando penalizaciones…</p></div></div></div>
        </section>

        <section class="section" data-section="results">
            <div class="section-header"><h2>Resultados</h2><p class="section-sub">Clasificaciones y tiempos</p></div>
            <div class="card"><div class="table-wrap" id="resultsTable"><div class="empty-state"><i data-lucide="list-ordered"></i><p>Cargando resultados…</p></div></div></div>
        </section>

        <section class="section" data-section="stats">
            <div class="section-header"><h2>Estadísticas</h2><p class="section-sub">Análisis de rendimiento</p></div>
            <div class="kpi-grid" id="statsKpi"></div>
        </section>

        <section class="section" data-section="manufacturer">
            <div class="section-header"><h2>Mi Fabricante</h2><p class="section-sub">Datos de tu organización</p></div>
            <div class="card" id="manufacturerCard"><div class="empty-state"><i data-lucide="building-2"></i><p>Cargando datos…</p></div></div>
        </section>

    </div>
</main>

<script src="/public/js/dashboard.js"></script>
</body>
</html>