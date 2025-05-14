<?php

namespace App\Http\Controllers\Services\CMS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class EkoController extends Controller
{
    public function generateUrl(): Response
    {
        $data = [
            'initiator_id' => env('INITIATOR_ID'),
            'user_code' => $request->user()->user_eko_code,
            'client_ref_id' => uniqid('CMS-GL')
        ];

        $response = Http::withHeaders($this->ekoHeaders())
            ->post('https://staging.eko.in/ekoapi/v1/marketuat/airtelPartner/generateCmsUrl', $data);

        return $response;
    }
}
