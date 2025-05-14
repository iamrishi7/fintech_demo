<?php

namespace App\Http\Controllers\Services\Payout;

use Exception;
use App\Http\Controllers\Controller;
use App\Http\Requests\PayoutRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class RazorpayController extends Controller
{
    public function createContact(PayoutRequest $request): Response | Exception
    {
        $data = [
            'name' => $request->beneficiary_name,
        ];

        $response = Http::withBasicAuth(config('services.razorpay.key'), config('services.razorpay.secret'))->asJson()
            ->post(config('services.razorpay.base_url') . '/v1/contacts', $data);

        return $response;
    }

    public function createFundAccount(PayoutRequest $request, string $contact_id): Response | Exception
    {
        $data = [
            'contact_id' => $contact_id,
            'account_type' => 'bank_account',
            'bank_account' => $request->account_number
        ];

        $response = Http::withBasicAuth(config('services.razorpay.key'), config('services.razorpay.secret'))->asJson()
            ->post(config('services.razorpay.base_url') . '/v1/fund_accounts', $data);

        if ($response->failed()) {
            $this->releaseLock($request->user()->id);
            abort($response->status(), $response['error']['message']);
        }

        return $response;
    }

    public function initiateTransaction(PayoutRequest $request, string $reference_id): array | Exception
    {
        $contact_id = $this->createContact($request);
        $fund_id = $this->createFundAccount($request, $contact_id['id']);

        $data = [
            'account_number' => $request->account_number,
            'fund_account_id' => $fund_id['id'],
            'mode' => $request->mode,
            'amount' => $request->amount * 100,
            'refernce_id' => $reference_id,
            'currency' => 'INR',
            'purpose' => 'payout'
        ];

        $response = Http::retry(2, 100)->withBasicAuth(
            config('services.razorpay.key'),
            config('services.razorpay.secret')
        )
            ->asJson()
            ->withHeader('X-Payout-Idempotency', $this->generateIdempotentKey())
            ->post(config('services.razorpay.base_url') . '/v1/payouts', $data);

        $array = [
            'status' => $response['status'] ?? 'error',
            'message' => $response['message'],
        ];

        return ['response' => $response, 'metadata' => $array];

        return $response;
    }
}
