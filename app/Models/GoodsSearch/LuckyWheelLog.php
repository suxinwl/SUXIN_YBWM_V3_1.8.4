<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LuckyWheelLog extends BaseModel
{
    use HasFactory;
    // 指定表名，如果表名和模型名的复数形式不匹配
    protected $table = 'lucky_wheel_log';

    protected $with = [
       'user'
    ];
    public function user()
    {
        return $this->hasOne(Member::class, 'id', 'userId')->select(['id', 'nickname', 'avatar', 'mobile','realname']);
    }
    // 允许批量赋值的字段
    protected $fillable = [
        'userId',
        'rewardId',
        'uniacid',
        'count',
        'reward_name',
        'rewardPic',
        'state',
    ];



}
