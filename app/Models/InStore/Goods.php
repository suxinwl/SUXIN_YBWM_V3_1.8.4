<?php

namespace App\Models\InStore;

use App\Models\BaseModel;
use App\Models\Goods\Channel;
use App\Models\Goods\SpuList;
use App\Models\GoodsSpu;
use App\Models\SpuCatgorys;
use App\Models\Store\StoreGoodsSku;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class Goods extends BaseModel
{
    protected $table = 'store_goods';
    protected $primaryKey = 'spuId';
    protected $guarded = [];
    public $_skus;
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


    public function storeGoodsSku()
    {
        return $this->hasMany(StoreGoodsSku::class, 'spuId', 'spuId')
            ->with(['sku'])
            ->withTrashed()
            ->where('recipeId', $this->recipeId)
            ->where('storeId', $this->storeId);
    }


    public function getSkusAttribute()
    {
        $type = $this->type;
        if (!$this->_skus && $this->spu->specSwitch == 1) {
            if ($this->_skus === null && $this->spu->specSwitch == 1) {
                $this->_skus = collect($this->storeGoodsSku)->map(function ($item, $key) {
                    $oldPrice = $item->sku->price;
                    $oldInventory = $item->sku->inventory;
                    $item->sku->price = $item->selfPriceSwitch == 1 ? $item->inStorePrice : $item->price;
                    $item->sku->inventory = $item->inventory ?? $oldInventory;
                    $item->sku->oldPrice = $oldPrice;
                    $item->sku->dayFilling = $item->dayFilling;
                    $item->sku->surplusInventory = $item->surplusInventory;
                    $item->sku->oldInventory = $oldInventory;
                    $item->sku->inStorePrice = $item->inStorePrice ?? 0;
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
            $goods['sales'] = Cache::get("storeGoods:{$this->storeId}:{$goods['id']}");
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
        if (!$this->_singleSpec && $this->spu->specSwitch == 0) {
            $model = collect($this->storeGoodsSku)->first();
            if ($model) {
                $modelSku = collect($model->sku)->toArray();
                $modelSku['surplusInventory'] = $model->surplusInventory;
                $modelSku['oldPrice'] = $model->sku->price;
                $modelSku['oldInventory'] = $model->sku->inventory;
                $modelSku['price'] = $model->selfPriceSwitch == 1 ? $model->inStorePrice : $model->price;
                $modelSku['dayFilling'] = $model->dayFilling;
                $modelSku['inventory'] = $model->inventory;
                $modelSku['inStorePrice'] = $model->inStorePrice;
            }
            $this->_singleSpec = empty($modelSku) ? null : $modelSku;
        }
        return $this->_singleSpec;
    }

    public function category()
    {
        return $this->hasMany(SpuCatgorys::class, 'spuId', 'spuId');
    }
}
