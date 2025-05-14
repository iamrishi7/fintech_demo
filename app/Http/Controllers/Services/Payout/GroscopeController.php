<?php

namespace App\Http\Controllers\Services\Payout;

use App\Http\Controllers\Controller;
use App\Http\Requests\PayoutRequest;
use App\Models\Payout;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GroscopeController extends Controller
{

    public function processResponse(Response $response, int $status): array
    {
        switch ($status) {
            case 0005:
                if (in_array(strtolower($response['statusCode']), ['pending', 'success'])) {
                    $data = [
                        'status' => 'success',
                        'message' => $response['msg'],
                        'utr' => null,
                        'transaction_status' => strtolower($response['statusCode'])
                    ];
                } else {
                    $data = [
                        'status' => 'failed',
                        'message' => $response['msg'],
                        'transaction_status' => strtolower($response['statusCode']),
                        'utr' => null
                    ];
                }
                break;

            default:
                $data = [
                    'status' => 'error',
                    'message' => $response['msg'] ?? "An error occurred while processing your request",
                ];
                break;
        }

        return ['data' =>  $data, 'response' => $response->body()];
    }

    public function updateResponse($response, int $status): array
    {
        switch ($status) {
            case true:
                if (in_array(strtolower($response['data'][0]['status']), ['pending', 'success'])) {
                    $data = [
                        'status' => 'success',
                        'message' => $response['msg'],
                        'utr' => $response['data'][0]['utr_number'],
                        'transaction_status' => strtolower($response['data'][0]['status'])
                    ];
                } else {
                    $data = [
                        'status' => 'failed',
                        'message' => $response['msg'],
                        'utr' => $response['data']['utr_number'],
                        'transaction_status' => strtolower($response['data'][0]['status'])
                    ];
                }
                break;

            default:
                $data = [
                    'status' => 'error',
                    'message' => $response['msg'] ?? "An error occurred while processing your request",
                ];
                break;
        }

        return ['data' =>  $data, 'response' => $response];
    }

    public function initiateTransaction(PayoutRequest $request, string $reference_id)
    {
        $data = [
            'order_id' => $reference_id,
            'payment_mode' => $request->mode,
            'bank_name' => $request->bank_name,
            'amount' => $request->amount,
            'account_holder_name' => $request->beneficiary_name,
            'account_number' => $request->account_number,
            'ifsc_code' => strtoupper($request->ifsc_code)
        ];

        $response = Http::withHeaders([
            'X-Client-IP' => '10.0.1.4',
            'X-Auth-Token' => config('services.groscope.token')
        ])->post(config('services.groscope.base_url') . '/payout', $data);

        Log::info($response->body());

        if ($response->failed()) {
            $this->releaseLock($request->user()->id);
            abort($response->status(), "Gateway Failure!");
        }

        return $this->processResponse($response, $response['status']);
    }

    public function updateTransaction(string $transaction_id, $payout)
    {
        $decode = json_decode($payout->metadata, true);
        $response = Http::withHeaders([
            'X-Client-IP' => '10.0.1.4',
            'X-Auth-Token' => config('services.groscope.token')
        ])->post(config('services.groscope.base_url') . '/check-status', ['transaction_id' => $decode['txn_id']]);
        $response = json_decode($response->body(), true);
        return $this->updateResponse($response, $response['status']);
    }
}
