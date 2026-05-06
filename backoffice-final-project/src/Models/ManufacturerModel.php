<?php

namespace App\Models;

class ManufacturerModel extends BaseModel{

    public function getAllAdmin(int $userId): array
    {
        return $this->call('sp_admin_all_manufacturers', [$userId]);
    }

    public function getMyData(int $userId): array
    {
        return $this->callOne('sp_manufacturer_my_data', [$userId]);
    }

}

?>