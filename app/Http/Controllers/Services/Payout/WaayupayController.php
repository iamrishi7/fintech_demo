<?php

namespace App\Http\Controllers\Services\Payout;

use App\Http\Controllers\Controller;
use App\Http\Requests\PayoutRequest;
use App\Models\Payout;
use Exception;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WaayuPayController extends Controller
{

    public function processResponse(Response $response): array
    {
        switch ($response['status']) {
            case 'true':
                if (in_array($response['msg'], ['processing', 'success'])) {
                    $data = [
                        'status' => 'success',
                        'message' => $response['error']['message'],
                        'utr' => $response['error']['payoutid'],
                        'transaction_status' => strtolower($response['error']['status'])
                    ];
                } else {
                    $data = [
                        'status' => 'failed',
                        'message' => $response['error']['message'],
                        'transaction_status' => strtolower($response['error']['status']),
                        'utr' => $response['error']['payoutid']
                    ];
                }
                break;

            default:
                $data = [
                    'status' => 'error',
                    'message' => $response['error'] ?? "An error occurred while processing your request",
                ];
                break;
        }

        return ['data' =>  $data, 'response' => $response->body()];
    }

    public function updateResponse(Response $response): array
    {
        switch ($response['status']) {
            case 'true':
                $data = [
                    'status' => 'success',
                    'message' => $response['error']['message'],
                    'utr' => $response['error']['payoutid'],
                    'transaction_status' => strtolower($response['error']['status'])
                ];
                break;

            case 'false':
                if ($response['msg'] == 'processing') {
                    $data = [
                        'status' => 'pending',
                        'message' => $response['error']['message'],
                        'utr' => $response['error']['payoutid'],
                        'transaction_status' => strtolower($response['error']['status'])
                    ];
                } else {
                    $data = [
                        'status' => 'failed',
                        'message' => $response['error']['message'],
                        'transaction_status' => strtolower($response['error']['status']),
                        'utr' => $response['error']['payoutid']
                    ];
                }
                break;

            default:
                $data = [
                    'status' => 'error',
                    'message' => $response['error'] ?? "An error occurred while processing your request",
                ];
                break;
        }

        return ['data' =>  $data, 'response' => $response->body()];
    }

    public function initiateTransaction(PayoutRequest $request, string $reference_id): array | Exception
    {
        $token = $this->waayupayToken();
        Cache::put('waayupay-token', $token['token']);
        $token = Cache::get('waayupay-token');

        $data = [
            'name' => $request->beneficiary_name,
            'email' => $request->user()->email,
            'mobile' => $request->user()->phone_number,
            'amount' => $request->amount,
            'contactid' => $reference_id,
            'userKey' => config('services.waayupay.user_key'),
            'password' => config('services.waayupay.password'),
            'bankName' => $request->bank_name,
            'mode' => 'imps',
            'accountNumber' => $request->account_number,
            'ifscCode' => $request->ifsc_code
        ];
        $response = Http::withToken($token)
            ->asForm()
            ->withoutVerifying()
            ->post(config('services.waayupay.base_url') . '/payout/transaction', $data);

        return $this->processResponse($response, $response['status']);
    }


    public function updateTransaction(string $transaction_id)
    {
        $token = $this->waayupayToken();
        Cache::put('waayupay-token', $token['token']);
        $token = Cache::get('waayupay-token');
        $payout = Payout::where('reference_id', $transaction_id)->first();

        $data = [
            'txnid' => $payout['metadata']['error']['txnid']
        ];

        $response = Http::withToken($token)
            ->asForm()
            ->withoutVerifying()
            ->get(config('services.waayupay.base_url') . '/payout/txn/status/check', $data);

        if ($response->failed()) {
            abort($response->status(), "Failed to fetch data. Please try again later.");
        }

        return $this->updateResponse($response);
    }
}
