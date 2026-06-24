<?php

namespace App\Models\TakeOut;

use App\Models\BaseModel;
use App\Models\Coupon\MemberCoupon;
use App\Models\Delivery\Rule;
use App\Models\Delivery\Store as DeliveryStore;
use App\Models\Material;
use App\Models\Member\Address;
use App\Models\Store;
use App\Models\Store\StoreGoods;
use App\Models\Store\StoreGoodsSku;
use Cache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class Coupon extends BaseModel
{
    protected $guarded = [];
    protected $_couponList;
    protected $_couponData;
    protected $appends = ['couponData'];
    public function getCouponListAttribute()
    {
        if (empty($this->_couponList)) {
            $type = $this->couponType;
            if ($this->carList)
                $couponList = MemberCoupon::whereHas('coupon')
                    ->where('uniacid', $this->uniacid)
                    ->where('userId', $this->userId)
                    ->where('state', 1)
                    ->groupBy('couponId')
                    ->orderBy('id', 'desc')
                    ->get();
            $this->_couponList = collect($couponList)->toArray();
        }
        return $this->_couponList;
    }

    public function getCouponDataAttribute()
    {
        if (empty($this->_couponData)) {
            $storeId = $this->storeId;
            $money = $this->money;
            $goods = $this->carList->goodsList;
            $selectId = $this->selectId;
            $scene = $this->scene;
            $this->_couponData = collect($this->couponList)->map(function ($coupon) use ($goods, $storeId, $money, $selectId, $scene) {
                $coupon['select'] = 0;
                $coupon['useState'] = 'true';
                if (strtotime($coupon['startTime']) > time()) {
                    $coupon['useState'] = 'false';
                    $coupon['msg'][] = $coupon['startTime'] . '后才可使用';
                }
                if (strtotime($coupon['endTime']) < time()) {
                    $coupon['useState'] = 'false';
                    $coupon['msg'][] = '优惠券已过期';
                }
                if (!in_array($scene, $coupon['coupon']['scenario'])) {
                    $coupon['useState'] = 'false';
                    $coupon['msg'][] = '当前场景不可使用';
                }
                if ($coupon['coupon']['storeType'] == 2 && !in_array($storeId, $coupon['coupon']['storeIds'])) {
                    $coupon['useState'] = 'false';
                    $coupon['msg'][] = '当前门店不可使用';
                }
                if ($coupon['coupon']['storeType'] == 3 && in_array($storeId, $coupon['coupon']['storeIds'])) {
                    $coupon['useState'] = 'false';
                    $coupon['msg'][] = '当前门店不可使用';
                }
                if ($coupon['coupon']['type'] == 4 &&  ($this->scene != 1 || $this->deliveryFree || $this->carList->deliveryMoney['money'] == 0)) {
                    $coupon['useState'] = 'false';
                    $coupon['msg'][] = '订单无配送费';
                }
                if ($coupon['coupon']['goodsType'] == 2) {
                    $goods = collect($goods)->whereIn('spuId', $coupon['coupon']['goodsIds'])->all();
                    if ($coupon['coupon']['rule']['disType'] == 5) {
                        $goods = collect($goods)->whereIn('spuId', $coupon['coupon']['goodsIds'])->all();
                    }
                }
                if ($coupon['coupon']['goodsType'] == 3) {
                    $goods = collect($goods)->whereNotIn('spuId', $coupon['coupon']['goodsIds'])->all();
                    if ($coupon['coupon']['rule']['disType'] == 5) {
                        $goods = collect($goods)->whereNotIn('spuId', $coupon['coupon']['goodsIds'])->all();
                    }
                }
                if ($coupon['coupon']['type'] == 5) {
                    if ($coupon['coupon']['rule']['disType'] == 1) {
                        $goods = [collect($goods)->whereIn('discountType',[0,10])->sortByDesc('price')->first()];
                    } else {
                        $goods = [collect($goods)->whereIn('discountType',[0,10])->sortBy('price')->first()];
                    }
                }
                $goodsMoney = collect($goods)->sum('money');
                if ($goodsMoney == 0) {
                    $coupon['useState'] = 'false';
                    $coupon['msg'][] = '当前商品不可使用';
                }
                $money = $goodsMoney;
                //计算减去会员价后，优惠券再去计算优惠金额
//                $discounts=$this->carList->discounts;
//                if($discounts['vipDiscount']){
//                    $vipDiscount=$discounts['vipDiscount'];
//                    $money=bcsub($money,$vipDiscount['money'],2);
//                }
                $materialMoney = collect($goods)->sum('materialMoney');
                $boxMoney = collect($goods)->sum('boxMoney');
                if (!in_array(1, $coupon['coupon']['threshold'] ?? [0])) {
                    $money = bcsub($money, $materialMoney ?? 0, 2);
                }
                if ($this->diningType == 2 && in_array(1, $coupon['coupon']['threshold'] ?? [0])) {
                    $money = bcadd($money, $boxMoney ?? 0, 2);
                }
                if (in_array(1, $coupon['coupon']['threshold'] ?? [0])) {
                    $money = bcadd($money, $this->carList->deliveryMoney['money'] ?? 0, 2);
                }
                $res = bcsub(bcmul($money, 100), bcmul($coupon['coupon']['startMoney'], 100));
                if ($coupon['coupon']['startSwitch'] == 1 &&  $res < 0) {
                    $coupon['useState'] = 'false';
                    $coupon['msg'][] = '下单金额未达到使用门槛';
                }

                if ($coupon['coupon']['type'] == 1) {
                    $discountMoney =  $coupon['coupon']['rule']['money'];
                    if ($goodsMoney < $discountMoney) {
                        $discountMoney = $goodsMoney;
                    }
                    $coupon['money'] = $discountMoney;
                }
                if ($coupon['coupon']['type'] == 2) {
                    $discountMoney = bcmul(bcdiv($money, 100, 4), intval($coupon['coupon']['rule']['discount'] * 10, 2), 2);
                    if ($discountMoney * 100 < 1) {
                        $discountMoney = 0;
                    }
                    $discountMoney = bcsub($money, $discountMoney, 2);
                    $coupon['money'] = $discountMoney;
                }
                if ($coupon['coupon']['type'] == 3) {
                    if ($coupon['coupon']['rule']['disType'] == 1) {
                        $goods = collect($goods)->sortByDesc('price')->first();
                    } else {
                        $goods = collect($goods)->sortBy('price')->first();
                    }
                    $coupon['money'] = $goods->discountPrice > 0 ? $goods->discountPrice : $goods->price;
                }
                if ($coupon['coupon']['type'] == 4) {
                    if ($coupon['coupon']['rule']['disContent'] == 1) {
                        $coupon['money'] = $this->carList->deliveryMoney['money'] ?? 0;
                    }
                    if ($coupon['coupon']['rule']['disContent'] == 3) {
                        $money = $coupon['coupon']['rule']['money'];
                        if ($money > $this->carList->deliveryMoney['money']) {
                            $money = $this->carList->deliveryMoney['money'] ?? 0;
                        }
                        $coupon['money'] = $money;
                    }
                }
                if ($coupon['coupon']['type'] == 5) {
                    if ($coupon['coupon']['rule']['disType'] == 1) {
                        $goods = collect($goods)->whereIn('discountType',[0,10])->sortByDesc('price')->first();
                    } else {
                        $goods = collect($goods)->whereIn('discountType',[0,10])->sortBy('price')->first();
                    }
                    if ($goods->money < $coupon['coupon']['rule']['money']) {
                        $coupon['useState'] = 'false';
                        $coupon['msg'][] = '金额未达到使用门槛';
                    }
                    $coupon['money'] = $coupon['coupon']['rule']['money'];
                    //$coupon['money'] = bcsub($goods->money, $coupon['coupon']['rule']['money'], 2);
                }
                $coupon['select'] = 0;
                if ($coupon['coupon']['id'] == $selectId) {
                    $coupon['select'] = 1;
                }
                return $coupon;
            })->sortByDesc('money')->groupBy('useState')->toArray();
        }
        return $this->_couponData;
    }
}
