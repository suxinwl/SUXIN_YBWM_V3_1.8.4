<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MiniPrivacysetting extends BaseModel
{
    use HasFactory;
    protected $table = 'mini_privacysetting';
    protected $casts =  [
        'data' => 'array',
    ];
}
