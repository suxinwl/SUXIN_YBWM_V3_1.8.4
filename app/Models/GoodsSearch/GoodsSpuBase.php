<?php

namespace App\Models\GoodsSearch;

use App\Models\BaseModel;
use App\Models\Goods\Channel;
use App\Models\Goods\SpuAttrIds;
use App\Models\Goods\SpuMaterialIds;
use App\Models\Goods\SpuSpecIds;
use App\Models\GoodsCat;
use App\Models\GoodsRecommend\Goods;
use App\Models\Recipe\RecipeGoods;
use App\Models\Store\StoreGoods;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class GoodsSpuBase extends BaseModel
{
    protected $table = 'goods_spu';
    use HasFactory, SoftDeletes;
    protected $guarded = [];
}
