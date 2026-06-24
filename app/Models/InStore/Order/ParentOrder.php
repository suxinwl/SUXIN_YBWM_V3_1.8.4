<?php

namespace App\Models\InStore\Order;

use App\Models\BaseModel;
use App\Models\Goods\SpuList;
use App\Models\GoodsSpu;
use App\Models\Material;
use App\Models\Order\OrderGoods;
use App\Models\Order\OrderIndex;
use App\Models\Store\GoodsList;
use App\Models\Store\StoreGoods;
use App\Models\Store\StoreGoodsSku;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class ParentOrder extends Order
{
    protected $table = 'instore_order';

    public function goods()
    {
        return $this->hasMany(OrderGoods::class, 'prentOrderSn', 'orderSn')->withTrashed();
    }
}
