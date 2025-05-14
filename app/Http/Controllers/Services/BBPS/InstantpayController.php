<?php

namespace App\Http\Controllers\Services\BBPS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class InstantpayController extends Controller
{
    public function headers(): array
    {
        return [];
    }

    public function categoryList()
    {
        $response = Http::withHeaders($this->headers())
            ->get(config('services.instantpay.base_url') . '/marketplace/utilityPayments/category');

        $data = json_decode($response->body());
        $array = $data->data;
        $final =  array_map(function ($data) {
            return [
                'category_id' => $data->categoryKey,
                'category_name' => $data->categoryName,
                'category_icon' => $data->iconUrl
            ];
        }, $array);

        return $final;
    }

    public function operatorList(string $category_id)
    {
        $response = Http::withHeaders($this->headers())
            ->get(config('services.instantpay.base_url') . '/marketplace/utilityPayments/billers', ['filters' => ['categoryKey' => $category_id]]);

        $data = json_decode($response->body());
        $array = $data->records;
        $final = array_map(function ($data) {
            return [
                'operator_id' => $data->billerId,
                'operator_name' => $data->billerName,
                'operator_icon' => $data->iconUrl,
                'operator_status' => $data->billerStatus,
                'availabe' => $data->isAvailable
            ];
        }, $array);

        return $final;
    }

    public function operator(string $id)
    {
        $response = Http::withHeaders($this->headers())
            ->get(config('services.instantpay.base_url') . '/marketplace/utilityPayments/billerDetails', ['billerId' => $id]);

        return $response;
    }

    public function fetchBill(Request $request)
    {
        $data = [
            'billerId' => $request->operator_id,
            'initChannel' => $request->channel,
            'externalRef' => uniqid('BBPS-FB'),
            'inputParameters' => [
                'param1' => $request->utility_number
            ],
            'deviceInfo' => [
                'mac' => $request->mac_address,
                'ip' => $request->ip()
            ],
            'remarks' => [
                'param1' => $request->phone_number
            ],
            'transactionAmount' => $request->amount
        ];

        $response = Http::withHeaders($this->headers())
            ->post(config('services.instantpay.base_url') . '/marketplace/utilityPayments/prePaymentEnquiry', $data);

        return $response;
    }

    public function payBill(Request $request)
    {
        $data = [
            'billerId' => $request->operator_id,
            'externalRef' => uniqid('BBPS-PB'),
            'enquiryReferenceId' => $request->reference_id,
            'telecomCircle' => $request->telecom_circle,
            'inputParameters' => [
                'param1' => $request->utility_number
            ],
            'initChannel' => $request->channel,
            'deviceInfo' => [
                'terminalId' => $request->terminal_id,
                'mac' => $request->mac_address,
                'ip' => $request->ip(),
                'postalCode' => $request->postal_code,
                'geoCode' => $request->geo_code
            ],
            'paymentMode' => 'Online',
            'paymentInfo' => [
                'Remarks' => 'OnlinePayment'
            ],
            'remarks' => [
                'param1' => $request->user()->phone_number
            ],
            'transactionAmount' => $request->amount,
            'customerPan' => $request->pan

        ];

        $response = Http::withHeaders($this->headers())
            ->post(config('services.instantpay.base_url') . '/marketplace/utilityPayments/payment', $data);

        return $response;
    }
}
