<?php

namespace App\Http\Controllers\Services\Payout;

use App\Http\Controllers\Controller;
use App\Http\Requests\PayoutRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SafexpayController extends Controller
{

    public function processResponse($response): array
    {
        if (in_array($response->response->code, ["0001", "0000"])) {
            $utr = ($response->payOutBean?->bankRefNo == 'NA') ? null : $response->payOutBean?->bankRefNo;
            $data = [
                'status' => 'success',
                'message' => $response->response->description,
                'utr' => $utr,
                'transaction_status' => strtolower($response->payOutBean->txnStatus)
            ];
        } else {
            $utr = ($response->payOutBean?->bankRefNo == 'NA') ? null : $response->payOutBean?->bankRefNo;
            $data = [
                'status' => 'failed',
                'message' => $response->response->description,
                'transaction_status' => strtolower($response->payOutBean->txnStatus),
                'utr' => $utr
            ];
        }

        return ['data' =>  $data, 'response' => $response->response];
    }


    public function encrypt(string $data, string $key, $type = '', $iv = "0123456789abcdef", $size = 16)
    {
        $pad = $size - (strlen($data) % $size);
        $padtext = $data . str_repeat(chr($pad), $pad);
        $crypt = openssl_encrypt(
            $padtext,
            "AES-256-CBC",
            base64_decode($key),
            OPENSSL_RAW_DATA
                | OPENSSL_ZERO_PADDING,
            $iv
        );
        return base64_encode($crypt);
    }

    public function decrypt(string $encrypt, string $key, string $iv)
    {
        $crypt = base64_decode($encrypt);
        $padtext = openssl_decrypt(
            $crypt,
            "AES-256-CBC",
            base64_decode($key),
            OPENSSL_RAW_DATA
                | OPENSSL_ZERO_PADDING,
            $iv
        );
        $pad = ord($padtext[strlen($padtext) - 1]);
        if ($pad > strlen($padtext)) {
            return false;
        }
        if (strspn($padtext, $padtext[strlen($padtext) - 1], strlen($padtext) - $pad) != $pad) {
            $text = "Error";
            return $text;
        }
        return json_decode(preg_replace('/[\x00-\x1F\x7F]/', "", $padtext));
    }

    public function initiateTransaction(PayoutRequest $request, string $reference_id)
    {
        $data = json_encode([
            'header' => [
                'operatingSystem' => 'WEB',
                'sessionId' => config('services.safexpay.merchant_id'),
                'version' => '1.0.0'
            ],
            'userInfo' => "{}",
            'transaction' => [
                'requestType' => 'WTW',
                'requestSubType' => 'PWTB',
                'tranCode' => 0,
                'txnAmt' => 0.0,
                'id' => config('services.safexpay.merchant_id'),
                'surChargeAmt' => 0,
                'txnCode' => 0,
                'userType' => 0
            ],
            'payOutBean' => [
                'mobileNo' => $request->user()->phone_number,
                'txnAmount' => $request->amount,
                'accountNo' => $request->account_number,
                'ifscCode' => strtoupper($request->ifsc_code),
                'bankName' => $request->bank_name,
                "txnType" => strtoupper($request->mode),
                'accountHolderName' => $request->beneficiary_name,
                'emailId' => $request->user()->email,
                'orderRefNo' => $reference_id,
                'count' => 0
            ]
        ]);
        $encryption = $this->encrypt($data, config('services.safexpay.merchant_key'));

        $payload = [
            'uId' => config('services.safexpay.merchant_id'),
            'payload' => $encryption
        ];

        $response = Http::post(config('services.safexpay.base_url'), $payload);

        Log::info(['response' => $response->body(), 'request' => $data]);

        if ($response->failed()) {
            $this->releaseLock($request->user()->id);
            abort($response->status(), "Gateway Failure!");
        }

        $decrypt = $this->decrypt($response['payload'], config('services.safexpay.merchant_key'), config('services.safexpay.iv'));


        return $this->processResponse($decrypt);
    }

    public function updateTransaction(string $reference_id)
    {
        $data = json_encode([
            'header' => [
                'operatingSystem' => 'WEB',
                'sessionId' => config('services.safexpay.merchant_id'),
                'version' => '1.0.0'
            ],
            'userInfo' => "{}",
            'transaction' => [
                'requestType' => 'TMH',
                'requestSubType' => 'STCHK',
                'tranCode' => 0,
                'txnAmt' => 0.0
            ],
            'payOutBean' => [
                'payoutId' => $reference_id,
                'orderRefNo' => uniqid("SFX_UPD"),
            ]
        ]);

        $encryption = $this->encrypt($data, config('services.safexpay.merchant_key'));

        $payload = [
            'uId' => config('services.safexpay.merchant_id'),
            'payload' => $encryption
        ];

        $response = Http::post(config('services.safexpay.base_url'), $payload);
        Log::info(['requuest' => $payload, 'response' => $response->body()]);

        if ($response->failed()) {
            abort($response->status(), "Failed to fetch data. Please try again later.");
        }

        $decrypt = $this->decrypt($response['payload'], config('services.safexpay.merchant_key'), config('services.safexpay.iv'));


        return $this->processResponse($decrypt);
    }
}
