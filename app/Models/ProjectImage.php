<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectImage extends Model
{
    use HasFactory;
    protected $table = 'projects_images';
    protected $appends = ['url'];

    protected $fillable = [
        'path',
        'project_id',
    ];

    public function getUrlAttribute(){
        return asset('storage/'.$this->path);
    }
}
