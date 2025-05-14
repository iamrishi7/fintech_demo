<?php

namespace App\Http\Controllers\Services\Payout;

use App\Http\Controllers\Controller;
use App\Http\Controllers\TransactionController;
use App\Http\Requests\PayoutRequest;
use App\Models\Payout;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RunpaisaController extends Controller
{
    public function errorLog(Response $response, Request $request, string $reference_id)
    {
        if (strtolower($response['status']) == 'failed') {
            TransactionController::reverseTransaction($reference_id);
            Payout::where(['user_id' => $request->user()->id, 'reference_id' => $reference_id])->delete();
            Log::info(['err_rnpaisa' => $response->body()]);
            abort(400, $response['message'] ?? "Gateway Failure!");
        }
    }

    public function token()
    {
        $response = Http::withHeaders(['client_id' => config('services.runpaisa.client_id'), 'client_secret' => config('services.runpaisa.client_secret')])
            ->asJson()
            ->post(config('services.runpaisa.base_url') . '/token');
        if (strtolower($response['status']) == 'failed') {
            Log::info(['err_rnpaisa' => $response->body()]);
            abort(400, $response['message'] ?? "Gateway Failure!");
        }
        Log::info(['err_rnpsa' => $response->body()]);
        Cache::put('runpaisa_token', $response['data']['token'], 59);
        return $response['data']['token'];
    }

    public function  initiateTransaction(PayoutRequest $request, string $reference_id)
    {
        if (!Cache::has('runpaisa_token')) {
            $this->token();
        }
        $token = Cache::get('runpaisa_token');

        $data = [
            'account_number' => $request->account_number,
            'ifsc_code' => $request->ifsc_code,
            'amount' => $request->amount,
            'order_id' => $reference_id,
            'beneficiary_name' => $request->beneficiary_name,
            'payment_mode' => strtoupper($request->mode)
        ];

        $response = Http::withHeader('token', $token)->asJson()
            ->post(config('services.runpaisa.base_url') . '/payment', $data);

        $this->errorLog($response, $request, $reference_id);

        return $this->processResponse($response);
    }

    public function processResponse(Response $response)
    {
        if ($response['code'] == 'RP000') {
            $data = [
                'status' => 'success',
                'message' => $response['message'],
                'utr' => $response['data']['utr_no'] ?? null,
                'transaction_status' => strtolower($response['status'])
            ];
        } else {
            $data = [
                'status' => 'error',
                'message' => $response['message'] ?? "An error occurred while processing your request",
            ];
        }

        return ['data' => $data, 'response' => $response->body()];
    }

    public function updateTransaction(string $reference_id)
    {
        if (!Cache::has('runpaisa_token')) {
            $this->token();
        }
        $token = Cache::get('runpaisa_token');

        $response = Http::withHeader('token', $token)->asJson()
            ->post(config('services.runpaisa.base_url') . '/status', ['order_id' => $reference_id]);

        if ($response->failed() || strtolower($response['status']) == 'failed') {
            Log::info(['err_rnpaisa' => $response->body()]);
            abort($response->status(), $response['message'] ?? "Gateway Failure!");
        }

        $data = [
            'status' => 'success',
            'transaction_status' => strtolower($response['status']),
            'utr' => $response['utr_no'] ?? null,
            'message' => $response['message']
        ];


        return ['data' => $data, 'response' => $response->body()];
    }
}
