<?php

namespace App\Http\Controllers\Services\BBPS;

use App\Http\Controllers\Controller;
use App\Http\Requests\BbpsTransactionRequest;
use App\Http\Resources\GeneralResource;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PaysprintController extends Controller
{
    public function categoryList()
    {
        $response = Http::withHeaders($this->paysprintHeaders())->asJson()
            ->post(config('services.paysprint.base_url') . '/bill-payment/bill/getoperator', ['mode' => 'online']);

        $data = json_decode($response->body());
        $array = $data->data;
        $final =  array_map(function ($data) {
            return [
                'category_id' => $data->category,
                'category_name' => $data->category
            ];
        }, $array);

        $unique = collect($final)->unique('category_name')->values();

        return $unique;
    }

    public function operatorList(Request $request)
    {
        $response = Http::withHeaders($this->paysprintHeaders())->asJson()
            ->post(config('services.paysprint.base_url') . '/bill-payment/bill/getoperator', ['mode' => 'online']);

        $data = json_decode($response->body());
        $array = $data->data;
        $final =  array_map(function ($data) {
            return [
                'operator_id' => $data->id,
                'category' => $data->category,
                'name' => $data->name
            ];
        }, $array);

        $unique = collect($final)->where('category', $request->category)->values();

        return $unique;
    }

    public function fetchBill(Request $request): Response
    {
        $data = [
            'operator' => $request->operator_id,
            'canumber' => $request->utility_number,
            'mode' => 'online'
        ];

        $response = Http::withHeaders($this->paysprintHeaders())->asJson()
            ->post(config('services.paysprint.base_url') . '/bill-payment/bill/fetchbill', $data);

        return $response;
    }

    public function payBill(BbpsTransactionRequest $request, string $reference_id): Response
    {
        $data = [
            'mode' => 'online',
            'referenceid' => $reference_id,
            'operator' => $request->operator_id,
            'canumber' => $request->utility_number,
            'amount' => $request->amount,
            'latitude' => $request->latitude, //divide
            'longitude' => $request->longitude,  //divide
            'bill_fetch' => $request->bill_response
        ];

        $response = Http::withHeaders($this->paysprintHeaders())->asJson()
            ->post(config('services.paysprint.base_url') . '/bill-payment/bill/paybill', $data);

        return $response;
    }
}
