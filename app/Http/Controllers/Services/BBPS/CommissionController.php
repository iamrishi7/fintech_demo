<?php

namespace App\Http\Controllers\Services\BBPS;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\BbpsCommission;
use App\Http\Controllers\Controller;
use App\Http\Controllers\TransactionController;
use Illuminate\Database\Eloquent\Model;

class CommissionController extends Controller
{
    public function findCommission(User $user, int $operator_id): array
    {
        return [
            'plan_id' => $user->plan_id ?? 1,
            'role_id' => $user->getRoleId(),
            'operator_id' => $operator_id,
        ];
    }

    public function distributeCommission(User $user, int $operator_id, float $amount, string $reference_id, string $utility_number, bool $parent = false, bool $calculation = false): Model
    {
        $instance = BbpsCommission::where($this->findCommission($user, $operator_id))->where('from', '<', $amount)->where('to', '>=', $amount)->get()->first();
        if (empty($instance)) {
            return [
                'debit_amount' => $fixed_charge = 0,
                'credit_amount' => $credit = 0
            ];
        }

        if ($parent == false) {
            $credit = 0;
            $fixed_charge = $instance->fixed_charge_flat ? $instance->fixed_charge : $amount * $instance->fixed_charge / 100;
        } else {
            $credit = $instance->is_flat ? $instance->commission : $amount * $instance->commission / 100;
            $fixed_charge = 0;
        }
        if ($calculation == true) {
            return [
                'debit_amount' => $fixed_charge,
                'credit_amount' => $credit
            ];
        }
        TransactionController::store($user, $reference_id, 'bbps_commission', "BBPS Commission for $utility_number", $credit, $fixed_charge);
        // $this->checkParent($user, $amount, $reference_id, $utility_number);
        return $instance;
    }

    public function checkParent(User $user, float $amount, string $reference_id, string $utility_number)
    {
        if (!is_null($user->parent_id)) {
            $parent = User::find($user->parent_id);
            $this->distributeCommission($parent, $amount, $reference_id, $utility_number, true);
        }
    }
}
