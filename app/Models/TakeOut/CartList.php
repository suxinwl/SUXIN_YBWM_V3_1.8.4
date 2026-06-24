<?php

namespace App\Models\TakeOut;

use App\Enums\SceneEnum;
use App\Models\BaseModel;
use App\Models\Material;
use App\Models\Member;
use App\Models\Store;
use App\Models\Store\StoreGoods;
use App\Models\Store\StoreGoodsSku;
use App\Services\ConfigService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class CartList extends BaseModel
{
    public $_diningType;
    public $_money;
    public $_lineMoney;
    public $_discounts = [];
    public $_newSub;
    public $_discountMoney;
    public $_goodsMoney;
    public $_deliveryMoney;
    public $_vipFreeMail;
    public $_realtimeState;
    public $_vipDiscount = 0;
    protected $fillable = [
        'uniacid', 'storeId', 'userId', 'scene', 'lat', 'lng', 'diningType', 'addressId'
    ];

    protected $appends = [
        'money', 'boxMoney', 'categoryIds', 'realtimeState', 'goodsCount', 'fullsub', 'deliveryMoney', 'lineMoney', 'newSub', 'discounts', 'discountMoney', 'sellMoney'
    ];

    protected $hidden = [
        'store'
    ];

    protected $with = [
        'goodsList'
    ];

    /**
     * Undocumented function
     *商品列表
     * @return void
     */

    // public function getDiningTypeAttribute()
    // {
    //     if(!$this->_diningType){
    //         $this->_diningType = Request()->diningType ?? $this->diningType ?? 0;
    //     }
    //    return $this->_diningType;
    // }

    public function goodsList()
    {

        $q=$this->hasMany(Cart::class, 'userId', 'userId')->with(['goods' => function ($q) {
            return $q->select(['id', 'name', 'logo', 'vipPriceSwitch'])
                ->where('uniacid', $this->uniacid);
        }])->where('uniacid', $this->uniacid)->where("storeId", $this->storeId);
        if($this->diningType==30){
            $q->where('diningType', $this->diningType);
        }
        return $q;
    }

    public function store()
    {
        return $this->hasOne(Store::class, 'id', 'storeId');
    }
    public function user()
    {
        return $this->hasOne(Member::class, 'id', 'userId');
    }

    public function getCategoryIdsAttribute()
    {
        return collect($this->goodsList)->pluck('category')->flatten(1)->pluck('catId')->unique()->values();
    }

    /**
     * Undocumented function
     *原价总金额
     * @return void
     */
    public function getSellMoneyAttribute()
    {
        return bcmul(collect($this->goodsList)->sum('sellMoney'), 1, 2);
    }

    public function getGoodsDiscountMoneyAttribute()
    {
        return bcmul(collect($this->goodsList)->sum('discountMoney'), 1, 2);
    }

    public function getGoodsMoneyAttribute()
    {
        if (empty($this->_goodsMoney)) {
            $goodsMoney = bcmul(collect($this->goodsList)->sum('money'), 1, 2);
            $fullSub = $this->fullsub['sub'] ?? 0;
            $newSub = $this->newSub ?? 0;
            $vipDiscount = $this->vipDiscount ?? 0;

            $res = ConfigService::getChannelConfig('basicSetting', $this->uniacid);
            $mutex=$res['mutex'];
            if($mutex==1){
                $goodsMoney = bcsub($goodsMoney, $vipDiscount, 2);
            }else{
                $goodsMoney = bcsub($goodsMoney, $fullSub, 2);
                $goodsMoney = bcsub($goodsMoney, $newSub, 2);
                $goodsMoney = bcsub($goodsMoney, $vipDiscount, 2);
            }
            $goodsMoney  = $goodsMoney < 0 ? 0 : $goodsMoney;
            $this->_goodsMoney =  bcmul($goodsMoney, 1, 2);
        }
        return $this->_goodsMoney;
    }

    public function getRealtimeStateAttribute()
    {

        if (!$this->_realtimeState) {
            $this->_realtimeState = $this->store->realtimeState;

            if ($this->_realtimeState == 3) {

                if ($this->scene == SceneEnum::SCENE_TAKEOUT && $this->store->storeSetting['outAppoint'] == 1 && in_array(2, $this->store->storeSetting['outStepTime'] ?? [0])) {
                    $this->_realtimeState = 4;
                }
                if ($this->scene == SceneEnum::SCENE_INSTORE && $this->store->storeSetting['takeSubscribe'] == 1 && in_array(2, $this->store->storeSetting['takeAppointTimeStep'] ?? [0])) {
                    $this->_realtimeState = 4;
                }

            }
            if ($this->_realtimeState == 1) {
                if ($this->scene == SceneEnum::SCENE_TAKEOUT) {
                    if (
                        $this->store->storeSetting['outAppoint'] == 1
                        && empty($this->store->storeSetting['outStepTime'] ?? [])
                        && $this->store_setting['outCloseImmediateDine'] == 0
                    ) {
                        $this->_realtimeState = 3;
                    } elseif (
                        $this->store->storeSetting['outAppoint'] == 1
                        && empty($this->store->storeSetting['outStepTime'] ?? [])
                    ) {
                        $this->_realtimeState = 5;
                    } elseif (
                        $this->store->storeSetting['outAppoint'] == 1
                        && !empty($this->store->storeSetting['outStepTime'] ?? [])
                        && $this->store->storeSetting['outCloseImmediateDine'] == 1
                    ) {
                        $this->_realtimeState = 4;
                    } else if (
                        $this->store->storeSetting['outAppoint'] == 1
                        && !empty($this->store->storeSetting['outStepTime'] ?? [])
                        && $this->store->storeSetting['outCloseImmediateDine'] == 0
                    ) {
                        if (!in_array(2, $this->store->storeSetting['outStepTime'])) {
                            $this->_realtimeState = 1;
                        }
                    } else if (
                        $this->store->storeSetting['outAppoint'] == 1
                        && !empty($this->store->storeSetting['outStepTime'] ?? [])
                        && $this->store->storeSetting['outCloseImmediateDine'] == 1
                    ) {
                        $this->_realtimeState = 3;
                    }
                } elseif ($this->scene == SceneEnum::SCENE_INSTORE) {

                    if (
                        $this->store->storeSetting['takeSubscribe'] == 1
                        && empty($this->store->storeSetting['takeAppointTimeStep'] ?? [])
                        && $this->store->storeSetting['takeCloseEat'] == 2
                    ) {
                        $this->_realtimeState = 3;
                    } elseif (
                        $this->store->storeSetting['takeSubscribe'] == 1
                        && empty($this->store->storeSetting['takeAppointTimeStep'] ?? [])
                        && $this->store->storeSetting['takeCloseEat'] == 1
                    ) {
                        $this->_realtimeState = 5;
                    } elseif (
                        $this->store->storeSetting['takeSubscribe'] == 1
                        && !empty($this->store->storeSetting['takeAppointTimeStep'] ?? [])
                        && $this->store->storeSetting['takeCloseEat'] == 0
                    ) {
                        if (!in_array(2, $this->store->storeSetting['outStepTime'])) {
                            $this->_realtimeState = 1;
                        }
                    } elseif (
                        $this->store->storeSetting['takeSubscribe'] == 1
                        && !empty($this->store->storeSetting['takeAppointTimeStep'] ?? [])
                        && $this->store->storeSetting['takeCloseEat'] == 1
                    ) {
                        $this->_realtimeState = 4;
                    }
                }
            }
        }
        return $this->_realtimeState;
    }

    /**
     * Undocumented function
     *总金额   = 商品总金额 + 总包装费 + 配送费
     * @return void
     */
    public function getMoneyAttribute()
    {
        if (empty($this->_money)) {
            $this->_money = $this->goodsMoney;
        }
        return $this->_money;
    }

    /**
     * Undocumented function
     *总金额   = 商品总金额 + 总包装费
     * @return void
     */
    public function getLineMoneyAttribute()
    {
        if (!$this->_lineMoney) {
            $this->_lineMoney = $this->sellMoney;
        }
        return $this->_lineMoney;
    }


    /**
     * Undocumented function
     *总包装费
     * @return void
     */
    public function getBoxMoneyAttribute()
    {
        if ($this->diningType == 2||$this->diningType == 30) {
            return 0;
        }
        return bcmul(collect($this->goodsList)->sum('boxMoney'), 1, 2);
    }

    /**
     * Undocumented function
     *加料总金额
     * @return void
     */
    public function getMaterialMoneyAttribute()
    {
        return bcmul(collect($this->goodsList)->sum('materialMoney'), 1, 2);
    }


    /**
     * Undocumented function
     *商品数量
     * @return void
     */
    public function getGoodsCountAttribute()
    {
        return collect($this->goodsList)->sum('num');
    }

    /**
     * Undocumented function
     *运费
     * @return void
     */

    public function getDeliveryMoneyAttribute()
    {
        if (!$this->_deliveryMoney) {
            if ($this->diningType == 0) {
                $model = new Delivery(
                    [
                        "goodsCount" => $this->goodsCount,
                        "goodsMoney" => $this->sellMoney,
                        "uniacid" => $this->uniacid,
                        "storeId" => $this->storeId,
                        "lat" =>   Request()->lat,
                        "lng" => Request()->lng,
                        "addressId" => Request()->addressId ?? $this->addressId,
                        'diningType' => $this->diningType
                    ]
                );
                $model =  $model->toArray();
            } else {
                $model = ['state' => 1];
            }

            $oneDeliverySwitch = true;
            foreach ($this->goodsList as $key => $goods) {
                $spu = $goods->spu;
                if ($spu->spu->oneDeliverySwitch == 0) {
                    $oneDeliverySwitch = false;
                    break;
                }
            }
            if ($this->scene == 1 &&  $oneDeliverySwitch) {
                $model['state'] = 0;
                $model['msg'] = "单点不配送";
            }
            if ($this->diningType == 1) {
                $model['money'] = 0;
                $model['startRule'] = [
                    'type' => $this->store->storeSetting['takeLiftState'],
                    'value' => $this->store->storeSetting['takeLiftPrice'] ?? 0,
                    'value2' => $this->store->storeSetting['takeLiftNum'] ?? 0,
                ];
                if ($model['startRule']['type'] == 1 && $model['startRule']['value'] > $this->money) {
                    $model['state'] = 0;
                    $model['msg'] = "还差" . bcsub($model['startRule']['value'], $this->money, 2) . "元起提";
                }
                if ($model['startRule']['type'] == 2 && $model['startRule']['value2'] > $this->goodsCount) {
                    $model['state'] = 0;
                    $model['msg'] = "还差" . bcsub($model['startRule']['value2'], $this->goodsCount) . "件商品起提";
                }
            }
            $this->_deliveryMoney = $model;
        }
        return $this->_deliveryMoney;
    }


    public function getSceneAttribute()
    {
        if ($this->diningType == 0) {
            return  SceneEnum::SCENE_TAKEOUT;
        }

        if ($this->diningType == 30) {
            return  SceneEnum::SCENE_ExpressDelivery;
        }
        return SceneEnum::SCENE_INSTORE;
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
                "goods" => $this->goodsList,
                "uniacid" => $this->uniacid,
                "storeId" => $this->storeId,
                'scene' => $this->scene,
                'boxMoney' => $this->boxMoney,
                'deliveryMoney' => $deliveryMoney,
                'materialMoney' => $this->materialMoney
            ]
        );
        return $model->discounts ?? null;
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
            if ($discount->goodsType == 2) {
                $goods =   collect($this->goodsList)->filter(function ($goods, $key) use ($discount) {
                    return  in_array($goods->spuId, $discount->goodsIds);
                })->count();
            }
            if ($discount->goodsType == 3) {
                $goods =   collect($this->goodsList)->filter(function ($goods, $key) use ($discount) {
                    return  !in_array($goods->spuId, $discount->goodsIds);
                })->count();
            }
            if (empty($goods)) {
                return null;
            }
        }
        return $discount->money;
    }

    public function getDiscountMoneyAttribute()
    {
        if (empty($this->_discountMoney)) {
            $this->_discountMoney = bcadd(collect($this->discounts)->sum('money'), $this->goodsDiscountMoney, 2);
        }
        return $this->_discountMoney;
    }


    public function getVipFreeMailAttribute()
    {
        if (empty($this->_vipFreeMail) && $this->deliveryMoney > 0) {
            $vip = $this->user->vip;
            if ($vip->freeMailSwitch && $this->goodsMoney > $vip->freeMailLimit) {
                $this->_vipFreeMail = $this->deliveryMoney;
            }
        }
        return $this->_vipFreeMail;
    }

    public function getDeliveryFreeAttribute()
    {
        if (empty($this->_vipFreeMail) && $this->deliveryMoney > 0) {
            $vip = $this->user->vip;
            if ($vip->freeMailSwitch && $this->goodsMoney > $vip->freeMailLimit) {
                $this->_vipFreeMail = $this->deliveryMoney;
            }
        }
        return $this->_vipFreeMail;
    }

    public function getDiscountsAttribute()
    {
        $res = ConfigService::getChannelConfig('basicSetting', $this->uniacid);
        $mutex=$res['mutex'];
        if($mutex==1){
            if (empty($this->_discounts)) {

                if ($this->vipDiscount) {
                    $this->_discounts['vipDiscount'] = ['activityId' => 0, 'activityName' => "会员{$this->user->vip->discount}折", 'type' => 'vipDiscount', 'money' => $this->vipDiscount, 'title' => "折"];
                }else{
                    if ($this->fullsub) {
                        $this->_discounts['fullsub'] = ['activityId' => $this->store->fullSub->id, 'activityName' => $this->store->fullSub->name, 'type' => 'fullsub', 'money' => $this->fullsub['sub'], 'title' => "减"];
                    }
                    if ($this->newSub) {
                        $this->_discounts['newSub'] = ['activityId' => $this->store->newSub->id, 'activityName' => $this->store->newSub->name, 'type' => 'newSub', 'money' => $this->newSub, 'title' => "新"];
                    }
                }
                // if ($this->VipDiscount) {
                //     $this->_discounts['vipFreeMail'] = ['activityId' => 0, 'activityName' => '', 'type' => 'newSub', 'money' => $this->vipFreeMail, 'title' => "会员免配送费"];
                // }
            }
        }else{
            if (empty($this->_discounts)) {
                if ($this->fullsub) {
                    $this->_discounts['fullsub'] = ['activityId' => $this->store->fullSub->id, 'activityName' => $this->store->fullSub->name, 'type' => 'fullsub', 'money' => $this->fullsub['sub'], 'title' => "减"];
                }
                if ($this->newSub) {
                    $this->_discounts['newSub'] = ['activityId' => $this->store->newSub->id, 'activityName' => $this->store->newSub->name, 'type' => 'newSub', 'money' => $this->newSub, 'title' => "新"];
                }
                if ($this->vipDiscount) {
                    $this->_discounts['vipDiscount'] = ['activityId' => 0, 'activityName' => "会员{$this->user->vip->discount}折", 'type' => 'vipDiscount', 'money' => $this->vipDiscount, 'title' => "折"];
                }
                // if ($this->VipDiscount) {
                //     $this->_discounts['vipFreeMail'] = ['activityId' => 0, 'activityName' => '', 'type' => 'newSub', 'money' => $this->vipFreeMail, 'title' => "会员免配送费"];
                // }
            }
        }

        return $this->_discounts;
    }

    public function getVipDiscountAttribute()
    {
        $day=date('w');
        $check=$this->user->vip->weekArr?in_array($day,$this->user->vip->weekArr):true;
        if($check){
            if ($this->user->vip->discountSwitch == 1) {
                $discountGoods = collect($this->goodsList)->where('discountType', ">", 0)->first();
                $goodsMoney  = collect($this->goodsList)->where('discountType', 0)->where('goods.vipPriceSwitch', 1)->sum('sellMoney');
                if (empty($discountGoods) && $goodsMoney > 0) {
                    $this->_vipDiscount = bcmul((100 - bcmul($this->user->vip->discount, 10, 2)), bcdiv($goodsMoney, 100, 4), 2);
                }
            }
        }
        return $this->_vipDiscount;
    }
}
