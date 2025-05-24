<?php

namespace App\Http\Controllers\Services\Payout;

use Carbon\Carbon;
use App\Models\Payout;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\TransactionController;
use App\Http\Requests\PayoutRequest;
use App\Http\Resources\GeneralResource;
use App\Models\Service;
use App\Models\Transaction;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class FlowController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $data = Payout::where(['user_id' => $request->user()->id])
            ->fiterByRequest($request)
            ->whereBetween('created_at', [$request->from ?? Carbon::now()->startOfDay(), $request->to ?? Carbon::now()->endOfDay()])
            ->latest('created_at')
            ->paginate(30);

        return GeneralResource::collection($data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PayoutRequest $request): JsonResource
    {

        $lock = Cache::lock($request->user()->id, 30);
        if (!$lock->get()) {
            abort(423, "Can't lock user account");
        }

        $service = Service::where(['name' => 'payout', 'active' => 1])->firstOrFail();
        // $class_name = Str::of($service->provider . "_" . "controller")->studly();
        // $class = __NAMESPACE__ . "\\" . $class_name;
        // $instance = new $class;
        // if (!class_exists($class)) {
        //     abort(501, "Provider not supported.");
        //     $lock->release();
        // }

        $reference_id = strtoupper(uniqid('DPAY'));

        TransactionController::store($request->user(), $reference_id, 'payout', "Payout initiated for {$request->account_number}", 0, $request->amount);
        $payout = Payout::create([
            'user_id' => $request->user()->id,
            'provider' => $service->provider,
            'reference_id' => $reference_id,
            'account_number' => $request->account_number,
            'ifsc_code' => $request->ifsc_code,
            'beneficiary_name' => $request->beneficiary_name,
            'mode' => $request->mode,
            'amount' => $request->amount,
            'status' => 'pending',
            'remarks' => $request->remarks,
        ]);
        $commission_class = new CommissionController;
        $commission_class->distributeCommission($request->user(), $request->amount, $reference_id, false, false, $request->account_number);

        // $transaction_request = $instance->initiateTransaction($request, $reference_id);

        // if ($transaction_request['data']['status'] != 'success') {
        //     TransactionController::reverseTransaction($payout->reference_id);
        //     $payout->delete();
        //     $lock->release();
        //     abort(400, $transaction_request['data']['message']);
        // }

        // if (in_array($transaction_request['data']['transaction_status'], ['hold', 'initiated', 'processing', 'pending'])) {
        //     $status = "pending";
        // } else {
        //     $status = $transaction_request['data']['transaction_status'];
        // }

        $payout->update([
            'status' => 'pending',
            'description' => "Transaction was successful",
            'utr' => "",
            'metadata' => ['TEST']
        ]);

        $lock->release();

        return new GeneralResource($payout);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(string $id)
    {
        $data = DB::transaction(function () use ($id) {
            $payout = Payout::where(function ($q) {
                $q->where('status', 'initiated')
                    ->orWhere('status', 'success')
                    ->orWhere('status', 'pending');
            })->findOrFail($id);

            $payout->status = 'success';
            $payout->utr = random_int(1000000000, 999999999999);
            $payout->save();

            return new GeneralResource($payout);
        }, 2);

        return $data;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
