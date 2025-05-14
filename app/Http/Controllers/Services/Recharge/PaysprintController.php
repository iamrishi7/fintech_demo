<?php

namespace App\Http\Controllers\Services\Recharge;

use App\Http\Controllers\Controller;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PaysprintController extends Controller
{
    public function hlrCheck(Request $request): Response
    {
        $data = [
            'number' => $request->number,
            'type' => 'mobile'
        ];

        $response = Http::withHeaders($this->paysprintHeaders())->asJson()
            ->post('https://paysprint.in/service-api/api/v1/service/recharge/hlrapi/hlrcheck', $data);

        return $response;
    }

    public function browsePlan(string $circle, string $operator): Response
    {
        $data = [
            'circle' => $circle,
            'op' => $operator
        ];

        $response = Http::withHeaders($this->paysprintHeaders())->asJson()
            ->post('https://paysprint.in/service-api/api/v1/service/recharge/hlrapi/browseplan', $data);

        return $response;
    }

    public function operatorList(): Response
    {
        return Http::withHeaders($this->paysprintHeaders())->asJson()
            ->post('https://paysprint.in/service-api/api/v1/service/recharge/recharge/getoperator', []);
    }

    public function doRecharge(Request $request): Response
    {
        $data = [
            'operator' => $request->operator,
            'canumber' => $request->canumber,
            'amount' => $request->amount,
            'referenceid' => uniqid('RECH')
        ];

        $response = Http::withHeaders($this->paysprintHeaders())->asJson()
            ->post('https://paysprint.in/service-api/api/v1/service/recharge/recharge/dorecharge', $data);

        return $response;
    }

    public function statusEnquiry(Request $request): Response
    {
        $data = [
            'referenceid' => $request->referenceId
        ];

        $response = Http::withHeaders($this->paysprintHeaders())->asJson()
            ->post('https://paysprint.in/service-api/api/v1/service/recharge/recharge/status', $data);

        return $response;
    }
}
