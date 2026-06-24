<?php

namespace App\Models\Goods;

use App\Models\BaseModel;
use App\Models\GoodsRecommend\Goods;
use App\Models\GoodsSpu;
use App\Models\Recipe\RecipeGoods;
use App\Models\Store\StoreGoods;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class SpuList extends GoodsSpu
{
    protected $table = 'goods_spu';
    use HasFactory, SoftDeletes;
    protected $with = [
    ];
}
