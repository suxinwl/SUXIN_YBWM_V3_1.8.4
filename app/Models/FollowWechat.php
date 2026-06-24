<?php
namespace App\Models;
use App\Models\Order\OrderGoods;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class FollowWechat extends BaseModel
{
    protected $table = 'follow_wechat';
    use HasFactory;
    protected $guarded = [];
}
