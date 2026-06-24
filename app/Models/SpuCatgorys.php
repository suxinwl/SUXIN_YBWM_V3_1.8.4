<?php

namespace App\Models;

use App\Models\Order\OrderGoods;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpuCatgorys extends BaseModel
{

    protected $primaryKey = 'spuId';
    protected $table = 'spu_catids';
    use HasFactory;
    protected $guarded = [];
}
