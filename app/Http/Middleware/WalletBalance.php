<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Services\Payout\CommissionController;
use App\Models\Service;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WalletBalance
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $service = Service::findOrFail($request->service_id);

        switch ($service->name) {
            case 'payout':
                $commission = new CommissionController;
                $commission = $commission->distributeCommission($user, $request->amount, "calcs", false, true);
                $debit = $request->amount + $commission['debit_amount'] -  $commission['credit_amount'];
                break;

            default:
                $debit =  $request->amount;
                break;
        }

        $balance_left = $user->wallet - $debit;

        if ($user->capped_balance >= $balance_left) {
            abort(402, "Insufficient balance.  Please top up your wallet.");
        }

        return $next($request);
    }
}
