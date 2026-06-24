<?php

namespace App\Models\Goods;

use App\Models\Material;
use App\Models\MaterialCat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpuMaterialIds extends Model
{
    protected $table = 'spu_materialids';
    use HasFactory;
    protected $primaryKey = 'materialId';
    protected $guarded = [];
    protected $hidden = ['materialCat', 'attrValue','pivot'];
    public $_value;
    public function materialCat()
    {
        return $this->hasOne(MaterialCat::class, 'id', 'materialId');
    }
    public function attrValue()
    {
        return $this->belongsToMany(Material::class, 'spu_materialvalueids', 'materialId', 'valueId')->withPivot(['checkId', 'spuId']);
    }

    public function getMaterialListAttribute()
    {
        if (!$this->_value) {
            $arr = $this->attrValue()->wherePivot('spuId', $this->spuId)->get();
            $this->_value = collect($arr)->map(function ($item, $key) {
                $data = collect($item)->toArray();
                $data['checkId'] = $data['pivot']['checkId'];
                unset($data['pivot'],$data['created_at'],$data['updated_at']);
                return $data;
            });
        }
        return $this->_value;
    }
}
