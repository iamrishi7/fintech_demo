<?php

namespace App\Http\Controllers\Services\Payout;

use App\Http\Controllers\Controller;
use App\Http\Requests\PayoutRequest;
use Exception;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PaydeerController extends Controller
{
    /**
     * Initiate a payout transaction.
     *
     * @param PayoutRequest $request The request instance containing all the necessary data for the transaction.
     * @return Response The response instance from the paydeer API.
     */

    public function initiateTransaction(PayoutRequest $request, string $reference_id): array | Exception
    {
        if (!Cache::has('paydeer-token')) {
            $token = $this->paydeerToken();
            Cache::put('paydeer-token', $token['data']['access_token']);
        }

        $token = Cache::get('paydeer-token');

        $data = [
            'name' => $request->beneficiary_name,
            'email' => $request->user()->email,
            'mobile' => $request->user()->phone_number ?? 9971412064,
            'address' => $request->user()->address ?? 'Dubai',
            'amount' => $request->amount,
            'reference' => $reference_id,
            'trans_mode' => $request->mode,
            'account' => $request->account_number,
            'ifsc' => $request->ifsc_code
        ];
        $response = Http::withToken($token)
            ->post(config('services.paydeer.base_url') . '/API/public/api/v1.1/payoutTransaction/async', $data);

        $array = [
            'status' => $response['status'] ?? 'error',
            'message' => $response['message'],
            // 'transaction_status' => $response['data']['status'] ?? 'failed'
        ];

        return ['response' => $response, 'metadata' => $array];
    }
}
