<?php

namespace App\Models\Goods;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpuAttrValueIds extends Model
{
    protected $table = 'spu_attrvalueids';
    use HasFactory;
    protected $with = [];
    protected $guarded = [];
}
