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
        'orderCollect',
        'collectNum',
        'collectId',
        'payGiftId',
        'payGift',
        'couopnList',
        'discounts',
        'couponCount',
        'goodsMoney',
        'discountMoney',
        'autoReceive',
        'expFormat',
        'expName',
        'exp',
        'integralFormat',
        'integralName',
        'integral',
        'scene',
        'goodsList',
        'boxMoney',
        'sellMoney',
        'money',
        'people',
        'prentOrderSn',
        'payType',
        'addNum',
        'prentGoods',
        'goodsNum',
        'user'
    ];

    protected $attribute = [
        'couponId' => 0,
        'people' => 0,
        'tableId' => 0,
        'diningType' => 4,
        'userId' => 0
    ];

    public function getGoodsAttribute()
    {
        return $this->_cartlist;
    }


    /**
     * Undocumented function
     *商品数量
     * @return void
     */

    public function getGoodsNumAttribute()
    {
        return $this->carList->goodsCount;
    }





    /**
     * 购物车商品
     */
    public function getGoodsListAttribute()
    {
        return $this->carList->goodsList;
    }


    /**
     * 原价格
     */
    public function getSellMoneyAttribute()
    {
        return  bcadd($this->carList->sellMoney, $this->deliveryMoney, 2);
    }

    /**
     * 划线价
     */
    public function getLineMoneyAttribute()
    {
        return  bcadd($this->carList->lineMoney, $this->deliveryMoney, 2);
    }

    /**
     * 实际价格
     */

    public function getMoneyAttribute()
    {
        return  bcsub(bcadd($this->carList->sellMoney, $this->deliveryMoney, 2), $this->discountMoney, 2);
    }

    /**
     * 商品价格
     */

    public function getGoodsMoneyAttribute()
    {
        return  $this->carList->goodsMoney;
    }


    /**
     * 打包费
     */
    public function getBoxMoneyAttribute()
    {
        return "0.00";
    }

    /**
     * 门店信息
     */
    public function getStoreAttribute()
    {
        return $this->carList->store;
    }

    /**
     * 门店信息
     */
    public function getUserAttribute()
    {
        return $this->carList->user;
    }


    /**
     * 积分设置
     */
    public function getExpSettingAttribute()
    {
        if (!$this->_expSetting) {
            $this->_expSetting = ConfigService::getChannelConfig('growthSetting', $this->uniacid);
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
            $this->_discountMoney = collect($this->discounts)->sum('money');
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

    public function getCouopnListAttribute()
    {
        if (empty($this->_couponList)) {
            $model = new Coupon(['selectId' => $this->couponId, 'uniacid' => $this->uniacid, 'userId' => $this->userId, 'scene' => $this->scene, 'carList' => $this->carList]);
            $this->_couponList = $model->couponData;
        }
        return $this->_couponList;
    }

    public function getCouponCountAttribute()
    {
        return collect($this->couopnList['true'])->count();
    }
    public function getOrder()
    {
        return $this->order->fill($this->toArray());
    }
}
