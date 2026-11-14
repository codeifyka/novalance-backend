<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    public function jobPosts()
    {
        return $this->belongsToMany(JobPost::class, 'job_category', 'category_id', 'job_post_id');
    }

    public function services()
    {
        return $this->hasMany(Service::class, 'category_id');
    }
}
