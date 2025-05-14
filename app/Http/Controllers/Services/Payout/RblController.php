<?php

namespace App\Http\Controllers\Services\Payout;

use App\Http\Controllers\Controller;
use App\Http\Requests\PayoutRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class RblController extends Controller
{
    public function initiateTransaction(Request $request, string $reference_id="kh")
    {
        $data = [
            'Single_Payment_Corp_Req' => [
                'Header' => [
                    'TranID' => $reference_id,
                    'Corp_ID' => ''
                ],

                'Body' => [
                    'Amount' => $request->amount ?? 100,
                    'Debit_Acct_No' => '123456789012',
                    'Debit_IFSC' => 'SBIN0032284',
                    'Debit_Mobile' => '997412064',
                    'Ben_IFSC' => $request->ifsc_code ?? 'SBIN0032284' ,
                    'Ben_Acct_No' => $request->account_number ?? '123456789012',
                    'Ben_Name' => $request->beneficiary_name ?? 'Rishi',
                    'Ben_BankName' => $request->bank_name ?? 'SBI Bank',
                    'Mode_of_Pay' => strtoupper($request->mode ?? 'IMPS'),
                    'Remarks' => 'tst'
                ],

                'Signature' => [
                    'Signature' => base64_encode(json_encode($request->all()?? ['test']))
                ]
            ]
        ];

        $response = Http::dd()->withBasicAuth(config('services.rbl.key'), config('services.rbl.secret'))
        ->withQueryParameters(['client_id' => config('services.rbl.client_id'), 'client_secret' => config('services.rbl.client_secret')])
        ->post(config('services.rbl.base_url').'/v1/payments/corp/payment', $data);

        return $response;
    }
}
