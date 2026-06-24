<?php

namespace App\Models\GoodsActivity;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    use HasFactory;
    protected $table = 'goods_activity_stores';
    protected $fillable = [
        'uniacid', 'activityId', 'type', 'storeId',
    ];
}
