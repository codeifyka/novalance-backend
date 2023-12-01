<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
