<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpenWecahtExtJson extends BaseModel
{
    use HasFactory;
    protected $table = 'open_wechat_version_extjson';
    protected $guarded = [];
    protected $casts =  [
        'extJson' => 'array',
    ];
}
