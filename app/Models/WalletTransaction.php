<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WalletTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'wallet_id',
        'type',
        'amount',
        'direction',
        'source_type',
        'source_id',
        'provider',
        'provider_data',
        'status',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'provider_data' => 'array',
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
