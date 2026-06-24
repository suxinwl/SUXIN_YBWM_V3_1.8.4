<?php

namespace App\Models\Delivery;

use App\Models\BaseModel;
use App\Services\Delivery\WaisongBangService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Channel extends BaseModel
{
    use HasFactory;
    protected $table = 'delivery_channel';
    protected $fillable = ['uniacid', 'config', 'storeId', 'type', 'channelId'];
    protected $casts =  [
        'config' => 'array',
    ];


    public function getAmountAttribute()
    {
        if ($this->type == 3 && $this->storeId > 0) {
            return bcdiv(WaisongBangService::balance($this->storeId, $this->uniacid), 100, 2);
        }
        return '0.00';
    }

    public function getStoreDetailAttribute()
    {
        if ($this->type == 3 && $this->storeId > 0) {
            return WaisongBangService::storeDetail($this->storeId, $this->uniacid); 
        }
        return null;
    }

    public static function boot()
    {
        parent::boot();
        static::saving(function ($model) {
            if (empty($model->config)) {
                if ($model->type == 3) {
                    $str = '[{"ship_way":2,"name":"达达经济"},{"ship_way":4,"name":"美团飞速达"},{"ship_way":3,"name":"美团快速达"},{"ship_way":7,"name":"顺丰同城"},{"ship_way":8,"name":"闪送"},{"ship_way":10,"name":"蜂鸟众包"},{"ship_way":11,"name":"UU跑腿"},{"ship_way":13,"name":"裹小递"},{"ship_way":14,"name":"达达优质"},{"ship_way":15,"name":"顺丰同城B"},{"ship_way":16,"name":"顺丰同城C"},{"ship_way":17,"name":"达达急送"},{"ship_way":18,"name":"顺丰企业C"}]';
                    $data = json_decode($str, true);
                    $model->config = collect($data)->map(function ($item) {
                        return collect($item)
                            ->put('state', 0);
                    })->toArray();
                }
            }
        });
    }
}
