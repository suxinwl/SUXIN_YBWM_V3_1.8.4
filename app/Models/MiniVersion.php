<?php

namespace App\Models;

use App\Services\OpenWechat\ChannelOpenWechat;
use Illuminate\Broadcasting\Channel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MiniVersion extends BaseModel
{
    use HasFactory;
    protected $table = 'mini_version';
}
