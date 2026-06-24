<?php

namespace App\Models\Goods;

use App\Models\Attr;
use App\Models\AttrValue;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpuAttrIds extends BaseModel
{
    protected $table = 'spu_attrids';
    use HasFactory;
    protected $primaryKey = 'attrId';
    protected $guarded = [];
    protected $hidden = ['attr', 'attrValue'];
    public $_value;
    public function attr()
    {
        return $this->hasOne(Attr::class, 'id', 'attrId');
    }
    public function attrValue()
    {
        return $this->belongsToMany(AttrValue::class, 'spu_attrvalueids', 'attrId', 'valueId')->withPivot(['checkId', 'spuId']);
    }

    public function getValueAttribute()
    {
        if (!$this->_value) {
            $arr = $this->attrValue()->wherePivot('spuId', $this->spuId)->get();
            $this->_value =  collect($arr)->map(function ($item, $key) {
                $data = collect($item)->toArray();
                $data['checkId'] = $data['pivot']['checkId'];
                unset($data['pivot'],$data['created_at'],$data['updated_at']);
                return $data;
            });
        }
        return $this->_value;
    }
}
