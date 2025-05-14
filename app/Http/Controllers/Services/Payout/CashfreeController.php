<?php

namespace App\Http\Controllers\Services\Payout;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\PayoutRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class CashfreeController extends Controller
{
    public function processResponse(Response $response, string $status)
    {
        if (in_array(strtolower($status), ['approval_pending', 'received', 'pending', 'queued', 'received'])) {
            $data = [
                'status' => 'success',
                'message' => "Transaction has been initiated.",
                'utr' => $response['transfer_utr'] ?? null,
                'transaction_status' => 'pending'
            ];
        } elseif (strtolower($status) == 'success') {
            $data = [
                'status' => 'success',
                'message' => $response['status_description'],
                'utr' => $response['transfer_utr'] ?? null,
                'transaction_status' => 'success'
            ];
        } else {
            $data = [
                'status' => 'error',
                'message' => $response['status_description'] ?? "An error occurred while processing your request",
            ];
        }

        return ['data' => $data, 'response' => $response->body()];
    }

    public function autheticate()
    {
        return [
            'X-Client-Id' => config('services.cashfree.client_id'),
            'X-Client-Secret' => config('services.cashfree.client_secret'),
        ];
    }

    public function headers()
    {
        return [
            'x-client-id' => config('services.cashfree.client_id'),
            'x-client-secret' => config('services.cashfree.client_secret'),
            'x-api-version' => '2024-01-01',
            'Authorization' => "Bearer" . " " . $this->authorizeRequest()
        ];
    }

    public function authorizeRequest()
    {
        $response = Http::withHeaders($this->autheticate())->post('https://payout-api.cashfree.com/payout/v1/authorize');
        if ($response->failed()) {
            abort($response->status(), $response['message']);
        }
        return $response['data']['token'];
    }

    public function getBenificiary(PayoutRequest $request)
    {
        $data = [
            'bank_account_number' => $request->account_number,
            'bank_ifsc' => $request->ifsc_code
        ];

        $response = Http::withHeaders($this->headers())
            ->get(config('services.cashfree.base_url') . '/payout/beneficiary', $data);

        if ($response->failed() && $response['code'] == 'beneficiary_not_found') {
            return $this->createBeneficiary($request);
        }
        $this->abortRequest($response, $request);

        return $response['beneficiary_id'];
    }

    public function createBeneficiary(PayoutRequest $request)
    {
        $data = [
            'beneficiary_id' => str_replace(' ', '', $request->beneficiary_name . time()),
            'beneficiary_name' => $request->beneficiary_name,
            'beneficiary_instrument_details' => [
                'bank_account_number' => $request->account_number,
                'bank_ifsc' => $request->ifsc_code
            ]
        ];

        $response = Http::withHeaders($this->headers())
            ->post(config('services.cashfree.base_url') . '/payout/beneficiary', $data);

        $this->abortRequest($response, $request);
        return $response['beneficiary_id'];
    }

    public function abortRequest(Response $response, PayoutRequest $request)
    {
        if ($response->failed()) {
            Log::info(['error_cshfre' => $response->body()]);
            $this->releaseLock($request->user()->id);
            abort($response->status(), $response['message']);
        }
    }

    public function initiateTransaction(PayoutRequest $request, string $refrence_id)
    {
        $data = [
            'transfer_id' => $refrence_id,
            'transfer_amount' => $request->amount,
            'transfer_mode' => strtolower($request->mode),
            'beneficiary_details' => [
                'beneficiary_id' => $this->getBenificiary($request)
            ]
        ];

        $response = Http::withHeaders($this->headers())
            ->post(config('services.cashfree.base_url') . '/payout/transfers', $data);

        $this->abortRequest($response, $request);
        return $this->processResponse($response, $response['status']);
    }

    public function updateTransaction(string $refrence_id)
    {
        $response = Http::withHeaders($this->headers())
            ->get(config('services.cashfree.base_url') . '/payout/transfers', ['transfer_id' => $refrence_id]);

        if ($response->failed()) {
            abort($response->status(), $response['message']);
        }

        return $this->processResponse($response, $response['status']);
    }
}
