<?php

namespace App\Http\Controllers;

use App\Http\Requests\AepsTransactionRequest;
use App\Http\Requests\BbpsTransactionRequest;
use App\Http\Requests\PayoutRequest;
use Carbon\Carbon;
use App\Models\Otp;
use Firebase\JWT\JWT;
use Illuminate\Cache\Lock;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Http\Resources\GeneralResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Eko headers
     */
    public function ekoHeaders(): array
    {
        $encoded_key = base64_encode(config('services.eko.key'));
        $secret_key_timestamp = (int)round(microtime(true) * 1000);
        $signature = hash_hmac('SHA256', $secret_key_timestamp, $encoded_key, true);
        $secret_key = base64_encode($signature);

        return [
            'developer_key' => config('services.eko.developer_key'),
            'secret-key' => $secret_key,
            'secret-key-timestamp' => $secret_key_timestamp
        ];
    }

    /**
     * return array
     * secret key
     * secret key timestamp
     * data
     */
    public function requestHash(array $data): array
    {
        $headers = $this->ekoHeaders();
        $string = $headers['secret-key-timestamp'] . $data['utility_number'] . $data['amount'] . $data['user_code'];
        $signature_request_hash = hash_hmac("SHA256", $string, base64_encode(env('EKO_KEY')), true);
        $request_hash = base64_encode($signature_request_hash);
        return array_merge($headers, ['request_hash' => $request_hash]);
    }

    /**
     * Token Generation for Paysprint
     */
    public function paysprintHeaders(): array
    {
        $key = config('services.paysprint.jwt');
        Log::info($key);
        $payload = [
            'timestamp' => time(),
            'partnerId' => config('services.paysprint.partner_id'),
            'reqid' => abs(crc32(uniqid()))
        ];

        $jwt = JWT::encode($payload, $key, 'HS256');
        return [
            'Token' => $jwt,
            'Authorisedkey' => config('services.paysprint.authorised_key')
        ];
    }

    public function paydeerToken(): Response
    {
        return Http::post(config('services.paydeer.base_url') . '/API/public/api/v1.1/generateToken', ['clientKey' => config('services.paydeer.key'), 'clientSecret' => config('services.paydeer.secret')]);
    }

    public function waayupayToken(): Response
    {
        $response = Http::asForm()->withoutVerifying()->post(config('services.waayupay.base_url') . '/login', ['email' => config('services.waayupay.email'), 'password' => config('services.waayupay.password')]);
        Log::info($response);
        return $response;
    }

    // public function triggerSms(Request $request, array $contents)
    // {
    //     $user = $request->user();
    //     $link = env('FRONTEND_URL');
    //     $phone = $user->phone_number;
    //     $password = Str::random(8);
    //     $text = `Hello {$user->name}, Welcome to . Visit {$link}/login to start your transaction use Login Id : {$user->email} and Password : $password. -From PESA24 TECHNOLOGY PRIVATE LIMITED`;
    //     $otp =  Http::post("http://alerts.prioritysms.com/api/web2sms.php?workingkey=Ab6a47904876c763b307982047f84bb80&to=$phone&sender=PTECHP&message=$text", []);
    // }

    public function lockRecords($key): Lock
    {
        return Cache::lock($key, 30);
    }

    public function releaseLock($key): bool
    {
        return Cache::lock($key)->release();
    }

    public function generateIdempotentKey(): string
    {
        return once(function () {
            return (string) Str::uuid();
        });
    }
}
