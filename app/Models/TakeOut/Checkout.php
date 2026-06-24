<?php

namespace App\Models\TakeOut;

use App\Enums\SceneEnum;
use App\Models\BaseModel;
use App\Models\EquityCard\Member;
use App\Models\Material;
use App\Models\Member\Address;
use App\Models\Member\UserPayStore;
use App\Models\OrderCollect\OrderCollect;
use App\Models\Partner;
use App\Models\PayGift\PayGift;
use App\Models\Store;
use App\Models\Store\StoreGoods;
use App\Models\Store\StoreGoodsSku;
use App\Models\TradeIn\Activity;
use App\Services\AddressGeoService;
use App\Services\ConfigService;
use App\Services\MapService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use App\Models\ChannelConfig;
use function App\Models\Wechat\Pay\validate;

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
    public $_autoReceive;
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
    public $_deliveryModel;
    public $_deliveryModeSub;
    public $_deliveryFree = false;
    public $_partner;
    public $_tradeinGoodsList = [];
    public $_tradeinGoodsData;
    protected $fillable = [
        'uniacid', 'storeId', 'tradeinGoodsId', 'partnerId', 'reqAddressId', 'couponId', 'userId', 'contacts', 'diningType', 'notes', 'serverTime', 'mobile', 'addressId'
    ];
    protected $appends = [
        'serviceMoneyArr','tradeinGoodsList', 'tradeinGoodsData', 'partner', 'payNum', 'goodsNum', 'deliverySub', 'orderCollect', 'collectNum', 'realtimeState', 'collectId', 'payGiftId', 'payGift', 'couopnList', 'discounts', 'couponCount', 'goodsMoney', 'discountMoney', 'autoReceive', 'nextPrintTime', 'expFormat', 'expName', 'exp', 'integralFormat', 'integralName', 'integral', 'deliveryMoney', 'address', 'appointment', 'scene', 'goodsList', 'delivery', 'boxMoney', 'sellMoney', 'money', 'reservationTime'
    ];
    protected $attribute = [
        'couponId' => 0
    ];
    public function getCarListAttribute()
    {
        if (!$this->_cartlist) {
            $model = new CartList([
                "uniacid" => $this->uniacid,
                'storeId' => $this->storeId,
                'userId' => $this->userId,
                'diningType' => $this->diningType,
                'addressId' => $this->addressId,
            ]);
            if (empty($model->goodsCount)) {
                throw new BadRequestException('请先添加商品');
            }
            $this->_cartlist =  $model;
        }
        return $this->_cartlist;
    }


    public function getContactsAttribute()
    {

        if (!$this->_contacts) {
            $this->__contacts =  Request()->contacts ?? null;
        }
        return $this->__contacts;
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

    /**
     * 用户地址Id
     */
    public function getAddressIdAttribute($value)
    {

        if ($this->attributes['addressId']) {
            return  $this->attributes['addressId'];
        }
        if ($this->diningType ==30) {
            return 0;
        }
        if ($this->addressList) {
            $address =  collect($this->addressList)->where('disable', 0)->first();
            if (empty($address)) {
                return 0;
            }
            return $address->id;
        }
    }


    /**
     * 用户地址Id
     */

    public function getDefaultAddress()
    {
        if ($this->addressList) {
            $address =  collect($this->addressList)->where('disable', 0)->first();
            if (empty($address)) {
                return 0;
            }
            return $address->id;
        }
    }


    public function getAddressAttribute()
    {
        $this->getDefaultAddress();

        return collect($this->addressList)->where('id', $this->addressId)->first();
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
        return  bcadd(bcadd(bcadd($this->carList->sellMoney, $this->deliveryMoney, 2), $this->boxMoney, 2), $this->tradeinSellMoney, 2);
    }

    /**
     * 划线价
     */
    public function getLineMoneyAttribute()
    {
        return  $this->sellMoney;
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
        $money=bcadd(bcadd($this->goodsMoney, bcsub($this->deliveryMoney, $this->deliveryDiscounts, 2), 2), $this->boxMoney, 2);
        $serviceMoney=$this->serviceMoneyArr['service_money'];
        $money=bcadd($money,$serviceMoney,2);
        return $money;
    }

    /**
     * 商品价格
     */

    public function getGoodsMoneyAttribute()
    {

        $goodMoney =  bcsub(bcadd($this->carList->goodsMoney, $this->tradeinMoney, 2), $this->goodsDiscounts, 2);
        if ($goodMoney <= 0) {
            $goodMoney = 0;
        }
        return  $goodMoney;
    }

    /**
     * 配送
     */
    public function getDeliveryAttribute()
    {

        if (!$this->_delivery) {
            $delivery = $this->carList->deliveryMoney;
            $delivery['discount'] = 0;
            $delivery['origMoney'] = $this->carList->deliveryMoney['money'];

            if ($this->scene == 2||$this->scene == 30) {
                $delivery['minutes'] = 0;
            }
            if($this->scene == 30){
                $express_delivery = ConfigService::getChannelConfig('express_delivery', $this->uniacid);
                switch ($express_delivery['rule']){
                    case 1;
                        $goodsNum=$this->carList->goodsCount;
                        $delivery['money']=$goodsNum==1?$express_delivery['one_fee']:bcadd($express_delivery['one_fee'],bcmul(bcsub($goodsNum,1),$express_delivery['two_fee'],2),2);
                        $delivery['money']= $delivery['money']?:0;
                        break;
                    case 2;
                        $delivery['money']='0.01';
                        break;
                    case 3;
                        $delivery['money']=$express_delivery['fixed_price']?:0.00;
                        break;
                }
                $delivery['origMoney']=$delivery['money'];
                $delivery['discount'] = 0.00;
            }else{
                if ($this->deliveryFree) {
                    $delivery['discount'] = $delivery['origMoney'];
                    $delivery['money'] = 0;
                } elseif (isset($this->discounts['coupon']) && $this->discounts['coupon']['type'] == 'deliveryCoupon') {
                    $delivery['discount'] = $this->discounts['coupon']['money'];
                    $delivery['money'] = bcsub($delivery['origMoney'], $delivery['discount'], 2);
                } elseif (isset($this->discounts['deliverySub'])) {
                    $delivery['discount'] = $this->deliverySub['sub'];
                    $delivery['money'] = bcsub($delivery['origMoney'], $delivery['discount'], 2);
                }
            }
            $this->_delivery = $delivery;
        }



        return $this->_delivery;
    }

    public function getDeliveryModelAttribute()
    {
        if (!$this->_deliveryModel) {
            $this->_deliveryModel = collect(new Delivery(
                [
                    "goodsCount" => 0,
                    "goodsMoney" => 0,
                    "uniacid" => $this->uniacid,
                    "storeId" => $this->storeId,
                    "lat" =>   Request()->lat,
                    "lng" => Request()->lng,
                    'diningType' => $this->diningType
                ]
            ))->toArray();
        }
        return $this->_deliveryModel;
    }

    public function getRealtimeStateAttribute()
    {
        return $this->carList->realtimeState;
    }

    /**
     * 配送费
     */
    public function getDeliveryMoneyAttribute()
    {
        if ($this->scene == 2) {
            return "0.00";
        }
        return $this->delivery['origMoney'] ?? 0;
    }

    /**
     * 配送费
     */
    public function getDeliveryFreeAttribute()
    {
        if (!$this->_deliveryFree) {
            if ($this->scene == 2) {
                return $this->_deliveryFree;
            }
            $member = Member::where('userId', $this->userId)->where('endTime', '>', Carbon::now()->toDateTimeString())->first();
            if (!$member) {
                return $this->_deliveryFree;
            }
            $card = $member->equityCard;
            if (!$card->deliveryFreeSwitch || $this->goodsMoney < $card->deliveryFreeMoney) {
                return $this->_deliveryFree;
            }
            $this->_deliveryFree =  true;
        }
        return $this->_deliveryFree;
    }


    /**
     * 配送费
     */
    public function getDeliverySubAttribute()
    {

        if ($this->scene == 2) {
            return null;
        }
        if (!$this->_deliveryModeSub) {
            if (!isset($this->discounts['coupon']) || $this->discounts['coupon']['type'] != 'deliveryCoupon') {
                if ($this->store->deliverySub) {
                    $model = new DeliverySub([
                        'discount' => $this->store->deliverySub,
                        'deliveryMoney' => $this->carList->deliveryMoney['money'] ?? 0,
                        'money' => $this->carList->sellMoney
                    ]);
                    $this->_deliveryModeSub = $model->discounts;
                }
            }
            $vip = $this->user->vip;
            if ($vip->freeMailSwitch==1 && $this->goodsMoney > $vip->freeMailLimit) {
                $this->_deliveryModeSub = [
                    'activityId' => $vip->id,
                    'activityName' => '会员权益免配送费',
                    'type' => 'deliverySub',
                    'money' => $this->delivery['origMoney'],
                    'sub'=> $this->delivery['origMoney'],
                    'title' => "减"];
            }
            if($this->diningType == 30){
                $express_delivery = ConfigService::getChannelConfig('express_delivery', $this->uniacid);
                if($this->carList->sellMoney>=$express_delivery['money']){
                    $this->_deliveryModeSub = [
                        'activityId' =>0,
                        'activityName' => '快递配送包邮',
                        'type' => 'deliverySub',
                        'money' =>$this->delivery['origMoney']?:0,
                        'sub'=> $this->carList->sellMoney>=$express_delivery['money']?$this->delivery['origMoney']:0,
                        'title' => "减"
                    ];
                }

            }

        }
        return $this->_deliveryModeSub;
    }

    /**
     * 打包费
     */
    public function getBoxMoneyAttribute()
    {
        if ($this->diningType == 2||$this->diningType == 30) {
            return "0.00";
        }
        return bcadd($this->carList->boxMoney, $this->tradeinBoxMoney, 2);
    }

    /**
     * 门店信息
     */
    public function store()
    {
        return $this->hasOne(Store::class, 'id', 'storeId');
    }

    /**
     * 门店信息
     */
    public function getUserAttribute()
    {
        return $this->carList->user;
    }

    /**
     * 门店信息
     */
    public function getGoodsNumAttribute()
    {
        return $this->carList->goodsCount;
    }

    /**
     * 门店信息
     */
    public function getInBusinessAttribute()
    {
        if (!$this->_inBusiness) {
            $businessCheck = collect($this->store->storeSetting['takeAppointTimeStep'])->sum();
            if ($this->scene == SceneEnum::SCENE_TAKEOUT) {
                $businessCheck = collect($this->store->storeSetting['outStepTime'])->sum();
            }
            $checked =  $businessCheck  == 3 ? 1 : 0;
            $startDay = date("Y-m-d", time());
            foreach ($this->timeArr as $key => $v) {
                $startTime = strtotime($startDay . $v['start']);
                $endTime = strtotime($startDay . $v['end']);
                if ($businessCheck == 1) {
                    if (time() > $startTime && time() < $endTime) {
                        $checked = 1;
                    }
                }
                if ($businessCheck == 2) {
                    if (time() > $startTime && time() < $endTime) {
                        $checked = 0;
                    }
                }
            }
        }
        return  $checked;
    }


    /**
     * 处理营业时间
     */
    public function getTimeArrAttribute()
    {
        if (!$this->_timeArr) {
            $timeArr =  $this->store->timeArr;
            if (empty($timeArr)) {
                return false;
            }
            $lowStartTime = null;
            $timeKey = null;
            $lowEndTime = null;
            foreach ($timeArr as $key => $time) {
                if ($time['ciri'] && $time['end'] != '00:00') {
                    $timeArr[] = ['start' => $time['start'], 'end' => "00:00"];
                    $timeArr[] = ['start' => "00:00", 'end' => $time['end']];
                    unset($timeArr['times'][$key]);
                }
            }
            $timeArr = collect($timeArr)->sortBy('start')->toArray();
            foreach ($timeArr as $key => $time) {
                $startTime = strtotime(date("Y-m-d " . $time['start']));
                $endTime = $time['end'] == "00:00" ? strtotime(date("Y-m-d " . $time['end'])) + 3600 * 24  : strtotime(date("Y-m-d " . $time['end']));
                if ($lowStartTime == null && $lowEndTime == null) {
                    $lowStartTime = $startTime;
                    $lowEndTime = $endTime;
                    $timeKey = $key;
                    $timeData[$key] = $time;
                } else {
                    if ($startTime > $lowEndTime) {
                        $lowStartTime = $startTime;
                        $lowEndTime = $endTime;
                        $timeKey = $key;
                        $timeData[$key] = $time;
                    } else {
                        if ($endTime > $lowEndTime) {
                            $timeData[$timeKey] = ['start' => date("H:i", $lowStartTime), 'end' => $time['end']];
                            $lowEndTime =  $endTime;
                        }
                    }
                }
            }
            $this->_timeArr = $timeData;
        }
        return $this->_timeArr;
    }

    /**
     * 最长预约天数
     */

    public function getReservationDayAttribute()
    {
        $config = $this->store->storeSetting;
        if ($this->scene == 2) {
            return  $config['takeSubscribeNum'];
        }

        if ($this->scene == 1) {
            return  $config['outAppointDay'];
        }
    }



    /**
     * 预约时间间隔
     */
    public function getIntervalAttribute()
    {
        if (!$this->_interval) {
            $config = $this->store->storeSetting;
            if ($this->scene == 2) {
                // $this->_interval =  $config['takeMakeTime'] * $this->carList->goodsCount + $config['takeTimeStep'];
                $this->_interval =  $config['takeTimeStep'];
            }

            if ($this->scene == 1) {
                //$this->_interval =  $config['outMakeTime'] * $this->carList->goodsCount + $config['outTimeStep'] + $this->delivery['minutes'];
                $this->_interval =  $config['outTimeStep'];
            }
        }
        return $this->_interval;
    }

    /**
     * 预约时间数组
     */
    public function getReservationTimeAttribute()
    {
        if (!$this->_reservationTime) {
            $config = $this->store->storeSetting;
            if ($this->scene == 2) {
                if (!$config['takeSubscribe']) {
                    return [];
                }
                $addTime =   ($config['takeMakeTime'] * $this->carList->goodsCount) * 60;
            }
            if ($this->scene == 1) {
                if (!$config['outAppoint']) {
                    return [];
                }
                $addTime =   ($this->delivery['minutes'] + $config['outMakeTime'] * $this->carList->goodsCount) * 60;
            }
            for ($i = 0; $i <= $this->reservationDay; $i++) {
                $timeDataArr = [];
                $startDay = date("Y-m-d", strtotime("+{$i}day"));
                //$startDay = date("Y-m-d", strtotime($startDay) + 3600 * 24 * intval($config['startTime']));
                $toDay = ($startDay ==  date("Y-m-d", time())) ? 1 : 0;
                foreach ($this->timeArr as $key => $v) {
                    $itemTime = null;
                    if ($v['start'] == '00:00' && $v['end'] == "00:00") {
                        $startTime = strtotime($startDay . ' 00:00:00');
                        $endTime = strtotime($startDay . ' 23:59:59');
                    } else {
                        $startTime = strtotime($startDay . $v['start']);
                        if ($v['end'] == "00:00") {
                            $endTime = strtotime($startDay . '23:59:59');
                        } else {
                            $endTime = strtotime($startDay . $v['end']);
                        }
                    }
                    $timeNum = intval($this->interval) == 0 ? 0 : ceil(($endTime - $startTime) / 60 / intval($this->interval));
                    for ($j = 0; $j < $timeNum; $j++) {
                        if (empty($itemTime) || empty($data[$i]['timeArr'])) {
                            if (empty($itemTime)) {
                                if (time() > $startTime) {
                                    $itemTime = time() + intval($this->interval) + $addTime;
                                } else {
                                    $itemTime = $startTime + 60 * $j * intval($this->interval) + $addTime;
                                }
                            } else {
                                $itemTime = $itemTime +  $addTime;
                            }
                        } else {
                            $itemTime = $itemTime +  60 * intval($this->interval);
                        }
                        if ($itemTime > time() && $itemTime <= $endTime) {

                            if ($toDay) {
                                $data[$i]['title'] = '今天';
                                $data[$i]['timeArr'][] = ['title' => date("H:i", $itemTime), 'value' => date("Y-m-d H:i", $itemTime)];
                            } else {
                                $data[$i]['title'] = date("m-d", $itemTime);
                                $data[$i]['timeArr'][] = ['title' => date("H:i", $itemTime), 'value' => date("Y-m-d H:i", $itemTime)];
                            }
                            $timeDataArr[$i][$key] = 1;
                        }
                        if (($j + 1) == $timeNum) {
                            $itemTime = null;
                        }
                    }
                }
            }
            $this->_reservationTime = empty($data) ? [] : array_values($data);
        }
        return $this->_reservationTime;
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
            $this->_integralSetting = ConfigService::getChannelConfig('integralSetting', $this->uniacid, $this->store->isolateStore);
        }
        return $this->_integralSetting;
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


    /**
     *收货地址列表
     */
    public function getAddressListAttribute()
    {

        if (!$this->_addressList) {
            $distance = $this->deliveryModel['km'];
            $distanceSql = "round((st_distance(point(lng, lat), point({$this->store->lng}, {$this->store->lat}) ) / 0.0111),2)";
            $list = Address::select('*')->addselect(DB::raw($distanceSql . ' as distance'))->where('uniacid', $this->uniacid)
                ->where('userId', $this->userId)
                ->orderBy("distance", 'asc')
                ->orderBy('isDefault', 'desc')
                ->orderBy('id', 'desc')
                ->get();
            if($this->diningType ==0){
                $this->_addressList = collect($list)->map(function ($item) use ($distance) {
                    $item['disable'] = 0;
                    if ($item->distance > $distance) {
                        $item['disable'] = 1;
                    }
                    return $item;
                })->sortBy('disable')->all();
            }else{
                foreach ($list as $v){
                    if(empty($v->city)){
                        $res = MapService::region($v->lat, $v->lng, $this->uniacid);
                        $model = Address::where(['uniacid' => $this->uniacid])->where(['userId' => $this->userId])->where(['id' => $v->id])->first();
                        $model->province=$res['address_component']['province'];
                        $model->city=$res['address_component']['city'];
                        $model->district=$res['address_component']['district'];
                        $model->save();
                    }
                }
                $this->_addressList =$list;
            }
        }
        return $this->_addressList;
    }


    /**
     * 下次打印时间
     */
    public function getNextPrintTimeAttribute()
    {
        if (!$this->_nextPrintTime) {
            $config = $this->store->storeSetting;
            if ($this->scene == 1) {
                if ($this->appointment  == 1 && $config['takeoutSwitch'] == 1 && $config['outPrintTime'] && in_array(2, $config['outPrintTime'])) {
                    $this->_nextPrintTime  = date("Y-m-d H:i:00", strtotime($this->serverTime) - $config['outBeforPrint'] * 60);
                }
            } else {
                if ($this->appointment  == 1 && $config['pickupSwitch'] == 1 && $config['takeAppointTimeStep'] && in_array(2, $config['takeAppointTimeStep'])) {
                    $this->_nextPrintTime  =  date("Y-m-d H:i:00", strtotime($this->serverTime) - $config['takeBeforTime'] * 60);
                }
            }
        }
        return $this->_nextPrintTime;
    }



    /**
     * 自动接单
     */
    public function getAutoReceiveAttribute()
    {
        if (!$this->_autoReceive) {

            $config = $this->store->storeSetting;
            if ($this->scene == 1) {
                $this->_autoReceive = $config['outOrderState'] == 1 ?  1 : 0;
            } else {
                $this->_autoReceive = $config['takeOrder'] == 1 ?  1 : 0;
            }
        }
        return $this->_autoReceive;
    }

    public function getPayNumAttribute()
    {
        $key = "payNum:{$this->uniacid}:{$this->storeId}:{$this->userId}";
        if (Cache::has($key)) {
            return Cache::get($key) + 1;
        }
        return  1;
    }


    public function getReceivePrintAttribute()
    {
        if (!$this->_receivePrint) {
            $config = $this->store->storeSetting;
            if ($this->scene == 1) {
                if ($this->appointment  == 1 && $config['takeoutSwitch'] && !in_array(1, $config['outPrintTime'])) {
                    $this->_receivePrint  = 0;
                }
            } else {
                if ($this->appointment  == 1 && $config['pickupSwitch'] && !in_array(1, $config['takeAppointTimeStep'])) {
                    $this->_receivePrint  =  0;
                }
            }
            $this->_receivePrint = 1;
        }
        return $this->_receivePrint;
    }


    public function check()
    {
        try {
            $lock_key = 'lock_checkout_' . $this->userId;
            $is_lock  = Redis::setnx($lock_key, 1); // 加锁
            if (!$is_lock) { // 获取锁权限
                if (!$is_lock) { // 获取锁权限
                    // 防止死锁
                    Redis::del($lock_key);
                    throw new BadRequestException('系统繁忙请稍后再试');
                } else {
                    if (Redis::ttl($lock_key) == -1) {
                        Redis::expire($lock_key, 1);
                    }
                }
            }
        } catch (\Exception $e) {
            Redis::del($lock_key);
        }
        if (
            $this->store->storeSetting['takeoutSwitch'] == 0 && $this->diningType == 0
        ) {
            throw new BadRequestException('该门店未开启外卖渠道');
        }

        if (
            $this->store->storeSetting['pickupSwitch'] == 0 && $this->diningType == 2
        ) {
            throw new BadRequestException('该门店未开启自提渠道');
        }
        if (
            $this->store->storeSetting['expressSwitch'] == 0 && $this->diningType ==30
        ) {
            throw new BadRequestException('该门店未开启快递渠道');
        }

        if (!in_array($this->diningType, [0, 1, 2,30])) {
            throw new BadRequestException('请选择取餐方式');
        }
        $oneDeliverySwitch = true;
        foreach ($this->goodsList as $key => $goods) {
            $goods->check();
        }

        if ($this->scene == 0 ||$this->scene == 30&& $this->addressId == 0) {
            throw new BadRequestException('请选择收货地址');
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

    public function getGoodsDiscountsAttribute()
    {
        if (!empty($this->couponId)) {

            $coupon = collect($this->couopnList['true'])->where('id', $this->couponId)->first();

            if ($coupon) {
                if ($coupon['coupon']['couponType'] == 1) {
                    $discounts['coupon'] = ['activityId' => $coupon['id'], 'activityName' => $coupon['coupon']['name'], 'type' => 'coupon', 'money' => $coupon['money'], 'title' => "券"];
                }
            }
        }
        return  collect($discounts)->sum('money');
    }

    public function getDeliveryDiscountsAttribute()
    {
        $discounts = [];
        if ($this->deliveryFree) {
            $discounts['deliveryFree'] = ['activityId' => $this->user->equityCard->id, 'activityName' => "免配权益", 'type' => 'deliveryFree', 'money' => $this->delivery['discount'], 'title' => "免"];
        } else {
            if (!empty($this->couponId)) {
                $coupon = collect($this->couopnList['true'])->where('id', $this->couponId)->first();
                if ($coupon['coupon']['couponType'] == 2) {
                    $discounts['coupon'] = ['activityId' => $coupon['id'], 'activityName' => $coupon['coupon']['name'], 'type' => 'deliveryCoupon', 'money' => $coupon['money'], 'title' => "券"];
                }
            }
            if ($this->deliverySub) {
                $discounts['deliverySub'] = ['activityId' => $this->store->deliverySub->id, 'activityName' => $this->store->deliverySub->name, 'type' => 'deliverySub', 'money' => $this->deliverySub['sub'], 'title' => "减"];
            }
        }
        return  collect($discounts)->sum('money');
    }

    public function getDiscountsAttribute()
    {
        if (!$this->_discounts) {
            $discounts = $this->carList->discounts ?? [];
            if (!empty($this->couponId)) {
                $coupon = collect($this->couopnList['true'])->where('id', $this->couponId)->first();
                if ($coupon) {
                    if ($coupon['coupon']['couponType'] == 1) {
                        $discounts['coupon'] = ['activityId' => $coupon['id'], 'activityName' => $coupon['coupon']['name'], 'type' => 'coupon', 'money' => $coupon['money'], 'title' => "券"];
                    }
                    if ($coupon['coupon']['couponType'] == 2) {
                        $discounts['coupon'] = ['activityId' => $coupon['id'], 'activityName' => $coupon['coupon']['name'], 'type' => 'deliveryCoupon', 'money' => $coupon['money'], 'title' => "券"];
                    }
                }
            }

            if ($this->deliveryFree) {
                $discounts['deliveryFree'] = ['activityId' => $this->user->equityCard->id, 'activityName' => "免配权益", 'type' => 'deliveryFree', 'money' => $this->delivery['discount'], 'title' => "免"];
            } elseif ($this->deliverySub) {
                $discounts['deliverySub'] = ['activityId' => $this->store->deliverySub->id?:$this->deliverySub['activityId'], 'activityName' => $this->store->deliverySub->name?:$this->deliverySub['activityName'], 'type' => 'deliverySub', 'money' => $this->deliverySub['sub'], 'title' => "减"];
            }
            $this->_discounts = $discounts;
        }
        return $this->_discounts;
    }

    public function getCouopnListAttribute()
    {
        if (empty($this->_couponList)) {
            $model = new Coupon([
                'selectId' => $this->couponId,
                'storeId' => $this->storeId,
                'uniacid' => $this->uniacid,
                'userId' => $this->userId,
                'scene' => $this->scene,
                'carList' => $this->carList,
                'diningType' => $this->diningType,
                'deliveryFree' => $this->deliveryFree,
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

    public function getOrderCollectAttribute()
    {
        if (!$this->_orderCollect && $this->user->mobile) {
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

    public function getPartnerAttribute()
    {
        if (!$this->_partner) {
            $config = ConfigService::getChannelConfig('distributor', $this->uniacid, $this->store->isolateStore);
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
                        'money' =>$partnerMoney
                    ];
                    if ($config['level'] == 2) {
                        $floatNumber =$this->money; // 浮点数
                        $percentage = $config['levelRate']['second']; // 百分比，不带百分号
                        // 将百分比转换为小数，例如："5.5" 转换为 "0.055"
                        $percentage /= 100;
                        // 使用 bcmul 进行精确乘法
                        $partnerMoney = bcmul($floatNumber, $percentage,2);
                        $parent = Partner::where('uniacid', $this->uniacid)->where('userId', $partner->parentId)->first();
                        if ($parent) {
                            $data[1] = [
                                'level' => 2,
                                'partnerId' => $partner->userId,
                                'money' =>$partnerMoney
                            ];
                        }
                    }
                }
                $this->_partner = $data;
            }
        }
        return $this->_partner;
    }


    public function getTradeinGoodsListAttribute()
    {
        if (!$this->_tradeinGoodsList) {
            $storeId = $this->storeId;
            $uniacid = $this->uniacid;
            $diningType = $this->diningType;
            $isolateStore = $this->store->isolateStore;
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
                ->when($this->store->isolate == 1, function ($q) use ($storeId) {
                    return $q->where('storeId', $storeId);
                })
                ->where('state', 1)
                ->where('scenario', 'like', "%{$scenario}%")
                ->where("startTime", "<", Carbon::now()->toDateTimeString())
                ->where("endTime", ">=", Carbon::now()->toDateTimeString())
                ->where(function ($q) use ($storeId, $uniacid, $isolateStore) {
                    return $q->whereHas('stores', function ($q) use ($storeId, $uniacid) {
                        return $q->where(function ($q) use ($storeId, $uniacid) {
                            return $q->where('storeId', $storeId)->where('type', 2);
                        })->orWhere(function ($q) use ($storeId, $uniacid) {
                            return $q->where('storeId', '!=', $storeId)->where('type', 3);
                        });
                    })->orWhere(function ($q) use ($uniacid, $isolateStore) {
                        return $q->where('storeType', 1)->where('uniacid', $uniacid)->where('storeId', $isolateStore);
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
        return collect($this->tradeinGoodsData)->sum('boxMoney');
    }

    public function getTradeinDiscountMoneyAttribute()
    {
        return collect($this->tradeinGoodsData)->sum('discountMoney');
    }
    public function getTradeinSellMoneyAttribute()
    {
        return collect($this->tradeinGoodsData)->sum('sellMoney');
    }
}
