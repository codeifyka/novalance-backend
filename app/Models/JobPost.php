<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Proposal;

class JobPost extends Model
{
    use HasFactory;
    protected $fillable = [
        'title',
        'description',
        'size',
        'experience_level',
        'user_id',
        'expected_delivery_time',
        'budjet',
        'illustrative_files',
        'category_id'
    ];


    public function categories()
    {
        return $this->belongsToMany(Category::class, 'job_category', 'job_post_id', 'category_id');
    }
    
    public function proposals()
    {
        return $this->hasMany(Proposal::class, 'job_post_id');
    }

}
