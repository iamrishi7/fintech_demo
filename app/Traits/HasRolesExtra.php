<?php

namespace App\Traits;

use Illuminate\Support\Collection;
use Spatie\Permission\Traits\HasRoles;

trait HasRolesExtra
{
    use HasRoles;

    public function getRoleId(): int
    {
        $this->loadMissing('roles');
        return $this->roles->pluck('id')[0];
    }
}
