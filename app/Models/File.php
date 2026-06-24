<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;

class File extends Model
{
    use HasFactory;
    protected $table = 'core_file';
    protected $guarded = [];
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
    public static function boot()
    {
        parent::boot();
        static::deleted(function ($model) {
            @unlink(public_path($model->path));
        });
    }
}
