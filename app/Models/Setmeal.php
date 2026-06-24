<?php

namespace App\Models;

use App\Models\Admin\Apply;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Setmeal extends BaseModel
{
    use HasFactory, SoftDeletes;
    protected $table = 'setmeal';
    protected $casts =  [
        'money' => 'array',
        'package' => 'array',
        'prolong' => 'array',
        'style' => 'array'
    ];
    protected $attributes = [
        "desc" => "",
        "marketingTag" => "",
        "day" => 0
    ];
    protected $fillable = ['state','sort','title','subtitle','desc','marketingTagSwitch','marketingTag','styleSwitch','style','prolongSwitch','prolong','soldOutSwitch','package','money','type','day','smsNum','storeNum'];

    public function apply()
    {
        return $this->hasMany(Apply::class, 'musterId', 'id');
    }
}
