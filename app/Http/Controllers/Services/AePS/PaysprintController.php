<?php

namespace App\Http\Controllers\Services\AePS;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class PaysprintController extends Controller
{
    public function encryptBody(array $data): string
    {
        $key = env('ENCRYPTION_KEY');
        $iv = env('ENCRYPTION_IV');

        $cipher = openssl_encrypt(json_encode($data, true), 'AES-128-CBC', $key, $options = OPENSSL_RAW_DATA, $iv);
        $body = base64_encode($cipher);

        return $body;
    }

    public function merchantAuthentication(Request $request): Response
    {
        $user = auth()->user();
        $data = [
            'mobilenumber' => $user->phone_number,
            'submerchantid' => $user->paysprint_merchant_id,
            'accessmodetype' => $user->accessmodetype,
            'referenceno' => uniqid("AEPS-AU"),
            'timestamp' => date('Y-m-d H:i:s'),
            'adhaarnumber' => $request->aadhaar,
            'latitude' => $request->latlong, //divide
            'longitude' => $request->latlong,  //divide
            'data' => $request->piddata,
            'ipaddress' => $request->ip()
        ];

        $encryption = $this->encryptBody($data);

        $response = Http::withHeaders($this->paysprintHeaders())->asJson()
            ->post('https://paysprint.in/service-api/api/v1/service/aeps/kyc/Twofactorkyc/merchant_authencity', ['body' => $encryption]);

        return $response;
    }

    public function aepsTransaction(Request $request): object
    {
        $user = $request->user();
        $data = [
            'mobilenumber' => $user->phone_number,
            'submerchantid' => $user->paysprint_merchant_id,
            'accessmodetype' => $user->accessmodetype,
            'pipe' => 'bank1|bank2|bank3',
            'is_iris' => 'no',
            'transactiontype' => $request->serviceType,
            'referenceno' => uniqid("AEPS-CW"),
            'timestamp' => date('Y-m-d H:i:s'),
            'amount' => $request->amount,
            'adhaarnumber' => $request->adhaar,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'data' => $request->piddata,
            'ipaddress' => $request->ip(),
            'requestremarks' => $request->remarks,
            'nationalbankidentification' => $request->bankCode,
            'MerAuthTxnId' => $request->authenticity
        ];

        $encryption = $this->encryptBody($data);
        switch ($request->serviceType) {
            case 'BE':
                $url = 'https://paysprint.in/service-api/api/v1/service/aeps/balanceenquiry/index';
                continue;
            case 'MS':
                $url = 'https://paysprint.in/service-api/api/v1/service/aeps/ministatement/index';
                continue;
            case 'CW':
                $url = 'https://paysprint.in/service-api/api/v1/service/aeps/authcashwithdraw/index';
                continue;
            default:
                return response("Not a valid service.", 400);
                break;
        }

        $response = Http::withHeaders($this->paysprintHeaders())->asJson()
            ->post($url, ['body' => $encryption]);

        return $response;
    }
}
