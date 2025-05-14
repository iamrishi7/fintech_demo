<?php

namespace App\Http\Controllers\Dashboard\User;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OnboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function ekoOnboard(Request $request)
    {
        $user = $request->user();
        $data = [
            'initiator_id' => config('services.eko.initiator_id'),
            'pan_number' => $user->getRawOriginal('pan_number'),
            'mobile' => $user->phone_number,
            'first_name' => $user->first_name,
            'middle_name' => $user->middle_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'residence_address' => json_encode($user->address->makeHidden(['id', 'user_id', 'created_at', 'updated_at', 'shop_name'])),
            'dob' => $user->date_of_birth,
            'shop_name' => $user->address->shop_name
        ];

        $response = Http::withHeaders($this->ekoHeaders())->asForm()
            ->put(config('services.eko.base_url') . '/v1/user/onboard', $data);

        Log::info(['onboard' => $response->body()]);
        if ($response->failed() || !array_key_exists('data', $response->json())) {
            abort(400, $response['message'] ?? "Failed to onboard");
        }

        if ($response['status'] == 0) {
            $user = User::findOrFail($user->id);
            $user->eko_user_code = $response['data']['user_code'];
            $user->save();
        }

        return ['message' => $response['message']] ?? ['eko_id' => $user->eko_user_code];
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
