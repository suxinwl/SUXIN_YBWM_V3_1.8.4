<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Drag extends BaseModel
{
    protected $table = 'drag';
    protected $fillable = ['notes', 'uniacid', 'storeId', 'title', 'type', 'data', 'appType', 'channel', 'state'];
    protected $casts =  [
        'data' => 'array',
    ];
    use HasFactory;


    public static function boot()
    {
        parent::boot();
        static::saving(function ($model) {
            if ($model->storeId) {
                $key = 'drag:' . $model->uniacid . 'storeId:' . $model->storeId;
            } else {
                $key = 'drag:' . $model->uniacid;
            }

            Cache::delete($key);
        });

        static::deleting(function ($model) {
            if ($model->storeId) {
                $key = 'drag:' . $model->uniacid . 'storeId:' . $model->storeId;
            } else {
                $key = 'drag:' . $model->uniacid;
            }
            Cache::delete($key);
        });
    }
}
