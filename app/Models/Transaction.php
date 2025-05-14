<?php

namespace App\Models;

use App\Http\Resources\GeneralResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Transaction extends Model
{
    use HasFactory, HasUuids;

    protected $fillable  = [
        'user_id',
        'updated_by',
        'triggered_by',
        'reference_id',
        'service',
        'description',
        'credit_amount',
        'debit_amount',
        'opening_balance',
        'closing_balance',
        'metadata',
        'gst'
    ];

    /**
     * Get the beneficiary that owns the Transaction
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->select(['id', 'name', 'phone_number']);
    }

    /**
     * Get the reviewer that owns the Transaction
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by')->select(['id', 'name', 'phone_number']);
    }

    /**
     * Get the reviewer that owns the Transaction
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function triggered_by(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by')->select(['id', 'name', 'phone_number']);
    }

    public function scopeAdminFiterByRequest($query, Request $request)
    {
        if (!empty($request['transaction_id'])) {
            $query->where('reference_id', 'like', "%{$request->transaction_id}%");
        }


        if (!empty($request['user_id'])) {
            $query->join('users', 'users.id', '=', 'transactions.user_id')
                ->where('users.phone_number', $request->user_id)->select('transactions.*');
        }

        if (!empty($request['account_number'])) {
            $query->where('description', 'like', "%{$request->account_number}%");
        }

        return $query;
    }

    public function scopeDailySales($query)
    {
        return $query->join('users', 'users.id', '=', 'transactions.user_id')
            ->select(
                'user_id',
                'service',
                'users.name as user_name',
                DB::raw('SUM(credit_amount) as total_credit_amount'),
                DB::raw('SUM(debit_amount) as total_debit_amount')
            )->groupBy(['user_id', 'service', 'user_name']);
    }
}
