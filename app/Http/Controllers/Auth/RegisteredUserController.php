<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Mail\SendPassword;
use App\Models\Plan;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;

class RegisteredUserController extends Controller
{
    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            // 'phone_number' => ['required', 'digits:10'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:' . User::class],
            // 'password' => ['required', 'confirmed', Rules\Password::defaults()],
            // 'mpin' => ['required', 'confirmed'],
            // 'terms' => ['accepted']
        ]);

        $role = Role::where('default', true)->first();
        $password = Str::random(8);
        $plan = Plan::where('default', 1)->first();
        $pin = rand(1001, 9999);
        $user = User::create([
            'first_name' => $request->first_name,
            'name' => Str::squish($request->first_name . ' ' . $request->last_name),
            'last_name' => $request->last_name,
            'email' => $request->email,
            'capped_balance' => 0,
            'password' => Hash::make($password),
            'pin' => Hash::make($pin),
            'plan_id' => $plan->id ?? null
        ])->assignRole($role->name);

        event(new Registered($user));

        Mail::to($request->email)
        ->send(new SendPassword($password, 'password', $pin));
        // Auth::login($user);

        return response()->json(['data' => 'registered sucessfully']);
    }

    public function createToken(Request $request)
    {
        $request->validate(['email' => 'required|unique:new_registration_token']);

        DB::table('new_registration_token')->insert([
            'email' => $request->email,
            'token' => Str::uuid(),
            'expiry_at' => Carbon::now()->addDay(),
            'created_at' => now()
        ]);
    }
}
