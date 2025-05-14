<?php

namespace App\Http\Controllers\Services\Payout;

use App\Http\Controllers\Controller;
use App\Http\Requests\PayoutRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayninjaController extends Controller
{
    public function processResponse($response, string $status) : array
    {
        if ($status == 'success') {
            $data = [
                'status' => 'success',
                'message' => $response['message'],
                'utr' => $response['data']['utr'],
                'transaction_status' => $response['data']['status']
            ];
        } else {
            $data = [
                'status' => 'error',
                'message' => $response['message'] ?? "An error occurred while processing your request",
            ];
        }

        return ['data' =>  $data, 'response' => $response->body()];
    }

    public static function encryptDecrypt($action, $string, $secret, $iv)
    {
        $output = ($action == 'encrypt') ? base64_encode(openssl_encrypt($string, 'AES-256-CBC', $secret, OPENSSL_RAW_DATA, $iv)) : openssl_decrypt(base64_decode($string), 'AES-256-CBC', $secret, OPENSSL_RAW_DATA, $iv);
        return $output;
    }

    public function initiateTransaction(PayoutRequest $request, string $reference_id)
    {
        $data = [
            'ben_name' => $request->beneficiary_name,
            'ben_phone_number' => $request->user()->phone_number,
            'ben_account_number' => $request->account_number,
            'ben_ifsc' => strtoupper($request->ifsc_code),
            'ben_bank_name' => $request->bank_name,
            'amount' => $request->amount,
            'merchant_reference_id' => $reference_id,
            'transfer_type' => strtoupper($request->mode),
            'narration' => "payout",
            'apicode' => 810,
        ];
        $secretkey = config('services.payninja.client_secret');
        $signature = hash('sha256', implode("-", $data) . $secretkey);
        $data['signature'] = $signature;
        $iv = bin2hex(random_bytes(8));
        $encrypted_data = self::encryptDecrypt('encrypt', json_encode($data), config('services.payninja.client_secret'), $iv);
        unset($data);

        $response = Http::asJson()->withHeader('api-Key', config('services.payninja.client_id'))->post(config('services.payninja.base_url') . '/api/v1/payout/fundTransfer', ['encdata' => $encrypted_data, 'iv' => $iv, 'key' => config('services.payninja.client_id')]);

        if ($response->failed()) {
            Log::info(['err_payninja' => $response->body()]);
            Log::info(['err_pnj_req' => $request->all()]);
            $this->releaseLock($request->user()->id);
            abort($response->status(), "Gateway Failure!");
        }

        return $this->processResponse($response, $response['status']);
    }

    public function updateTransaction(string $reference_id)
    {
        $iv = bin2hex(random_bytes(8));
        $data = ['merchant_reference_id' => $reference_id];
        $encrypted_data = self::encryptDecrypt('encrypt', json_encode($data), config('services.payninja.client_secret'), $iv);
        $response = Http::asJson()->withHeader('api-Key', config('services.payninja.client_id'))->post(config('services.payninja.base_url') . '/api/v1/payout/fundTransfer', ['encdata' => $encrypted_data, 'iv' => $iv, 'key' => config('services.payninja.client_id')]);
        if ($response->failed()) {
            Log::info(['err_payninja' => $response->body()]);
            abort($response->status(), "Gateway Failure!");
        }
        return $this->processResponse($response, $response['status']);
    }
}
