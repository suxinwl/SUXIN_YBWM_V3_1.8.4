<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attr extends BaseModel
{
    protected $table = 'attr';
    use HasFactory;
    protected $fillable = ['uniacid','sort', 'notes', 'name', 'multipleSwitch', 'mustSwitch','storeId'];
    protected $with = ['value'];

    public function value()
    {
        return $this->hasMany(AttrValue::class, 'attrId', 'id');
    }
}
