<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class StoreConfig extends Model
{
    use HasFactory;
    protected $table = 'store_config';
    protected $fillable = [
        'data', 'name', "ident", "storeId"
    ];
    protected $casts =  [
        'data' => 'array',
    ];


    public static function boot()
    {
        parent::boot();
        static::saving(function ($model) {
            $key =  "storeConfigMap:" . $model->storeId;
            Cache::delete($key);
        });
    }
}
