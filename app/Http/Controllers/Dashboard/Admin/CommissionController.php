<?php

namespace App\Http\Controllers\Dashboard\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CommissionRequest;
use App\Http\Resources\GeneralResource;
use App\Models\AepsCommission;
use App\Models\BbpsCommission;
use App\Models\DmtCommission;
use App\Models\LicCommission;
use App\Models\PayoutCommission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\Permission\Models\Role;

class CommissionController extends Controller
{

    /***********************************Payout***********************************/
    public function createPayoutCommission(CommissionRequest $request): Model
    {
        $role = Role::where(['name' => $request->role_id, 'guard_name' => 'api'])->first();
        if (!$role) {
            abort(404, "Invalid role");
        }

        $data = PayoutCommission::create([
            'plan_id' => $request->plan_id,
            'role_id' => $role->id,
            'from' => $request->from,
            'to' => $request->to,
            'fixed_charge' => $request->fixed_charge,
            'commission' => $request->commission,
            'fixed_charge_flat' => $request->fixed_charge_flat,
            'is_flat' => $request->is_flat,
        ]);

        return $data;
    }

    public function updatePayoutCommission(Request $request, PayoutCommission $payout): Model
    {
        $role = Role::where(['name' => $request->role_id, 'guard_name' => 'api'])->first();
        if (!$role) {
            $role_id = $payout->role_id;
        } else {
            $role_id = $role->id;
        }
        $payout->update([
            'plan_id' => $request->plan_id ?? $payout->plan_id,
            'role_id' => $role_id,
            'from' => $request->from ?? $payout->from,
            'to' => $request->to ?? $payout->to,
            'fixed_charge' => $request->fixed_charge ?? $payout->fixed_charge,
            'commission' => $request->commission ?? $payout->commission,
            'fixed_charge_flat' => $request->fixed_charge_flat ?? $payout->fixed_charge_flat,
            'is_flat' => $request->is_flat ?? $payout->is_flat
        ]);

        return $payout;
    }

    /***********************************AePS***********************************/
    public function createAepsCommission(CommissionRequest $request): Model
    {
        $data = AepsCommission::create([
            'plan_id' => $request->plan_id,
            'role_id' => $request->role_id,
            'from' => $request->from,
            'to' => $request->to,
            'service' => $request->service_type,
            'fixed_charge' => $request->fixed_charge,
            'commission' => $request->commission,
            'fixed_charge_flat' => $request->fixed_charge_flat,
            'is_flat' => $request->is_flat,
        ]);

        return $data;
    }

    public function updateAepsCommission(Request $request, AepsCommission $aeps): Model
    {
        $aeps->update([
            'plan_id' => $request->plan_id ?? $aeps->plan_id,
            'role_id' => $request->role_id ?? $aeps->role_id,
            'from' => $request->from ?? $aeps->from,
            'to' => $request->to ?? $aeps->to,
            'service' => $request->service ?? $aeps->service,
            'fixed_charge' => $request->fixed_charge ?? $aeps->fixed_charge,
            'commission' => $request->commission ?? $aeps->commission,
            'fixed_charge_flat' => $request->fixed_charge_flat ?? $aeps->fixed_charge_flat,
            'is_flat' => $request->is_flat ?? $aeps->is_flat
        ]);

        return $aeps;
    }

    /***********************************BBPS***********************************/
    public function createBbpsCommission(CommissionRequest $request): Model
    {
        $data = BbpsCommission::create([
            'plan_id' => $request->plan_id,
            'role_id' => $request->role_id,
            'operator_id' => $request->operator_id,
            'from' => $request->from,
            'to' => $request->to,
            'service' => $request->service,
            'fixed_charge' => $request->fixed_charge,
            'commission' => $request->commission,
            'fixed_charge_flat' => $request->fixed_charge_flat,
            'is_flat' => $request->is_flat,
        ]);

        return $data;
    }

    public function updateBbpsCommission(Request $request, BbpsCommission $bbps): Model
    {
        $bbps->update([
            'plan_id' => $request->plan_id ?? $bbps->plan_id,
            'role_id' => $request->role_id ?? $bbps->role_id,
            'operator_id' => $request->operator_id ?? $bbps->operator_id,
            'from' => $request->from ?? $bbps->from,
            'to' => $request->to ?? $bbps->to,
            'service' => $request->service ?? $bbps->service,
            'fixed_charge' => $request->fixed_charge ?? $bbps->fixed_charge,
            'commission' => $request->commission ?? $bbps->commission,
            'fixed_charge_flat' => $request->fixed_charge_flat ?? $bbps->fixed_charge_flat,
            'is_flat' => $request->is_flat ?? $bbps->is_flat
        ]);

        return $bbps;
    }

    /***********************************DMT***********************************/
    public function createDmtCommission(CommissionRequest $request): Model
    {
        $data = DmtCommission::create([
            'plan_id' => $request->plan_id,
            'role_id' => $request->role_id,
            'operator_id' => $request->operator_id,
            'from' => $request->from,
            'to' => $request->to,
            'service' => $request->service,
            'fixed_charge' => $request->fixed_charge,
            'commission' => $request->commission,
            'fixed_charge_flat' => $request->fixed_charge_flat,
            'is_flat' => $request->is_flat,
        ]);

        return $data;
    }

    public function updateDmtCommission(Request $request, DmtCommission $dmt): Model
    {
        $dmt->update([
            'plan_id' => $request->plan_id ?? $dmt->plan_id,
            'role_id' => $request->role_id ?? $dmt->role_id,
            'operator_id' => $request->operator_id ?? $dmt->operator_id,
            'from' => $request->from ?? $dmt->from,
            'to' => $request->to ?? $dmt->to,
            'service' => $request->service ?? $dmt->service,
            'fixed_charge' => $request->fixed_charge ?? $dmt->fixed_charge,
            'commission' => $request->commission ?? $dmt->commission,
            'fixed_charge_flat' => $request->fixed_charge_flat ?? $dmt->fixed_charge_flat,
            'is_flat' => $request->is_flat ?? $dmt->is_flat
        ]);

        return $dmt;
    }

    /***********************************LIC***********************************/
    public function createLicCommission(CommissionRequest $request): Model
    {
        $data = LicCommission::create([
            'plan_id' => $request->plan_id,
            'role_id' => $request->role_id,
            'operator_id' => $request->operator_id,
            'from' => $request->from,
            'to' => $request->to,
            'fixed_charge' => $request->fixed_charge,
            'commission' => $request->commission,
            'fixed_charge_flat' => $request->fixed_charge_flat,
            'is_flat' => $request->is_flat,
        ]);

        return $data;
    }

    public function updateLicCommission(Request $request, LicCommission $lic): Model
    {
        $lic->update([
            'plan_id' => $request->plan_id ?? $lic->plan_id,
            'role_id' => $request->role_id ?? $lic->role_id,
            'operator_id' => $request->operator_id ?? $lic->operator_id,
            'from' => $request->from ?? $lic->from,
            'to' => $request->to ?? $lic->to,
            'fixed_charge' => $request->fixed_charge ?? $lic->fixed_charge,
            'commission' => $request->commission ?? $lic->commission,
            'fixed_charge_flat' => $request->fixed_charge_flat ?? $lic->fixed_charge_flat,
            'is_flat' => $request->is_flat ?? $lic->is_flat
        ]);

        return $lic;
    }

    public function createCommission(CommissionRequest $request): JsonResource
    {
        $service = $request->service;
        switch ($service) {
            case 'payout':
                $data = $this->createPayoutCommission($request);
                break;

            // case 'aeps':
            //     $data = $this->createAepsCommission($request);
            //     break;

            // case 'bbps':
            //     $data = $this->createBbpsCommission($request);
            //     break;

            // case 'dmt':
            //     $data = $this->createDmtCommission($request);
            //     break;

            // case 'lic':
            //     $data = $this->createLicCommission($request);
            //     break;

            default:
                $data = 'Inappropriate data.';
                break;
        }

        return new GeneralResource($data);
    }

    public function updateCommission(Request $request, string $id): JsonResource
    {
        $request->validate([
            'service' => ['required', 'in:payout,aeps,dmt,bbps']
        ]);
        $service = $request->service;
        switch ($service) {
            case 'payout':
                $commission = PayoutCommission::findOrFail($id);
                $this->updatePayoutCommission($request, $commission);
                break;

            case 'aeps':
                $commission = AepsCommission::findOrFail($id);
                $this->updateAepsCommission($request, $commission);
                break;

            case 'bbps':
                $commission = BbpsCommission::findOrFail($id);
                $this->updateBbpsCommission($request, $commission);
                break;

            case 'bbps':
                $commission = DmtCommission::findOrFail($id);
                $this->updateDmtCommission($request, $commission);
                break;

            case 'lic':
                $commission = LicCommission::findOrFail($id);
                $this->updateLicCommission($request, $commission);
                break;

            default:
                $commission = 'no data sent';
                break;
        }

        return new GeneralResource($commission);
    }


    public function deleteCommission(Request $request, string $id)
    {
        $request->validate([
            'service' => ['required', 'in:payout']
        ]);
        $service = $request->service;
        switch ($service) {
            case 'payout':
                PayoutCommission::findOrFail($id)->delete();
                break;

            default:
                $commission = 'no data sent';
                break;
        }

        return response()->noContent();
    }

    public function getCommission(Request $request, $id): JsonResource
    {
        $request->validate([
            'service' => ['required', 'in:payout,aeps']
        ]);
        $service = $request->service;
        switch ($service) {
            case 'payout':
                $role = Role::where(['name' => $request->role_id, 'guard_name' => 'api'])->first();
                if (!$role) {
                    abort(404, "Role not found");
                }
                $data = PayoutCommission::where(['plan_id' => $id, 'role_id' => $role->id])->paginate(10);
                break;

            // case 'aeps':
            //     $data = AepsCommission::where(['plan_id' => $id, 'role_id' => $request->role_id])->paginate(10);
            //     break;

            // case 'bbps':
            //     $data = BbpsCommission::where(['plan_id' => $id, 'role_id' => $request->role_id])->paginate(10);
            //     break;

            // case 'dmt':
            //     $data = DmtCommission::where(['plan_id' => $id, 'role_id' => $request->role_id])->paginate(10);
            //     break;

            // case 'lic':
            //     $data = LicCommission::where(['plan_id' => $id, 'role_id' => $request->role_id])->paginate(10);
            //     break;

            default:
                $data = 'Inappropriate Data';
                break;
        }

        return GeneralResource::collection($data);
    }
}
