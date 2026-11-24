<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'balance',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class)->latest();
    }

    public function getEscrowBalanceAttribute(): float
    {
        $user = $this->user;
        if (!$user) return 0.0;

        if ($user->account_type === 'client') {
            return (float) Proposal::whereHas('job_post', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })->whereIn('status', ['active', 'disputed'])->sum('bid');
        } elseif ($user->account_type === 'freelancer') {
            return (float) Proposal::where('freelancer_id', $user->id)
                ->whereIn('status', ['active', 'disputed'])
                ->sum('bid');
        }

        return 0.0;
    }
}
