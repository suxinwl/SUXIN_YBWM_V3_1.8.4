<?php

namespace App\Models\InStore;

use App\Enums\SceneEnum;
use App\Models\BaseModel;
use App\Models\InStore\Order\Order;
use App\Models\Material;
use App\Models\Member\Address;
use App\Models\Member\MemberBase;
use App\Models\NewSub\NewSub;
use App\Models\OrderCollect\OrderCollect;
use App\Models\PayGift\PayGift;
use App\Models\Store;
use App\Models\Store\StoreGoods;
use App\Models\Store\StoreGoodsSku;
use App\Models\Tables\Servers;
use App\Models\Tables\Table;
use App\Services\AddressGeoService;
use App\Services\ConfigService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Request;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class StoreCheckout extends BaseModel
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
    public $_inStoreConfig;
    public $_freezeCount;
    public $_tableFormat;
    public $_tableNum;
    public $_newSub;
    public $_goodsMoney;
    public $_fullsub;

    protected $fillable = [
        'freeReason',
        'free',
        'wipeZero',
        'manualDiscountData',
        'manualDiscount',
        'smallChange',
        'advancedDiscount',
        'notes',
        'uniacid',
        'tableId',
        'adminId',
        'storeId',
        'people',
        'score',
        'packaging',
        'couponId',
        'userId',
        'diningType',
        'notes',
        'serverTime', 'mobile'
    ];
    protected $appends = [
        'orderCollect',
        'collectNum',
        'collectId',
        'payGiftId',
        'payGift',
        'couponList',
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
        'inStoreConfig',
        'totalMoney',
        'tableMoney',
        'lineMoney',
        'totalGoodsNum',
        'generalGoods',
        'discountsGoods',
        'freezeCount',
        'discountsPlus',
        'tableFormat',
        'tableNum',
        'goodsSellMoney',
        'packaging',
        'fullSub',
        'newSub',
    ];

    protected $attribute = [
        'people' => 0,
        'tableId' => 0,
        'diningType' => 4,
        'userId' => 0,
        'advancedDiscount' => null,
        'smallChange' => null
    ];

    protected $with = ['user'];

    public function getCarListAttribute()
    {
        return  new CartList([
            "uniacid" => $this->uniacid,
            'storeId' => $this->storeId,
            'userId' => $this->userId ?? 0,
            'tableId' => $this->tableId,
            'score' => $this->score,
            'adminId' => $this->adminId,
            'orderSn' => $this->prentOrderSn,
            'diningType' => $this->diningType
        ]);
    }

    public function getFreezeCountAttribute()
    {
        if (!$this->_freezeCount) {
            return FreezeOrder::where('uniacid', $this->uniacid)
                ->where('storeId', $this->storeId)
                ->where('userId', $this->adminId)
                ->count();
        }
        return 0;
    }
    public function store()
    {
        return $this->hasOne(Store::class, 'id', 'storeId');
    }
    public function user()
    {
        return $this->hasOne(MemberBase::class, 'id', 'userId');
    }

    public function prentOrder()
    {
        return $this->hasOne(Order::class, 'orderSn', 'prentOrderSn')->select(['id', 'orderSn', 'payMoney', 'money', 'sellMoney', 'goodsNum', 'notes']);
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

    public function getPackagingAttribute()
    {
        $res =  collect($this->goodsList)->where('pack', 0)->first();
        return  empty($res) && $this->goodsNum > 0 ? 1 : 0;
    }


    public function getPrentOrderSnAttribute()
    {
        if (!$this->_prentOrderSn) {
            $this->_prentOrderSn = $this->tables->orderSn ?? null;
        }
        return $this->_prentOrderSn;
    }

    /**
     * 门店信息
     */
    public function getPickNoAttribute()
    {
        return $this->prentOrder->attributes['pickNo'] ?? '';
    }

    public function getDiscountsPlusAttribute()
    {
        return collect($this->goodsList)->where('discountType', '>', 0)->map(function ($goods, $key) {
            if ($goods->discountType == 1) {
                return [
                    'activityId' => 0,
                    'activityName' => $goods->discountLabel,
                    'type' => 'goodsGive',
                    'money' => $goods->discountMoney,
                    'reason' => $goods->reason,
                    'title' => "赠菜"
                ];
            } elseif ($goods->discountType == 2) {
                return [
                    'activityId' => 0,
                    'activityName' => "手动{$goods->discountLabel}",
                    'type' => 'goodsDiscount',
                    'money' => $goods->discountMoney,
                    'reason' => $goods->reason,
                    'title' => "手动打折"
                ];
            } elseif ($goods->discountType == 3) {
                return [
                    'activityId' => 0,
                    'activityName' => "手动{$goods->discountLabel}",
                    'type' => 'goodsSub',
                    'money' => $goods->discountMoney,
                    'reason' => $goods->reason,
                    'title' => "手动减免"
                ];
            } else {
                return [
                    'activityId' => 0,
                    'activityName' => $goods->discountLabel,
                    'type' => 'goodsDiscount',
                    'money' => $goods->discountMoney,
                    'reason' => $goods->reason,
                    'title' => $goods->discountLabel
                ];
            }
        })->merge(collect($this->discounts)->values())->values();
    }

    /**
     * 门店信息
     */
    public function getPickFixAttribute()
    {
        return $this->prentOrder->pickFix ?? '';
    }

    public function getPickAllAttribute()
    {
        return 0;
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
            $tableId = $this->tableId;
            $server = Servers::where('uniacid', $this->uniacid)
                ->where('storeId', $this->storeId)
                ->whereHas('tables', function ($q) use ($tableId) {
                    return $q->where('tableId', $tableId);
                })->first();
            if ($server) {
                $money = $server->type == 1 ? bcmul(intval($this->people), floatval($server->price), 2) : floatval($server->price);
            }
            $this->_tableMoney = $money ?? 0;
            $this->_tableFormat = $server->name ?? null;
            $this->_tableNum = $server->type == 1 ?  $this->people : 1;
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
        if (!$this->_inStoreConfig) {
            $this->_inStoreConfig =  $this->store->inStoreSetting;
        }
        return $this->_inStoreConfig;
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
     * 原价商品
     */
    public function getGeneralGoodsAttribute()
    {
        return  collect($this->goodsList)->where('discountType', 0)->values();
    }

    /**
     * 优惠商品
     */
    public function getDiscountsGoodsAttribute()
    {
        return  collect($this->goodsList)->where('discountType', '>', 0)->values();
    }


    /**
     * 原价格
     */
    public function getSellMoneyAttribute()
    {
        return  bcadd($this->carList->sellMoney, $this->tableMoney, 2);
    }

    /**
     * 原价商品
     */
    public function getGoodsSellMoneyAttribute()
    {
        return  $this->carList->goodsSellMoney;
    }


    /**
     * 实际价格
     */

    public function getMoneyAttribute()
    {
        $money  = bcsub($this->sellMoney, $this->discountMoney, 2);
        $money =  $money > 0 ? $money : 0;
        if ($this->discounts['free']) {
            return 0;
        }
        return $money;
    }

    /**
     * 商品价格
     */

    public function getGoodsMoneyAttribute()
    {
        if (empty($this->_goodsMoney)) {
            $goodsMoney  = bcsub($this->sellMoney, $this->discountMoney, 2);
            $this->_goodsMoney =  $goodsMoney < 0 ? 0 : $goodsMoney;
        }
        return $this->_goodsMoney;
    }



    /**
     * 打包费
     */
    public function getBoxMoneyAttribute()
    {
        return bcmul($this->carList->boxMoney, 1, 2);
    }


    /**
     * 费用合计
     */
    public function getTotalMoneyAttribute()
    {
        return  !empty($this->prentOrderSn) ? bcadd($this->prentOrder->money, $this->money, 2) : $this->money;
    }

    public function getLineMoneyAttribute()
    {
        return  !empty($this->prentOrderSn) ? bcadd($this->prentOrder->sellMoney, $this->sellMoney, 2) : $this->sellMoney;
    }
    /**
     * 商品价格
     */

    public function getTotalGoodsNumAttribute()
    {
        return  !empty($this->prentOrderSn) ? bcadd($this->prentOrder->goodsNum, $this->goodsNum) : $this->goodsNum;
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
     * 开台时间
     */
    public function getOpenTimeAttribute()
    {
        return  !empty($this->prentOrderSn) ? $this->prentOrder->openTime : $this->tables->updated_at ?? null;
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
                if ($config->integralState == 0) {
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
                        $int = $this->goodsNum *  $config['onePieceGive'];
                    }

                    if ($config->giveType == 3) {
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
            $this->_autoReceive = 1;
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
                        $this->_payType = 2;
                        break;
                    case 5:
                        $this->_payType =  1;
                        break;
                    case 6:
                        $this->_payType = 1;
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
        if (empty($this->carList->goodsCount)) {
            throw new BadRequestException('请先添加商品');
        }
        foreach ($this->goodsList as $key => $goods) {
            $goods->check();
        }
        Redis::del($lock_key);
    }

    public function getDiscountMoneyAttribute()
    {
        return bcadd(collect($this->discounts)->sum('money'), $this->carList->goodsDiscountMoney, 2);
    }


    public function getDiscountsAttribute()
    {
        if ($this->free) {
            $discounts['free'] = [
                'activityId' => 0,
                'activityName' => "免单",
                'type' => 'free',
                'money' => 0,
                'reason' => $this->freeReason,
                'title' => "免单"
            ];
            return empty($discounts) ? null : $discounts;
        }
        $discounts = collect($this->carList->discounts)->merge($this->manualDiscountData ?? []);
        if (!empty($this->couponId)) {
            $coupon = collect($this->couponList['true'])->where('id', $this->couponId)->first();
            if ($coupon) {
                if ($coupon['coupon']['couponType'] == 1) {
//                    if ($this->goodsMoney == 0) {
//                        $discounts['coupon'] = ['activityId' => $coupon['id'], 'activityName' => $coupon['coupon']['name'], 'type' => 'coupon', 'money' => 0, 'title' => "优惠券"];
//                    } else {
//                        $discounts['coupon'] = ['activityId' => $coupon['id'], 'activityName' => $coupon['coupon']['name'], 'type' => 'coupon', 'money' => $coupon['money'], 'title' => "优惠券"];
//                    }

                    $price = bcmul(bcdiv($this->goodsMoney, 100, 4), $coupon['coupon']['rule']['discount']*10, 2);
                    $price=bcsub($this->goodsMoney,$price,2);
                    $discounts['coupon'] = ['activityId' => $coupon['id'], 'activityName' => $coupon['coupon']['name'], 'type' => 'coupon', 'money' => $coupon['coupon']['rule']['money']?:$price, 'title' => "优惠券"];

                }
                if ($coupon['coupon']['couponType'] == 2) {
                    $discounts['coupon'] = ['activityId' => $coupon['id'], 'activityName' => $coupon['coupon']['name'], 'type' => 'deliveryCoupon', 'money' => $coupon['money'], 'title' => "配送费优惠券"];
                }
                if ($coupon['coupon']['type'] == 3) {
                    $goods=StoreGoodsSku::where('spuId',$coupon['coupon']['goodsIds'][0])->first();

                    $discountMoney =$goods->price;
                    $discounts['coupon'] = ['activityId' => $coupon['id'], 'activityName' => $coupon['coupon']['name'], 'type' => 'coupon', 'money' => $discountMoney, 'title' => "优惠券"];

                }
            }
        }
        if ($this->fullsub) {
            $discounts['fullsub'] = ['activityId' => $this->store->fullSub->id, 'activityName' => $this->store->fullSub->name, 'type' => 'fullsub', 'money' => $this->fullsub['sub'], 'title' => "减"];
        }
        if ($this->newSub) {
            $discounts['newSub'] = ['activityId' => $this->newSub->id, 'activityName' => $this->newSub->name, 'type' => 'newSub', 'money' => $this->newSub->money, 'title' => "新"];
        }
        return empty($discounts) ? null : $discounts;
    }

    public function getCouponListAttribute()
    {
        if (empty($this->_couponList) && !collect($this->discountPlus)->where('type', 'coupon')->first()) {
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
        return  collect($this->couponList['true'])->count();
    }

    public function getPayGiftAttribute()
    {
        return $this->_payGift;
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
        if (!$this->_orderCollect && $this->userId > 0) {
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

    public function calculateDiscount()
    {
        $money=$this->money;
        $this->manualDiscountData = [];
        $manualDiscountData = [];
        if ($this->manualDiscount) {
            $reason = $this->manualDiscount['reason'] ?? null;
            if ($this->manualDiscount['type'] == 'discount') {
                $discountMoney  = bcmul(bcdiv($money, 100, 4), intval($this->manualDiscount['discount']), 2);
                $manualDiscountData['manualDiscount'] = [
                    'activityId' => 0,
                    'activityName' => '整单打' . bcdiv($this->manualDiscount['discount'], 10, 1) . "折",
                    'type' => 'manualDiscount',
                    'reason' => $reason,
                    'money' => bcsub($money, $discountMoney, 2),
                    'title' => "整单打折"
                ];
            }
            if ($this->manualDiscount['type'] == 'sub') {
                $discountMoney  = $this->manualDiscount['discount'] > $money? $money : $this->manualDiscount['discount'];
                $manualDiscountData['manualDiscount'] = [
                    'activityId' => 0,
                    'activityName' => '整单立减' . $discountMoney . "元",
                    'type' => 'manualDiscount',
                    'reason' => $reason,
                    'money' => $discountMoney,
                    'title' => "整单立减"
                ];
            }
            $this->manualDiscountData = $manualDiscountData;
        }
        if ($this->wipeZero) {
            if ($this->wipeZero['type'] == 'Y') {

                $discountMoney = fixY($money);
                $manualDiscountData['wipeZero'] = [
                    'activityId' => 0,
                    'activityName' => '抹零' . $discountMoney . "元",
                    'type' => 'wipeZero',
                    'money' => $discountMoney,
                    'title' => "抹零"
                ];
            }
            if ($this->wipeZero['type'] == 'J') {
                $discountMoney = fixJ($money);
                $manualDiscountData['wipeZero'] = [
                    'activityId' => 0,
                    'activityName' => '抹零' . $discountMoney . "角",
                    'type' => 'wipeZero',
                    'money' => $discountMoney,
                    'title' => "抹零"
                ];
            }
            if ($this->wipeZero['type'] == 'F') {
                $discountMoney = fixF($money);
                $manualDiscountData['wipeZero'] = [
                    'activityId' => 0,
                    'activityName' =>  '抹零' . $discountMoney . "分",
                    'type' => 'wipeZero',
                    'money' => $discountMoney,
                    'title' => "抹零"
                ];
            }
            if ($this->wipeZero['type'] == 'custom') {
                $manualDiscountData['wipeZero'] = [
                    'activityId' => 0,
                    'activityName' => '抹零' . floatval($this->wipeZero['discount']) . "元",
                    'type' => 'wipeZero',
                    'money' => floatval($this->wipeZero['discount']),
                    'title' => "抹零"
                ];
            }
            $this->manualDiscountData = $manualDiscountData;
        }
        return true;
    }

    public function getFullSubAttribute()
    {
        $discount = $this->store->fullsub;
        if (empty($discount) || !in_array($this->scene, $discount->scenario)) {
            return null;
        }
        if (!$this->_fullsub) {
            $model = new FullSub(
                [
                    'discount' => $discount,
                    "goods" => $this->goodsList,
                    "uniacid" => $this->uniacid,
                    "storeId" => $this->storeId,
                    'scene' => $this->scene,
                    'boxMoney' => 0,
                    'deliveryMoney' => 0,
                    'materialMoney' => 0
                ]
            );
            $this->_fullsub = $model->discounts ?? null;
        }

        return $this->_fullsub;
    }

    public  function getNewSubAttribute()
    {
        $scene = Request()->scene;
        $storeId = $this->storeId;
        $uniacid = $this->uniacid;
        $user = $this->user;
        $key = "payNum:{$uniacid}:{$storeId}:{$this->userId}";
        $num = Cache::get($key);
        if (empty($this->_newSub) && $user && $num == 0) {
            $this->_newSub = NewSub::where('uniacid', $uniacid)
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
                ->where('startTime', '<=', date("Y-m-d H:i:s"))
                ->where('endTime', '>=', date("Y-m-d H:i:s"))
                ->first();
        }
        return $this->_newSub;
    }


    public function  getSetMealMoney()
    {
        $money = 0.00;
        if ($this->setMealData) {
            foreach ($this->setMealData as $key => $item) {
                $money = bcmul(collect($this->setMealData)->sum('money'), 1, 2);
            }
        }
        return $money;
    }


    public function getSetMealDataAttribute()
    {
        if ($this->attributes['setMealData']) {
            return collect(json_decode($this->attributes['setMealData'], 320))->map(function ($goods, $key) {
                $goods['money'] = bcmul($goods['price'], intval($goods['num']), 2);
                if ($goods['attrData']['material']) {
                    foreach ($goods['attrData']['material'] as $key => $item) {
                        $material = Material::where('uniacid', $this->uniacid)->find($item['id']);
                        if (empty($material)) {
                            throw new BadRequestException($item['name'] . '不存在或已下架');
                        }
                        if ($material->inventory < intval($item['num'])) {
                            throw new BadRequestException($item['name'] . "库存不足");
                        }
                        $goods['money'] = bcadd($goods['money'], bcmul($material->price, intval($item['num']), 2), 2);
                    }
                }
                return $goods;
            })->values();
        }
        return $this->attributes['setMealData'];
    }
}
