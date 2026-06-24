<?php

namespace App\Models\TradeIn;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Store extends BaseModel
{
    use HasFactory;
    protected $table = 'tradein_stores';
    protected $fillable = [
        'uniacid', 'activityId', 'type', 'storeId',
    ];
}
