<?php

namespace App\Http\Controllers\Services\Payout;

use App\Http\Controllers\Controller;
use App\Http\Controllers\TransactionController;
use App\Models\PayoutCommission;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CommissionController extends Controller
{
    public function findCommission(User $user): array
    {
        return [
            'plan_id' => $user->plan_id,
            'role_id' => $user->getRoleId(),
        ];
    }

    public function distributeCommission(User $user, float $amount, string $reference_id, bool $parent = false, bool $calculation = false, $account_number = null): Model | array
    {
        $instance = PayoutCommission::where($this->findCommission($user))->where('from', '<', $amount)->where('to', '>=', $amount)->first();

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
        $gst = $fixed_charge * 0.18;
        TransactionController::store($user, $reference_id, 'payout_commission', "Payout Commission for $account_number", $credit, $fixed_charge, null, $gst);
        // $this->checkParent($user, $amount, $reference_id, $account_number);
        return $instance;
    }

    public function checkParent(User $user, float $amount, $reference_id, $account_number)
    {
        if (!is_null($user->parent_id)) {
            $parent = User::find($user->parent_id);
            $lock = $this->lockRecords($user->parent_id);
            $this->distributeCommission($parent, $amount, $reference_id, true, false, $account_number);
            $lock->release();
        }
    }
}
