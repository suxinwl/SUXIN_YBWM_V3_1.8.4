<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Material extends BaseModel
{
    use HasFactory;
    protected $table = 'material';
    protected $fillable = ['name','catId','sort', 'state', 'sn', 'image', 'uniacid', 'price', 'inventory', 'autoReplenish'];
    protected $attributes = [
        'sort' => 0,
        'sn' => '',
        'state' => 1,
        'image' => '',
        'price' => 0,
        'inventory' => 1,
        'autoReplenish' => 0
    ];

    protected $with =[
        'category'
    ];

    public function category (){
        return $this->hasOne(MaterialCat::class,'id','catId');
    }
}
