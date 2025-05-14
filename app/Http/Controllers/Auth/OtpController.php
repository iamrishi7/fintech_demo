<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\SendOtp;
use App\Models\Otp;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class OtpController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    public function mailOtp(Request $request)
    {
        if (!DB::table('services')->where(['name' => 'otp', 'active' => 1, 'provider' => 'portal'])->exists()) {
            abort(400, "OTP not required.");
        }
        $request->validate(['email' => ['required', 'email', 'exists:users,email']]);

        $otp = rand(100001, 999999);
        $user = User::where('email', $request->email)->firstOrFail();
        Mail::to($user->email)->send(new SendOtp($otp));
        $this->store($otp, 'login', $user->id);

        return response()->json(['message' => "OTP sent"]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store($password, $intent, $user_id)
    {
        $data = Otp::create([
            'user_id' => $user_id,
            'password' => Hash::make($password),
            'intent' => $intent,
            'expiry_at' => Carbon::now()->addMinutes(5)
        ]);
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
