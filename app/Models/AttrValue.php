<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttrValue extends Model
{
    protected $table = 'attr_value';
    use HasFactory;
    protected $guarded = [];
    protected $casts =  [
        'value' => 'array',
    ];
}
