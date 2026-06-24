<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WechatAttachment extends BaseModel
{
    use HasFactory;
    protected $table = 'wechat_attachment';
    protected $guarded = [];
}
