<?php

namespace App\Models\PersionPay;

use App\Enums\SceneEnum;
use App\Models\BaseModel;
use App\Models\Material;
use App\Models\Member;
use App\Models\Member\Address;
use App\Models\Member\UserPayStore;
use App\Models\OrderCollect\OrderCollect;
use App\Models\PayGift\PayGift;
use App\Models\Store;
use App\Models\Store\StoreGoods;
use App\Models\Store\StoreGoodsSku;
use App\Services\AddressGeoService;
use App\Services\ConfigService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Request;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class Checkout extends BaseModel
{
    protected $fillable = [
        'uniacid', 'storeId', 'couponId', 'remarks', 'sellMoney', 'userId', 'remarks', 'scene', 'score'
    ];
    public $_integralSetting;
    public $_expSetting;
    public $_exp;
    public $_discounts = [];
    public $_discountMoney = 0;
    public $_couponList = [];
    public $_couponId;
    public $_payGift;
    public $_integral;
    public $_newSub;
    protected $appends = [
        'integralFormat', 'expFormat', 'integral', 'exp', 'discountMoney', 'money', 'discounts', 'couponList', 'couponCount', 'newSub', 'fullSub'
    ];
    protected $attributes = [
        'scene' => 5
    ];
    /**
     * 积分设置
     */
    public function getIntegralSettingAttribute()
    {
        if (!$this->_integralSetting) {
            $this->_integralSetting = ConfigService::getChannelConfig('integralSetting', $this->uniacid, $this->store->isolateStore);
        }
        return $this->_integralSetting;
    }

    public function getFullSubAttribute()
    {
        $discount = $this->store->fullsub;
        if (empty($discount) || !in_array($this->scene, $discount->scenario)) {
            return null;
        }
        if ($this->diningType != 0) {
            $deliveryMoney = 0;
        } else {
            $deliveryMoney =  $this->deliveryMoney['state'] == 0  ? 0 : $this->deliveryMoney['money'];
        }
        $model = new FullSub(
            [
                'discount' => $discount,
                "goodsMoney" => $this->sellMoney,
                "uniacid" => $this->uniacid,
                "storeId" => $this->storeId,
                'scene' => $this->scene,
                'boxMoney' => 0,
                'deliveryMoney' => 0,
                'materialMoney' => 0
            ]
        );
        return $model->discounts ?? null;
    }


    /**
     * 成长值设置
     */
    public function getExpSettingAttribute()
    {
        if (!$this->_expSetting) {
            $this->_expSetting = ConfigService::getChannelConfig('growthSetting', $this->uniacid, $this->store->isolateStore);
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

    public function user()
    {
        return $this->hasOne(Member::class, 'id', 'userId');
    }

    /**
     * 赠送积分
     */
    public function getIntegralAttribute()
    {
        if (!$this->_integral) {
            $config = $this->integralSetting;
            if (empty($config) || !$this->user->mobile) {
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
                $this->_integral = round($int  * $power);
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
            if (empty($config) || !$this->user->mobile) {
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
                        $int = $config->oneOrderGive;
                    }
                }
                $this->_exp = round($int  * $power);
            }
        }
        return $this->_exp;
    }

    public function getNewSubAttribute()
    {
        if (empty($this->_newSub)) {
            $discount = $this->store->newSub;
            if (empty($discount) || !in_array($this->scene, $discount->scenario)) {
                return null;
            }
            if ($discount->goodsType == 1) {
                $goods = true;
            }
            if (empty($goods)) {
                return null;
            }
        }
        return $discount->money;
    }





    public function getPayNumAttribute()
    {
        $key = "payNum:{$this->uniacid}:{$this->storeId}:{$this->userId}";
        if (Cache::has($key)) {
            return Cache::get($key) + 1;
        }
        return  1;
    }

    public function getMoneyAttribute()
    {
        $money = bcsub($this->sellMoney, $this->discountMoney, 2);
        if ($money <= 0) {
            return 0.00;
        }
        return $money;
    }


    public function getDiscountMoneyAttribute()
    {
        if (empty($this->_discountMoney)) {
            $this->_discountMoney = bcmul(collect($this->discounts)->sum('money'), 1, 2);
        }
        return $this->_discountMoney;
    }



    public function getDiscountsAttribute()
    {
        if (in_array($this->score, [10, 11])) {
            return [];
        }
        if (!$this->_discounts) {
            if (!empty($this->couponId)) {
                $coupon = collect($this->couponList['true'])->where('id', $this->couponId)->first();
                if ($coupon) {
                    if ($coupon['coupon']['couponType'] == 1) {
                        $discounts['coupon'] = ['activityId' => $coupon['id'], 'activityName' => $coupon['coupon']['name'], 'type' => 'coupon', 'money' => $coupon['money'], 'title' => "券"];
                    }
                }
            }
            if ($this->fullsub) {
                $discounts['fullsub'] = ['activityId' => $this->store->fullSub->id, 'activityName' => $this->store->fullSub->name, 'type' => 'fullsub', 'money' => $this->fullsub['sub'], 'title' => "减"];
            }
            if ($this->newSub) {
                $discounts['newSub'] = ['activityId' => $this->store->newSub->id, 'activityName' => $this->store->newSub->name, 'type' => 'newSub', 'money' => $this->newSub, 'title' => "新"];
            }
            $this->_discounts = $discounts;
        }
        return $this->_discounts;
    }

    public function getCouponListAttribute()
    {
        if (empty($this->_couponList)) {
            $model = new Coupon([
                'selectId' => $this->couponId,
                'storeId' => $this->storeId,
                'uniacid' => $this->uniacid,
                'userId' => $this->userId,
                'scene' => 5,
                'money' => $this->sellMoney
            ]);
            $this->_couponList = collect($model->couponData)->toArray();
        }
        return $this->_couponList;
    }

    public function getCouponCountAttribute()
    {
        return collect($this->couponList['true'])->count();
    }

    public function getPayGiftAttribute()
    {
        if (!$this->_payGift && $this->user->mobile) {
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
                ->where('money', "<=", $this->money)
                ->first();
        }
        return $this->_payGift;
    }
    public function getPayGiftIdAttribute()
    {
        return $this->payGift ? $this->payGift->id : 0;
    }

    public function store()
    {
        return $this->hasOne(Store::class, 'id', 'storeId');
    }
}
