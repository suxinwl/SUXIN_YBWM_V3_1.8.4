<?php

namespace App\Models\GoodsRecommend;

use App\Models\BaseModel;
use App\Models\Store as ModelsStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Store extends BaseModel
{
    use HasFactory;
    protected $table = 'goods_recommend_store';
    protected $guarded = [];

    public function store(){
        return $this->hasOne(ModelsStore::class,'id','storeId');
    }
}
