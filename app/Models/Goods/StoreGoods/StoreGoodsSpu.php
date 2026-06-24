<?php

namespace App\Models\Goods;

use App\Models\BaseModel;
use App\Models\GoodsRecommend\Goods;
use App\Models\GoodsSpu;
use App\Models\Recipe\RecipeGoods;
use App\Models\Store\StoreGoods;
use App\Models\Store\StoreGoodsSku;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Channel extends GoodsSpu
{

    public function skus()
    {
        return $this->hasMany(StoreGoodsSku::class, 'spuId', 'id')->where('type', 2);
    }

    public function singleSpec()
    {
        return $this->hasOne(StoreGoodsSku::class, 'spuId', 'id')->where('type', 1);
    }
}
