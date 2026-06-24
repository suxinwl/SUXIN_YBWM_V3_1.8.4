<?php

namespace App\Models\Store;

use App\Models\BaseModel;
use App\Models\Goods\Channel;
use App\Models\GoodsSearch\GoodsSpuBase;
use App\Models\GoodsSku;
use App\Models\GoodsSpu;
use App\Models\Recipe\Recipe;
use App\Models\Recipe\RecipeGoods;
use App\Models\SpuCatgorys;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use App\Models\Recipe\RecipeGoodsSku;
class StoreGoods extends BaseModel
{
    protected $table = 'store_goods';
    protected $primaryKey = 'spuId';
    protected $guarded = [];
    public $_skus;
    public $_goods;
    public $_singleSpec;
    use HasFactory, SoftDeletes;
    protected $hidden = [
        'spu', 'storeGoodsSku'
    ];
    protected $appends = [
        'goods'
    ];

    public function spu()
    {
        return $this->hasOne(GoodsSpu::class, 'id', 'spuId')->withTrashed()
            ->select([
                'id', 'name', 'logo', 'specSwitch', 'isShow', 'sort', 'channelIds', 'orderlimitSwitch',
                'orderlimit',
                'userlimitSwitch',
                "userlimit",
                "daylimitSwitch",
                "daylimit",
                'min',
                'daylimitSwitch',
                'oneDeliverySwitch',
                'specSwitch',
            ]);
    }


    public function storeSkus()
    {
        return $this->hasMany(StoreGoodsSku::class, 'spuId', 'spuId')->withTrashed();
    }

    public function channel()
    {
        return $this->hasMany(Channel::class, 'spuId', 'spuId');
    }


    public function storeGoodsSku()
    {
        return $this->hasMany(StoreGoodsSku::class, 'spuId', 'spuId')
            ->with(['sku'])
            ->withTrashed()
            ->where('recipeId', $this->recipeId)
            ->where('storeId', $this->storeId)
            ->groupBy('specMd5');
    }


    public function recipe()
    {
        return $this->hasOne(Recipe::class, 'id', 'recipeId');
    }

    public function getSkusAttribute()
    {

        if ($this->_skus === null && $this->spu->specSwitch == 1) {
            $this->_skus = collect($this->storeGoodsSku)->map(function ($model, $key) {
                $modelSku=empty($model->sku)?array():$model->sku->toArray();
                if($modelSku){
                    $modelSku['surplusInventory'] = $model->surplusInventory;
                    $modelSku['oldPrice'] = $model->sku->price;
                    $modelSku['oldInventory'] = $model->sku->inventory;
                    $modelSku['price'] = $model->price?:$model->sku->price;
                    $modelSku['dayFilling'] = $model->dayFilling;
                    $modelSku['inventory'] = $model->inventory;
                    $modelSku['discount'] = collect($model->discount)->toArray();
                    if ($model->discount->type == 6) {
                        if ($model->discount->discountType == 1) {
                            $price = bcmul(bcdiv($modelSku['price'], 100, 4), $model->discount->wmDiscount * 10, 2);
                        }
                        if ($model->discount->discountType == 2) {
                            $price = bcsub($modelSku['price'], $model->discount->wmDiscount, 2);
                            if ($price < 0) {
                                $price = 0;
                            }
                        }
                        if ($model->discount->discountType == 3) {
                            $price = $model->discount->wmDiscount;
                            if ($price < 0) {
                                $price = 0;
                            }
                        }
                        $modelSku['discount']['price'] = $price;
                        $modelSku['discount']['linePrice'] = $modelSku['price'];
                    }
                    return $modelSku;
                }else{
                    //storeGoodsSku::where('specMd5',$model->specMd5)->forceDelete();
                }

            });
        }
        return $this->_skus;
    }

    public function getGoodsAttribute()
    {
        if (empty($this->_goods)) {
            if($this->spu){
                $goods=empty($this->spu)?array():$this->spu->toArray();
            }else{
                $storeGoodsModel=StoreGoods::where('id',$this->id)->first();
                $spuId=$storeGoodsModel->spuId;
                $storeId=$storeGoodsModel->storeId;
                //StoreGoods::where('id',$this->id)->forceDelete();
                // StoreGoodsSku::where('spuId',$spuId)->where('storeId',$storeId)->forceDelete();
            }

            $sku = $this->skus ?? $this->spu->skus;
            $singleSpec = $this->singleSpec ?? $this->spu->singleSpec;
            $sales = Cache::get("storeGoods:{$this->storeId}:{$goods['id']}");
            if ($sales < 0) {
                Cache::set("storeGoods:{$this->storeId}:{$goods['id']}", 0);
                $sales = 0;
            }
            $goods['sales'] = Cache::get("storeGoods:{$this->storeId}:{$goods['id']}");

            $goods['singleSpec'] = $singleSpec;
            $goods['skus'] = $sku;
            if ($sku) {
                $goods['price']  = collect($goods['skus'])->min('price');
                $goods['equityCardPrice'] =  collect($goods['skus'])->where('discount.type', 10)->min('discount.price');
                $goods['goodsInventory']  = collect($goods['skus'])->sum('surplusInventory');
                $goods['discountMinPrice'] = collect($goods['skus'])->where('discount.type', 6)->min('discount.price');
                $goods['mixPrice'] = collect($goods['skus'])->min('price');
                $goods['maxPrice'] = collect($goods['skus'])->max('price');
                $goods['maxOldPrice'] = collect($goods['skus'])->max('oldPrice');
                $goods['mixOldPrice'] = collect($goods['skus'])->min('oldPrice');
                $goods['maxInStorePrice'] = collect($goods['skus'])->max('inStorePrice');
                $goods['minInStorePrice'] = collect($goods['skus'])->min('inStorePrice');
                $goods['discounts'] = collect($goods['skus'])->pluck('discount')->filter(function ($item, $key) {
                    return $item['type'] > 6 && $item['type'] != 10;
                })->unique('type')->values();
            }
            if ($singleSpec) {
                $goods['price']  = $goods['singleSpec']['price'];
                $goods['discountMinPrice'] =  collect([])->push($goods['singleSpec'])->where('discount.type', 6)->min('discount.price');
                $goods['equityCardPrice'] =  collect([])->push($goods['singleSpec'])->where('discount.type', 10)->min('discount.price');
                $goods['goodsInventory']  = collect([])->push($goods['singleSpec'])->sum('surplusInventory');
                $goods['inStorePrice'] = collect($goods['singleSpec'])->max('inStorePrice');
                $goods['discounts'] =  collect([])->push($goods['singleSpec'])->pluck('discount')->filter(function ($item, $key) {
                    return $item['type'] > 6 && $item['type'] != 10;
                })->values();
            }
            $this->_goods = $goods;
        }
        return $this->_goods;
    }


    public function getSingleSpecAttribute()
    {
        if ($this->spu->specSwitch == 0) {
            $model = collect($this->storeGoodsSku)->first();
            if(empty($model)){
                StoreGoods::where('recipeId',0)->forceDelete();
                $model = collect($this->storeGoodsSku)->first();
            }
            $modelSku=empty($model)?array():$model->sku->toArray();
            $modelSku['surplusInventory'] = $model->surplusInventory;
            $modelSku['oldPrice'] = $model->sku->price;
            $modelSku['oldInventory'] = $model->sku->inventory;
            $modelSku['price'] = $model->price;
            $modelSku['inStorePrice'] = $model->inStorePrice;
            $modelSku['dayFilling'] = $model->dayFilling;
            $modelSku['inventory'] = $model->inventory;
            $modelSku['discount'] = collect($model->discount)->toArray();
            if ($model->discount->type == 6) {
                $modelSku['oldPrice'] =  $modelSku['price'];
                if ($model->discount->discountType == 1) {
                    $price = bcmul(bcdiv($modelSku['price'], 100, 4), $model->discount->wmDiscount * 10, 2);
                }
                if ($model->discount->discountType == 2) {
                    $price = bcsub($modelSku['price'], $model->discount->wmDiscount, 2);
                    if ($price < 0) {
                        $price = 0;
                    }
                }
                if ($model->discount->discountType == 3) {
                    $price = $model->discount->wmDiscount;
                    if ($price < 0) {
                        $price = 0;
                    }
                }
                $modelSku['discount']['price'] = $price;
                $modelSku['discount']['linePrice'] = $modelSku['price'];
            }
            $this->_singleSpec =  $modelSku;
        }
        return $this->_singleSpec;
    }

    public function category()
    {
        return $this->hasMany(SpuCatgorys::class, 'spuId', 'spuId');
    }

    public static function boot()
    {
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

    public function scopeShelf($q)
    {
        return $q;
    }

    public function scopeOffShelf($q)
    {
        return $q->onlyTrashed();
    }

    public function scopeInventoryOff($q, $type, $storeId)
    {
        return $q->whereHas('storeSkus', function ($q) use ($type, $storeId) {
            return $q->where("surplusInventory", '<=', 15)->where('type', $type)->where('storeId', $storeId);
        });
    }
}
