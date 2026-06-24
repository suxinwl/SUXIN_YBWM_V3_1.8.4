<?php

namespace App\Models\Goods;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SetmealGoods extends Model
{
    protected $table = 'setmeal_goods';
    use HasFactory;
    protected $fillable = [
        'name', 'type', 'uniacid', 'spuId', 'sort', 'select'
    ];
    protected $with = [
        'goods'
    ];

    public function goods()
    {
        return $this->hasMany(SetmealGoodsIds::class, 'setmealGoodsId', 'id');
    }
}
