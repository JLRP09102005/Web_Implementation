<?php

namespace App\Models;

class TeamModel extends BaseModel{

    public function getPublicList(): array
    {
        return $this->call('sp_public_teams_list');
    }

    public function getAllAdmin(int $userId): array
    {
        return $this->call('sp_admin_all_teams', [$userId]);
    }

    public function getAllAnalyst(int $userId): array
    {
        return $this->call('sp_analyst_all_teams', [$userId]);
    }

    public function getMyManufacturer(int $userId): array
    {
        return $this->call('sp_manufacturer_my_teams', [$userId]);
    }

    public function getMyTeamManager(int $userId): array
    {
        return $this->call('sp_teammanager_my_team', [$userId]);
    }

}

?>