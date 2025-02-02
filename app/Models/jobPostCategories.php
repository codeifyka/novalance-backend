<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class jobPostCategories extends Model
{
    use HasFactory;
    protected $table = 'job_category';

    protected $fillable = [
        'category_id',
        'job_post_id',
        'created_at',
        'updated_at'
    ];
}
