<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Login;
use App\Models\Otp;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{

    public function checkCredentials(Request $request)
    {
        $request->only(['email', 'password', 'otp']);

        $user = User::whereAny(['email', 'phone_number'], '=', $request->email)->first();

        if (!$user || !Hash::check($request['password'], $user->password)) {
            throw ValidationException::withMessages([
                'error' => ['Credentials do not match our records.']
            ]);
        }

        if (DB::table('services')->where(['name' => 'otp', 'active' => 1, 'provider' => 'portal'])->exists()) {
            $this->validateOtp($user->id, $request->otp);
        }

        return $user->email;
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request): JsonResponse
    {
        $email = $this->checkCredentials($request);

        if (!$token = auth()->attempt(['email' => $email, 'password' => $request->password])) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $user = auth()->user();
        $user['roles'] = auth()->user()->getRoleNames()->first();
        $cookie = cookie("token", $token, auth()->factory()->getTTL() * 60, '/', config('session.domain'), true, true);
        $this->loginRecord($request);
        return response()->json($this->respondWithToken(['user' => $user]))->withCookie($cookie);
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(): JsonResponse
    {
        return response()->json(['user' => auth()->user(), 'role' => auth()->user()->getRoleNames()->first()]);
    }

    public function loginRecord(Request $request)
    {
        Login::create([
            'user_id' => $request->user()->id,
            'ip_address' => $request->ip(),
            'latlong' => $request->latlong
        ]);
    }

    public function validateOtp($user_id, $password)
    {
        $otp = Otp::where(['user_id' => $user_id, 'intent' => 'login', 'used' => 0])->latest()->first();
        if (!$otp || !Hash::check($password, $otp->password) || $otp->expiry_at < now()) {
            abort(400, "Invalid OTP.");
        }
        $otp->used = 1;
        $otp->save();

        return true;
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh(): JsonResponse
    {
        return $this->respondWithToken(auth()->refresh());
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request)
    {
        if (Auth::check()) {
            auth()->logout();
        }
        $cookie = cookie("token", null, -1, '/', config('session.domain'), true, true);
        return response()->json(['message' => 'log out successfully.'])->withCookie($cookie);
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }
}
