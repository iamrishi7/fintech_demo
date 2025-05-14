<?php

namespace App\Http\Controllers\Services\Recharge;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\RechargeCommission;
use Illuminate\Database\Eloquent\Model;

class CommissionController extends Controller
{
    public function findCommission(User $user, int $operator_id, string $service): array
    {
        return [
            'plan_id' => $user->plan_id ?? 1,
            'role_id' => $user->getRoleId(),
            'operator_id' => $operator_id,
            'service' => $service
        ];
    }

    public function distributeCommission(User $user, int $operator_id, string $service, float $amount, bool $parent = false): Model
    {
        $instance = RechargeCommission::where([$this->findCommission($user, $operator_id, $service)])->where('from', '<', $amount)->where('to', '>=', $amount)->get()->first();
        $fixed_charge = $parent ? 0 : $instance->fixed_charge;
        $credit = $instance->is_flat ? $instance->commission : $amount * $instance->commission / 100;
        $this->checkParent($user, $service, $amount);
        return $instance;
    }

    public function checkParent(User $user, string $service, float $amount)
    {
        if (!is_null($user->parent_id)) {
            $parent = User::find($user->parent_id);
            $this->distributeCommission($parent, $service, $amount, true);
        }
    }
}
