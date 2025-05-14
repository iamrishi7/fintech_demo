<?php

namespace App\Http\Controllers\Services\AePS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class EkoController extends Controller
{

    public function merchantAuthentication(Request $request): Response
    {
        $user = auth()->user();
        $data = [
            'initiator_id' => env('INITIATOR_ID'),
            'user_code' => $user->eko_user_code,
            'customer_id' => $user->phone_number,
            'aadhar' => $user->aadhaar,
            'client_ref_id' => uniqid("AEPS-AU"),  // Change it
            'latlong' => implode(',', [$request->latitude, $request->longitude]),
            'bank_code' => "HDFC",
            'piddata' => $request->piddata,
            //reference_tid
        ];

        $response = Http::withHeaders($this->ekoHeaders())
            ->post('https://staging.eko.in/ekoapi/v2/aeps/aepsmerchantauth', $data);

        return $response;
    }

    public function aepsTransaction(Request $request, int $service): Response
    {
        $user = $request->user();
        $hash_data = [
            'utility_number' => $request->aadhaar,
            'amount' => $request->amount,
            'user_code' => $user->eko_user_code ?? 20810200
        ];
        $request_hash = $this->requestHash($hash_data);
        $data = [
            'service_type' => $service,
            'initiator_id' => env('INITIATOR_ID'),
            'user_code' => $user->eko_user_code ?? 20810200,
            'customer_id' => $user->phone_number ?? 9999999999,
            'aadhar' => $request_hash['request_hash'],
            'client_ref_id' => uniqid("AEPS-AU"),  // Change it
            'amount' => $request->amount,
            'notify_customer' => 0,
            'piddata' => $request->piddata,
            'source_ip' => $request->ip(),
            'latlong' => $request->latlong,
            'bank_code' => $request->bankCode,
            'reference_tid' => $request->authenticity
        ];
        $response = Http::asJson()->withHeaders($request_hash)
            ->post('https://staging.eko.in/ekoapi/v2/aeps', $data);

        return $response;
    }

    public function transactionInquiry(Request $request): Response
    {
        $transaction_id = $request->transactionId;
        $response = Http::withHeaders($this->ekoHeaders())
            ->get("https://staging.eko.in/ekoapi/v1/transactions/$transaction_id", ['initiator_id' => env('INITIATOR_ID')]);

        return $response;
    }
}
