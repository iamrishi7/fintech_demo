<?php

namespace App\Http\Controllers\Services\Bbps;

use App\Models\Bbps;
use App\Models\Service;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use function PHPSTORM_META\map;
use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResource;
use App\Http\Requests\BbpsTransactionRequest;

use App\Http\Controllers\TransactionController;
use App\Http\Controllers\Services\BBPS\EkoController;
use App\Http\Controllers\Services\BBPS\PaysprintController;
use Illuminate\Support\Facades\Cache;

class FlowController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function categories(Request $request)
    {
        $service = Service::findOrFail($request->service_id);
        $class_name = Str::of($service->provider . "_" . "controller")->studly();
        $class = __NAMESPACE__ . "\\" . $class_name;
        $instance = new $class;
        if (!class_exists($class)) {
            abort(501, "Provider not supported.");
        }

        $categories = $instance->categoryList();
        return GeneralResource::collection($categories);
    }

    public function operators(Request $request)
    {
        $service = Service::findOrFail($request->service_id);
        $class_name = Str::of($service->provider . "_" . "controller")->studly();

        $class = __NAMESPACE__ . "\\" . $class_name;
        $instance = new $class;
        if (!class_exists($class)) {
            abort(501, "Provider not supported.");
        }
        $categories = $instance->operatorList($request);

        return GeneralResource::collection($categories);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(BbpsTransactionRequest $request)
    {
        $lock = Cache::lock($request->user()->id, 30);
        if (!$lock->get()) {
            abort(423, "Can't lock user account");
        }

        $service = Service::findOrFail($request->service_id);
        $class_name = Str::of($service->provider . "_" . "controller")->studly();
        $class = __NAMESPACE__ . "\\" . $class_name;
        $instance = new $class;
        if (!class_exists($class)) {
            abort(501, "Provider not supported.");
            $lock->release();
        }

        $reference_id = uniqid('BBPS-');

        $transaction_request = $instance->paybill($request, $reference_id);
        if ($transaction_request['data']['status'] != 'success') {
            $lock->release();
            abort(400, $transaction_request['data']['message']);
        }

        $bbps = Bbps::create([
            'user_id' => $request->user()->id,
            'operator_id' => $request->operator_id,
            'amount' => $request->amount,
            'status' => $transaction_request->status,
            'transaction_id' => $transaction_request->transaction_id,
            'utility_number' => $request->utility_number,
            'phone_number' => $request->phone_number
        ]);

        TransactionController::store($request->user(), $reference_id, 'bbps', "Bill Payment for {$request->utility_number}", 0, $request->amount);
        $this->releaseLock($request->user()->id);

        return new GeneralResource($bbps);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
