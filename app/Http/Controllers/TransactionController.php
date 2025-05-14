<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public static function store(User $user, string $reference_id, string $service, string $description, float $credit_amount, float $debit_amount, array $response = null, float $gst = null)
    {
        $auth_user = auth()->user() ?? $user;
        $last_transaction = Transaction::where('user_id', $user->id)->latest()->orderByDesc('id')->limit(1)->first();
        $closing_balance = ($last_transaction->closing_balance ?? 0) + $credit_amount - $debit_amount;
        Transaction::create([
            'user_id' => $user->id,
            'updated_by' => $auth_user->id,
            'triggered_by' => $auth_user->id,
            'reference_id' => $reference_id,
            'service' => $service,
            'description' => $description,
            'credit_amount' => $credit_amount,
            'debit_amount' => $debit_amount,
            'opening_balance' => $last_transaction->closing_balance ?? 0,
            'closing_balance' => $closing_balance,
            'gst' => $gst
        ]);

        DB::transaction(function () use ($user, $closing_balance) {
            $user->lockForUpdate();
            $user->wallet = $closing_balance;
            $user->save();
        }, 3);
    }

    public static function reverseTransaction(string $reference_id)
    {
        DB::transaction(function () use ($reference_id) {
            $transactions = Transaction::where('reference_id', $reference_id)->get();
            foreach ($transactions as $transaction) {
                self::store(User::find($transaction->user_id), $reference_id, $transaction->service, 'Refunding for' . ' ' . $transaction->description, $transaction->debit_amount, $transaction->credit_amount);
            }
        }, 3);
    }
}
