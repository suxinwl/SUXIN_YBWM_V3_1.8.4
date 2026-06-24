<?php

namespace App\Models\Order;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayLog extends BaseModel
{
    use HasFactory;
    protected $table = 'pay_log';
    protected $fillable = ['uniacid', 'orderSn', 'storeId',"paySn"];
}
