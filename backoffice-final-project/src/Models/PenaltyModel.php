<?php

namespace App\Models;

class PenaltyModel extends BaseModel{

    public function getAllAdmin(int $userId): array
    {
        return $this->call('sp_admin_all_penalties', [$userId]);
    }

    public function getAllAnalyst(int $userId): array
    {
        return $this->call('sp_analyst_all_penalties', [$userId]);
    }

    public function getAllCommissioner(int $userId): array
    {
        return $this->call('sp_commissioner_all_penalties', [$userId]);
    }

    public function getAllRaceDirector(int $userId): array
    {
        return $this->call('sp_racedirector_all_penalties', [$userId]);
    }

}

?>