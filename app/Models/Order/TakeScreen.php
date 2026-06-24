<?php

namespace App\Models\Order;

use App\Models\BaseModel;
use App\Models\InStore\Order\Order;
use App\Models\Store\StoreBase;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TakeScreen extends BaseModel
{
    use HasFactory;
    public $_order;
    protected $table = 'take_screen';
    protected $fillable = ['storeId', 'uniacid', 'orderSn', 'state', 'pickNo', 'orderTime', 'packaging', 'diningType', 'source'];
    protected $appends = [
        'packagingFormat', 'orderTime', 'minutes'
    ];
    public function getPackagingFormatAttribute()
    {
        return $this->packaging == 0 ? "堂食" : "打包";
    }

    public function getOrderTimeAttribute()
    {
        return date("m-d H:i:s", strtotime($this->attributes['orderTime']));
    }

    public function getMinutesAttribute()
    {
        return intval((time() - strtotime($this->attributes['orderTime'])) / 60);
    }

    public function scopeCount($q, $storeId = null)
    {
        return $q->select(DB::raw("
        IFNULL(sum(if((state = 3 or state = 4),1,0)),0) as allCount,
        IFNULL(sum(if((state = 3),1,0)),0) as makingCount,
        IFNULL(sum(if((state = 4),1,0)),0) as makedCount,
        IFNULL(sum(if((state = 6),1,0)),0) as completeCount,
        "));
    }

    public function store()
    {
        return $this->hasOne(StoreBase::class, 'id', 'storeId');
    }
}
