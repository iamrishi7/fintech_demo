<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;

class FundTransfer extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Get the user that owns the FundTransfer
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin that owns the FundTransfer
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function scopeAdminFilterByRequest($query, Request $request)
    {
        if (!empty($request['user_id'])) {
            $query->join('users', 'users.id', '=', 'fund_transfers.user_id')
                ->where('users.phone_number', $request->user_id)
                ->select('fund_transfers.*');
        }
    }
}
