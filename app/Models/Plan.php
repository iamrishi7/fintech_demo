<?php

namespace App\Models;

use App\Models\AepsCommission;
use App\Models\PayoutCommission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Plan extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Get all of the aeps for the Plan
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function aeps(): HasMany
    {
        return $this->hasMany(AepsCommission::class);
    }

    /**
     * Get all of the payouts for the Plan
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function payouts(): HasMany
    {
        return $this->hasMany(PayoutCommission::class);
    }

    /**
     * Get the user that owns the Plan
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
