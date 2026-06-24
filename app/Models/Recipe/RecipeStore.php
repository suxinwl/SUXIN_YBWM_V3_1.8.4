<?php

namespace App\Models\Recipe;

use App\Models\BaseModel;
use App\Models\Store;
use App\Models\Store\StoreGoods;
use App\Models\Store\StoreGoodsSku;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class RecipeStore extends BaseModel
{
    protected $table = 'recipe_store';
    use HasFactory;
    protected $with = [
        'store'
    ];
    protected $guarded = [];

    public function store()
    {
        return $this->hasOne(Store::class, 'id', 'storeId');
    }
    public function recipe()
    {
        return $this->hasOne(Recipe::class, 'id', 'recipeId');
    }

    public function goods()
    {
        return $this->hasMany(StoreGoods::class, 'storeId', 'storeId')->where('recipeId', $this->recipeId);
    }
    public function goodsSkus()
    {
        return $this->hasMany(StoreGoodsSku::class, 'storeId', 'storeId')->where('recipeId', $this->recipeId);
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            try {
                $spus = RecipeGoods::withTrashed()->where('recipeId', $model->recipeId)->get();
                $spuList = [];
                $skuList = [];
                foreach ($spus as $key => $spu) {
                    $spuList[] = new StoreGoods([
                        'uniacid' => $model->uniacid,
                        'type' => $spu->type,
                        'recipeId' => $model->recipeId,
                        'storeId' => $model->storeId,
                        'spuId' => $spu->spuId,
                        'state' => 1,
                        'sort' => 0,
                        'deleted_at' => $spu->deleted_at
                    ]);
                }
                $model->goods()->saveMany($spuList);
                $skus = RecipeGoodsSku::withTrashed()->where('recipeId', $model->recipeId)->get();
                foreach ($skus as $key => $sku) {
                    $skuList[] = new StoreGoodsSku([
                        'uniacid' => $model->uniacid,
                        'type' => $sku->type,
                        'recipeId' => $model->recipeId,
                        'storeId' => $model->storeId,
                        'specMd5' => $sku->specMd5,
                        'inventory' => $sku->inventory,
                        'surplusInventory' => $sku->inventory,
                        'state' => $sku->state,
                        'spuId' => $sku->spuId,
                        'price' => $sku->price,
                        'state' => 1,
                        'sort' => 0,
                        'deleted_at' => $spu->deleted_at
                    ]);
                }
                $model->goodsSkus()->saveMany($skuList);
            } catch (\Exception $e) {
                throw new BadRequestException($e->getMessage());
            }
        });

        static::deleting(function ($model) {
            StoreGoods::withTrashed()->where('storeId', $model->storeId)
                ->where('recipeId', $model->recipeId)
                ->forceDelete();
            StoreGoodsSku::withTrashed()->where('storeId', $model->storeId)
                ->where('recipeId', $model->recipeId)
                ->forceDelete();
        });
    }
}
