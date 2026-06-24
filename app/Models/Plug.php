<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plug extends BaseModel
{
    protected $table = 'plug';
    protected $guarded = [];
    protected $casts =  [
        'payData' => 'array',
    ];
    protected $appends = [
        'status', 'display'
    ];
    use HasFactory;
    use SoftDeletes;

    public function applyPlug()
    {
        return $this->hasOne(ApplyPlugs::class, 'plugId', 'id');
    }


    public function getStatusAttribute()
    {
        return  !empty($this->applyPlug) ?  $this->applyPlug->state : 0;
    }

    public function getDisplayAttribute()
    {
        return  !empty($this->applyPlug) ? $this->applyPlug->display : 0;
    }
}
