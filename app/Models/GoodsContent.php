<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoodsContent extends BaseModel
{
    protected $table = 'goods_content';
    use HasFactory;
    protected $fillable = ['uniacid','content','spuId'];
    protected $attribute = [
        'content' => '',
    ];
}
