<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OnlineMessage extends Model
{
    use HasFactory;
    protected $table = 'news';
    protected $fillable = ['uniacid', 'storeId', 'channel', 'scene', 'orderSn', 'title', 'body'];
}
