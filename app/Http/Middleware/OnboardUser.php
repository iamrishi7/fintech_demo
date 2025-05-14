<?php

namespace App\Http\Middleware;

use App\Models\Service;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OnboardUser
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // $service = Service::where(['id' => $request->service_id, 'active' => 1])->first();
        // if (!$service) {
        //     return response()->json(['message' => 'Invalid Service'], 404);
        // }

        // if ($service->provider == 'eko') {
        //     if (is_null($request->user()->eko_user_code)) {
        //         return response()->json(["message" => "Please complete your onboarding first."], 403);
        //     }
        // }

        // if ($service->activation_required == 1 && $request->user()->active == 0) {
        //     return response()->json(["message" => "Your ID should be active to use this service" ], 403);
        // }

        return $next($request);
    }
}
