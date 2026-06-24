<?php

namespace App\Models\Goods;

use App\Models\BaseModel;
use App\Models\Spec;
use App\Models\SpecValue;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpuSpecIds extends BaseModel
{
    protected $table = 'spu_specids';
    use HasFactory;
    protected $primaryKey = 'specId';
    protected $guarded = [];
    protected $_value;
    protected $hidden = [
        'spec','specData'
    ];
    public function spec()
    {
        return $this->hasOne(Spec::class, 'id', 'specId');
    }

    public function specData()
    {
        return $this->belongsToMany(SpecValue::class, 'spu_specvalueids', 'specId', 'valueId')->withPivot(['spuId', 'checkId']);
    }

    public function getValueAttribute()
    {
        if (!$this->_value) {
            $data = $this->specData()->wherePivot('spuId', $this->spuId)->get();
            $this->_value = collect($data)->map(function ($item, $key) {
                $data = collect($item)->toArray();
                $data['checkId'] = $data['pivot']['checkId'];
                unset($data['pivot'],$data['created_at'],$data['updated_at']);
                return $data;
            });
        }
        return $this->_value;
    }
}
