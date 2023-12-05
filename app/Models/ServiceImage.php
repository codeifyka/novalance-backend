<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceImage extends Model
{
    use HasFactory;
    protected $table = 'services_images';
    protected $appends = ['url'];

    protected $fillable = [
        'path',
        'service_id',
    ];

    public function getUrlAttribute(){
        return asset('storage/'.$this->path);
    }
}
