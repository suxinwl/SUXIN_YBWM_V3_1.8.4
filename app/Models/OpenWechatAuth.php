<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpenWechatAuth extends BaseModel
{
    use HasFactory;
    protected $table = 'open_wechat_token';
    protected $casts =  [
        'data' => 'array',
        'func_info' => 'array',
        'miniData' => 'array'
    ];
}
