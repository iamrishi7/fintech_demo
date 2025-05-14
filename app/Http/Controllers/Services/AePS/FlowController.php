<?php

namespace App\Http\Controllers\Services\Aeps;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResource;
use App\Http\Requests\MerchantAuthRequest;
use App\Http\Controllers\TransactionController;
use App\Models\Aeps;

class FlowController extends Controller
{

    /**
     * -----------------------------------------
     * All AePS requests will be processed here
     * -----------------------------------------
     * Step 1: Middleware checks
     * Step 2: Optimistic/Pessimistic Locking for
     *         all monetary requests
     * Step 3: Validate all requests
     * Step 4: API calls
     * Step 5: Commit or rollback transactions (depends on response)
     */
    public function authentication(MerchantAuthRequest $request): JsonResponse
    {
        // Eko request
        $eko = new EkoController();
        $response = $eko->merchantAuthentication($request);
        return response()->json(['reference_tid' => $response['data']['reference_tid']], 200);

        // Paysprint Request
        $paysprint = new PaysprintController();
        $response = $paysprint->merchantAuthentication($request);
        return response()->json(['reference_tid' => $response['MerAuthTxnId']], 200);
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $reference_id = uniqid('PAY-');
        $transaction = $this->initiateRequests($request, $reference_id);

        // Aeps::create([
        //     'user_id' => $request->user()->id,

        // ]);

        TransactionController::store($request->user(), $reference_id, 'payout', "Payout initiated", 0, $request->amount, []);
        $commission_class = new CommissionController;
        $commission_class->distributeCommission($request->user(), 'aeps', $request->amount);

        $this->releaseLock($request->user()->id);

        return new GeneralResource("");
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
