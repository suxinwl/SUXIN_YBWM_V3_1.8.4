<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaterialCat extends BaseModel
{
    protected $table = 'material_cat';
    protected $fillable = ['sort','name','uniacid'];
    use HasFactory;

    public function materialList()
    {
        return $this->hasMany(Material::class, 'catId', 'id');
    }
}
