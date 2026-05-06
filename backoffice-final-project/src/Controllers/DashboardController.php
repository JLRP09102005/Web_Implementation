<?php 

namespace App\Controllers;

use App\Core\Container;

class DashboardController {

    public function __construct() {}
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

    private function pdo(): \PDO
    {
        return Container::getInstance()->make('db.readonly');
    }

    private function call(string $sp, array $params = []): array
    {
        $pdo = $this->pdo();
        $ph = implode(',', array_fill(0, count($params), '?'));
        $stmt = $pdo->prepare("CALL {$sp}(" . ($ph ?: '') . ")");
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        return $rows;
    }

    // ── GET /api/overview ───────────────────────────────────
    public function overview(array $urlParams): void
    {
        $this->requireAuth();
        $user = $_SESSION['user'];

        try
        {

            $races = $this->call('sp_public_race_calendar');
            $pilots = $this->call('sp_public_pilots_list');

            $teams = [];
            $vehicles = [];
            $penalties = [];

            $role = $user['role'];
            $userId = (int)$user['id'];

            if (in_array($role, ['software-administrator', 'administratorDB']))
            {
                $teams = $this->call('sp_admin_all_teams', [$userId]);
                $vehicles = $this->call('sp_admin_all_vehicles', [$userId]);
                $penalties = $this->call('sp_admin_all_penalties', [$userId]);
            }
            elseif ($role === 'data-analyst')
            {
                $teams = $this->call('sp_analyst_all_teams', [$userId]);
                $vehicles = $this->call('sp_analyst_all_vehicles', [$userId]);
                $penalties = $this->call('sp_analyst_all_penalties', [$userId]);
            }
            elseif ($role === 'comissioner-boss')
            {
                $penalties = $this->call('sp_commissioner_all_penalties', [$userId]);
            }
            elseif ($role === 'race-director')
            {
                $penalties = $this->call('sp_racedirector_all_penalties', [$userId]);
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

}

?>