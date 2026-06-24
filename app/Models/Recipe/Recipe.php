<?php

namespace App\Models\Recipe;

use App\Models\BaseModel;
use App\Models\GoodsSpu;
use App\Models\SpuCatgorys;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Recipe extends BaseModel
{
    protected $table = 'recipe';
    protected $fillable = ['name', 'desc','uniacid'];
    use HasFactory;


    public function goods()
    {
        return $this->hasMany(RecipeGoods::class, 'recipeId', 'id');
    }


    public function goodsSku()
    {
        return $this->hasMany(RecipeGoodsSku::class, 'recipeId', 'id');
    }

    public function takeoutGoods()
    {
        return $this->hasMany(RecipeGoods::class, 'recipeId', 'id')->withTrashed()->where("type", 1);
    }

    public function store()
    {
        return $this->hasMany(RecipeStore::class, 'recipeId', 'id');
    }

    public function takeoutCats()
    {
        return $this->belongsToMany(SpuCatgorys::class, 'recipe_goods', 'recipeId', 'spuId')->wherePivot('type', 1);
    }

    public function inStoreCats()
    {
        return $this->belongsToMany(SpuCatgorys::class, 'recipe_goods', 'recipeId', 'spuId')->wherePivot('type', 2);
    }
}
