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

class Channel extends BaseModel
{
    protected $table = 'goods_channel';
    use HasFactory;
    protected $guarded = [];
}
