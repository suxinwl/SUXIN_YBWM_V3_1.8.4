<?php

namespace App\Models\InStore;

use App\Enums\SceneEnum;
use App\Models\BaseModel;
use App\Models\InStore\Order\Order;
use App\Models\Material;
use App\Models\Member\Address;
use App\Models\OrderCollect\OrderCollect;
use App\Models\Partner;
use App\Models\PayGift\PayGift;
use App\Models\Store;
use App\Models\Store\StoreGoods;
use App\Models\Store\StoreGoodsSku;
use App\Models\Tables\Servers;
use App\Models\Tables\Table;
use App\Models\TradeIn\Activity;
use App\Services\AddressGeoService;
use App\Services\ConfigService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Request;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class Checkout extends BaseModel
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
    public $_tableFormat;
    public $_tableNum;
    public $_tradeinGoodsList = [];
    public $_tradeinGoodsData;
    public $_partner;
    protected $fillable = [
        'uniacid', 'tableId', 'tradeinGoodsId', 'storeId', 'partner','people', 'score', 'packaging', 'couponId', 'userId', 'diningType', 'notes', 'serverTime', 'mobile'
    ];
    protected $appends = [
        'serviceMoneyArr',
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
        'store',
        'payNum',
        'tableMoney',
        'tableFormat',
        'tableNum',
        'packaging',
        'tradeinGoodsList',
        'tradeinGoodsData'
    ];

    protected $attribute = [
        'couponId' => 0,
        'people' => 0,
        'tableId' => 0,
        'diningType' => 4
    ];

    public function getCarListAttribute()
    {
        if (!$this->_cartlist) {
            $model = new CartList([
                "uniacid" => $this->uniacid,
                'storeId' => $this->storeId,
                'userId' => $this->userId,
                'tableId' => $this->tableId,
                'score' => $this->score,
                'diningType' => $this->diningType,
            ]);
            if (empty($model->goodsCount)) {
                throw new BadRequestException('请先添加商品');
            }
            $this->_cartlist =  $model;
        }
        return $this->_cartlist;
    }

    public function getPackagingAttribute()
    {
        $res =  collect($this->goodsList)->where('pack', 0)->first();
        return  empty($res) && $this->goodsNum > 0 ? 1 : 0;
    }

    public function getPayNumAttribute()
    {
        if ($this->diningType == 4) {
            return 0;
        }
        $key = "payNumInStore:{$this->uniacid}:{$this->storeId}:{$this->userId}";
        if (Cache::has($key)) {
            return Cache::get($key) + 1;
        }
        return  1;
    }

    public function prentOrder()
    {
        return $this->hasOne(Order::class, 'orderSn', 'prentOrderSn')->select(['id', 'orderSn', 'payMoney', 'payType']);
    }

    public function getAddNumAttribute()
    {
        if ($this->prentOrderSn) {
            $count =  Order::select(['id'])->where('uniacid', $this->uniacid)
                ->where('prentOrderSn', $this->prentOrderSn)
                ->where('state', '>', 0)
                ->where('goodsNum', '>', 0)
                ->get();
            return collect($count)->count() + 1;
        }
        return 1;
    }

    public function tables()
    {
        return $this->hasOne(Table::class, 'id', 'tableId');
    }


    /**
     * 开台时间
     */
    public function getOpenTimeAttribute()
    {
        return  !empty($this->prentOrderSn) ? $this->prentOrder->openTime : $this->tables->updated_at ?? null;
    }

    public function getPrentOrderSnAttribute()
    {
        if (!$this->_prentOrderSn) {
            $this->_prentOrderSn = $this->tables->orderSn ?? null;
        }
        return $this->_prentOrderSn;
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

    public function getPeopleAttribute()
    {
        if (!$this->_people) {
            $this->_people = $this->tables->people ?? 0;
        }
        return $this->_people;
    }
    public function getPrentGoodsAttribute()
    {
        if (!$this->_prentGoods) {
            $this->_prentGoods = $this->prentOrder->subGoods ?? null;
        }
        return $this->_prentGoods;
    }

    public function getTableMoneyAttribute()
    {
        if (!$this->_tableMoney && $this->diningType == 4 && !$this->prentOrderSn) {
            $this->_tableMoney = $this->carList->tableMoney;
            $this->_tableFormat = $this->carList->tableFormat;
            $this->_tableNum = $this->carList->tableNum;
        }
        return $this->_tableMoney ?? 0;
    }
    public function getTableFormatAttribute()
    {
        return $this->_tableFormat ?? null;
    }

    public function getTableNumAttribute()
    {
        return $this->_tableNum ?? 0;
    }


    public function getInStoreConfigAttribute()
    {
        if (!$this->_contacts) {
            $this->__contacts =  Request()->contacts ?? null;
        }
        return $this->__contacts;
    }

    public function getSceneAttribute()
    {
        if ($this->diningType == 4) {
            return 3;
        } else {
            return 4;
        }
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
        // return  bcadd(bcadd($this->carList->sellMoney, $this->deliveryMoney, 2), $this->tableMoney, 2);
        return  bcadd(bcadd(bcadd($this->carList->sellMoney, $this->tableMoney, 2), $this->boxMoney, 2), $this->tradeinSellMoney, 2);
    }

    /**
     * 划线价
     */
    public function getLineMoneyAttribute()
    {
        //return  bcadd($this->carList->lineMoney, $this->deliveryMoney, 2);
        return  bcadd(bcadd($this->carList->lineMoney, $this->deliveryMoney, 2), $this->tradeinSellMoney, 2);
    }
    /**
     * 订单服务费
     */
    public function getServiceMoneyArrAttribute()
    {
        $money=bcadd(bcadd($this->goodsMoney, bcsub($this->deliveryMoney, $this->deliveryDiscounts, 2), 2), $this->boxMoney, 2);
        $res = ConfigService::getChannelConfig('basicSetting', $this->uniacid);
        $percentage=$res['service_charge'];
        $service_money=0.00;
        if($percentage){
            $percentage /= 100;
            $service_money = bcmul($money,$percentage,2);
        }
        $serviceArr=[
            'service_charge'=>$res['service_charge']?:0,
            'service_money'=>$service_money?:0.00,
        ];
        return  $serviceArr;
    }
    /**
     * 实际价格
     */

    public function getMoneyAttribute()
    {
        $money =  bcsub($this->sellMoney, $this->discountMoney, 2);
        $serviceMoney=$this->serviceMoneyArr['service_money'];
        $money=bcadd($money,$serviceMoney,2);
        $money = $money < 0 ? 0 : $money;
        return $money;
    }


    /**
     * 商品价格
     */

    public function getGoodsMoneyAttribute()
    {
        return  bcmul(bcadd($this->carList->goodsMoney, $this->tradeinMoney, 2), 1, 2);
    }

    public function getGoodsSellMoneyAttribute()
    {
        return  bcmul(bcadd($this->carList->goodsSellMoney, $this->tradeinSellMoney, 2), 1, 2);
    }


    /**
     * 打包费
     */
    public function getBoxMoneyAttribute()
    {
        return  bcmul(bcadd(0, $this->tradeinBoxMoney, 2), 1, 2);
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
     * 预约单 0即时单 1预约单
     */
    public function getAppointmentAttribute()
    {
        return   empty($this->serverTime)  ? 0 : 1;
    }


    /**
     * 积分设置
     */
    public function getIntegralSettingAttribute()
    {
        if (!$this->_integralSetting) {
            $this->_integralSetting = ConfigService::getChannelConfig('integralSetting', $this->uniacid,$this->store->isolateStore);
        }
        return $this->_integralSetting;
    }


    /**
     * 积分设置
     */
    public function getExpSettingAttribute()
    {
        if (!$this->_expSetting) {
            $this->_expSetting = ConfigService::getChannelConfig('growthSetting', $this->uniacid,$this->store->isolateStore);
        }
        return $this->_expSetting;
    }

    public function getIntegralNameAttribute()
    {
        return  '';
    }


    public function getIntegralFormatAttribute()
    {
        return numFormat($this->integral);
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
                        $int = $config->oneOrderGive;
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
     * 下次打印时间
     */
    public function getNextPrintTimeAttribute()
    {
        if (!$this->_nextPrintTime) {
            $this->_nextPrintTime  = null;
        }
        return $this->_nextPrintTime;
    }


    /**
     * 自动接单
     */
    public function getAutoReceiveAttribute()
    {
        if (!$this->_autoReceive) {
            if ($this->store) {
                switch ($this->diningType) {
                    case 4:
                        $this->_autoReceive = ($this->store->inStoreSetting['order']['receive'] == 2 && $this->sellMoney <= $this->store->inStoreSetting['order']['money']);
                        break;
                    case 5:
                        $this->_autoReceive = ($this->store->inStoreSetting['delivery']['receive'] == 2);
                        break;
                    case 6:
                        $this->_autoReceive = ($this->store->inStoreSetting['callNum']['receive'] == 2);
                        break;
                }
            }
        }
        return intval($this->_autoReceive);
    }

    /**
     * 支付模式 1餐前支付 2餐后支付
     */
    public function getPayTypeAttribute()
    {
        if (!$this->_payType) {
            if ($this->store) {
                switch ($this->diningType) {
                    case 4:
                        if ($this->prentOrder) {
                            $this->_payType = $this->prentOrder->payType;
                        } else {
                            $this->_payType = $this->store->inStoreSetting['order']['payMode'] == 1 && $this->store->inStoreSetting['order']['setting'] == 1 ? 1 : 2;
                        }
                        break;
                    case 5:
                        $this->_payType = $this->store->inStoreSetting['delivery']['payMode'] ?? 1;
                        break;
                    case 6:
                        $this->_payType = $this->store->inStoreSetting['callNum']['payMode'] ?? 1;
                        break;
                }
            }
        }
        return $this->_payType;
    }


    public function getCleanTimeAttribute()
    {
    }

    public function getReceivePrintAttribute()
    {
        if (!$this->_receivePrint) {
            $this->_receivePrint = 1;
        }
        return $this->_receivePrint;
    }


    public function check()
    {
        try {
            $lock_key = 'lock_inStore_' . $this->tableId;
            $is_lock  = Redis::setnx($lock_key, 1); // 加锁
            if (!$is_lock) { // 获取锁权限
                // 防止死锁
                Redis::del($lock_key);
                throw new BadRequestException('系统繁忙请稍后再试');
            } else {
                if (Redis::ttl($lock_key) == -1) {
                    Redis::expire($lock_key, 1);
                }
            }
        } catch (\Exception $e) {
            Redis::del($lock_key);
        }
        foreach ($this->goodsList as $key => $goods) {
            $goods->check();
        }
        Redis::del($lock_key);
    }

    public function getDiscountMoneyAttribute()
    {
        if (empty($this->_discountMoney)) {
            $this->_discountMoney = bcadd(bcadd(collect($this->discounts)->sum('money'), $this->carList->goodsDiscountMoney, 2), $this->tradeinDiscountMoney, 2);
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
                            $discounts['coupon'] = ['activityId' => $coupon['id'], 'activityName' => $coupon['coupon']['name'], 'type' => 'coupon', 'money' => 0, 'title' => "券"];
                        } else {
                            $discounts['coupon'] = ['activityId' => $coupon['id'], 'activityName' => $coupon['coupon']['name'], 'type' => 'coupon', 'money' => $coupon['money'], 'title' => "券"];
                        }
                    }
                    // if ($coupon['coupon']['couponType'] == 2) {
                    //     $discounts['coupon'] = ['activityId' => $coupon['id'], 'activityName' => $coupon['coupon']['name'], 'type' => 'deliveryCoupon', 'money' => $coupon['money'], 'title' => "券"];
                    // }
                }
            }
            $this->_discounts = $discounts;
        }
        return $this->_discounts;
    }

    public function getCouopnListAttribute()
    {
        if (!collect($this->prentOrder->discountsAll)->where('type', 'coupon')->first()) {
            $model = new Coupon([
                'selectId' => $this->couponId,
                'uniacid' => $this->uniacid,
                'userId' => $this->userId,
                'scene' => $this->scene,
                'carList' => $this->carList
            ]);
            $this->_couponList = $model->couponData;
        }
        return $this->_couponList;
    }

    public function getCouponCountAttribute()
    {
        return collect($this->couopnList['true'])->count();
    }

    public function getPayGiftAttribute()
    {
        if (!$this->_payGift && $this->diningType != 4 && $this->userId > 0 && $this->user->mobile) {
            $storeId = $this->storeId;
            $uniacid = $this->uniacid;
            $isolate = $this->store->isolateStore;
            $this->_payGift = PayGift::where('uniacid', $this->uniacid)
                ->where(function ($q) use ($storeId, $uniacid, $isolate) {
                    return $q->whereHas('stores', function ($q) use ($storeId, $uniacid) {
                        return $q->where(function ($q) use ($storeId, $uniacid) {
                            return $q->where('storeId', $storeId)->where('type', 2);
                        })->orWhere(function ($q) use ($storeId, $uniacid) {
                            return $q->where('storeId', '!=', $storeId)->where('type', 3);
                        });
                    })->when($isolate == 0, function ($q) use ($uniacid) {
                        return $q->orWhere(function ($q) use ($uniacid) {
                            return $q->where('storeType', 1)
                                ->where('storeId', 0)
                                ->where('uniacid', $uniacid);
                        });
                    });
                })
                ->where('scenario', 'like', "%{$this->scene}%")
                ->where('startTime', '<=', date("Y-m-d H:i:s"))
                ->where('endTime', '>=', date("Y-m-d H:i:s"))
                ->where('money', "<", $this->money)
                ->first();
        }
        return $this->_payGift;
    }

    public function getPayGiftIdAttribute()
    {
        return $this->payGift ? $this->payGift->id : 0;
    }

    public function getOrderCollectAttribute()
    {
        if (!$this->_orderCollect) {
            $storeId = $this->storeId;
            $uniacid = $this->uniacid;
            $this->_orderCollect = OrderCollect::where('uniacid', $this->uniacid)
                ->where(function ($q) use ($storeId, $uniacid) {
                    return $q->whereHas('stores', function ($q) use ($storeId, $uniacid) {
                        return $q->where(function ($q) use ($storeId, $uniacid) {
                            return $q->where('storeId', $storeId)->where('type', 2);
                        })->orWhere(function ($q) use ($storeId, $uniacid) {
                            return $q->where('storeId', '!=', $storeId)->where('type', 3);
                        });
                    })->orWhere(function ($q) use ($uniacid) {
                        return $q->where('storeType', 1)->where('uniacid', $uniacid);
                    });
                })
                ->where('scenario', 'like', "%{$this->scene}%")
                ->where('startTime', '<=', date("Y-m-d H:i:s"))
                ->where('endTime', '>=', date("Y-m-d H:i:s"))
                ->first();
        }
        return $this->_orderCollect;
    }

    public function getCollectIdAttribute()
    {
        return $this->orderCollect ? $this->orderCollect->id : 0;
    }

    public function getCollectNumAttribute()
    {
        if ($this->orderCollect->type == 1) {
            return 1;
        }
        if ($this->orderCollect->type == 2) {
            return $this->carList->goodsCount;
        }
        return 0;
    }

    public function getVipDiscountAttribute()
    {
        if ($this->user->vip->discountSwitch == 1) {
            $discountGoods = collect($this->goodsList)->where('discountType', ">", 0)->first();
            $goodsMoney  = collect($this->goodsList)->where('discountType', 0)->where('goods.vipPriceSwitch', 1)->sum('sellMoney');
            if (empty($discountGoods) && $goodsMoney > 0) {
                $this->_vipDiscount = bcmul((100 - bcmul($this->user->vip->discount, 10, 2)), bcdiv($goodsMoney, 100, 3), 2);
            }
        }
        return $this->_vipDiscount;
    }

    public function getTradeinGoodsListAttribute()
    {
        if (!$this->_tradeinGoodsList) {
            $storeId = $this->storeId;
            $uniacid = $this->uniacid;
            $diningType = $this->diningType;
            if (in_array($diningType, [1, 2])) {
                $scenario = 2;
            } elseif ($diningType == 0) {
                $scenario = 1;
            } elseif (in_array($diningType, [5, 6])) {
                $scenario = 4;
            } elseif ($diningType == 4) {
                $scenario = 3;
            } else {
                return $scenario = 0;
            }
            $model = Activity::where('uniacid', $this->uniacid)
                ->with(['goods' => function ($q) use ($storeId, $uniacid) {
                    return $q->whereHas('storeGoods', function ($q) use ($storeId, $uniacid) {
                        return $q->where('storeId', $storeId);
                    });
                }])
                ->where('state', 1)
                ->where('scenario', 'like', "%{$scenario}%")
                ->where("startTime", "<", Carbon::now()->toDateTimeString())
                ->where("endTime", ">=", Carbon::now()->toDateTimeString())
                ->where(function ($q) use ($storeId, $uniacid) {
                    return $q->whereHas('stores', function ($q) use ($storeId, $uniacid) {
                        return $q->where(function ($q) use ($storeId, $uniacid) {
                            return $q->where('storeId', $storeId)->where('type', 2);
                        })->orWhere(function ($q) use ($storeId, $uniacid) {
                            return $q->where('storeId', '!=', $storeId)->where('type', 3);
                        });
                    })->orWhere(function ($q) use ($uniacid) {
                        return $q->where('storeType', 1)->where('uniacid', $uniacid);
                    });
                })
                ->first();
            if ($model) {
                $this->_tradeinGoodsList = collect($model->goods)->map(function ($goods) {
                    $goods->setAppends(['activityPrice']);
                    return $goods;
                })->filter(function ($goods) {
                    return $goods->activityPrice !== null;
                })->all();
            }
        }
        return $this->_tradeinGoodsList;
    }


    public function getTradeinGoodsDataAttribute()
    {
        if ($this->tradeinGoodsId && !$this->_tradeinGoodsData) {
            $goods = collect($this->tradeinGoodsList)->where('id', $this->tradeinGoodsId)->first();
            if ($goods) {
                $model = new Cart();
                $model->fill([
                    'specMd5' => $goods->specMd5,
                    'diningType' => $this->diningType,
                    'attrData' => empty($goods->spec) ? [] : [
                        'spec' => $goods->spec,
                        'attr' => '',
                        'matal' => '',
                        'material' => ''
                    ],
                    'tableId' => $this->tableId,
                    'spuId' => $goods->spuId,
                    'discountType' => 12,
                    'discountNum' => 1,
                    'num' => 1
                ]);
                $model->uniacid = $this->uniacid;
                $model->storeId = $this->storeId;
                $model->userId = $this->userId;
                $model = $model->model(false);
                $model->num = 1;
                $model->discountType = 12;
                $model->discountNum = 1;
                $model->discountPrice = $goods->activityPrice;
                $model->discountLabel = "换购";
                $model->setMealMoney = $model->getSetMealMoney();
                $model->materialMoney = $model->getMaterialMoney();
                $model->discountMoney = $model->getDiscountMoney();
                $model->sellMoney = $model->getSellMoney();
                $model->money = $model->getMoney();
                $model->boxMoney = $model->getBoxMoney();
                $this->_tradeinGoodsData = [$model];
            }
            return $this->_tradeinGoodsData;
        }
        return $this->_tradeinGoodsData;
    }

    public function getTradeinMoneyAttribute()
    {
        return collect($this->tradeinGoodsData)->sum('money');
    }

    public function getTradeinBoxMoneyAttribute()
    {

        return $this->packaging == 1 ? collect($this->tradeinGoodsData)->sum('boxMoney') : 0;
    }

    public function getTradeinDiscountMoneyAttribute()
    {
        return collect($this->tradeinGoodsData)->sum('discountMoney');
    }
    public function getTradeinSellMoneyAttribute()
    {
        return collect($this->tradeinGoodsData)->sum('sellMoney');
    }

    public function getPartnerAttribute()
    {
        if (!$this->_partner) {
            $config = ConfigService::getChannelConfig('distributor', $this->uniacid, 0);
            $partner = Partner::where('uniacid', $this->uniacid)->where('userId', $this->userId)->first();
            if ($config['partnerPaySwitch'] == 1 && $partner) {
                //内购
                $floatNumber =$this->money; // 浮点数
                $percentage = $config['levelRate']['first']; // 百分比，不带百分号
                // 将百分比转换为小数，例如："5.5" 转换为 "0.055"
                $percentage /= 100;
                // 使用 bcmul 进行精确乘法
                $partnerMoney = bcmul($floatNumber, $percentage,2);
                $data[0] = [
                    'level' => 1,
                    'partnerId' => $partner->userId,
                    'money' => $partnerMoney
                ];
                if ($config['level'] == 2) {
                    $parent = Partner::where('uniacid', $this->uniacid)->where('userId', $partner->parentId)->first();
                    if ($parent) {
                        $floatNumber =$this->money; // 浮点数
                        $percentage = $config['levelRate']['second']; // 百分比，不带百分号
                        // 将百分比转换为小数，例如："5.5" 转换为 "0.055"
                        $percentage /= 100;
                        // 使用 bcmul 进行精确乘法
                        $partnerMoney = bcmul($floatNumber, $percentage,2);
                        $data[1] = [
                            'level' => 2,
                            'partnerId' => $parent->userId,
                            'money' => $partnerMoney
                        ];
                    }
                }
                $this->_partner = $data;
            } elseif ($this->user->partnerId) {
                //二级分销
                $partner = Partner::where('uniacid', $this->uniacid)->where('userId', $this->user->partnerId)->first();
                if ($partner) {
                    $floatNumber =$this->money; // 浮点数
                    $percentage = $config['levelRate']['first']; // 百分比，不带百分号
                    // 将百分比转换为小数，例如："5.5" 转换为 "0.055"
                    $percentage /= 100;
                    // 使用 bcmul 进行精确乘法
                    $partnerMoney = bcmul($floatNumber, $percentage,2);
                    $data[0] = [
                        'level' => 1,
                        'partnerId' => $partner->userId,
                        'money' => $partnerMoney
                    ];
                    if ($config['level'] == 2) {
                        $parent = Partner::where('uniacid', $this->uniacid)->where('userId', $partner->parentId)->first();
                        if ($parent) {
                            $floatNumber =$this->money; // 浮点数
                            $percentage = $config['levelRate']['second']; // 百分比，不带百分号
                            // 将百分比转换为小数，例如："5.5" 转换为 "0.055"
                            $percentage /= 100;
                            // 使用 bcmul 进行精确乘法
                            $partnerMoney = bcmul($floatNumber, $percentage,2);
                            $data[1] = [
                                'level' => 2,
                                'partnerId' => $partner->userId,
                                'money' => $partnerMoney
                            ];
                        }
                    }
                }
                $this->_partner = $data;
            }
        }
        return $this->_partner;
    }
}
