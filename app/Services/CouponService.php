<?php

namespace App\Services;

use AlibabaCloud\SDK\Iot\V20180120\Models\ListAnalyticsDataRequest\condition;
use App\Events\CouponEvent;
use App\Models\ChannelConfig;
use App\Models\TopLevel;
use App\Traits\ResourceTrait;
use App\Models\Config;
use App\Models\Coupon\Activity;
use App\Models\Coupon\Coupon;
use App\Models\Coupon\MemberCoupon;
use App\Models\Member;
use App\Models\Member\Address;
use App\Models\OpenWechatAuth;
use App\Models\Store;
use App\Models\StoreConfig;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

use function PHPSTORM_META\map;

class CouponService
{

    /**
     * 领取
     */
    public static function receive($couponId, $userId)
    {
        $coupon = Activity::where('state', 1)->where('endTime', '>=', date("Y-m-d H:i:s", time()))->where('id', $couponId)->first();
        if (empty($coupon)) {
            throw new BadRequestException('优惠券活动不存在或已结束');
        }
        if ($coupon->inventoryLimit['userLimitSwitch'] == 1 && $coupon->inventory <= 0) {
            throw new BadRequestException('优惠活动库存不足');
        }
        $limitKey = "activityCoupon:userlimit:{$couponId}{$userId}";
        $userlimit = Cache::get($limitKey, 0);
        if ($coupon->inventoryLimit['userLimitSwitch'] == 1 && $userlimit > 0 && $userlimit >= $coupon->inventoryLimit['userLimit']) {
            throw new BadRequestException('领取已达上限');
        }
        $dayLimitKey = "activityCoupon:userDaylimit:{$couponId}" . date("Ymd") . ":{$userId}";
        $dayLimit = Cache::get($dayLimitKey, 0);
        if ($coupon->inventoryLimit['userDaySwitch'] == 1 && $userlimit > 0 && $dayLimit > $coupon->inventoryLimit['userDaylimit']) {
            throw new BadRequestException('今日领取已达上限');
        }
        $user = Member::find($userId);
        foreach ($coupon->couponIds as $key => $v) {
            $coupon = Coupon::where('state', 1)->find($v['id']);
            if($coupon->userType==2){
                if(empty($user->vipCard)){
                    continue;
                }
            }
            if($coupon->userType==3){
                $commonElements = array_intersect($user->labelId, $coupon->tags);
                if (count($commonElements) <= 0) {
                    continue;
                }
            }
            if($coupon->userType==4){
                $commonElements = array_intersect($user->groupId, $coupon->groupId);
                if (count($commonElements) <= 0) {
                    continue;
                }
            }
            if ($coupon->inventoryLimit['userLimitSwitch'] == 1 && $coupon->inventoryLimit['userLimit']> 0) {
                $userlimit=MemberCoupon::where('couponId',$v['id'])->where('userId',$userId)->where('state',1)->count();
                if($userlimit>=$coupon->inventoryLimit['userLimit']){
                     continue;
                }
            }
            if ($coupon->inventoryLimit['userDaySwitch'] == 1 && $coupon->inventoryLimit['userDay']>0) {
                $today = Carbon::today();
                $userDaylimit=MemberCoupon::where('couponId',$v['id'])->where('userId',$userId)->where('state',1)
                    ->whereDate('startTime',$today)->count();
                if($userDaylimit>=$coupon->inventoryLimit['userDay']){
                    continue;
                }
            }
            if ($coupon) {
                $num = empty($v['num']) ? 1 : $v['num'];
                $miniData[] = [
                    'toUser' => $user->getMiniOpenId(),
                    'uniacid' => $coupon->uniacid,
                    'name' => $coupon->name,
                    'num' => $num,
                    'type' => $coupon->typeFormat,
                    'time' => $coupon->timeArr['endTime']
                ];
                for ($i = 0; $i < $num; $i++) {
                    $couponData[] =
                        array_merge([
                            'uniacid' => $coupon->uniacid,
                            'userId' => $userId,
                            'orderId' => 0,
                            'couponId' => $coupon->id,
                            'channel' => 1,
                            'state' => 1,
                            'sort' => 0,
                            'sn' => CouponRandInt(10),
                            'storeId' => $coupon->storeId,
                            'startTime' => $coupon->timeArr['startTime'],
                            'endTime' => $coupon->timeArr['endTime'],
                            'created_at' => date("Y-m-d H:i:s", time()),
                            'updated_at' => date("Y-m-d H:i:s", time()),
                        ], [
                            'source' => "couponActivity:{$coupon->id}"
                        ]);
                }
                $coupon->subInventory($num);
            }
        }
        if ($couponData) {
            MemberCoupon::insert($couponData);
            foreach ($miniData as $key => $v) {
                Event(new CouponEvent($v, 'coupon'));
            }
        }
        Cache::increment($dayLimitKey, 1);
        Cache::increment($limitKey, 1);
        return true;
    }

    /**
     * 发放
     */
    public static function issue($couponId = [], $userId, $channel = 2, $options = [])
    {
        try {
            if (empty($couponId) || !is_array($couponId)) {
                return false;
            }
            $user = Member::find($userId);
            $miniData = [];
            foreach ($couponId as $key => $v) {
                $coupon = Coupon::where('state', 1)->find($v['id']);
                if ($coupon) {
                    $num = empty($v['num']) ? 1 : $v['num'];
                    $miniData[] = ['toUser' => $user->getMiniOpenId(), 'uniacid' => $coupon->uniacid, 'name' => $coupon->name, 'num' => $num, 'type' => $coupon->typeFormat, 'time' => $coupon->timeArr['endTime']];
                    for ($i = 0; $i < $num; $i++) {
                        $couponData[] =
                            array_merge([
                                'uniacid' => $coupon->uniacid,
                                'userId' => $userId,
                                'orderId' => 0,
                                'couponId' => $coupon->id,
                                'channel' => $channel,
                                'state' => 1,
                                'sort' => 0,
                                'sn' => CouponRandInt(10),
                                'storeId' => $coupon->storeId,
                                'startTime' => $coupon->timeArr['startTime'],
                                'endTime' => $coupon->timeArr['endTime'],
                                'created_at' => date("Y-m-d H:i:s", time()),
                                'updated_at' => date("Y-m-d H:i:s", time()),
                            ], $options);
                        //$coupon->subInventory(1);
                    }
                }
            }
            if ($couponData) {
                MemberCoupon::insert($couponData);
                foreach ($miniData as $key => $v) {
                    Event(new CouponEvent($v, 'coupon'));
                }
            }
            return true;
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return false;
        }
    }
}
