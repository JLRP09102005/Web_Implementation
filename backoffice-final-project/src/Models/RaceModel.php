<?php

namespace App\Models;

class RaceModel extends BaseModel{

    public function getPublicCalendar(): array
    {
        return $this->call('sp_public_race_calendar');
    }

    public function getAllAdmin(int $userId): array
    {
        return $this->call('sp_admin_all_races', [$userId]);
    }

    public function getAllAnalyst(int $userId): array
    {
        return $this->call('sp_analyst_all_races', [$userId]);
    }

    public function getAllCommissioner(int $userId): array
    {
        return $this->call('sp_commissioner_all_races', [$userId]);
    }

    public function getAllRaceDirector(int $userId): array
    {
        return $this->call('sp_racedirector_all_races', [$userId]);
    }

    public function getAllMechanical(int $userId): array
    {
        return $this->call('sp_mechanical_all_races', [$userId]);
    }

    public function getAllTeamManager(int $userId): array
    {
        return $this->call('sp_teammanager_all_races', [$userId]);
    }

}

?>