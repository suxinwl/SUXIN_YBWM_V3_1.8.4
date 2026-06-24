<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Spec extends BaseModel
{
    protected $table = 'spec';
    use HasFactory;
    protected $fillable = ['sort', 'name', 'imgSwitch', 'desc', 'uniacid', 'storeId'];
    protected $with = ['value'];
    public function value()
    {
        return $this->hasMany(SpecValue::class, 'specId', 'id');
    }
}
