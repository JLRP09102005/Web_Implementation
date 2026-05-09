<?php
$user   = $_SESSION['user'];
$role   = $user['role'];
$userId = (int)$user['id'];
$username = htmlspecialchars($user['username'] ?? 'Usuario');
$initial  = strtoupper(mb_substr($username, 0, 1));
$roleLabel = htmlspecialchars($role);

// ── Secciones visibles por rol ───────────────────────────────
$sectionMap = [
    'administratorDB' => ['panel','carreras','pilotos','equipos','vehiculos','penalizaciones','resultados','inscripciones','estadisticas','administracion'],
    'commissioner-boss'           => ['panel','carreras','penalizaciones','resultados'],
    'manufacturer-representative' => ['panel','equipos','vehiculos','fabricante'],
    'mechanical-boss'             => ['panel','vehiculos','carreras'],
    'pilot'                       => ['panel','pilotos','resultados','inscripciones'],
    'team-manager'                => ['panel','pilotos','equipos','vehiculos','penalizaciones','resultados','inscripciones'],
    'race-director'               => ['panel','carreras','penalizaciones','resultados','estadisticas'],
    'data-analyst'                => ['panel','carreras','pilotos','equipos','vehiculos','penalizaciones','resultados','inscripciones','estadisticas'],
    'readonly-public'             => ['panel','carreras','pilotos','resultados'],
];

$visible = $sectionMap[$role] ?? ['panel'];

// ── Labels e iconos del sidebar ──────────────────────────────
$navItems = [
    'panel'          => ['label' => 'Panel',          'icon' => 'layout-dashboard'],
    'carreras'       => ['label' => 'Carreras',        'icon' => 'flag'],
    'pilotos'        => ['label' => 'Pilotos',         'icon' => 'user'],
    'equipos'        => ['label' => 'Equipos',         'icon' => 'users'],
    'vehiculos'      => ['label' => 'Vehículos',       'icon' => 'car'],
    'penalizaciones' => ['label' => 'Penalizaciones',  'icon' => 'alert-triangle'],
    'resultados'     => ['label' => 'Resultados',      'icon' => 'trophy'],
    'inscripciones'  => ['label' => 'Inscripciones',   'icon' => 'clipboard-list'],
    'estadisticas'   => ['label' => 'Estadísticas',    'icon' => 'bar-chart-2'],
    'fabricante'     => ['label' => 'Mi Fabricante',   'icon' => 'factory'],
    'administracion' => ['label' => 'Administración',  'icon' => 'settings'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WEC — Panel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/public/css/dashboard.css">
</head>
<body>

<!-- Overlay mobile -->
<div class="overlay" id="overlay" aria-hidden="true"></div>

<!-- ── Sidebar ── -->
<aside class="sidebar" id="sidebar" role="navigation" aria-label="Navegación principal">

    <div class="sidebar-header">
        <div class="sidebar-logo">
            <svg width="28" height="28" viewBox="0 0 48 48" fill="none" aria-hidden="true">
                <circle cx="24" cy="24" r="22" stroke="#e10600" stroke-width="2.5"/>
                <path d="M14 24 L20 16 L24 22 L28 16 L34 24 L28 32 L24 26 L20 32 Z" fill="#e10600"/>
            </svg>
            <span class="logo-text">WEC</span>
        </div>
        <button class="sidebar-close" id="sidebarClose" aria-label="Cerrar menú">
            <i data-lucide="x" width="18" height="18"></i>
        </button>
    </div>

    <nav class="sidebar-nav" aria-label="Secciones">
        <?php foreach ($visible as $sectionId):
            $item = $navItems[$sectionId] ?? ['label' => $sectionId, 'icon' => 'circle'];
        ?>
        <button
            class="nav-item<?= $sectionId === 'panel' ? ' active' : '' ?>"
            data-section="<?= $sectionId ?>"
            aria-current="<?= $sectionId === 'panel' ? 'page' : 'false' ?>"
        >
            <i data-lucide="<?= $item['icon'] ?>" width="16" height="16" aria-hidden="true"></i>
            <?= htmlspecialchars($item['label']) ?>
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

<!-- ── Main ── -->
<div class="main">

    <!-- Topbar -->
    <header class="topbar">
        <button class="btn-menu" id="btnMenu" aria-label="Abrir menú" aria-expanded="false" aria-controls="sidebar">
            <i data-lucide="menu" width="20" height="20" aria-hidden="true"></i>
        </button>
        <h1 class="topbar-title" id="topbarTitle">Panel</h1>
        <span class="topbar-role-badge"><?= $roleLabel ?></span>
    </header>

    <!-- Content -->
    <main class="content" id="mainContent">

        <!-- ── PANEL ── -->
        <?php if (in_array('panel', $visible)): ?>
        <section class="section active" id="sec-panel" aria-label="Panel">
            <div class="section-header">
                <h2>Panel de control</h2>
                <p class="section-sub">Resumen del campeonato</p>
            </div>
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
            </div>
            <div class="card mt-6">
                <div class="card-header">
                    <h3><i data-lucide="flag" width="15" height="15" aria-hidden="true"></i> Próximas carreras</h3>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr>
                            <th>Carrera</th><th>Circuito</th><th>País</th><th>Fecha</th><th>Duración</th>
                        </tr></thead>
                        <tbody id="overviewRacesBody">
                            <tr><td colspan="5" class="empty-state-cell">Cargando...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- ── CARRERAS ── -->
        <?php if (in_array('carreras', $visible)): ?>
        <section class="section" id="sec-carreras" aria-label="Carreras">
            <div class="section-header">
                <h2>Carreras</h2>
                <p class="section-sub">Calendario del campeonato</p>
            </div>
            <div class="card">
                <div class="card-header">
                    <h3><i data-lucide="flag" width="15" height="15" aria-hidden="true"></i> Calendario</h3>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr>
                            <th>Carrera</th><th>Circuito</th><th>País</th><th>Fecha</th><th>Duración</th>
                        </tr></thead>
                        <tbody id="racesBody">
                            <tr><td colspan="5" class="empty-state-cell">Cargando...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- ── PILOTOS ── -->
        <?php if (in_array('pilotos', $visible)): ?>
        <section class="section" id="sec-pilotos" aria-label="Pilotos">
            <div class="section-header">
                <h2>Pilotos</h2>
                <p class="section-sub">Pilotos del campeonato</p>
            </div>
            <div class="card">
                <div class="card-header">
                    <h3><i data-lucide="user" width="15" height="15" aria-hidden="true"></i> Listado</h3>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr>
                            <th>Nombre</th><th>Edad</th><th>Categoría</th>
                        </tr></thead>
                        <tbody id="pilotsBody">
                            <tr><td colspan="3" class="empty-state-cell">Cargando...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- ── EQUIPOS ── -->
        <?php if (in_array('equipos', $visible)): ?>
        <section class="section" id="sec-equipos" aria-label="Equipos">
            <div class="section-header">
                <h2>Equipos</h2>
                <p class="section-sub">Equipos participantes</p>
            </div>
            <div class="card">
                <div class="card-header">
                    <h3><i data-lucide="users" width="15" height="15" aria-hidden="true"></i> Listado</h3>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr>
                            <th>Equipo</th><th>Fabricante</th><th>Mecánicos</th>
                        </tr></thead>
                        <tbody id="teamsBody">
                            <tr><td colspan="3" class="empty-state-cell">Cargando...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- ── VEHÍCULOS ── -->
        <?php if (in_array('vehiculos', $visible)): ?>
        <section class="section" id="sec-vehiculos" aria-label="Vehículos">
            <div class="section-header">
                <h2>Vehículos</h2>
                <p class="section-sub">Flota registrada</p>
            </div>
            <div class="card">
                <div class="card-header">
                    <h3><i data-lucide="car" width="15" height="15" aria-hidden="true"></i> Vehículos</h3>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr>
                            <th>Modelo</th><th>Especificaciones</th>
                        </tr></thead>
                        <tbody id="vehiclesBody">
                            <tr><td colspan="2" class="empty-state-cell">Cargando...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- ── PENALIZACIONES ── -->
        <?php if (in_array('penalizaciones', $visible)): ?>
        <section class="section" id="sec-penalizaciones" aria-label="Penalizaciones">
            <div class="section-header">
                <h2>Penalizaciones</h2>
                <p class="section-sub">Historial de penalizaciones</p>
            </div>
            <div class="card">
                <div class="card-header">
                    <h3><i data-lucide="alert-triangle" width="15" height="15" aria-hidden="true"></i> Penalizaciones</h3>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr>
                            <th>Tipo</th><th>Motivo</th><th>Valor</th><th>Aplica a</th>
                        </tr></thead>
                        <tbody id="penaltiesBody">
                            <tr><td colspan="4" class="empty-state-cell">Cargando...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- ── RESULTADOS ── -->
        <?php if (in_array('resultados', $visible)): ?>
        <section class="section" id="sec-resultados" aria-label="Resultados">
            <div class="section-header">
                <h2>Resultados</h2>
                <p class="section-sub">Clasificaciones de carrera</p>
            </div>
            <div class="card">
                <div class="card-header">
                    <h3><i data-lucide="trophy" width="15" height="15" aria-hidden="true"></i> Tabla de resultados</h3>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr>
                            <th>Pos.</th><th>Vehículo</th><th>Tiempo</th><th>Puntos equipo</th><th>Puntos piloto</th>
                        </tr></thead>
                        <tbody id="resultsBody">
                            <tr><td colspan="5" class="empty-state-cell">Cargando...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- ── INSCRIPCIONES ── -->
        <?php if (in_array('inscripciones', $visible)): ?>
        <section class="section" id="sec-inscripciones" aria-label="Inscripciones">
            <div class="section-header">
                <h2>Inscripciones</h2>
                <p class="section-sub">Inscripciones al campeonato</p>
            </div>
            <div class="card">
                <div class="card-header">
                    <h3><i data-lucide="clipboard-list" width="15" height="15" aria-hidden="true"></i> Inscripciones</h3>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr>
                            <th>Equipo</th><th>Carrera</th><th>Vehículo</th>
                        </tr></thead>
                        <tbody id="inscriptionsBody">
                            <tr><td colspan="3" class="empty-state-cell">Cargando...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- ── ESTADÍSTICAS ── -->
        <?php if (in_array('estadisticas', $visible)): ?>
        <section class="section" id="sec-estadisticas" aria-label="Estadísticas">
            <div class="section-header">
                <h2>Estadísticas</h2>
                <p class="section-sub">Métricas globales del campeonato</p>
            </div>
            <div class="card">
                <div class="card-header">
                    <h3><i data-lucide="bar-chart-2" width="15" height="15" aria-hidden="true"></i> Puntos por equipo (Top 10)</h3>
                </div>
                <div style="padding:1rem">
                    <canvas id="chartTeamPoints" height="300"></canvas>
                </div>
            </div>
            <div class="card mt-6">
                <div class="card-header">
                    <h3><i data-lucide="alert-triangle" width="15" height="15" aria-hidden="true"></i> Penalizaciones por tipo</h3>
                </div>
                <div style="padding:1rem">
                    <canvas id="chartPenaltyTypes" height="260"></canvas>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- ── MI FABRICANTE ── -->
        <?php if (in_array('fabricante', $visible)): ?>
        <section class="section" id="sec-fabricante" aria-label="Mi Fabricante">
            <div class="section-header">
                <h2>Mi Fabricante</h2>
                <p class="section-sub">Información de tu fabricante</p>
            </div>
            <div class="card" id="manufacturerCard">
                <div style="padding:1.5rem" id="manufacturerInfo">
                    <div class="sk-line sk-l"></div>
                    <div class="sk-line sk-s"></div>
                </div>
            </div>
            <div class="card mt-6">
                <div class="card-header">
                    <h3><i data-lucide="users" width="15" height="15" aria-hidden="true"></i> Equipos asociados</h3>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Equipo</th><th>Mecánicos</th></tr></thead>
                        <tbody id="manufacturerTeamsBody">
                            <tr><td colspan="2" class="empty-state-cell">Cargando...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- ── ADMINISTRACIÓN ── -->
        <?php if (in_array('administracion', $visible)): ?>
        <section class="section" id="sec-administracion" aria-label="Administración">
            <div class="section-header">
                <h2>Administración</h2>
                <p class="section-sub">Gestión completa de datos y usuarios</p>
            </div>

            <!-- Tabs entidades -->
            <div class="admin-tabs" role="tablist" aria-label="Entidades">
                <?php foreach ([
                    'pilots'  => 'Pilotos',
                    'teams'   => 'Equipos',
                    'vehicles'=> 'Vehículos',
                    'races'   => 'Carreras',
                    'circuits'=> 'Circuitos',
                    'manufacturers' => 'Fabricantes',
                    'penalties'=> 'Penalizaciones',
                    'results' => 'Resultados',
                ] as $eid => $elabel): ?>
                <button class="admin-tab<?= $eid === 'pilots' ? ' active' : '' ?>"
                    role="tab"
                    data-entity="<?= $eid ?>"
                    aria-selected="<?= $eid === 'pilots' ? 'true' : 'false' ?>">
                    <?= $elabel ?>
                </button>
                <?php endforeach; ?>
            </div>

            <div class="card mt-6">
                <div class="card-header">
                    <h3 id="adminTableTitle"><i data-lucide="database" width="15" height="15" aria-hidden="true"></i> Datos</h3>
                    <button class="btn-add" id="btnAdminAdd" aria-label="Añadir registro">
                        <i data-lucide="plus" width="14" height="14" aria-hidden="true"></i>
                        Añadir
                    </button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead id="adminTableHead"><tr><th>—</th></tr></thead>
                        <tbody id="adminTableBody">
                            <tr><td class="empty-state-cell">Selecciona una entidad</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
        <?php endif; ?>

    </main>
</div>

<!-- Modal CRUD -->
<div class="modal-overlay" id="crudModalOverlay" hidden aria-modal="true" role="dialog" aria-labelledby="crudModalTitle">
    <div class="modal">
        <div class="modal-header">
            <h3 id="crudModalTitle">Añadir</h3>
            <button class="modal-close" id="crudModalClose" aria-label="Cerrar">
                <i data-lucide="x" width="18" height="18" aria-hidden="true"></i>
            </button>
        </div>
        <form id="crudModalForm" class="modal-body" novalidate>
            <div id="crudModalFields"></div>
            <div class="modal-footer">
                <button type="button" class="btn-ghost-sm" id="crudModalCancel">Cancelar</button>
                <button type="submit" class="btn-primary-sm" id="crudModalSubmit">Guardar</button>
            </div>
        </form>
    </div>
</div>

<!-- Toast -->
<div class="toast-container" id="toastContainer" aria-live="polite" aria-atomic="true"></div>

<!-- Datos para JS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
<script>
    window.WEC = {
        userId:  <?= $userId ?>,
        role:    <?= json_encode($role) ?>,
        visible: <?= json_encode($visible) ?>
    };
</script>
<script src="/public/js/dashboard.js"></script>
</body>
</html>