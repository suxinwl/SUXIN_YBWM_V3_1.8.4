<?php

namespace App\Models\Store;

use App\Models\BaseModel;
use App\Models\Goods\Channel;
use App\Models\Goods\SpuList;
use App\Models\GoodsSpu;
use App\Models\SpuCatgorys;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class InStoreGoods extends BaseModel
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
                'salesTimeSwitch',
                'salesTimeData'
            ])
            ->with(['label', 'category', 'channel']);
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
            ->where('storeId', $this->storeId)
            ->where('type', 1);
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
        if (!$this->_skus === null) {
            if (count($this->storeSkus) == 1 && $this->spu->specSwitch == 0) {
                $this->_skus = collect($this->storeSkus)->map(function ($item, $key) {
                    $oldPrice = $item->sku->price;
                    $oldInventory = $item->sku->inventory;
                    $item->sku->price = $item->selfPriceSwitch == 1 ? $item->inStorePrice : $item->price;
                    $item->sku->inventory = $item->inventory ?? $oldInventory;
                    $item->sku->oldPrice = $oldPrice;
                    $item->sku->dayFilling = $item->dayFilling;
                    $item->sku->surplusInventory = $item->surplusInventory;
                    $item->sku->oldInventory = $oldInventory;
                    return $item->sku->toArray();
                })->all();
            }
        }
        return $this->_skus;
    }

    public function getGoodsAttribute()
    {
        if (empty($this->_goods)) {
            $goods = $this->spu->toArray();
            $sku = $this->skus ?? $this->spu->skus;
            $singleSpec = $this->singleSpec ?? $this->spu->singleSpec;
            $goods['singleSpec'] = $singleSpec;
            if ($sku) {
                $goods['maxPrice'] = collect($sku)->max('price');
                $goods['mixPrice'] = collect($sku)->min('price');
            }
            if ($singleSpec) {
                $goods['linePrice'] = $singleSpec['linePrice'];
                $goods['price'] = $singleSpec['price'];
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
            $modelSku['price'] = $model->selfPriceSwitch == 1 ? $model->inStorePrice : $model->price;
            $modelSku['dayFilling'] = $model->dayFilling;
            $modelSku['inventory'] = $model->inventory;
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
