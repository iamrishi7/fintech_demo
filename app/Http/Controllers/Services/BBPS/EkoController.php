<?php

namespace App\Http\Controllers\Services\BBPS;

use App\Http\Controllers\Controller;
use App\Http\Requests\BbpsTransactionRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class EkoController extends Controller
{
    public function categoryList()
    {
        $response = Http::asJson()
            ->get('https://staging.eko.in/ekoapi/v2/billpayments/operators_category');

        $data = json_decode($response->body());
        $array = $data->data;
        $final =  array_map(function ($data) {
            return [
                'category_id' => $data->operator_category_id,
                'category_name' => $data->operator_category_name
            ];
        }, $array);

        return $final;
    }

    public function locationList(): Response
    {
        $response = Http::asJson()
            ->get('https://staging.eko.in/ekoapi/v2/billpayments/operators_location');

        return $response;
    }

    public function operatorList(Request $request): Response
    {
        $data = [
            'Location' => $request->location,
            'Category' => $request->category
        ];

        $response = Http::asJson()
            ->get('https://staging.eko.in/ekoapi/v2/billpayments/operators_location', $data);

        return $response;
    }

    public function operatorParams($id): Response
    {
        $response = Http::asJson()
            ->get("https://staging.eko.in/ekoapi/v2/billpayments/operators/$id");

        return $response;
    }

    public function fetchBill(Request $request)
    {
        $user = $request->user();
        $data = [
            'user_code' => $user->eko_user_code ?? 20810200,
            'cliend_ref_id' => uniqid('BBPS-FB'), //change it
            'sender_name' => $user->name ?? 'Kaushik',
            'operator_id' => $request->operator_id,
            'utility_acc_no' => $request->utility_number,
            'confirmation_mobile_no' => $request->confirmation_mobile_no,
            'source_ip' => $request->ip(),
            'Latlong' => $request->latlong,
            //dob7
        ];

        $response = Http::withHeaders($this->ekoHeaders())->asJson()
            ->post('https://staging.eko.in/ekoapi/v2/billpayments/fetchbill?initiator_id=9962981729', $data);

        return $response;
    }

    public function payBill(BbpsTransactionRequest $request, string $reference_id): Response
    {
        $user = $request->user();
        $hash_data = [
            'utility_number' => $request->utilityAccNo,
            'amount' => $request->amount,
            'user_code' => $user->eko_user_code
        ];

        $data = [
            'user_code' => $user->eko_user_code,
            'cliend_ref_id' => $reference_id, //change it
            'sender_name' => $user->name,
            'operator_id' => $request->operator_id,
            'utility_acc_no' => $request->utility_number,
            'confirmation_mobile_no' => $request->phone_number ?? $user->phone_number,
            'source_ip' => $request->ip(),
            'latlong' => $request->latlong,
            'amount' => $request->amount,
            'billfetchresponse' => $request->bill_response,
            'hc_channel' => 1
            //dob7
        ];

        $response = Http::withHeaders($this->requestHash($hash_data))->asJson()
            ->post('https://staging.eko.in/ekoapi/v2/billpayments/paybill?initiator_id=9962981729', $data);

        if ($response->failed()) {
            $this->releaseLock($request->user()->id);
            abort($response->status(), $response['message']);
        }

        return $response;
    }
}
