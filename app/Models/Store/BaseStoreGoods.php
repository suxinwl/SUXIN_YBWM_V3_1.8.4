<?php

namespace App\Models\Store;

use App\Models\BaseModel;
use App\Models\Goods\Channel;
use App\Models\GoodsSpu;
use App\Models\SpuCatgorys;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class BaseStoreGoods extends BaseModel
{
    protected $table = 'store_goods';
    protected $primaryKey = 'id';
    protected $guarded = [];
    use HasFactory, SoftDeletes;

    public static function boot()
    {
//        $duplicates = StoreGoodsSku::query()->groupBy('storeId','spuId')->havingRaw('count(*) > 1')->get();
//        foreach ($duplicates as $duplicate) {
//            $duplicate->delete(); // 删除除了第一条记录之外的所有记录
//        }

        parent::boot();
        static::deleting(function ($model) {
            try {
                $list = StoreGoodsSku::withTrashed()->where("storeId", $model->storeId)->where("spuId", $model->spuId)->delete();
            } catch (\Exception $e) {
            }
        });
        static::restoring(function ($model) {
            try {
                $list = StoreGoodsSku::withTrashed()->where("storeId", $model->storeId)->where("spuId", $model->spuId)->restore();
            } catch (\Exception $e) {
            }
        });
    }
}
