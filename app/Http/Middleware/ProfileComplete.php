<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProfileComplete
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (is_null($user->phone_number)) {
            return response()->json(['message' => 'Add phone number.'], 400);
        }

        if (is_null($user->date_of_birth)) {
            return response()->json(['message' => 'Add Date of Birth.'], 400);
        }

        if (is_null($user->pan_number)) {
            return response()->json(['message' => 'Add PAN Number.'], 400);
        }

        if (empty($user->address)) {
            return response()->json(['message' => 'Add your address.'], 400);
        }
        
        return $next($request);
    }
}
