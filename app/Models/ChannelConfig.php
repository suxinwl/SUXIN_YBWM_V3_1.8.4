<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class ChannelConfig extends BaseModel
{
    use HasFactory;
    protected $table = 'channel_config';
    protected $casts =  [
        'data' => 'array',
    ];
    protected $fillable = [
        'data', 'name', "ident", "uniacid",'storeId'
    ];

    /**
     * 查询用户的时候name字段处理
     *
     * @author Eric
     * @param $value
     * @return string
     */
    public function getDataAttribute($value)
    {
        return json_decode($value);
    }
    public static function boot()
    {
        parent::boot();
        static::saving(function ($model) {
            $key =  "configMap:" . $model->uniacid . 'storeId:' . $model->storeId;
            Cache::delete($key);
        });
    }
}
