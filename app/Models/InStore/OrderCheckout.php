<?php

namespace App\Models\InStore;

use App\Enums\SceneEnum;
use App\Models\BaseModel;
use App\Models\InStore\Order\Order;
use App\Models\Material;
use App\Models\Member\Address;
use App\Models\OrderCollect\OrderCollect;
use App\Models\PayGift\PayGift;
use App\Models\Store;
use App\Models\Store\StoreGoods;
use App\Models\Store\StoreGoodsSku;
use App\Models\Tables\Table;
use App\Services\AddressGeoService;
use App\Services\ConfigService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Request;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class OrderCheckout extends BaseModel
{
    public $_cartlist;
    public $_addressList;
    public $_addressId;
    public $_address;
    public $_interval;
    public $_timeArr;
    public $_reservationTime;
    public $_integralSetting;
    public $_expSetting;
    public $_integral;
    public $_exp;
    public $_mobile;
    public $_nextPrintTime;
    public $_autoReceive = 0;
    public $_receivePrint;
    public $_inBusiness = 0;
    public $_discounts = [];
    public $_discountMoney = 0;
    public $_couponList = [];
    public $_delivery;
    public $_couponId;
    public $_contacts;
    public $_payGift;
    public $_orderCollect;
    public $_collecNum;
    public $_prentOrderSn;
    public $_people;
    public $_tableMoney;
    public $_payType;
    public $_prentOrder;
    public $_prentGoods;
    public $_cleanTime;
    protected $fillable = [
        "order"
    ];
    protected $appends = [
        'exp',
        'integral',
        'sellMoney',
        'money',
        'goodsNum',
        'discountMoney'
    ];

    protected $attribute = [
        'couponId' => 0,
        'people' => 0,
        'tableId' => 0,
        'diningType' => 4,
        'userId' => 0
    ];


    /**
     * Undocumented function
     *商品数量
     * @return void
     */

    public function getGoodsNumAttribute()
    {
        return collect($this->goodsList)->sum('num');
    }





    /**
     * 购物车商品
     */
    public function getGoodsListAttribute()
    {
        return  collect($this->order->goods)->all();
    }


    /**
     * 原价格
     */
    public function getSellMoneyAttribute()
    {
        return  bcadd(collect($this->goodsList)->where('state', "<", 8)->sum('sellMoney'), $this->order->tableMoney, 2);
    }


    /**
     * 实际价格
     */

    public function getMoneyAttribute()
    {
        return  bcsub($this->sellMoney, $this->discountMoney, 2);
    }

    /**
     * 商品价格
     */

    public function getGoodsMoneyAttribute()
    {
        return  bcmul(collect($this->goodsList)->where('state', "<", 8)->sum('money'), 1, 2);
    }


    /**
     * 打包费
     */
    public function getBoxMoneyAttribute()
    {
        return bcmul(collect($this->goodsList)->where('pack', 1)->sum('boxMoney'), 1, 2);
    }

    /**
     * 门店信息
     */
    public function getStoreAttribute()
    {
        return $this->order->store;
    }

    /**
     * 门店信息
     */
    public function getUserAttribute()
    {
        return $this->order->user;
    }


    /**
     * 积分设置
     */
    public function getExpSettingAttribute()
    {
        if (!$this->_expSetting) {
            $this->_expSetting = ConfigService::getChannelConfig('growthSetting', $this->order->uniacid);
        }
        return $this->_expSetting;
    }

    public function getIntegralNameAttribute()
    {
        return  '';
    }

    public function getIntegralFormatAttribute()
    {
        return numFormat($this->Integral);
    }

    /**
     * 赠送积分
     */
    public function getIntegralAttribute()
    {
        return 0;
        if (!$this->_integral) {
            $config = $this->integralSetting;
            if (empty($config)) {
                $this->_integral = 0;
            } else {
                $power = $this->user->vip->integralMultiplierSwitch == 1 ?  $this->user->vip->integralMultiplier : 1;
                if ($config['integralState'] == 0) {
                    $int = 0;
                } else {
                    if ($config['giveType'] == 1) {
                        $money  = round($this->money);
                        $int = $money *  $config['oneYuanGive'];
                    }

                    if ($config['giveType'] == 2) {
                        $int = $this->carList->goodsCount *  $config['onePieceGive'];
                    }

                    if ($config['giveType'] == 3) {
                        $int = $config['oneOrderGive'];
                    }
                }
                $this->_integral = $int  * $power;
            }
        }
        return $this->_integral;
    }


    public function getExpNameAttribute()
    {
        return  '';
    }


    public function getExpFormatAttribute()
    {
        return numFormat($this->exp);
    }

    /**
     * 赠送的成长值
     */
    public function getExpAttribute()
    {
        return 0;
        if (!$this->_exp) {
            $config = $this->expSetting;
            if (empty($config)) {
                $this->_exp = 0;
            } else {
                $power = 1;
                if ($config['growthState'] == 0) {
                    $int = 0;
                } else {
                    if ($config['giveType'] == 1) {
                        $money  = round($this->money);
                        $int = $money *  $config['oneYuanGive'];
                    }

                    if ($config['giveType'] == 2) {
                        $int = $this->goodsCount *  $config['onePieceGive'];
                    }

                    if ($config['giveType'] == 3) {
                        $int = $config['oneOrderGive'];
                    }
                }
                $this->_exp = $int  * $power;
            }
        }
        return $this->_exp;
    }


    /**
     * 订单优惠金额
     */
    public function getDiscountMoneyAttribute()
    {
        if (empty($this->_discountMoney)) {
            $this->_discountMoney = bcadd(collect($this->discounts)->sum('money'), collect($this->goodsList)->where('state', "<", 8)->sum('discountMoney'), 2);
        }
        return $this->_discountMoney;
    }


    public function getDiscountsAttribute()
    {
        if (empty($this->_discounts)) {
            $discounts = $this->carList->discounts;
            if (!empty($this->couponId)) {
                $coupon = collect($this->couopnList['true'])->where('id', $this->couponId)->first();
                if ($coupon) {
                    if ($coupon['coupon']['couponType'] == 1) {
                        if ($this->goodsMoney == 0) {
                            $discounts['coupon'] = ['activityId' => $coupon['id'], 'activityName' => $coupon['coupon']['name'], 'type' => 'coupon', 'money' => 0, 'title' => "优惠券"];
                        } else {
                            $discounts['coupon'] = ['activityId' => $coupon['id'], 'activityName' => $coupon['coupon']['name'], 'type' => 'coupon', 'money' => $coupon['money'], 'title' => "优惠券"];
                        }
                    }
                    if ($coupon['coupon']['couponType'] == 2) {
                        $discounts['coupon'] = ['activityId' => $coupon['id'], 'activityName' => $coupon['coupon']['name'], 'type' => 'deliveryCoupon', 'money' => $coupon['money'], 'title' => "配送费优惠券"];
                    }
                }
            }
            $this->_discounts = $discounts;
        }
        return $this->_discounts;
    }
}
