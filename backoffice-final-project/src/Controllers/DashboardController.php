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

            $teams = [];
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
        $user   = $this->session();
        $role   = $user['role'];
        $userId = (int)$user['id'];

        try {
            $rows = match(true) {
                in_array($role, ['software-administrator', 'administratorDB'])
                    => $this->vehicleModel->getAllAdmin($userId),
                $role === 'data-analyst'
                    => $this->vehicleModel->getAllAnalyst($userId),
                $role === 'mechanical-boss'
                    => $this->vehicleModel->getMyMechanical($userId),
                $role === 'manufacturer-representative'
                    => $this->vehicleModel->getMyManufacturer($userId),
                $role === 'team-manager'
                    => $this->vehicleModel->getMyTeamManager($userId),
                default => [],
            };
            $this->json($rows);
        } catch (\Throwable $e) {
            $this->json(['error' => 500, 'message' => $e->getMessage()]);
        }
    }

}

?>