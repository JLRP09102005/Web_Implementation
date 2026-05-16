<?php

namespace App\Controllers;

use App\Core\Container;
use App\Models\RaceModel;
use App\Models\PilotModel;
use App\Models\TeamModel;
use App\Models\VehicleModel;
use App\Models\PenaltyModel;
use App\Models\ResultModel;
use App\Models\ManufacturerModel;

class DashboardController {

    public function __construct(
        private RaceModel $raceModel,
        private PilotModel $pilotModel,
        private TeamModel $teamModel,
        private VehicleModel $vehicleModel,
        private PenaltyModel $penaltyModel,
        private ResultModel $resultModel,
        private ManufacturerModel $manufacturerModel
    ) {}

    public function __clone()  { throw new \Exception("No clonable DashboardController"); }
    public function __sleep()  { throw new \Exception("No serializable DashboardController"); }
    public function __wakeup() { throw new \Exception("No unserializable DashboardController"); }

    // ── Helpers ──────────────────────────────────────────────

    private function verifyAdminPassword(string $password): void
    {
        $user = $this->session();
        $adminId = (int)$user['id'];

        if (!$this->isAdmin($user['role'])) { http_Response_code(401); $this->json(['error' => 401, 'message' => 'No authorized']); }

        $pdo = Container::getInstance()->make('db.admin');
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id_user = ?");
        $stmt->execute([$adminId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!isset($row)) { http_Response_code(403); $this->json(['error' => 403, 'message' => 'Administrador no encontrado']); }

        $adminHash = $row['password_hash'];

        if (!password_verify($password, $adminHash)) { http_Response_code(403); $this->json(['error' => 403, 'message' => 'Contraseña de administrador incorrecta']); }
    }

    private function requireAuth(): void
    {
        if (empty($_SESSION['user'])) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['error' => 401, 'message' => 'No authenticated']);
            exit;
        }
    }

    private function json(mixed $data): void
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    private function session(): array { return $_SESSION['user'] ?? [] ; }

    private function isAdmin(string $role): bool
    {
        return in_array($role, ['software-administrator', 'administratorDB']);
    }

    // ── PUBLIC methods ────────────────────────────────────
    public function revealHash(array $urlParams): void
    {
        $this->requireAuth();
        $user = $this->session();

        if (!$this->isAdmin($user['role'])) { http_Response_code(403); $this->json(['error' => 403, 'message' => 'Fordidden']); }

        $body = json_decode(file_get_contents('php://input'), true);
        $targetUserId = (int)($body['target_user_id'] ?? 0);
        $adminPassword = $body['admin_password'] ?? '';

        if (!$targetUserId || !$adminPassword) { http_Response_code(400); $this->json(['error' => 400, 'message' => 'Missing parameters']); }

        $this->verifyAdminPassword($adminPassword);

        $pdo = Container::getInstance()->make('db.admin');
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id_user = ?");
        $stmt->execute([$targetUserId]);
        $hash = $stmt->fetchColumn();

        if (!isset($hash) || $hash === false) { http_Response_code(404); $this->json(['error' => 404, 'message' => 'User not found']); }

        $this->json(['hash' => $hash]);
    }
    
    // ── GET /api/overview ────────────────────────────────────

    public function overview(array $urlParams): void
    {
        $this->requireAuth();
        $user   = $this->session();
        $role   = $user['role'];
        $userId = (int)$user['id'];

        try {
            $races    = $this->raceModel->getPublicCalendar();
            $pilots   = $this->pilotModel->getPublicList();
            $teams    = $this->teamModel->getPublicList();
            $vehicles  = [];
            $penalties = [];

            if ($this->isAdmin($role)) {
                $races    = $this->raceModel->getAllAdmin($userId);
                $pilots   = $this->pilotModel->getAllAdmin($userId);
                $teams    = $this->teamModel->getAllAdmin($userId);
                $vehicles  = $this->vehicleModel->getAllAdmin($userId);
                $penalties = $this->penaltyModel->getAllAdmin($userId);
            } elseif ($role === 'data-analyst') {
                $races    = $this->raceModel->getAllAnalyst($userId);
                $pilots   = $this->pilotModel->getAllAnalyst($userId);
                $teams    = $this->teamModel->getAllAnalyst($userId);
                $vehicles  = $this->vehicleModel->getAllAnalyst($userId);
                $penalties = $this->penaltyModel->getAllAnalyst($userId);
            } elseif ($role === 'comissioner-boss') {
                $races    = $this->raceModel->getAllCommissioner($userId);
                $penalties = $this->penaltyModel->getAllCommissioner($userId);
            } elseif ($role === 'race-director') {
                $races    = $this->raceModel->getAllRaceDirector($userId);
                $penalties = $this->penaltyModel->getAllRaceDirector($userId);
            } elseif ($role === 'team-manager') {
                $pilots   = $this->pilotModel->getAllTeamManager($userId);
                $teams    = $this->teamModel->getMyTeamManager($userId);
                $vehicles  = $this->vehicleModel->getMyTeamManager($userId);
            } elseif ($role === 'mechanical-boss') {
                $races    = $this->raceModel->getAllMechanical($userId);
                $vehicles  = $this->vehicleModel->getMyMechanical($userId);
            } elseif ($role === 'manufacturer-representative') {
                $teams    = $this->teamModel->getMyManufacturer($userId);
                $vehicles  = $this->vehicleModel->getMyManufacturer($userId);
            }

            $this->json([
                'total_races'     => count($races),
                'total_pilots'    => count($pilots),
                'total_teams'     => count($teams),
                'total_vehicles'  => count($vehicles),
                'total_penalties' => count($penalties),
                'races'           => $races,
            ]);
        } catch (\Throwable $e) {
            http_Response_code(500);
            $this->json(['error' => 500, 'message' => $e->getMessage()]);
        }
    }

    // ── GET /api/pilots ──────────────────────────────────────

    public function pilots(array $urlParams): void
    {
        $this->requireAuth();
        $user   = $this->session();
        $role   = $user['role'];
        $userId = (int)$user['id'];

        try {
            if ($this->isAdmin($role))          { $rows = $this->pilotModel->getAllAdmin($userId); }
            elseif ($role === 'data-analyst')   { $rows = $this->pilotModel->getAllAnalyst($userId); }
            elseif ($role === 'team-manager')   { $rows = $this->pilotModel->getAllTeamManager($userId); }
            elseif ($role === 'pilot')          { $rows = $this->pilotModel->getAllTeamManager($userId); }
            else                                { $rows = $this->pilotModel->getPublicList(); }

            $this->json($rows);
        } catch (\Throwable $e) {
            http_Response_code(500);
            $this->json(['error' => 500, 'message' => $e->getMessage()]);
        }
    }

    // ── GET /api/races ───────────────────────────────────────

    public function races(array $urlParams): void
    {
        $this->requireAuth();
        $user   = $this->session();
        $role   = $user['role'];
        $userId = (int)$user['id'];

        try {
            if ($this->isAdmin($role))                { $rows = $this->raceModel->getAllAdmin($userId); }
            elseif ($role === 'data-analyst')         { $rows = $this->raceModel->getAllAnalyst($userId); }
            elseif ($role === 'comissioner-boss')     { $rows = $this->raceModel->getAllCommissioner($userId); }
            elseif ($role === 'race-director')        { $rows = $this->raceModel->getAllRaceDirector($userId); }
            elseif ($role === 'mechanical-boss')      { $rows = $this->raceModel->getAllMechanical($userId); }
            elseif ($role === 'team-manager')         { $rows = $this->raceModel->getAllTeamManager($userId); }
            else                                      { $rows = $this->raceModel->getPublicCalendar(); }

            $this->json($rows);
        } catch (\Throwable $e) {
            http_Response_code(500);
            $this->json(['error' => 500, 'message' => $e->getMessage()]);
        }
    }

    // ── GET /api/teams ───────────────────────────────────────

    public function teams(array $urlParams): void
    {
        $this->requireAuth();
        $user   = $this->session();
        $role   = $user['role'];
        $userId = (int)$user['id'];

        try {
            if ($this->isAdmin($role))                        { $rows = $this->teamModel->getAllAdmin($userId); }
            elseif ($role === 'data-analyst')                 { $rows = $this->teamModel->getAllAnalyst($userId); }
            elseif ($role === 'manufacturer-representative')  { $rows = $this->teamModel->getMyManufacturer($userId); }
            elseif ($role === 'team-manager')                 { $rows = $this->teamModel->getMyTeamManager($userId); }
            else                                              { $rows = $this->teamModel->getPublicList(); }

            $this->json($rows);
        } catch (\Throwable $e) {
            http_Response_code(500);
            $this->json(['error' => 500, 'message' => $e->getMessage()]);
        }
    }

    // ── GET /api/vehicles ────────────────────────────────────

    public function vehicles(array $urlParams): void
    {
        $this->requireAuth();
        $user   = $this->session();
        $role   = $user['role'];
        $userId = (int)$user['id'];

        try {
            if ($this->isAdmin($role))                        { $rows = $this->vehicleModel->getAllAdmin($userId); }
            elseif ($role === 'data-analyst')                 { $rows = $this->vehicleModel->getAllAnalyst($userId); }
            elseif ($role === 'mechanical-boss')              { $rows = $this->vehicleModel->getMyMechanical($userId); }
            elseif ($role === 'manufacturer-representative')  { $rows = $this->vehicleModel->getMyManufacturer($userId); }
            elseif ($role === 'team-manager')                 { $rows = $this->vehicleModel->getMyTeamManager($userId); }
            else                                              { $rows = []; }

            $this->json($rows);
        } catch (\Throwable $e) {
            http_Response_code(500);
            $this->json(['error' => 500, 'message' => $e->getMessage()]);
        }
    }

    // ── GET /api/penalties ───────────────────────────────────

    public function penalties(array $urlParams): void
    {
        $this->requireAuth();
        $user   = $this->session();
        $role   = $user['role'];
        $userId = (int)$user['id'];

        try {
            if ($this->isAdmin($role))              { $rows = $this->penaltyModel->getAllAdmin($userId); }
            elseif ($role === 'data-analyst')       { $rows = $this->penaltyModel->getAllAnalyst($userId); }
            elseif ($role === 'comissioner-boss')   { $rows = $this->penaltyModel->getAllCommissioner($userId); }
            elseif ($role === 'race-director')      { $rows = $this->penaltyModel->getAllRaceDirector($userId); }
            // elseif ($role === 'team-manager')       { $rows = $this->penaltyModel->getAllTeamManager($userId); }
            else                                    { $rows = []; }

            $this->json($rows);
        } catch (\Throwable $e) {
            http_Response_code(500);
            $this->json(['error' => 500, 'message' => $e->getMessage()]);
        }
    }

    // ── GET /api/results ─────────────────────────────────────

    public function results(array $urlParams): void
    {
        $this->requireAuth();
        $user   = $this->session();
        $role   = $user['role'];
        $userId = (int)$user['id'];

        try {
            if ($this->isAdmin($role))              { $rows = $this->resultModel->getAllAdmin($userId); }
            elseif ($role === 'data-analyst')       { $rows = $this->resultModel->getAllAnalyst($userId); }
            elseif ($role === 'comissioner-boss')   { $rows = $this->resultModel->getAllCommissioner($userId); }
            elseif ($role === 'race-director')      { $rows = $this->resultModel->getAllRaceDirector($userId); }
            elseif ($role === 'team-manager')       { $rows = $this->resultModel->getMyTeamManager($userId); }
            elseif ($role === 'pilot')              { $rows = $this->resultModel->getMyPilot($userId); }
            else                                    { $rows = []; }

            $this->json($rows);
        } catch (\Throwable $e) {
            http_Response_code(500);
            $this->json(['error' => 500, 'message' => $e->getMessage()]);
        }
    }

    // ── GET /api/manufacturer ────────────────────────────────

    public function manufacturer(array $urlParams): void
    {
        $this->requireAuth();
        $userId = (int)$this->session()['id'];

        try {
            $data = $this->manufacturerModel->getMyData($userId);
            http_Response_code(404);
            $this->json($data ?: ['error' => 404, 'message' => 'No manufacturer data']);
        } catch (\Throwable $e) {
            http_Response_code(500);
            $this->json(['error' => 500, 'message' => $e->getMessage()]);
        }
    }

    // ── GET /api/inscriptions ────────────────────────────────

    public function inscriptions(array $urlParams): void
    {
        $this->requireAuth();
        $user   = $this->session();
        $role   = $user['role'];
        $userId = (int)$user['id'];

        try {
            if ($this->isAdmin($role))          { $rows = $this->callSP('sp_admin_all_inscriptions', $userId); }
            elseif ($role === 'data-analyst')   { $rows = $this->callSP('sp_analyst_all_inscriptions', $userId); }
            elseif ($role === 'team-manager')   { $rows = $this->callSP('sp_teammanager_my_inscriptions', $userId); }
            elseif ($role === 'pilot')          { $rows = $this->callSP('sp_pilot_my_inscriptions', $userId); }
            else                                { $rows = []; }

            $this->json($rows);
        } catch (\Throwable $e) {
            http_Response_code(500);
            $this->json(['error' => 500, 'message' => $e->getMessage()]);
        }
    }

    // ── GET /api/stats ───────────────────────────────────────

    public function stats(array $urlParams): void
    {
        $this->requireAuth();
        $user   = $this->session();
        $role   = $user['role'];
        $userId = (int)$user['id'];

        $allowed = ['software-administrator', 'administratorDB', 'data-analyst', 'race-director'];
        if (!in_array($role, $allowed)) {
            $this->json(['error' => 403, 'message' => 'Forbidden']);
        }

        try {
            $results        = $this->callSP('sp_admin_all_results', $userId);
            $penalties      = $this->callSP('sp_admin_all_penalties', $userId);
            $pilots         = $this->callSP('sp_admin_all_pilots', $userId);
            $pilotInscrip   = $this->callSP('sp_admin_all_pilots_inscriptions', $userId);

            // 1. Puntos por equipo (Top 10)
            $teamPoints = [];
            foreach ($results as $r) {
                $team = $r['team_name'] ?? 'Unknown';
                $teamPoints[$team] = ($teamPoints[$team] ?? 0) + (int)($r['base_points_team'] ?? 0);
            }
            arsort($teamPoints);
            $teamPoints = array_slice($teamPoints, 0, 10, true);

            // 2. Penalizaciones por tipo
            $penTypes = [];
            foreach ($penalties as $p) {
                $type = $p['penalty_type'] ?? 'Unknown';
                $penTypes[$type] = ($penTypes[$type] ?? 0) + 1;
            }

            // 3. Participaciones por carrera
            $raceParticip = [];
            foreach ($results as $r) {
                $race = $r['event_name'] ?? 'Unknown';
                $raceParticip[$race] = ($raceParticip[$race] ?? 0) + 1;
            }

            // 4. Valor total de penalizaciones por equipo (Top 10)
            $penByTeam = [];
            foreach ($penalties as $p) {
                $team = $p['team_name'] ?? 'Sin equipo';
                if (!$team) continue;
                $penByTeam[$team] = ($penByTeam[$team] ?? 0) + (float)($p['penalty_value'] ?? 0);
            }
            arsort($penByTeam);
            $penByTeam = array_slice($penByTeam, 0, 10, true);

            // 5. Distribución de edades de pilotos
            $ageGroups = ['18-25' => 0, '26-30' => 0, '31-35' => 0, '36-40' => 0, '41+' => 0];
            foreach ($pilots as $p) {
                $age = (int)($p['pilot_age'] ?? 0);
                if ($age <= 25)      $ageGroups['18-25']++;
                elseif ($age <= 30)  $ageGroups['26-30']++;
                elseif ($age <= 35)  $ageGroups['31-35']++;
                elseif ($age <= 40)  $ageGroups['36-40']++;
                else                 $ageGroups['41+']++;
            }

            // 6. Puntos por piloto (Top 10, via pilots_inscriptions + results)
            $pilotPoints = [];
            foreach ($pilotInscrip as $pi) {
                $pilotName = $pi['pilot_name'] ?? 'Unknown';
                // Buscar resultado matching vehicle+race+team
                foreach ($results as $r) {
                    if ($r['id_vehicle'] == $pi['id_vehicle']
                        && $r['id_race']    == $pi['id_race']
                        && $r['id_team']    == $pi['id_team']) {
                        $pilotPoints[$pilotName] = ($pilotPoints[$pilotName] ?? 0)
                            + (int)($r['base_points_pilot'] ?? 0)
                            - (int)($r['penalty_points_pilot'] ?? 0);
                        break;
                    }
                }
            }
            arsort($pilotPoints);
            $pilotPoints = array_slice($pilotPoints, 0, 10, true);

            // 7. Número de penalizaciones por equipo
            $penCountByTeam = [];
            foreach ($penalties as $p) {
                $team = $p['team_name'] ?? null;
                if (empty($team)) continue;
                $penCountByTeam[$team] = ($penCountByTeam[$team] ?? 0) + 1;
            }
            arsort($penCountByTeam);
            $penCountByTeam = array_slice($penCountByTeam, 0, 10, true);

            $this->json([
                'team_points'       => $teamPoints,
                'penalty_types'     => $penTypes,
                'race_participations' => $raceParticip,
                'penalty_by_team'   => $penByTeam,
                'age_groups'        => $ageGroups,
                'pilot_points'      => $pilotPoints,
                'penalty_points_team' => $penCountByTeam,
            ]);

        } catch (\Throwable $e) {
            $this->json(['error' => 500, 'message' => $e->getMessage()]);
        }
    }


    // ── GET /api/admin/list ──────────────────────────────────

    public function adminList(array $urlParams): void
    {
        $this->requireAuth();
        $user   = $this->session();
        $role   = $user['role'];
        $userId = (int)$user['id'];

        if (!$this->isAdmin($role)) {
            http_Response_code(403);
            $this->json(['error' => 403, 'message' => 'Forbidden']);
        }

        $entity = $_GET['entity'] ?? '';

        $spMap = [
            'pilots'        => 'sp_admin_all_pilots',
            'teams'         => 'sp_admin_all_teams',
            'vehicles'      => 'sp_admin_all_vehicles',
            'races'         => 'sp_admin_all_races',
            'circuits'      => 'sp_admin_all_circuits',
            'manufacturers' => 'sp_admin_all_manufacturers',
            'inscriptions'  => 'sp_admin_all_inscriptions',
            'results'       => 'sp_admin_all_results',
            'penalties'     => 'sp_admin_all_penalties',
            'users'         => 'sp_admin_all_users',
        ];

        if (!isset($spMap[$entity])) {
            http_Response_code(400);
            $this->json(['error' => 400, 'message' => "Unknown entity: $entity"]);
        }

        try {
            $this->json($this->callSP($spMap[$entity], $userId));
        } catch (\Throwable $e) {
            http_Response_code(500);
            $this->json(['error' => 500, 'message' => $e->getMessage()]);
        }
    }

    // ── GET /api/pilot-categories ────────────────────────────────────
    public function pilotCategories(array $urlParams): void
    {
        $this->requireAuth();
        $user = $this->session();
        if (!$this->isAdmin($user['role'])) {
            $this->json(['error' => 403, 'message' => 'Forbidden']);
        }
        try {
            $pdo  = Container::getInstance()->make('db.readonly');
            $stmt = $pdo->prepare("SELECT id_pilot_category, pilot_category_name FROM pilot_categories ORDER BY pilot_category_name");
            $stmt->execute();
            $this->json($stmt->fetchAll(\PDO::FETCH_ASSOC));
        } catch (\Throwable $e) {
            $this->json(['error' => 500, 'message' => $e->getMessage()]);
        }
    }

    // ── GET /api/circuits ──────────────────────────────────
    public function circuits(array $urlParams): void
    {
        $this->requireAuth();
        try {
            $pdo  = Container::getInstance()->make('db.readonly');
            $stmt = $pdo->query("SELECT id_circuit, circuit_name, country FROM circuits ORDER BY circuit_name");
            $this->json($stmt->fetchAll(\PDO::FETCH_ASSOC));
        } catch (\Throwable $e) { $this->json(['error' => 500, 'message' => $e->getMessage()]); }
    }

    // ── GET /api/manufacturers ──────────────────────────────────
    public function manufacturers(array $urlParams): void
    {
        $this->requireAuth();
        try {
            $pdo  = Container::getInstance()->make('db.readonly');
            $stmt = $pdo->query("SELECT id_manufacturer, manufacturer_name FROM manufacturers ORDER BY manufacturer_name");
            $this->json($stmt->fetchAll(\PDO::FETCH_ASSOC));
        } catch (\Throwable $e) { $this->json(['error' => 500, 'message' => $e->getMessage()]); }
    }

    // ── POST /api/admin/crud ─────────────────────────────────
    public function adminCrud(array $urlParams): void
    {
        $this->requireAuth();
        $user = $this->session();
        if (!$this->isAdmin($user['role'])) {
            http_Response_code(403);
            $this->json(['error' => 403, 'message' => 'Forbidden']);
        }

        $body   = json_decode(file_get_contents('php://input'), true) ?? [];
        $action = $body['action'] ?? '';
        $entity = $body['entity'] ?? '';
        $data   = $body['data']   ?? [];

        if (!$action || !$entity) {
            http_Response_code(400);
            $this->json(['error' => 400, 'message' => 'action and entity required']);
        }

        $spMap = [
            'insert' => [
                'pilots'        => ['sp_InsertPilotData',        ['pilot_name','pilot_age','id_pilot_category']],
                'teams'         => ['sp_InsertTeamData',         ['team_name','mechanic_num','id_manufacturer']],
                'vehicles'      => ['sp_InsertVehicleData',      ['model','specifications_url']],
                'races'         => ['sp_InsertRaceData',         ['event_name','event_date','event_duration','id_circuit']],
                'circuits'      => ['sp_InsertCircuitData',      ['circuit_name','country','length_km','direction']],
                'manufacturers' => ['sp_InsertManufacturerData', ['manufacturer_name','manufacturer_country']],
                'penalties'     => ['sp_InsertPenaltyData',      ['penalty_type','reason','penalty_value','penalty_applies_to']],
                'users'         => ['sp_InsertUserData',         ['username','email','password_hash','team_id','role']],
            ],
            'update' => [
                'pilots'        => ['sp_UpdatePilotData',        ['id_pilot','pilot_name','pilot_age','id_pilot_category']],
                'teams'         => ['sp_UpdateTeamData',         ['id_team','team_name','mechanic_num','id_manufacturer']],
                'vehicles'      => ['sp_UpdateVehicleData',      ['id_vehicle','model','specifications_url']],
                'races'         => ['sp_UpdateRaceData',         ['id_race','event_name','event_date','event_duration','id_circuit']],
                'circuits'      => ['sp_UpdateCircuitData',      ['id_circuit','circuit_name','country','length_km','direction']],
                'manufacturers' => ['sp_UpdateManufacturerData', ['id_manufacturer','manufacturer_name','manufacturer_country']],
                'users'         => ['sp_UpdateUserData',         ['id_user','username','email','password_hash','team_id','role']],
            ],
            'delete' => [
                'pilots'        => ['sp_DeletePilotData',        ['id_pilot']],
                'teams'         => ['sp_DeleteTeamData',         ['id_team']],
                'vehicles'      => ['sp_DeleteVehicleData',      ['id_vehicle']],
                'races'         => ['sp_DeleteRaceData',         ['id_race']],
                'circuits'      => ['sp_DeleteCircuitData',      ['circuit_id']],
                'manufacturers' => ['sp_DeleteManufacturerData', ['id_manufacturer']],
                'penalties'     => ['sp_DeletePenaltyData',      ['id_penalty']],
                'users'         => ['sp_DeleteUserData',         ['id_user']],
            ],
        ];

        if (!isset($spMap[$action][$entity])) {
            http_Response_code(400);
            $this->json(['error' => 400, 'message' => "Unknown action/entity: $action/$entity"]);
        }

        [$sp, $params] = $spMap[$action][$entity];

        // ── Verificación de contraseña de administrador para todas las operaciones de usuarios ──
        if ($entity === 'users' && in_array($action, ['insert', 'update', 'delete'])) {
            $adminPass = $body['data']['admin_password'] ?? '';
            if ($adminPass === '') {
                http_Response_code(400);
                $this->json(['error' => 400, 'message' => 'Se requiere tu contraseña de administrador']);
            }
            $this->verifyAdminPassword($adminPass);
            unset($body['data']['admin_password']); // no se envía al SP
        }

        // ── Tratamiento especial para la contraseña de usuario ──
        if ($entity === 'users') {
            if ($action === 'insert') {
                // En inserción la contraseña es obligatoria
                if (empty($data['password_hash'])) {
                    http_Response_code(400);
                    $this->json(['error' => 400, 'message' => 'La contraseña del usuario no puede estar vacía']);
                }
                $data['password_hash'] = password_hash($data['password_hash'], PASSWORD_BCRYPT);
            } elseif ($action === 'update') {
                // En actualización, si el campo está vacío se omite (no se modifica la contraseña)
                if (empty($data['password_hash'])) {
                    $params = array_values(array_diff($params, ['password_hash']));
                    unset($data['password_hash']);
                } else {
                    $data['password_hash'] = password_hash($data['password_hash'], PASSWORD_BCRYPT);
                }
            }
        }

        $values = [];
        foreach ($params as $p) {
            if (!array_key_exists($p, $data)) {
                http_Response_code(400);
                $this->json(['error' => 400, 'message' => "Missing field: $p"]);
            }
            $val = $data[$p];
            // Si el campo es un ID o entero, forzar cast
            if (str_starts_with($p, 'id_') || in_array($p, ['circuit_id','pilot_age','mechanic_num','length_km','position','penalty_value','team_id'])) {
                $val = ($val === '' || !isset($val)) ? null : (int)$val;
            }
            $values[] = $val;
        }

        try {
            $pdo  = Container::getInstance()->make('db.admin');
            $ph   = implode(',', array_fill(0, count($values), '?')) . ',@spstate';
            $stmt = $pdo->prepare("CALL $sp($ph)");
            $stmt->execute($values);
            $stmt->closeCursor();
            $state = (int)($pdo->query("SELECT @spstate AS s")->fetchColumn());

            if ($state !== 1) {
                http_Response_code(500);
                $this->json(['error' => 500, 'message' => "SP returned state $state"]);
            }

            $this->json(['success' => true]);
        } catch (\Throwable $e) {
            http_Response_code(500);
            $this->json(['error' => 500, 'message' => $e->getMessage()]);
        }
    }

    // ── Helper SP ────────────────────────────────────────────

    private function callSP(string $sp, int $userId): array
    {
        $pdo  = Container::getInstance()->make('db.readonly');
        $stmt = $pdo->prepare("CALL $sp(?)");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        return $rows;
    }

}