<?php

namespace App\Models\Recipe;

use App\Models\BaseModel;
use App\Models\GoodsCat;
use App\Models\GoodsSpu;
use App\Models\SpuCatgorys;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class RecipeCategory extends BaseModel
{
    protected $table = 'goods_cat';
    use HasFactory;

    public function goodsCat()
    {
        return $this->belongsToMany(RecipeGoods::class, 'spu_catids', 'catId', 'spuId');
    }
}
