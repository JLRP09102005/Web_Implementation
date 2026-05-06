<?php

namespace App\Models;

class PilotModel extends BaseModel{

    public function getPublicList(): array
    {
        return $this->call('sp_public_pilots_list');
    }

    public function getAllAdmin(int $userId): array
    {
        return $this->call('sp_admin_all_pilots', [$userId]);
    }

    public function getAllAnalyst(int $userId): array
    {
        return $this->call('sp_analyst_all_pilots', [$userId]);
    }

    public function getAllTeamManager(int $userId): array
    {
        return $this->call('sp_teammanager_my_pilots', [$userId]);
    }

}

?>