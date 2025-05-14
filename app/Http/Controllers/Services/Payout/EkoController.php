<?php

namespace App\Http\Controllers\Services\Payout;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\PayoutRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EkoController extends Controller
{
    public function __construct()
    {
        $this->middleware('eko_onboard');
    }

    public function processResponse(Response $response, int $status): array
    {
        switch ($status) {
            case 0:
                if (in_array($response['data']['tx_status'], [0, 1, 5, 2])) {
                    $data = [
                        'status' => 'success',
                        'message' => $response['message'],
                        'utr' => $response['data']['bank_ref_num'] ?? null,
                        'transaction_status' => strtolower($response['data']['txstatus_desc'])
                    ];
                } else {
                    $data = [
                        'status' => 'failed',
                        'message' => $response['message'],
                        'transaction_status' => strtolower($response['data']['txstatus_desc']),
                        'utr' => $response['data']['bank_ref_num'] ?? null
                    ];
                }
                break;

            default:
                $data = [
                    'status' => 'error',
                    'message' => $response['message'] ?? "An error occurred while processing your request",
                ];
                break;
        }

        return ['data' =>  $data, 'response' => $response->body()];
    }

    public function initiateTransaction(PayoutRequest $request, string $reference_id): array
    {
        $this->activateService($service_code = 45);
        $data = [
            'initiator_id' => config('services.eko.initiator_id'),
            'client_ref_id' => $reference_id,
            'service_code' => $service_code,
            'payment_mode' => $request->mode,
            'recipient_name' => $request->beneficiary_name,
            'account' => $request->account_number,
            'ifsc' => strtoupper($request->ifsc_code),
            'sender_name' => $request->user()->name,
            'amount' => $request->amount
        ];

        $response = Http::withHeaders($this->ekoHeaders())
            ->asForm()
            ->post(config('services.eko.base_url') . "/v1/agent/user_code:{$request->user()->eko_user_code}/settlement", $data);

        Log::info(['response' => $response->body(), 'request' => $data]);

        if ($response->failed()) {
            $this->releaseLock($request->user()->id);
            abort($response->status(), "Gateway Failure!");
        }

        return $this->processResponse($response, $response['status']);
    }

    public function activateService(int $service_code)
    {
        $data = [
            'initiator_id' => config('services.eko.initiator_id'),
            'user_code' => auth()->user()->eko_user_code,
            'service_code' => $service_code
        ];

        $response = Http::withHeaders($this->ekoHeaders())->asForm()
            ->put(config('services.eko.base_url') . '/v1/user/service/activate', $data);

        if ($response->failed()) {
            $this->releaseLock(auth()->user()->id);
            abort(403, $response['message'] ?? "Failed.");
        }

        if (in_array($response['status'], [1295, 0]) && $response['data']['service_status'] == 1) {
            return true;
        } else {
            Log::info(['response' => $response->body(), 'request' => $data]);
            $this->releaseLock(auth()->user()->id);
            abort(403, $response['message'] ?? "Failed to Activate Service");
        }
    }

    public function updateTransaction(string $transaction_id)
    {
        $response = Http::withHeaders($this->ekoHeaders())
            ->get(config('services.eko.base_url') . "/v1/transactions/client_ref_id:$transaction_id", ['initiator_id' => config('services.eko.initiator_id')]);

        if ($response->failed()) {
            abort($response->status(), "Failed to fetch data. Please try again later.");
        }

        return $this->processResponse($response, $response['status']);
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
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
