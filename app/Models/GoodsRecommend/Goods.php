<?php

namespace App\Models\GoodsRecommend;

use App\Models\BaseModel;
use App\Models\GoodsSpu;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Goods extends BaseModel
{
    use HasFactory;
    protected $table = 'goods_recommend_goods';

    protected $guarded = [];
    public function spu(){
        return $this->hasOne(GoodsSpu::class,'id','spuId');
    }
}
