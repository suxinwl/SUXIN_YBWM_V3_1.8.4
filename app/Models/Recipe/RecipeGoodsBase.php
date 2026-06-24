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

class RecipeGoodsBase extends BaseModel
{
    protected $primaryKey = 'id';
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
        return $this->hasOne(GoodsSpu::class, 'id', 'spuId')->select(['id', 'name', 'logo', 'specSwitch', 'isShow', 'sort', 'channelIds']);
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
        return $this->hasMany(RecipeGoodsSku::class, 'spuId', 'spuId')->where("recipeId", $this->recipeId)->where('type', $this->type)->withTrashed();
    }

    public function goodsSingleSpec()
    {
        return $this->hasOne(RecipeGoodsSku::class, 'spuId', 'spuId')->where("recipeId", $this->recipeId)->where('type', $this->type)->withTrashed();
    }

    public function getSkusAttribute()
    {
        if (!$this->_skus) {
            $type = $this->type;
            $skus =  RecipeGoodsSku::with(['sku'])->where('spuId', $this->spuId)
                ->where('recipeId', $this->recipeId)
                ->where('type', $this->type)->get();
            if ($this->spu && $this->spu->specSwitch == 1) {
                $skus = collect($skus)->map(function ($item, $key) {
                    $oldPrice = $item->sku->price;
                    $oldInventory = $item->sku->inventory;
                    $item->sku->price = $item->price ?? $oldPrice;
                    $item->sku->inventory = $item->inventory ?? $oldInventory;
                    $item->sku->inStorePrice = $item->inStorePrice ?? 0;
                    $item->sku->oldPrice = $oldPrice;
                    $item->sku->oldInventory = $oldInventory;
                    return $item->sku->toArray();
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
            $model =  RecipeGoodsSku::with(['sku'])->where('spuId', $this->spuId)
                ->where('recipeId', $this->recipeId)
                ->where('type', $this->type)
                ->get();
            if ($this->spu && $this->spu->specSwitch == 0) { {
                    $model = collect($model)->first();
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
                $stores = RecipeStore::where('uniacid', $model->uniacid)->where('recipeId', $model->recipeId)->get();
                foreach ($stores as $key2 => $store) {
                    $storeGoods[$store->storeId] = [
                        'uniacid' => $model->uniacid,
                        'type' => $model->type,
                        'recipeId' => $model->recipeId,
                        'storeId' => $store->storeId,
                        'spuId' => $model->spuId,
                        'state' => 1,
                        'sort' => 0,
                        'deleted_at' => $model->deleted_at
                    ];
                    foreach ($list as $key => $skus) {
                        $sku[$key] = [
                            'uniacid' => $model->uniacid,
                            'type' => $model->type,
                            'spuId' => $model->spuId,
                            'recipeId' => $model->recipeId,
                            'specMd5' => $skus->specMd5,
                            'inventory' => $skus->inventory,
                            'inStoreInventory' => $skus->inventory,
                            'price' => $skus->price,
                            'inStorePrice' => $skus->price,
                            'state' => 1
                        ];
                        $storeSkuList[] = [
                            'uniacid' => $model->uniacid,
                            'type' => $model->type,
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
                            'state' => 1,
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
                    ->where('type', $model->type)
                    ->where('spuId', $model->spuId)
                    ->where('recipeId', $model->recipeId)
                    ->forceDelete();
                StoreGoodsSku::withTrashed()
                    ->where('type', $model->type)
                    ->where('spuId', $model->spuId)
                    ->where('recipeId', $model->recipeId)
                    ->forceDelete();
                $list = RecipeGoodsSku::where('recipeId', $model->recipeId)
                    ->withTrashed()
                    ->where('spuId', $model->spuId)
                    ->where("type", $model->type)
                    ->forceDelete();
            } catch (\Exception $e) {
                throw new BadRequestException($e->getMessage());
            }
        });
    }
}
