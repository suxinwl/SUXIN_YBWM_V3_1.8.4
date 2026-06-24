<?php

namespace App\Models\Goods;

use App\Models\Store\StoreGoodsBase;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SetmealGoodsIds extends Model
{
    protected $table = 'setmeal_goods_ids';
    use HasFactory;
    protected $fillable = [
        'uniacid', 'spuId', 'goodsId', 'sort', 'isDefault', 'name', 'price', 'logo', 'setmealGoodsId', 'specMd5', 'num', 'addPrice'
    ];
    protected $appends = [
        'attrData', 'materialData', 'isSpec'
    ];

    protected $hidden = [
        'attrs', 'materials'
    ];
    public function attrs()
    {
        return $this->hasMany(SpuAttrIds::class, 'spuId', 'spuId');
    }

    public function materials()
    {
        return $this->hasMany(SpuMaterialIds::class, 'spuId', 'spuId');
    }

    public function getAttrDataAttribute()
    {
        return collect($this->attrs)->map(function ($item, $key) {
            $data = collect($item->attr)->toArray();
            $data['state'] = $item->state;
            $data['value'] = $item->value;
            $data['checkList'] = collect($data['value'])->pluck('id')->all();
            return $data;
        });
    }

    public function getMaterialDataAttribute()
    {
        return collect($this->materials)->map(function ($item, $key) {
            $data = collect($item->materialCat)->toArray();
            $data['materialList'] = $item->materialList;
            $data['required'] = $item->required;
            $data['maxNum'] = $item->maxNum;
            $data['astrict'] = $item->astrict;
            $data['checkList'] = collect($data['materialList'])->pluck('id')->all();
            return $data;
        });
    }


    public function getIsSpecAttribute()
    {
        return !empty(collect($this->attrs)->toArray()) || !empty(collect($this->materials)->toArray());
    }

    public function storeSku()
    {
        return $this->hasOne(StoreGoodsBase::class, 'specMd5', 'specMd5');
    }
}
