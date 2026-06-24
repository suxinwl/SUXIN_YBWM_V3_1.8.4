<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LuckyWheel extends BaseModel
{
    use HasFactory;
    protected $table = 'lucky_wheel';
    protected $guarded = [];
    protected $casts = [
        'storeIds' => 'array',
        'acquireMethods' => 'array',
    ];


    protected $hidden = [
        'created_at',
        'updated_at',
    ];

}
