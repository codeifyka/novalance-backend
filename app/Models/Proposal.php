<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proposal extends Model
{
    use HasFactory;

    protected $fillable = [
        'freelancer_id',
        'job_post_id',
        'cover_letter',
        'bid',
        'duration',
        'status',
        'started_at',
        'ends_at'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ends_at' => 'datetime',
        'bid' => 'decimal:2',
    ];

    protected $appends = [
        'can_dispute',
    ];

    public function freelancer()
    {
        return $this->belongsTo(User::class, "freelancer_id");
    }

    public function job_post()
    {
        return $this->belongsTo(JobPost::class);
    }

    public function dispute()
    {
        return $this->hasOne(Dispute::class);
    }

    public function transactions()
    {
        return $this->morphMany(WalletTransaction::class, 'source');
    }

    public function getCanDisputeAttribute(): bool
    {
        return $this->status === 'active' 
            && $this->ends_at 
            && now()->greaterThanOrEqualTo($this->ends_at);
    }
}
