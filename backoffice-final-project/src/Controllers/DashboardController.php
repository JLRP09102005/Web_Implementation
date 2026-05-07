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
    public function __clone() { throw new \Exception("No clonable DashboardController"); }
    public function __sleep() { throw new \Exception("No serializable DashboardController"); }
    public function __wakeup() { throw new \Exception("No unserializable DashboardController"); }

    private function requireAuth(): void
    {
        if (empty($_SESSION['user']))
        {
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

    private function session(): array
    {
        return $_SESSION['user'];
    }

    // ── GET /api/overview ───────────────────────────────────
    public function overview(array $urlParams): void
    {
        $this->requireAuth();
        $user = $this->session();
        $role = $user['role'];
        $userId = (int)$user['id'];

        try
        {

            $races = $this->raceModel->getPublicCalendar();
            $pilots = $this->pilotModel->getPublicList();
            $teams = $this->teamModel->getPublicList();

            $vehicles = [];
            $penalties = [];

            if (in_array($role, ['software-administrator', 'administratorDB']))
            {
                $teams = $this->teamModel->getAllAdmin($userId);
                $vehicles = $this->vehicleModel->getAllAdmin($userId);
                $penalties = $this->penaltyModel->getAllAdmin($userId);
            }
            elseif ($role === 'data-analyst')
            {
                $teams = $this->teamModel->getAllAnalyst($userId);
                $vehicles = $this->vehicleModel->getAllAnalyst($userId);
                $penalties = $this->penaltyModel->getAllAnalyst($userId);
            }
            elseif ($role === 'comissioner-boss')
            {
                $penalties = $this->penaltyModel->getAllCommissioner($userId);
            }
            elseif ($role === 'race-director')
            {
                $penalties = $this->penaltyModel->getAllRaceDirector($userId);
            }

            $this->json([
                'total_races' => count($races),
                'total_pilots' => count($pilots),
                'total_teams' => count($teams),
                'total_vehicles' => count($vehicles),
                'total_penalties' => count($penalties),
                'races' => $races,
            ]);

        } catch (\Throwable $e) {
            $this->json(['error' => 500, 'message' => $e->getMessage()]);
        }
    }

    // ── GET /api/pilots ─────────────────────────────────────
    public function pilots(array $urlParams): void
    {
        $this->requireAuth();
        $user = $_SESSION['user'];
        $role = $user['role'];
        $userId = (int)$user['id'];

        try
        {

            if(in_array($role, ['software-administrator', 'administratorDB'])) { $rows = $this->pilotModel->getAllAdmin($userId); }
            elseif($role === 'data-analyst') { $rows = $this->pilotModel->getAllAnalyst($userId); }
            elseif($role === 'team_manager') { $rows = $this->pilotModel->getAllTeamManager($userId); }
            else { $rows = $this->pilotModel->getPublicList(); }

            $this->json($rows);

        } catch (\Throwable $e) {
            $this->json(['error' => 500, 'message' => $e->getMessage()]);
        }
    }

    // ── GET /api/races ──────────────────────────────────────
    public function races(array $urlParams): void
    {
        $this->requireAuth();
        $user = $_SESSION['user'];
        $role = $user['role'];
        $userId = (int)$user['id'];

        try
        {

            if(in_array($role, ['software-administrator', 'administratorDB'])) { $rows = $this->raceModel->getAllAdmin($userId); }
            elseif($role === 'data-analyst') { $rows = $this->raceModel->getAllAnalyst($userId); }
            elseif($role === 'comissioner-boss') { $rows = $this->raceModel->getAllComissioner($userId); }
            elseif($role === 'race-director') { $rows = $this->raceModel->getAllRaceDirector($userId); }
            elseif($role === 'mechanical-boss') { $rows = $this->raceModel->getAllMechanical($userId); }
            elseif($role === 'team-manager') { $rows = $this->raceModel->getAllTeamManager($userId); }
            else { $rows = $this->raceModel->getPublicCalendar(); }

            $this->json($rows);

        } catch(\Throwable $e){
            $this->json(['error' => 500, 'message' => $e->getMessage()]);
        }
    }

    // ── GET /api/vehicles ─────────────────────────────────────
    public function vehicles(array $urlParams): void
    {
        $this->requireAuth();
        $user = $this->session();
        $role = $user['role'];
        $userId = (int)$user['id'];

        try 
        {

            if(in_array($role, ['software-administrator', 'administratorDB'])) { $rows = $this->vehicleModel->getAllAdmin($userId); }
            elseif($role === 'data-analyst') { $rows = $this->vehicleModel->getAllAnalyst($userId); }
            elseif($role === 'mechanical-boss') { $rows = $this->vehicleModel->getMyMechanical($userId); }
            elseif($role === 'manufacturer-representative') { $rows = $this->vehicleModel->getMyManufacturer($userId); }
            elseif($role === 'team-manager') { $rows = $this->vehicleModel->getMyTeamManager($userId); }
            else { $rows = []; }

            $this->json($rows);

        } catch (\Throwable $e) {
            $this->json(['error' => 500, 'message' => $e->getMessage()]);
        }
    }

    // ── GET /api/penalties ────────────────────────────────────
    public function penalties(array $urlParams): void
    {
        $this->requireAuth();
        $user = $this->session();
        $role = $user['role'];
        $userId = (int)$user['id'];

        try 
        {

            if(in_array($role, ['software-administrator', 'administratorDB'])) { $rows = $this->penaltyModel->getAllAdmin($userId); }
            elseif($role === 'data-analyst') { $rows = $this->penaltyModel->getAllAnalyst($userId); }
            elseif($role === 'comissioner-boss') { $rows = $this->penaltyModel->getAllCommissioner($userId); }
            elseif($role === 'race-director') { $rows = $this->penaltyModel->getAllRaceDirector($userId); }
            else { $rows = []; }

            $this->json($rows);

        } catch (\Throwable $e) {
            $this->json(['error' => 500, 'message' => $e->getMessage()]);
        }
    }

    // ── GET /api/results ──────────────────────────────────────
    public function results(array $urlParams): void
    {
        $this->requireAuth();
        $user = $this->session();
        $role = $user['role'];
        $userId = (int)$user['id'];

        try 
        {

            if(in_array($role, ['software-administrator', 'administratorDB'])) { $rows = $this->resultModel->getAllAdmin($userId); }
            elseif($role === 'data-analyst') { $rows = $this->resultModel->getAllAnalyst($userId); }
            elseif($role == 'comissioner-boss') { $rows = $this->resultModel->getAllCommissioner($userId); }
            elseif($role === 'race-director') { $rows = $this->resultModel->getAllRaceDirector($userId); }
            elseif($role === 'team-manager') { $rows = $this->resultModel->getMyTeamManager($userId); }
            elseif($role === 'pilot') { $rows = $this->resultModel->getMyPilot($userId); }
            else { $rows = []; }

            $this->json($rows);

        } catch (\Throwable $e) {
            $this->json(['error' => 500, 'message' => $e->getMessage()]);
        }
    }

    // ── GET /api/manufacturer ─────────────────────────────────
    public function manufacturer(array $urlParams): void
    {
        $this->requireAuth();
        $userId = (int)$this->session()['id'];

        try 
        {

            $data = $this->manufacturerModel->getMyData($userId);
            $this->json($data ?: ['error' => 404, 'message' => 'No manufacturer data']);

        } catch (\Throwable $e) {
            $this->json(['error' => 500, 'message' => $e->getMessage()]);
        }
    }

}

?>