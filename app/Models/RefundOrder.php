<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefundOrder extends Model
{
    use HasFactory;
    protected $table = 'refund_order';
    protected $fillable = ['tableMoney', 'deliveryMoney', 'takeOutNo', 'state', 'data', 'source', 'storId', 'userId', 'uniacid', 'why', 'notes', 'goodsMoney', 'goodsMoney'];
    protected $casts =  [
        'data' => 'array',
    ];

    public static function boot()
    {
        parent::boot();
        static::saving(function ($model) {
            // $this->money = array_sum([$this->goodsMoney, $this->boxMoney, $this->deliveryMoney, $this->tableMoney]);
        });
    }
}
