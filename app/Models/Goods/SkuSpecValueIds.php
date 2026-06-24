<?php

namespace App\Models\Goods;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SkuSpecValueIds extends Model
{
    protected $table = 'sku_specvalueids';
    use HasFactory;
    protected $with = [];
    protected $guarded = [];
}
