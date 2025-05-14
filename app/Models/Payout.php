<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;

class Payout extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Get the user that owns the Payout
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeFiterByRequest($query, Request $request)
    {
        if (!empty($request['transaction_id'])) {
            $query->where('reference_id', 'like', "%{$request->transaction_id}%");
        }

        if (!empty($request['utr'])) {
            $query->where('utr', 'like', "%{$request->utr}%");
        }

        if (!empty($request['account_number'])) {
            $query->where('account_number', 'like', "%{$request->account_number}%");
        }

        return $query;
    }

    public function scopeAdminFilterByRequest($query, array $request)
    {
        if (!empty($request['transaction_id'])) {
            $query->where('reference_id', 'like', "%{$request['transaction_id']}%");
        }

        if (!empty($request['utr'])) {
            $query->where('utr', 'like', "%{$request['utr']}%");
        }

        if (!empty($request['account_number'])) {
            $query->where('account_number', 'like', "%{$request['account_number']}%");
        }

        if (!empty($request['status'])) {
            $query->where('status', $request['status']);
        }

        if (!empty($request['user_id'])) {
            $query->where('users.phone_number', $request['user_id'])
                ->join('users', 'users.id', '=', 'payouts.user_id');
        }

        return $query;
    }
}
