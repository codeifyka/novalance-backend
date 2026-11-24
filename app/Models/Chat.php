<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    use HasFactory;

    protected $fillable = [
        'freelancer_id',
        'client_id',
        'job_post_id',
        'service_id',
        'chattable_type',
        'chattable_id',
        'tag',
        'start_date',
        'end_date',
        'temporary_removed',
        'isRead',
    ];

    protected $casts = [
        'temporary_removed' => 'boolean',
    ];

    protected $appends = [
        'chat_tag',
        'chattable_title',
        'is_expired',
    ];

    public function getIsExpiredAttribute(): bool
    {
        return $this->end_date ? now()->greaterThan($this->end_date) : false;
    }

    public function chattable()
    {
        return $this->morphTo();
    }

    public function jobPost()
    {
        return $this->belongsTo(JobPost::class, 'job_post_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function freelancer()
    {
        return $this->belongsTo(User::class, 'freelancer_id');
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function lastMessage()
    {
        return $this->hasOne(Message::class)->latest();
    }

    public function getChatTagAttribute()
    {
        if ($this->tag) {
            return $this->tag;
        }
        if ($this->chattable_type === Service::class || $this->chattable_type === 'service' || $this->service_id) {
            return 'service';
        }
        if ($this->chattable_type === JobPost::class || $this->chattable_type === 'job_post' || $this->job_post_id) {
            return 'job_post';
        }
        return 'general';
    }

    public function getChattableTitleAttribute()
    {
        if ($this->relationLoaded('chattable') && $this->chattable) {
            return $this->chattable->title ?? null;
        }
        if ($this->relationLoaded('jobPost') && $this->jobPost) {
            return $this->jobPost->title ?? null;
        }
        if ($this->relationLoaded('service') && $this->service) {
            return $this->service->title ?? null;
        }
        return null;
    }
}
