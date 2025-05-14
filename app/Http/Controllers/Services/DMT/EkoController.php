<?php

namespace App\Http\Controllers\Services\DMT;

use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResource;
use Exception;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EkoController extends Controller
{
    public function customerInfo(Request $request): JsonResource | Exception
    {
        $customer_id = $request->customer_id;
        $data = [
            'initiator_id' => config('services.eko.initiator_id'),
            'user_code' => $request->user()->user_eko_code ?? 20810200
        ];

        $response = Http::withHeaders($this->ekoHeaders())
            ->get("https://staging.eko.in/ekoapi/v2/customers/mobile_number:$customer_id", $data);

        $data = json_decode($response->body());
        ($data->response_status_id == 0) ?
            $array = [
                'customer_id' => $data->data->customer_id,
                'used_limit' => $data->data->used_limit,
                'available_limit' => $data->data->available_limit,
                'mobile' => $data->data->mobile
            ] :
            abort(400, $data->message);

        return new GeneralResource($array);
    }

    public function createCustomer(Request $request): JsonResource | Exception
    {
        $data = [
            'initiator_id' => config('services.eko.initiator_id'),
            'user_code' => $request->user()->user_eko_code ?? 20810200,
            'name' => $request->name,
            'dob' => $request->dob,
            'residence_address' => json_encode($request->address),
            'skip_verification' => 1
        ];

        $response = Http::withHeaders($this->ekoHeaders())->asForm()
            ->put("https://staging.eko.in/ekoapi/v2/customers/mobile_number:{$request->phone_number}", $data);

        if ($response['status'] == 0) {
            ($response['response_type_id'] == 327) ?
                $data = [
                    'message' => $response['message'],
                    'status' => 'pending'
                ] :
                $data = [
                    'message' => $response['message'],
                    'status' => 'verified'
                ];
        } else {
            abort(400, $response['message']);
        }

        return new GeneralResource($data);
    }

    public function verifyCustomer(Request $request): JsonResource | Exception
    {
        $data = [
            'initiator_id' => config('services.eko.initiator_id'),
            'user_code' => $request->user()->user_eko_code ?? 20810200,
            'customer_id_type' => 'mobile_number',
            'customer_id' => $request->phone_number
        ];

        $response = Http::withHeaders($this->ekoHeaders())->asForm()
            ->put("https://staging.eko.in/ekoapi/v2/customers/verification/otp:{$request->otp}", $data);

        if ($response['status'] == 0) {
            $data = [
                'message' => $response['message'],
                'status' => 'verified'
            ];
        } else {
            abort(400, $response['message']);
        }

        return new GeneralResource($data);
    }

    public function addRecipient(Request $request): JsonResource | Exception
    {
        $data = [
            'initiator_id' => config('services.eko.initiator_id'),
            'user_code' => $request->user()->user_eko_code ?? 20810200,
            'bank_id' => $request->bank_id,
            'recipient_name' => $request->recipient_name,
            'recipient_mobile' => $request->recipient_mobile,
            'recipient_type' => 3,
        ];
        $acc_ifsc = $request->account_number . '_' . $request->ifsc;

        $response = Http::withHeaders($this->ekoHeaders())->asForm()
            ->put("https://staging.eko.in/ekoapi/v2/customers/mobile_number:{$request->phone_number}/recipients/acc_ifsc:$acc_ifsc", $data);

        ($response['status'] == 0) ?
            $data = [
                'recepient_id' => $response['recipient_id'],
                'message' => $response['message']
            ] :
            abort(400, $response['message']);

        return new GeneralResource($data);
    }

    public function recipientList(int $customer_id): Response
    {
        $data = [
            'initiator_id' => config('services.eko.initiator_id'),
            'user_code' => auth()->user()->user_eko_code ?? 20810200
        ];

        $response = Http::withHeaders($this->ekoHeaders())
            ->get("https://staging.eko.in/ekoapi/v2/customers/mobile_number:$customer_id/recipients", $data);

        return $response;
    }

    public function reipientDetails(Request $request): Response
    {
        $data = [
            'initiator_id' => config('services.eko.initiator_id'),
            'user_code' => $request->user()->user_eko_code ?? 20810200
        ];

        $response = Http::withHeaders($this->ekoHeaders())
            ->get("https://staging.eko.in/ekoapi/v2/customers/mobile_number:{$request->phone_number}/recipients/recipient_id:{$request->recipint_id}", $data);

        return $response;
    }

    public function initiateTransaction(Request $request): Response
    {
        $data = [
            'initiator_id' => config('services.eko.initiator_id'),
            'user_code' => $request->user()->user_eko_code ?? 20810200,
            'client_ref_id' => uniqid('DMT-MT'), //change it
            'timestamp' => now(),
            'currency' => 'INR',
            'recipient_id' => $request->recipientId,
            'amount' => $request->amount,
            'customer_id' => $request->customerId,
            'state' => $request->state,
            'channel' => $request->channel,
            'latlong' => $request->latlong
        ];

        $response = Http::withHeaders($this->ekoHeaders())->asForm()
            ->post('https://staging.eko.in/ekoapi/v2/transactions', $data);

        return $response;
    }

    public function transactionInquiry(Request $request): Response
    {
        $data = [
            'initiator_id' => config('services.eko.initiator_id'),
            'user_code' => $request->user()->user_eko_code
        ];
        $transaction_id = $request->transactionId;

        $response = Http::withHeaders($this->ekoHeaders())
            ->get("https://staging.eko.in/ekoapi/v2/transactions/$transaction_id", $data);


        return $response;
    }

    public function initiateRefund(Request $request): Response
    {

        $data = [
            'initiator_id' => config('services.eko.initiator_id'),
            'user_code' => $request->user()->user_eko_code,
            'state' => 1,
            'otp' => $request->otp
        ];

        $response = Http::withHeaders($this->ekoHeaders())
            ->post("https://staging.eko.in/ekoapi/v2/transactions/{$request->transaction_id}/refund", $data);

        return $response;
    }

    public function resendRefundOtp(string $transaction_id)
    {
        $response = Http::withHeaders($this->ekoHeaders())
            ->post("https://staging.eko.in/ekoapi/transactions/{$transaction_id}/refund/otp", ['initiator_id' => config('services.eko.initiator_id')]);

        return $response;
    }
}
