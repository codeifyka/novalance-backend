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
        'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function job_post()
    {
        return $this->belongsTo(JobPost::class);
    }
}
