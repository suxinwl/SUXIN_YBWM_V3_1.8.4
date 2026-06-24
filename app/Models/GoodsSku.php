<?php

namespace App\Models;

use App\Models\GoodsSearch\GoodsSpuBase;
use App\Models\Store\StoreGoodsSku;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GoodsSku extends BaseModel
{
    protected $table = 'goods_sku';
    use HasFactory, SoftDeletes;
    protected $fillable = ['boxMoney', 'uniacid', 'specMd5', 'type', 'spuId', 'price', 'linePrice', 'costPrice', 'inventory', 'component', 'dayFilling', 'barcode', 'sn'];
    protected $with = [
        'specName'
    ];
    protected $attributes = [
        'boxMoney' => 0,
        'barcode' => '',
        'sn' => '',
    ];

    public function specName()
    {
        return $this->belongsToMany(SpecValue::class, 'sku_specvalueids', 'skuId', 'valueId');
    }

    public function storeSku()
    {
        return $this->hasOne(StoreGoodsSku::class, 'specMd5', 'specMd5');
    }

    public function spu()
    {
        return $this->hasOne(GoodsSpuBase::class, 'id', 'spuId');
    }

    public function category()
    {
        return $this->belongsToMany(GoodsCat::class, 'spu_catids', 'spuId', 'catId', 'spuId')->orderBy('sort', 'asc')->orderBy('id', 'desc');
    }
}
