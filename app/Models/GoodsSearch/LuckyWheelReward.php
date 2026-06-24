<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LuckyWheelReward extends BaseModel
{
    use HasFactory;

    protected $table = 'lucky_wheel_rewards';
    protected $fillable = [
        'uniacid',
        'type',
        'couponId',
        'name',
        'pic',
        'count',
        'stock',
        'probability'
    ];
    protected $appends = ['calProbability'];
    protected $hidden = [
        'created_at',
        'updated_at',
    ];
    // 构造函数
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }
    public function getCalProbabilityAttribute()
    {
        $totalProbability = self::where('uniacid',$this->uniacid)->sum('probability');

        if ($totalProbability > 0) {
            $percentage = ($this->probability / $totalProbability) * 100;
            return number_format($percentage, 2);
        }
        return number_format(0, 2);
    }
}
