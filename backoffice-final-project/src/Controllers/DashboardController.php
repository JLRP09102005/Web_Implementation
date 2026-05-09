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

    private function session(): array { return $_SESSION['user']; }

    private function isAdmin(string $role): bool
    {
        return in_array($role, ['software-administrator', 'administratorDB']);
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
            $this->json($data ?: ['error' => 404, 'message' => 'No manufacturer data']);
        } catch (\Throwable $e) {
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
            $results   = $this->resultModel->getAllAdmin($userId);
            $penalties = $this->penaltyModel->getAllAdmin($userId);

            $teamPoints = [];
            foreach ($results as $r) {
                $team = $r['team_name'] ?? ($r['id_vehicle'] ?? 'Unknown');
                $teamPoints[$team] = ($teamPoints[$team] ?? 0) + (int)($r['base_points_team'] ?? 0);
            }
            arsort($teamPoints);
            $top10 = array_slice($teamPoints, 0, 10, true);

            $penTypes = [];
            foreach ($penalties as $p) {
                $type = $p['penalty_type'] ?? 'Unknown';
                $penTypes[$type] = ($penTypes[$type] ?? 0) + 1;
            }

            $this->json([
                'team_points'   => $top10,
                'penalty_types' => $penTypes,
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
        ];

        if (!isset($spMap[$entity])) {
            $this->json(['error' => 400, 'message' => "Unknown entity: $entity"]);
        }

        try {
            $this->json($this->callSP($spMap[$entity], $userId));
        } catch (\Throwable $e) {
            $this->json(['error' => 500, 'message' => $e->getMessage()]);
        }
    }

    // ── POST /api/admin/crud ─────────────────────────────────

    public function adminCrud(array $urlParams): void
    {
        $this->requireAuth();
        $user = $this->session();
        if (!$this->isAdmin($user['role'])) {
            $this->json(['error' => 403, 'message' => 'Forbidden']);
        }

        $body   = json_decode(file_get_contents('php://input'), true) ?? [];
        $action = $body['action'] ?? '';
        $entity = $body['entity'] ?? '';
        $data   = $body['data']   ?? [];

        if (!$action || !$entity) {
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
            ],
            'update' => [
                'pilots'        => ['sp_UpdatePilotData',        ['id_pilot','pilot_name','pilot_age','id_pilot_category']],
                'teams'         => ['sp_UpdateTeamData',         ['id_team','team_name','mechanic_num','id_manufacturer']],
                'vehicles'      => ['sp_UpdateVehicleData',      ['id_vehicle','model','specifications_url']],
                'races'         => ['sp_UpdateRaceData',         ['id_race','event_name','event_date','event_duration','id_circuit']],
                'circuits'      => ['sp_UpdateCircuitData',           ['id_circuit','circuit_name','country','length_km','direction']],
                'manufacturers' => ['sp_UpdateManufacturerData', ['id_manufacturer','manufacturer_name','manufacturer_country']],
            ],
            'delete' => [
                'pilots'        => ['sp_DeletePilotData',        ['id_pilot']],
                'teams'         => ['sp_DeleteTeamData',         ['id_team']],
                'vehicles'      => ['sp_DeleteVehicleData',      ['id_vehicle']],
                'races'         => ['sp_DeleteRaceData',         ['id_race']],
                'circuits'      => ['sp_DeleteCircuitData',      ['circuit_id']],
                'manufacturers' => ['sp_DeleteManufacturerData', ['id_manufacturer']],
                'penalties'     => ['sp_DeletePenaltyData',      ['id_penalty']],
            ],
        ];

        if (!isset($spMap[$action][$entity])) {
            $this->json(['error' => 400, 'message' => "Unknown action/entity: $action/$entity"]);
        }

        [$sp, $params] = $spMap[$action][$entity];

        $values = [];
        foreach ($params as $p) {
            if (!array_key_exists($p, $data)) {
                $this->json(['error' => 400, 'message' => "Missing field: $p"]);
            }
            $val = $data[$p];
            // Si el campo es un ID o entero, forzar cast
            if (str_starts_with($p, 'id_') || in_array($p, ['circuit_id','pilot_age','mechanic_num','length_km','position','penalty_value'])) {
                $val = $val === '' ? null : (int)$val;
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

            if ($state !== 0) {
                $this->json(['error' => 500, 'message' => "SP returned state $state"]);
            }

            $this->json(['success' => true]);
        } catch (\Throwable $e) {
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