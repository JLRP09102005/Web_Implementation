<?php
// dashboard.php — vista principal del dashboard
// El servidor solo llega aquí si el usuario está autenticado y tiene sesión válida.
// $user y $role deben ser inyectados por el router/middleware antes de incluir esta vista.
// Ejemplo en routes.php:
//   $router->get('/dashboard', function() {
//       $user = $_SESSION['user'];
//       require __DIR__ . '/../views/dashboard.php';
//   }, Router::ROLE_AUTHENTICATED);

$user     = $user  ?? $_SESSION['user'] ?? [];
$role     = $user['role']     ?? 'readonly-public';
$userName = $user['name']     ?? 'Usuario';
$userId   = $user['id']       ?? 0;

// Menú según rol — qué secciones puede ver cada rol
$navMap = [
    'software-administrator' => ['overview','pilots','vehicles','races','teams','penalties','results','stats'],
    'administratorDB'        => ['overview','pilots','vehicles','races','teams','penalties','results','stats'],
    'data-analyst'           => ['overview','pilots','vehicles','races','teams','penalties','results','stats'],
    'commissioner-boss'      => ['overview','races','penalties','results'],
    'race-director'          => ['overview','races','penalties','results'],
    'mechanical-boss'        => ['overview','races','vehicles'],
    'team-manager'           => ['overview','pilots','vehicles','races','results'],
    'manufacturer-representative' => ['overview','teams','vehicles','manufacturer'],
    'pilot'                  => ['overview','races','results'],
    'readonly-public'        => ['overview','races','pilots'],
];

$allowedSections = $navMap[$role] ?? ['overview'];
$defaultSection  = $allowedSections[0];

$sectionLabels = [
    'overview'     => ['icon' => 'layout-dashboard', 'label' => 'Panel'],
    'pilots'       => ['icon' => 'user-round',        'label' => 'Pilotos'],
    'vehicles'     => ['icon' => 'car',               'label' => 'Vehículos'],
    'races'        => ['icon' => 'flag',              'label' => 'Carreras'],
    'teams'        => ['icon' => 'users',             'label' => 'Equipos'],
    'penalties'    => ['icon' => 'triangle-alert',    'label' => 'Penalizaciones'],
    'results'      => ['icon' => 'trophy',            'label' => 'Resultados'],
    'stats'        => ['icon' => 'bar-chart-2',       'label' => 'Estadísticas'],
    'manufacturer' => ['icon' => 'building-2',        'label' => 'Mi Fabricante'],
];

$roleBadge = [
    'software-administrator'   => 'Administrador',
    'administratorDB'          => 'Admin DB',
    'data-analyst'             => 'Analista',
    'commissioner-boss'        => 'Comisario',
    'race-director'            => 'Director de Carrera',
    'mechanical-boss'          => 'Jefe Mecánico',
    'team-manager'             => 'Team Manager',
    'manufacturer-representative' => 'Fabricante',
    'pilot'                    => 'Piloto',
    'readonly-public'          => 'Público',
];

$initials = mb_strtoupper(implode('', array_map(fn($w) => mb_substr($w, 0, 1), explode(' ', trim($userName)))));
$initials = mb_substr($initials, 0, 2);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — WEC</title>
    <link rel="stylesheet" href="/public/css/dashboard.css">
    <!-- Lucide icons -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
</head>
<body>

<!-- ── Sidebar ─────────────────────────────────────────── -->
<aside class="sidebar" id="sidebar" aria-label="Navegación principal">

    <div class="sidebar-header">
        <div class="sidebar-logo">
            <!-- Logo SVG inline -->
            <svg width="28" height="28" viewBox="0 0 28 28" fill="none" aria-hidden="true">
                <rect width="28" height="28" rx="6" fill="#e10600"/>
                <path d="M5 20 L14 8 L23 20 M9.5 17 H18.5" stroke="white" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span class="logo-text">WEC Manager</span>
        </div>
        <button class="sidebar-close" id="sidebarClose" aria-label="Cerrar menú">
            <i data-lucide="x"></i>
        </button>
    </div>

    <nav class="sidebar-nav" aria-label="Secciones">
        <?php foreach ($allowedSections as $sectionId):
            $info = $sectionLabels[$sectionId] ?? ['icon' => 'circle', 'label' => ucfirst($sectionId)];
        ?>
        <button
            class="nav-item<?= $sectionId === $defaultSection ? ' active' : '' ?>"
            data-section="<?= htmlspecialchars($sectionId) ?>"
            aria-label="<?= htmlspecialchars($info['label']) ?>"
        >
            <i data-lucide="<?= htmlspecialchars($info['icon']) ?>"></i>
            <?= htmlspecialchars($info['label']) ?>
        </button>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar" aria-hidden="true"><?= htmlspecialchars($initials) ?></div>
            <div class="user-meta">
                <span class="user-name"><?= htmlspecialchars($userName) ?></span>
                <span class="user-role"><?= htmlspecialchars($roleBadge[$role] ?? $role) ?></span>
            </div>
        </div>
        <button class="btn-logout" id="logoutBtn" aria-label="Cerrar sesión" title="Cerrar sesión">
            <i data-lucide="log-out"></i>
        </button>
    </div>

</aside>

<!-- Overlay mobile -->
<div class="overlay" id="overlay" aria-hidden="true"></div>

<!-- ── Main ────────────────────────────────────────────── -->
<main class="main">

    <!-- Topbar -->
    <header class="topbar">
        <button class="btn-menu" id="menuBtn" aria-label="Abrir menú" aria-expanded="false">
            <i data-lucide="menu"></i>
        </button>
        <span class="topbar-title" id="topbarTitle"><?= htmlspecialchars($sectionLabels[$defaultSection]['label'] ?? 'Panel') ?></span>
        <span class="topbar-role-badge"><?= htmlspecialchars($roleBadge[$role] ?? $role) ?></span>
    </header>

    <!-- Contenido de secciones -->
    <div class="content">

        <!-- ── Overview ──────────────────────────────── -->
        <?php if (in_array('overview', $allowedSections)): ?>
        <section class="section active" data-section="overview" aria-label="Panel general">
            <div class="section-header">
                <h2>Panel</h2>
                <p class="section-sub">Resumen general del campeonato</p>
            </div>
            <div class="kpi-grid" id="kpiGrid">
                <!-- JS: loadOverview() rellena aquí -->
                <div class="sk-line sk-l skeleton"></div>
                <div class="sk-line sk-l skeleton"></div>
                <div class="sk-line sk-l skeleton"></div>
            </div>
            <div class="card mt-6">
                <div class="card-header">
                    <h3><i data-lucide="flag"></i> Próximas carreras</h3>
                </div>
                <div class="table-wrap" id="upcomingRaces">
                    <div class="sk-line skeleton" style="margin:1rem;"></div>
                    <div class="sk-line skeleton" style="margin:1rem;width:70%"></div>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- ── Pilotos ────────────────────────────────── -->
        <?php if (in_array('pilots', $allowedSections)): ?>
        <section class="section" data-section="pilots" aria-label="Pilotos">
            <div class="section-header">
                <h2>Pilotos</h2>
                <p class="section-sub">Listado de pilotos del campeonato</p>
            </div>
            <div class="card">
                <div class="table-wrap" id="pilotsTable">
                    <div class="sk-line skeleton" style="margin:1rem;"></div>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- ── Vehículos ──────────────────────────────── -->
        <?php if (in_array('vehicles', $allowedSections)): ?>
        <section class="section" data-section="vehicles" aria-label="Vehículos">
            <div class="section-header">
                <h2>Vehículos</h2>
                <p class="section-sub">Vehículos registrados en el campeonato</p>
            </div>
            <div class="card">
                <div class="table-wrap" id="vehiclesTable">
                    <div class="sk-line skeleton" style="margin:1rem;"></div>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- ── Carreras ───────────────────────────────── -->
        <?php if (in_array('races', $allowedSections)): ?>
        <section class="section" data-section="races" aria-label="Carreras">
            <div class="section-header">
                <h2>Carreras</h2>
                <p class="section-sub">Calendario completo de carreras</p>
            </div>
            <div class="card">
                <div class="table-wrap" id="racesTable">
                    <div class="sk-line skeleton" style="margin:1rem;"></div>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- ── Equipos ────────────────────────────────── -->
        <?php if (in_array('teams', $allowedSections)): ?>
        <section class="section" data-section="teams" aria-label="Equipos">
            <div class="section-header">
                <h2>Equipos</h2>
                <p class="section-sub">Equipos participantes en el campeonato</p>
            </div>
            <div class="card">
                <div class="table-wrap" id="teamsTable">
                    <div class="sk-line skeleton" style="margin:1rem;"></div>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- ── Penalizaciones ─────────────────────────── -->
        <?php if (in_array('penalties', $allowedSections)): ?>
        <section class="section" data-section="penalties" aria-label="Penalizaciones">
            <div class="section-header">
                <h2>Penalizaciones</h2>
                <p class="section-sub">Historial de penalizaciones aplicadas</p>
            </div>
            <div class="card">
                <div class="table-wrap" id="penaltiesTable">
                    <div class="sk-line skeleton" style="margin:1rem;"></div>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- ── Resultados ─────────────────────────────── -->
        <?php if (in_array('results', $allowedSections)): ?>
        <section class="section" data-section="results" aria-label="Resultados">
            <div class="section-header">
                <h2>Resultados</h2>
                <p class="section-sub">Clasificaciones y resultados de las carreras</p>
            </div>
            <div class="card">
                <div class="table-wrap" id="resultsTable">
                    <div class="sk-line skeleton" style="margin:1rem;"></div>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- ── Estadísticas ───────────────────────────── -->
        <?php if (in_array('stats', $allowedSections)): ?>
        <section class="section" data-section="stats" aria-label="Estadísticas">
            <div class="section-header">
                <h2>Estadísticas</h2>
                <p class="section-sub">Métricas globales del campeonato</p>
            </div>
            <div class="kpi-grid" id="statsKpi">
                <div class="sk-line sk-l skeleton"></div>
            </div>
        </section>
        <?php endif; ?>

        <!-- ── Mi Fabricante ──────────────────────────── -->
        <?php if (in_array('manufacturer', $allowedSections)): ?>
        <section class="section" data-section="manufacturer" aria-label="Mi fabricante">
            <div class="section-header">
                <h2>Mi Fabricante</h2>
                <p class="section-sub">Información de tu fabricante</p>
            </div>
            <div class="card" id="manufacturerCard">
                <div class="sk-line skeleton" style="margin:1rem;"></div>
                <div class="sk-line sk-s skeleton" style="margin:1rem;"></div>
            </div>
        </section>
        <?php endif; ?>

    </div><!-- /.content -->
</main><!-- /.main -->

<!-- Inyectar contexto de sesión para el JS -->
<script>
    window.WEC = {
        userId: <?= (int)$userId ?>,
        role:   <?= json_encode($role) ?>,
    };
</script>
<script src="/public/js/dashboard.js" defer></script>

</body>
</html>