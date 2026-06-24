<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoodsCatLabel extends BaseModel
{
    protected $table = 'goods_cat_label';
    use HasFactory;
    protected $fillable = ['name', 'sort', 'bgColor', 'textColor', 'uniacid', 'storeId'];
    public function store(){
        return $this->hasOne(StoreBase::class,'id','storeId');
    }
    
}
