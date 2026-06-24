<?php

namespace App\Models\Recipe;

use App\Models\BaseModel;
use App\Models\GoodsSku;
use App\Models\Store\StoreGoods;
use App\Models\Store\StoreGoodsSku;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecipeGoodsSku extends BaseModel
{
    protected $table = 'recipe_goods_sku';
    protected $fillable = ['uniacid','selfInventorySwitch','selfPriceSwitch','selfInventorySwitch','recipeId', 'spuId', 'specMd5', 'inventory', 'price', 'state', 'type'];
    use HasFactory, SoftDeletes;
    protected $casts =  [
        'specName' => 'array',
    ];
    public function recipe()
    {
        return $this->hasOne(Recipe::class, 'id', 'recipeId');
    }

//    public static function boot()
//    {
//        parent::boot();
//        static::created(function ($model) {
//            foreach ($model->recipe->store as $key => $store) {
//                $spus[] = StoreGoods::where('uniacid', $model->uniacid)->where('storeId', $model->storeId)->get();
//                foreach ($spus as $key => $spu) {
//                    $storeSkuList[] = [
//                        'uniacid' => $model->uniacid,
//                        'type' => $spu->type,
//                        'recipeId' => $model->recipeId,
//                        'storeId' => $store->storeId,
//                        'specMd5' => $model->specMd5,
//                        'inventory' => $model->inventory,
//                        'surplusInventory' => $model->inventory,
//                        'state' => $model->state?:1,
//                        'spuId' => $model->spuId,
//                        'price' => $model->price,
//                        'sort' => 0,
//                        'deleted_at' => $model->deleted_at
//                    ];
//                }
//                if ($storeSkuList) {
//                    StoreGoodsSku::insert($storeSkuList);
//                }
//            }
//        });
//    }

    public function sku()
    {
        return $this->hasOne(GoodsSku::class, 'specMd5', 'specMd5');
    }
}
