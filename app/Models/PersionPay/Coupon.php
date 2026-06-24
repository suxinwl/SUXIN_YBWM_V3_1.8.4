<?php

namespace App\Models\PersionPay;

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
            $uniacid = $this->uniacid;

            $couponList = MemberCoupon::whereHas('coupon')
                ->where('uniacid', $this->uniacid)
                ->where('userId', $this->userId)
                ->where('state', 1)
                ->where('startTime', "<=", date("Y-m-d H:i:s", time()))
                ->where('endTime', ">=", date("Y-m-d H:i:s", time()))
                ->groupBy('couponId')
                ->whereHas('coupon', function ($q)use($uniacid) {
                    return $q->whereIn('type', [1, 2])->where('uniacid',$uniacid);
                })
                ->get();
            $this->_couponList = collect($couponList)->toArray();
        }
        return $this->_couponList;
    }

    public function getCouponDataAttribute()
    {
        $storeId = $this->storeId;
        $money = $this->money;
        $selectId = $this->selectId;
        $scene = $this->scene;
        $goods = [];
        return collect($this->couponList)->map(function ($coupon) use ($goods, $storeId, $money, $selectId, $scene) {
            $coupon['useState'] = 'true';
            if (strtotime($coupon['startTime']) > time()) {
                $coupon['useState'] = 'false';
                $coupon['msg'][] = $coupon['startTime'] . '后才可使用';
            }
            if (strtotime($coupon['endTime']) < time()) {
                $coupon['useState'] = 'false';
                $coupon['msg'][] = '优惠券已过期';
            }
            if ($coupon['coupon']['storeType'] == 2 && !in_array($storeId, $coupon['coupon']['storeIds'])) {
                $coupon['useState'] = 'false';
                $coupon['msg'][] = '当前门店不可使用';
            }
            if ($coupon['coupon']['storeType'] == 3 && in_array($storeId, $coupon['coupon']['storeIds'])) {
                $coupon['useState'] = 'false';
                $coupon['msg'][] = '当前门店不可使用';
            }
            if (!in_array($scene,$coupon['coupon']['scenario'])) {
                $coupon['useState'] = 'false';
                $coupon['msg'][] ='当前场景不可使用';
            }
            if ($coupon['coupon']['startSwitch'] == 1 && $money < $coupon['coupon']['startMoney']) {
                $coupon['useState'] = 'false';
                $coupon['msg'][] = '下单金额未达到使用门槛';
            }
            $goodsMoney = $money;
            if ($coupon['coupon']['type'] == 1) {
                $discountMoney =  $coupon['coupon']['rule']['money'];
                if ($goodsMoney < $discountMoney) {
                    $discountMoney = $goodsMoney;
                }
                $coupon['money'] = $discountMoney;
            }
            if ($coupon['coupon']['type'] == 2) {
                $discountMoney = bcmul(bcdiv($goodsMoney, 100, 4), intval($coupon['coupon']['rule']['discount'] * 10, 2), 2);
                if ($discountMoney * 100 < 1) {
                    $discountMoney = 0;
                }
                $discountMoney = bcsub($goodsMoney, $discountMoney, 2);
                $coupon['money'] = $discountMoney;
            }
            $coupon['select'] = 0;
            if ($coupon['id'] == $selectId) {
                $coupon['select'] = 1;
            }
            return $coupon;
        })->sortByDesc('money')->groupBy('useState')->toArray();
    }
}
