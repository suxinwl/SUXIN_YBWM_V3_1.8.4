<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoodsUnit extends BaseModel
{

    protected $table = 'goods_unit';
    use HasFactory;
    protected $fillable = ['name','sort','storeId'];
}
