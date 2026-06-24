<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Menu extends BaseModel
{
    use SoftDeletes;
    protected $casts =  [
        'roleLevel' => 'array',
    ];
    public function getMetaAttribute($value){
        return json_decode($value);
    }
}
