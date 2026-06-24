<?php

namespace App\Models\Store;

use App\Models\BaseModel;
use App\Models\Goods\Channel;
use App\Models\Goods\SpuList;
use App\Models\GoodsSpu;
use App\Models\SpuCatgorys;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class GoodsList extends BaseModel
{
    protected $table = 'store_goods';
    protected $primaryKey = 'spuId';
    protected $guarded = [];
    public $_skus = null;
    public $_goods;
    public $_singleSpec;
    use HasFactory, SoftDeletes;
    protected $hidden = [
        'spu', 'salesTimeSwitch', 'salesTimeData'
    ];
    protected $appends = [
        'goods'
    ];

    public function spu()
    {
        return $this->hasOne(SpuList::class, 'id', 'spuId')->withTrashed()
            ->select([
                'id', 'name', 'logo', 'desc',
                'labelId',
                'specSwitch',
                'attrSwitch',
                'materialSwitch',
                'isExhibition',
                'isShow',
                'markId',
                'salesTimeSwitch',
                'salesTimeData',
                'orderlimitSwitch',
                'orderlimit',
                'userlimitSwitch',
                "userlimit",
                "daylimitSwitch",
                "daylimit",
                "min"
            ])
            ->with(['label', 'category', 'channel', 'mark']);
    }

    public function channel()
    {
        return $this->hasMany(Channel::class, 'spuId', 'spuId');
    }

    public function storeSkus()
    {
        return $this->hasMany(StoreGoodsSku::class, 'spuId', 'spuId')
            ->with(['sku'])
            ->withTrashed()
            ->where('recipeId', $this->recipeId)
            ->where('storeId', $this->storeId);
    }


    public function recipe()
    {
        return $this->hasOne(Recipe::class, 'id', 'recipeId');
    }

    public function category()
    {
        return $this->hasMany(SpuCatgorys::class, 'spuId', 'spuId');
    }

    public function getSkusAttribute()
    {
        if ($this->_skus === null && $this->spu->specSwitch == 1) {
            $this->_skus = collect($this->storeSkus)->map(function ($model, $key) {
                $modelSku = $model->sku->toArray();
                $modelSku['surplusInventory'] = $model->surplusInventory;
                $modelSku['oldPrice'] = $model->sku->price;
                $modelSku['oldInventory'] = $model->sku->inventory;
                $modelSku['price'] = $model->price;
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
            });
        }
        return $this->_skus;
    }

    public function getGoodsAttribute()
    {
        if (empty($this->_goods)) {
            $goods = $this->spu->toArray();
            $sku = $this->skus ?? $this->spu->skus;
            $singleSpec = $this->singleSpec ?? $this->spu->singleSpec;
            $goods['sales'] = $goods['initialSales'] + Cache::get("storeGoods:{$this->storeId}:{$goods['id']}");
            $goods['singleSpec'] = $singleSpec;
            $goods['skus'] = $sku;
            $goods['inTime'] = $this->spu->inTime;
            if ($sku) {
                $goods['maxPrice'] = collect($sku)->max('price');
                $goods['mixPrice'] = collect($sku)->min('price');
                $goods['goodsInventory'] = collect($sku)->sum('surplusInventory');
                $goods['discountMinPrice'] = collect($sku)->where('discount.price', ">", 0)->min('discount.price');
            }
            if ($singleSpec) {
                $goods['linePrice'] = $singleSpec['linePrice'];
                $goods['price'] = $singleSpec['price'];
                $goods['goodsInventory'] = $singleSpec['surplusInventory'];
                $goods['discountMinPrice'] = $singleSpec['discount']['price'] ?? null;
            }
            $this->_goods = $goods;
        }
        return $this->_goods;
    }


    public function getSingleSpecAttribute()
    {
        if (count($this->storeSkus) == 1 && $this->spu->specSwitch == 0) {
            $model = collect($this->storeSkus)->first();
            $modelSku = $model->sku->toArray();
            $modelSku['surplusInventory'] = $model->surplusInventory;
            $modelSku['oldPrice'] = $model->sku->price;
            $modelSku['oldInventory'] = $model->sku->inventory;
            $modelSku['price'] = $model->price;
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
            $this->_singleSpec =  $modelSku;
        }
        return $this->_singleSpec;
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

    public function scopeInventoryOff($q)
    {
        return $q->whereHas('storeSkus', function ($q) {
            return $q->where("surplusInventory", '<=', 10);
        });
    }
}
