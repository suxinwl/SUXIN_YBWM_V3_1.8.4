<?php

namespace App\Models\InStore\Order;

use App\Models\BaseModel;
use App\Models\Goods\SpuList;
use App\Models\GoodsSpu;
use App\Models\Material;
use App\Models\Order\User;
use App\Models\Store\GoodsList;
use App\Models\Store\StoreGoods;
use App\Models\Store\StoreGoodsSku;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class OrderBase extends BaseModel
{
    public $_config;
    use HasFactory;
    protected $table = 'instore_order';


    public function users()
    {
        return $this->hasMany(User::class, 'orderSn', 'orderSn');
    }
}
