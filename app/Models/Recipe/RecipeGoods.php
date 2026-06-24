<?php

namespace App\Models\Recipe;

use App\Models\BaseModel;
use App\Models\Goods\Channel;
use App\Models\GoodsCat;
use App\Models\GoodsSku;
use App\Models\GoodsSpu;
use App\Models\SpuCatgorys;
use App\Models\Store\StoreGoods;
use App\Models\Store\StoreGoodsSku;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class RecipeGoods extends BaseModel
{
    protected $primaryKey = 'spuId';
    protected $table = 'recipe_goods';
    protected $fillable = ['uniacid', 'selfPriceSwitch', 'selfInventorySwitch', 'recipeId', 'spuId', 'specMd5', 'inventory', 'price', 'state', 'type'];
    public $_skus;
    public $_singleSpec;
    public $_goods;
    use HasFactory, SoftDeletes;
    protected $hidden = [
        'spu'
    ];
    protected $appends = [
        'goods'
    ];
    public function spu()
    {
        return $this->hasOne(GoodsSpu::class, 'id', 'spuId')
            ->select(['id', 'name', 'logo', 'specSwitch', 'isShow', 'sort', 'channelIds']);
    }

    public function sku()
    {
        return $this->hasMany(RecipeGoodsSku::class, 'spuId', 'spuId')->where("recipeId", $this->recipeId)->withTrashed();
    }


    public function category()
    {
        return $this->hasMany(SpuCatgorys::class, 'spuId', 'spuId');
    }

    public function recipe()
    {
        return $this->hasOne(Recipe::class, 'id', 'recipeId');
    }

    public function goodsSku()
    {
        return $this->hasMany(RecipeGoodsSku::class, 'spuId', 'spuId')->where("recipeId", $this->recipeId)->where('type', 2)->withTrashed();
    }

    public function goodsSingleSpec()
    {
        return $this->hasOne(RecipeGoodsSku::class, 'spuId', 'spuId')->where("recipeId", $this->recipeId)->where('type', 1)->withTrashed();
    }

    public function getSkusAttribute()
    {
        if (!$this->_skus) {
            if ($this->spu && $this->spu->specSwitch == 1) {
                $skus = collect($this->goodsSku)->map(function ($item, $key) {
                    if($item->sku) {
                        $oldPrice = $item->sku->price;
                        $oldInventory = $item->sku->inventory;
                        $item->sku->price = $item->price ?? $oldPrice;
                        $item->sku->inventory = $item->inventory ?? $oldInventory;
                        $item->sku->inStorePrice = $item->inStorePrice ?? 0;
                        $item->sku->oldPrice = $oldPrice;
                        $item->sku->oldInventory = $oldInventory;
                        return $item->sku->toArray();
                    }else{
                        $a=GoodsSku::where('spuId',$item->spuId)->get();
                        $specMd5=[];
                        foreach ($a as $v){
                            $specMd5[]=$v->specMd5;
                        }
                        RecipeGoodsSku::where('recipeId',$item->recipeId)->where('spuId',$item->spuId)->whereNotIn('specMd5',$specMd5)->forceDelete();
                    }
                });
                $this->_skus = collect($skus)->all();
            }
        }
        return $this->_skus;
    }

    public function getGoodsAttribute()
    {
        if (!$this->_goods) {
            $goods=empty($this->spu)?array():$this->spu->toArray();
            $goods['skus'] = $this->skus;
            if ($goods['skus']) {
                $goods['maxPrice'] = collect($goods['skus'])->max('price');
                $goods['mixPrice'] = collect($goods['skus'])->min('price');
                $goods['maxOldPrice'] = collect($goods['skus'])->max('oldPrice');
                $goods['mixOldPrice'] = collect($goods['skus'])->min('oldPrice');
                $goods['maxInStorePrice'] = collect($goods['skus'])->max('inStorePrice');
                $goods['minInStorePrice'] = collect($goods['skus'])->min('inStorePrice');
            }
            $goods['singleSpec'] = $this->singleSpec;
            $this->_goods = $goods;
        }
        return $this->_goods;
    }

    public function getSingleSpecAttribute()
    {
        $type = $this->type;
        if (!$this->_singleSpec) {
            if ($this->spu && $this->spu->specSwitch == 0) { {
                $model = $this->goodsSingleSpec;
                if ($model) {
                    $modelSku = collect($model->sku)->toArray();
                    $modelSku['oldPrice'] = $model->sku->price;
                    $modelSku['oldInventory'] = $model->sku->inventory;
                    $modelSku['price'] = $model->price;
                    $modelSku['inventory'] = $model->inventory;
                    $modelSku['inStorePrice'] = $model->inStorePrice;
                }
                $this->_singleSpec = empty($modelSku) ? null : $modelSku;
            }
            }
        }

        return  $this->_singleSpec;
    }

    public function channel()
    {
        return $this->hasMany(Channel::class, 'spuId', 'spuId');
    }

    public static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            try {
                $list = GoodsSku::where('spuId', $model->spuId)->get();
                $stores = RecipeStore::where('uniacid', $model->uniacid)->where('recipeId', $model->recipeId)->groupBy('storeId')->get();
                RecipeStore::where('uniacid', $model->uniacid)->where('recipeId', $model->recipeId)->get();
                StoreGoods::withTrashed()
                    ->where('uniacid', $model->uniacid)
                    ->where('recipeId', $model->recipeId)
                    ->where('spuId', $model->spuId)
                    ->forceDelete();
                StoreGoodsSku::withTrashed()
                    ->where('uniacid', $model->uniacid)
                    ->where('recipeId', $model->recipeId)
                    ->where('spuId', $model->spuId)
                    ->forceDelete();
                foreach ($list as $key => $skus) {
                    $sku[$key] = [
                        'uniacid' => $model->uniacid,
                        'type' => $skus->type,
                        'spuId' => $model->spuId,
                        'recipeId' => $model->recipeId,
                        'specMd5' => $skus->specMd5,
                        'inventory' => $skus->inventory,
                        'inStoreInventory' => $skus->inventory,
                        'price' => $skus->price,
                        'inStorePrice' => $skus->price,
                        'state' => 1
                    ];
                    foreach ($stores as $key2 => $store) {
                        $storeGoods[$store->storeId] = [
                            'uniacid' => $model->uniacid,
                            'type' => 1,
                            'recipeId' => $model->recipeId,
                            'storeId' => $store->storeId,
                            'spuId' => $model->spuId,
                            'state' => 1,
                            'sort' => 0,
                            'deleted_at' => $model->deleted_at
                        ];

                        $storeSkuList[] = [
                            'uniacid' => $model->uniacid,
                            'type' => $skus->type,
                            'recipeId' => $model->recipeId,
                            'storeId' => $store->storeId,
                            'specMd5' => $skus->specMd5,
                            'inventory' => $skus->inventory,
                            'surplusInventory' => $skus->inventory,
                            'state' => $skus->state,
                            'spuId' => $skus->spuId,
                            'price' => $skus->price,
                            'inStoreInventory' => $skus->inventory,
                            'inStorePrice' => $skus->price,
                            'sort' => 0,
                            'deleted_at' => $skus->deleted_at
                        ];
                    }
                }


                if (!empty($sku)) {
                    RecipeGoodsSku::insert($sku);
                }
                if (!empty($storeGoods)) {
                    StoreGoods::insert($storeGoods);
                }
                if (!empty($storeSkuList)) {
                    StoreGoodsSku::insert($storeSkuList);
                }
            } catch (\Exception $e) {
                throw new BadRequestException($e->getMessage());
            }
        });

        static::forceDeleted(function ($model) {
            try {
                StoreGoods::withTrashed()
                    ->where('spuId', $model->spuId)
                    ->where('recipeId', $model->recipeId)
                    ->forceDelete();
                StoreGoodsSku::withTrashed()
                    ->where('spuId', $model->spuId)
                    ->where('recipeId', $model->recipeId)
                    ->forceDelete();
                RecipeGoodsSku::where('recipeId', $model->recipeId)
                    ->withTrashed()
                    ->where('spuId', $model->spuId)
                    ->forceDelete();
            } catch (\Exception $e) {
                throw new BadRequestException($e->getMessage());
            }
        });
    }
}
