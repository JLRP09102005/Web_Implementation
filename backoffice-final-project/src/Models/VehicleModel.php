<?php

namespace App\Models;

class VehicleModel extends BaseModel{

    public function getAllAdmin(int $userId): array
    {
        return $this->call('sp_admin_all_vehicles', [$userId]);
    }

    public function getAllAnalyst(int $userId): array
    {
        return $this->call('sp_analyst_all_vehicles', [$userId]);
    }

    public function getMymechanical(int $userId): array
    {
        return $this->call('sp_mechanical_my_vehicles', [$userId]);
    }

    public function getMyManufacturer(int $userId): array
    {
        return $this->call('sp_manufacturer_my_vehicles', [$userId]);
    }

    public function getMyTeamManager(int $userId): array
    {
        return $this->call('sp_teammanager_my_vehicles', [$userId]);
    }

}

?>