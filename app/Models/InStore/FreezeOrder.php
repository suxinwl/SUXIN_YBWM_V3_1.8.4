<?php

namespace App\Models\InStore;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FreezeOrder extends BaseModel
{
    protected $table = 'freeze_order';
    use HasFactory;
    protected $fillable = [
        'uniacid', "storeId", 'userId', 'checkout', 'goods'
    ];
    protected $casts = [
        'checkout' => 'array',
        'goods' => 'array'
    ];
}
