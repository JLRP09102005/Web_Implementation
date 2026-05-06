<?php

namespace App\Models;

class ResultModel extends BaseModel{

    public function getAllAdmin(int $userId): array
    {
        return $this->call('sp_admin_all_results', [$userId]);
    }

    public function getAllAnalyst(int $userId): array
    {
        return $this->call('sp_analyst_all_results', [$userId]);
    }

    public function getAllCommissioner(int $userId): array
    {
        return $this->call('sp_commissioner_all_results', [$userId]);
    }

    public function getAllRaceDirector(int $userId): array
    {
        return $this->call('sp_racedirector_all_results', [$userId]);
    }

    public function getMyTeamManager(int $userId): array
    {
        return $this->call('sp_teammanager_my_results', [$userId]);
    }

    public function getMyPilot(int $userId): array
    {
        return $this->call('sp_pilot_my_results', [$userId]);
    }

}

?>