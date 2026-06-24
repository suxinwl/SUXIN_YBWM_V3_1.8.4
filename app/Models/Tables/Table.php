<?php

namespace App\Models\Tables;

use App\Models\BaseModel;
use App\Models\InStore\Order\Order;
use App\Models\ShortLink;
use App\Models\Store;
use App\Services\ShortLinkService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class Table extends BaseModel
{
    use HasFactory;
    protected $table = 'table';
    protected $fillable = [
        'uniacid', 'storeId', 'name', 'areaId', 'typeId', 'zjm', 'orderSn', 'expiredTime', 'people', 'state', 'scan'
    ];
    protected $appends = [
        'stateFormat', 'minutes'
    ];

    public function store()
    {
        return $this->hasOne(Store::class, 'id', 'storeId');
    }



    public function getMinutesAttribute()
    {
        if (!empty($this->openTime)) {
            return intval((time() - strtotime($this->openTime)) / 60);
        }
        return null;
    }

    public function getServerConfigAttribute()
    {
        $tableId = $this->id;
        return Servers::whereHas('tables', function ($q) use ($tableId) {
            return $q->where('tableId', $tableId);
        })->first();
    }

    public function getOrderAttribute()
    {
        if (!empty($this->orderSn)) {
            $order = DB::table('instore_order')->where('orderSn', $this->orderSn)->first();
            return $order;
        }
        return null;
    }

    public function getStateFormatAttribute()
    {
        $data = [
            0 => "空闲",
            1 => '待下单',
            2 => "待结账",
            3 => "待清台",
            4 => "已预结"
        ];
        return $data[$this->state];
    }



    public function shortLink()
    {
        return $this->hasOne(ShortLink::class, 'ident', 'id')->where('type', 'table');
    }

    public function type()
    {
        return $this->hasOne(Type::class, 'id', 'typeId');
    }

    public function area()
    {
        return $this->hasOne(Area::class, 'id', 'areaId');
    }

    public function getDiningTypeAttribute()
    {
        if (in_array(1, $this->store->inStoreSetting['orderMode'])) {
            return 4;
        }
        if (in_array(2, $this->store->inStoreSetting['orderMode'])) {
            return 5;
        }
        throw new BadRequestException('未开启扫码点餐功能,请联系服务员');
    }

    public static function boot()
    {
        parent::boot();
        static::saved(function ($model) {
            if (empty($model->shortLink)) {
                ShortLinkService::createTableLink($model);
            }
        });
    }
    public function scopeCount($q)
    {
        return $q->select(DB::raw("IFNULL(sum(if(id > 0 ,1,0)),0) as allCount,IFNULL(sum(if(state = 0 ,1,0)),0) as freeCount,IFNULL(sum(if(state = 1,1,0)),0) as orderCount,IFNULL(sum(if(state = 2,1,0)),0) as settleCount,IFNULL(sum(if(state = 3,1,0)),0) as machineCount,IFNULL(sum(if(state = 4,1,0)),0) as prepareCount"));
    }
}
