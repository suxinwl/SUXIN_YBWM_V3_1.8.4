<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoodsLabel extends BaseModel
{
    protected $table = 'goods_label';
    use HasFactory;
    protected $fillable = ['name', 'sort','bgColor','textColor','notes','uniacid','storeId'];
}
