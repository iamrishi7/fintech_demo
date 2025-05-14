<?php

namespace App\Http\Controllers\Dashboard\User;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use App\Http\Resources\GeneralResource;
use App\Http\Controllers\Services\Payout\EkoController;

class DocumentController extends Controller
{

    public function panVerification(Request $request)
    {
        $request->validate([
            'pan_number' => ['required', 'regex:/^([A-Z]){5}([0-9]){4}([A-Z]){1}?$/']
        ]);

        $data = [
            'pan_number' => $request->pan_number,
            'purpose' => 1,
            'purpose_desc' => 'onboarding',
            'initiator_id' => config('services.eko.initiator_id')
        ];

        $response = Http::withHeaders($this->ekoHeaders())->asForm()
            ->post(config('services.eko.base_url') . '/v1/pan/verify', $data);

        Log::info($response);
        $user = User::findOrFail($request->user()->id);
        if ($response['status'] == 0) {
            if (strtoupper(Str::squish($response['data']['first_name'] . ' ' . $response['data']['middle_name'] . ' ' . $response['data']['last_name'])) == strtoupper(Str::squish($user->first_name . ' ' . $user->middle_name . ' ' . $user->last_name))) {
                $user->pan_number = $response['data']['pan_number'];
                $user->save();
                return new GeneralResource($user);
            } else {
                abort(403, "PAN name doesn't match with the user name");
            }
        } else {
            abort(502, $response['message']);
        }
    }

    public function getPanDetails(Request $request)
    {
        $request->validate([
            'pan_number' => ['required', 'regex:/^([A-Z]){5}([0-9]){4}([A-Z]){1}?$/']
        ]);

        $class = new EkoController;
        $class->activateService(4);

        $data = [
            'pan_number' => $request->pan_number,
            'purpose' => 1,
            'purpose_desc' => 'verification',
            'initiator_id' => config('services.eko.initiator_id')
        ];

        $response = Http::withHeaders($this->ekoHeaders())->asForm()
            ->post(config('services.eko.base_url') . '/v1/pan/verify', $data);

        Log::info(['request' => $data, 'response' => $response->body()]);

        if ($response['status'] == 0) {
            return new GeneralResource($response['data']);
        } else {
            abort(400, $response['message']);
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
