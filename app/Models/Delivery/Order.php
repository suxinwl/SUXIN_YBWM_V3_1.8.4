<?php

namespace App\Models\Delivery;

use App\Models\Admin\Apply;
use App\Models\BaseModel;
use App\Models\Order\TakeOutOrder;
use App\Models\ReallysavesMoney;
use App\Models\Store;
use App\Services\Delivery\FengniaoService;
use App\Services\Delivery\MaiyatianService;
use App\Services\Delivery\MakeService;
use App\Services\Delivery\QulaidaService;
use App\Services\Delivery\ShansongService;
use App\Services\Delivery\ShunfengService;
use App\Services\Delivery\UuService;
use App\Services\Delivery\WaisongBangService;
use App\Services\Delivery\DadaService;
use App\Services\DeliveryService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log as FacadesLog;
use Log;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use App\Models\Delivery\Store as DeliveryStore;

class Order extends BaseModel
{
    use HasFactory;
    protected $table = 'delivery_order';
    protected $guarded = [];
    protected $casts =  [
        'deliveryData' => 'array',
        'log' => 'array',
        'rider' => 'array',
        'startAddress' => 'array',
        'endAddress' => 'array'
    ];
    protected $appends = [
        'deliveryTypeFormat', 'callTypeFormat', "appointmentFormat", 'callStateFormat'
    ];


    public $_log = [];

    public function store()
    {
        return $this->hasOne(Store::class, 'id', 'storeId')->select(['id', 'name', 'storeMobile', 'contact']);
    }

    public function order()
    {
        return $this->hasOne(TakeOutOrder::class, 'orderSn', 'orderSn');
    }

    public function apply()
    {
        return $this->hasOne(Apply::class, 'id', 'uniacid');
    }

    public function call()
    {
        if ($this->callType == 1) {
            $this->callNum = $this->callNum;
            if ($this->callNum  == ($this->loseNum)) {
                $this->setLog("重试次数已达上限,转为手动配送");
                return true;
            }
            $this->close();
            if (isset($this->deliveryData[$this->deliveryIndex + 1])) {
                $delivery = $this->deliveryData[$this->deliveryIndex + 1];
            } else {
                $delivery = $this->deliveryData[0];
            }
            $this->channel = $delivery['type'];
            $this->channelName = $delivery['name'];
        }
        try {
            //$this->setLog("订单呼叫时间:" . date("Y-m-d H:i:s"));
            //$this->setLog("配送异常却换至" . $this->deliveryData[$this->deliveryIndex + 1]['name'] . '配送');
            $delivery = collect($this->deliveryData)->where('type', $this->channel)->first();
            if (empty($delivery) && $this->channel != 0) {
                $this->setLog("【配送异常】门店未配置该配送渠道");
                throw new BadRequestException('【配送异常】门店未配置该配送渠道');
            }
            $this->channelName = $delivery['name'];
            if ($this->channel == 1) {
                $this->maiYaTianCall();
                $this->callState = 1;
                $this->expiredTime = date("Y-m-d H:i:s", $delivery['minutes'] * 60 + time());
            } elseif ($this->channel == 2) {
                if ($this->maKeCall()) {
                    $this->callState = 1;
                    $this->expiredTime = date("Y-m-d H:i:s", $delivery['minutes'] * 60 + time());
                };
            } elseif ($this->channel == 3) {
                if ($this->waisongbangCall()) {
                    $this->callState = 1;
                    $this->expiredTime = date("Y-m-d H:i:s", $delivery['minutes'] * 60 + time());
                };
            } elseif ($this->channel == 4) {
                if ($this->reallyMoneyCall()) {
                    $this->callState = 1;
                    $this->expiredTime = date("Y-m-d H:i:s", $delivery['minutes'] * 60 + time());
                };
            }elseif($this->channel == 5){
                $this->qulaidaCall();
                $this->callState = 1;
                $this->expiredTime = date("Y-m-d H:i:s", $delivery['minutes'] * 60 + time());
            }elseif($this->channel == 6){
                $this->shunfengCall();
                $this->callState = 1;
                $this->expiredTime = date("Y-m-d H:i:s", $delivery['minutes'] * 60 + time());
            }elseif($this->channel == 7){
                $this->fengniaoCall();
                $this->callState = 1;
                $this->expiredTime = date("Y-m-d H:i:s", $delivery['minutes'] * 60 + time());
            }elseif($this->channel == 8){
                $this->uuCall();
                $this->callState = 1;
                $this->expiredTime = date("Y-m-d H:i:s", $delivery['minutes'] * 60 + time());
            }elseif($this->channel == 9){
                $this->dadaCall();
                $this->callState = 1;
                $this->expiredTime = date("Y-m-d H:i:s", $delivery['minutes'] * 60 + time());
            }elseif($this->channel == 10){
                $this->shansongCall();
                $this->callState = 1;
                $this->expiredTime = date("Y-m-d H:i:s", $delivery['minutes'] * 60 + time());
            } elseif ($this->channel == 0) {
                $this->callState = 1;
                $this->money = $this->orderMoney;
                $this->expiredTime = null;
                $this->thirdNo = $this->deliverySn;
                $this->penalMoney = 0;
                $this->stateFormat = '骑士配送中';
                $this->deliveryState = 0;
                $this->channelName = '门店自配送';
                $this->rider = ['mobile' => $this->store->storeMobile, 'name' => $this->store->contact, 'lat' => $this->store->lat, 'lng' => $this->store->lng];
                $this->setLog("【骑士已接单】由门店自配送的骑士{$this->store->contact}{$this->store->storeMobile}配送，等待骑士前往门店取货");
                $this->setLog("【骑士配送中】由门店自配送的骑士{$this->store->contact}{$this->store->storeMobile}已成功取货，正在配送中");
            }
        } catch (\Exception $e) {
            file_put_contents('delivery.log', $e->getMessage() . PHP_EOL, FILE_APPEND);
            $this->close();
            if (isset($this->deliveryData[$this->deliveryIndex + 1]) && $this->callType == 1) {
                $this->deliveryIndex = $this->deliveryIndex + 1;
                $this->channel = $this->deliveryData[$this->deliveryIndex]['type'];
                // $this->setLog("配送异常却换至" . $this->deliveryData[$this->deliveryIndex + 1]['name'] . '配送');
                $this->call();
            } else {
                // if ($this->callType == 1 && $this->callNum  < ($this->loseNum + 1)) {
                //     $this->callNum  = $this->callNum + 1;
                //     $this->deliveryIndex = 0;
                //     $this->call();
                // }
                //$this->setLog("配送渠道呼叫完毕,转为手动配送");
            }
            $this->callState = 2;
        }
        return true;
    }
    public function close()
    {
        try {
            if ($this->channel == 1) {
                return  $this->maiYaTianClose();
            }
            if ($this->channel == 2) {
                return  $this->maKeClose();
            }
            if ($this->channel == 3) {
                return  $this->waisongbangClose();
            }
            if ($this->channel == 4) {
                return  $this->RellySavesMoneyClose();
            }
            if ($this->channel == 5) {
                return  $this->qulaidaClose();
            }
            if ($this->channel ==6) {
                return  $this->shunfengClose();
            }
            if ($this->channel == 7) {
                return  $this->fengniaoClose();
            }
            if ($this->channel == 8) {
                return  $this->uuClose();
            }
            if ($this->channel == 9) {
                return  $this->dadaClose();
            }
            if ($this->channel == 10) {
                return  $this->shansongClose();
            }
            if ($this->channel == 0) {
                $this->callState = 2;
                $this->stateFormat = '订单已取消';
            }
        } catch (\Exception $e) {
            $this->setLog($this->channelName . "订单取消失败,原因：" . $e->getMessage());
            $this->save();
            return false;
        }
    }

    public function reallyMoneyCall()
    {
        try {
            $uniacid = $this->uniacid;
            $storeId = $this->storeId ?: 0;
            $row = DeliveryStore::where('uniacid', $uniacid)
                ->where('storeId', $storeId)->first();
            if ($row->deliveryType == 1) {
                $config = Channel::where('uniacid', $uniacid)
                    ->where('storeId', 0)
                    ->where('type', 4)->first();
                $outShopCode = $uniacid;
            }
            if ($row->deliveryType == 2) {
                $config = Channel::where('uniacid', $uniacid)
                    ->where('storeId', $storeId)
                    ->where('type', 4)->first();
                $outShopCode = $storeId;
            }
            if (empty($config)) {
                throw  new BadRequestHttpException('当前门店或者店铺没有授权');
            }
            $config = $config['config'];
            $param = array(
                'supplierCode' => '',
                'outShopCode' => $outShopCode,
                'outOrderNo' => $this->orderSn,
                'toAddress' => $this->endAddress['address'],
                'toAddressDetail' => $this->endAddress['description'],
                "toLng" => $this->endAddress['lng'], //收件经度， 目前只支持百度坐标
                "toLat" => $this->endAddress['lat'], //收件纬度, 目前只支持百度坐标
                "toReceiverName" => $this->endAddress['contact'], //收件人姓名
                "toMobile" => $this->endAddress['mobile'], //收件人联系方式
                'goodType' => 1,
                'weight' => 1
            );
            $res = ReallysavesMoney::calculateFreight($config['appId'], $config['secret'], $param);
            file_put_contents('delivery.log', json_encode($res) . PHP_EOL, FILE_APPEND);
            if ($res['code'] !== 200) {
                echo json_encode(['code' => 400, 'msg' => $res['message']]);
                die;
            }
            $billingDetailList = $res['data']['billingDetailList'];
            $billingList = array_column($billingDetailList, 'deliveryCode');
            $deliveryCode = $config['deliverySupplierList'];
            $deliverySupplierList = array_merge($billingList + $deliveryCode);
            $this->deliverySn = getTakeOutNo();
            $params = array(
                "outOrderNo" => $this->orderSn, //接入方平台订单号
                "multipleSupplierCodes" => $deliverySupplierList, //发单渠道编号（不填则为默认返回店铺全部可用的运力）
                "outShopCode" => $outShopCode, //发货门店 接入方门店编号(店到点模式下，与平台方编号必填一个)
                "shopId" => $config['reallyStoreId'], //发货门店 平台方门店编号（店到点模式下，与平台方编号必填一个）
                "toAddress" => $this->endAddress['address'], //收件地址
                "toAddressDetail" => $this->endAddress['description'], //收件人详细地址
                "toLng" => $this->endAddress['lng'], //收件经度， 目前只支持百度坐标
                "toLat" => $this->endAddress['lat'], //收件纬度, 目前只支持百度坐标
                "toReceiverName" => $this->endAddress['contact'], //收件人姓名
                "toMobile" => $this->endAddress['mobile'], //收件人联系方式
                "goodType" => 1,
                "weight" => 1 //物品重量,单位KG
            );
            $res = ReallysavesMoney::createOrder($config['appId'], $config['secret'], $params);
            if ($res['code'] !== 200) {
                echo json_encode(['code' => 400, 'msg' => $res['message']]);
                die;
            }
            $this->setLog("【等待骑士接单】呼叫" . $this->channelName . '成功,等待骑士接单');
        } catch (\Exception $e) {
            $this->setLog("【配送异常】呼叫" . $this->channelName . "失败，原因：" . $e->getMessage() . '，详情请联系客服！');
            throw new BadRequestException($e->getMessage());
        }
    }

    public function maiYaTianCall()
    {
        try {
            $shop_id='shopid'.$this->uniacid;
            $this->config = MaiyatianService::getConfig($this->storeId, $this->uniacid);
            $this->deliverySn = getTakeOutNo();
            $params = array(
                'origin_id' => $this->deliverySn, //你方订单号
                'order_sn' => rand(0, 9999), //订单流水号 【必须是0~99999以内的数字】
                'shop_id' => $shop_id, //你方门店ID
                'shop_name' => $this->apply->applyName, //你方门店名称
                'is_subscribe' => 0, //是否预约单
                'subscribe_time' => 0, //期望发单时间(秒时间戳)
                'city' => $this->config->config['cityCode'], //城市编码
                'sender_longitude' => $this->startAddress['lng'], //发件人经度
                'sender_latitude' =>  $this->startAddress['lat'], //发件人纬度
                'sender_address' => $this->startAddress['address'], //发件人地址
                'sender_phone' => $this->startAddress['tel'], //发件人手机号
                'receiver_longitude' => $this->endAddress['lng'], //收件人经度
                'receiver_latitude' => $this->endAddress['lat'], //收件人纬度
                'receiver_address' => $this->endAddress['address'], //收件人地址
                'receiver_address_detail' => $this->endAddress['address'] . $this->endAddress['description'], //收件人详细地址
                'receiver_name' => $this->endAddress['contact'], //收件人姓名
                'receiver_phone' => $this->endAddress['mobile'], //收件人手机号 【如果是只有虚拟号码，虚拟号格式（手机号_分机号码）例如：13700000000_1111】
                'remark' => '', //	备注
                'order_source' => 'other',
                'order_source_no' => $this->orderSn,
                'goods_category' => $this->config->config['categoryCode'],
                'map_type' => 1,
                'goods_value' => $this->order->goodsMoney * 100,
                'goods_weight' => $this->order->goodsMoney * 100,
            );
            $app = MaiyatianService::storeApp($this->storeId, $this->uniacid);
            $res =  $app->getClient()->postJson("/channel/order/add", $params)->toArray();
            if ($res['status'] != 1) {
                echo json_encode(['code'=>400,'msg'=>$res['msg']]);die;
                //throw new BadRequestException($res['msg']);
            }
            $res = $app->getClient()->postJson("/order/fee", ['origin_id' => $this->deliverySn])->toArray();
            if ($res['status'] != 1) {
                echo json_encode(['code'=>400,'msg'=>$res['msg']]);die;
                //throw new BadRequestException($res['msg']);
            }
            $logistic = $res['data']['logistic'][0]['list'];
            $logistic = collect($logistic)->where('enable', true)->pluck('logistic')->all();
            $res = $app->getClient()->postJson("/order/send", ['origin_id' => $this->deliverySn, 'logistics' => implode(',', $logistic)])->toArray();
            if ($res['status'] != 1) {
                echo json_encode(['code'=>400,'msg'=>$res['msg']]);die;
                //throw new BadRequestException($res['msg']);
            }
            $res = $app->getClient()->postJson("/order/detail", ['origin_id' => $this->deliverySn])->toArray();
            if ($res['status'] != 1) {
                echo json_encode(['code'=>400,'msg'=>$res['msg']]);die;
                //throw new BadRequestException($res['msg']);
            }
            $state = [
                '0' => "订单已被取消",
                '10' => '等待骑士接单',
                '20' => "骑士已接单",
                '30' => "等待骑士取件",
                '40' => "骑士配送中",
                '50' => "订单已送达"
            ];
            $data = $res['data'];
            $this->thirdNo = $data['order_sn'];
            $this->money =  $data['delivery_amount'] ?? 0;
            $this->penalMoney = $data['cancel_amount'];
            $this->deliveryState = $data['delivery_status'];
            $this->stateFormat = "等待骑士接单";
            $this->callState = 1;
            $this->setLog("【等待骑士接单】呼叫" . $this->channelName . '成功,等待骑士接单');
        } catch (\Exception $e) {
            $this->setLog("【配送异常】呼叫" . $this->channelName . "失败，原因：" . $e->getMessage() . '，详情请联系客服！');
            throw new BadRequestException($e->getMessage());
        }
    }



    public function maKeCall()
    {
        try {
            $storeId = $this->deliveryType == 1 ? 0 : $this->storeId;
            $this->config = MakeService::getConfig($storeId, $this->uniacid);
            $this->deliverySn = getTakeOutNo();
            $app = MakeService::storeApp($this->storeId, $this->uniacid);
            $res = $app->getClient()->get('index.php/apis/v2/get_delivery_price', [
                'query' => [
                    'fromcoord' => "{$this->startAddress['lat']},{$this->startAddress['lng']}",
                    "tocoord" => "{$this->endAddress['lat']},{$this->endAddress['lng']}",
                    "shopId" => $this->config->channelId
                ]
            ])->toArray();
            if ($res['error_code'] != 0) {
                throw new BadRequestException("查询价格失败:" . $res['msg']);
            }
            $price = $res['data']['total_price'];
            $params = [
                "token" => $app->getAccessToken()->getToken(),
                'goods_name' => $this->order->goodsFormat,
                'address' => json_encode([
                    "begin_detail" => $this->startAddress['address'],
                    'begin_address' => $this->startAddress['address'],
                    "begin_lat" => $this->startAddress['lat'],
                    "begin_lng" => $this->startAddress['lng'],
                    'begin_username' => $this->startAddress['address'],
                    'begin_phone' => $this->startAddress['tel'],
                    'end_detail' => $this->endAddress['description'],
                    'end_address' => $this->endAddress['address'] . $this->endAddress['description'],
                    'end_lat' => $this->endAddress['lat'],
                    'end_lng' => $this->endAddress['lng'],
                    'end_username' => $this->endAddress['contact'],
                    'end_phone' => $this->endAddress['mobile']
                ], 320),
                'pay_price' => $price,
                'total_price' => $price,
                'shop_id' => $this->config->channelId,
                'notify_url' => Request()->getSchemeAndHttpHost() . "/channel/notify/make/$this->uniacid",
                'order_no' => $this->deliverySn,
                'short_order_num' => $this->order->pickNo,
                'remark' => $this->order->notes,
            ];
            $res = $app->getClient()->postJson('index.php/apis/v2/create_order ', ['json' => $params])->toArray();
            if ($res['error_code'] != 0) {
                throw new BadRequestException("推送订单失败:" . $res['msg']);
            }
            $this->thirdNo = $res['data']['order_number'];
            $this->money =  $price;
            $this->callState = 1;
            $this->stateFormat = "等待骑士接单";
            $this->setLog("【等待骑士接单】呼叫" . $this->channelName . '成功,等待骑士接单');
            return true;
        } catch (\Exception $e) {
            $this->setLog("【配送异常】呼叫" . $this->channelName . "失败，原因：" . $e->getMessage() . '，详情请联系客服！');
            throw new BadRequestException($e->getMessage());
        }
    }

    public function waisongbangCall()
    {
        try {

            $this->config = WaisongBangService::getConfig($this->storeId, $this->uniacid);
            $app = WaisongBangService::storeApp($this->storeId, $this->uniacid);
            if ($this->deliveryState == 5 || !$this->thirdNo) {
                $this->deliverySn = getTakeOutNo();
                $row=Order::where('orderSn',$this->orderSn)->first();
                if($row){
                    throw new BadRequestException('请勿重复发单');
                }
                $params = array(
                    'store_id' => $this->config->channelId,
                    'receiver_name' => $this->endAddress['contact'],
                    'receiver_lng' => $this->endAddress['lng'], //发件人经度
                    'receiver_lat' =>  $this->endAddress['lat'], //发件人纬度
                    'receiver_address' => $this->endAddress['address'] . $this->endAddress['description'],
                    'receiver_phone' => $this->endAddress['mobile'], //发件人手机号
                    'platform_order_id' => $this->deliverySn,
                    'platform_day_id' => intval(str_replace($this->order->pickFix, '', $this->order->pickNo)),
                    'order_amount' => $this->order->money,
                    'order_weight' => 1,
                    'remark' => $this->order->goodsFormat
                );

                $res =  $app->getClient()->postJson("/OpenApi/order/create", $params)->toArray();
                if ($res['code'] != 0) {
                    throw new BadRequestException($res['msg']);
                }
                $this->thirdNo = $res['data']['order_id'];
                $this->save();
            }
            $res = $app->getClient()->postJson("/OpenApi/deliver/price/{$this->deliverySn}", [])->toArray();
            if ($res['code'] != 0) {
                throw new BadRequestException($res['msg']);
            }
            //var_dump($res);
            $ship_ways = $res['data'];
            $ship_ways = collect($ship_ways)
                ->where('fee', "<=", $this->config->amount)
                ->map(function ($item) {
                    return [
                        'way_code' => $item['way_code'],
                        "fee_type" => $item['fee_type'],
                    ];
                })->values()->toArray();
            if (empty($ship_ways)) {
                throw new BadRequestException('余额不足，请充值');
            }
            $res = $app->getClient()->postJson("/OpenApi/deliver/create/{$this->deliverySn}", ['ship_way_map' => $ship_ways])->toArray();
            if ($res['code'] != 0) {
                throw new BadRequestException($res['msg']);
            }
            $res = $app->getClient()->postJson("/OpenApi/deliver/records/{$this->deliverySn}", ['origin_id' => $this->deliverySn])->toArray();
            if ($res['code'] != 0) {
                throw new BadRequestException($res['msg']);
            }
            $state = [
                5 => "订单已取消",
                1 => '等待骑士接单',
                2 => "等待骑士取件",
                3 => "骑士配送中",
                4 => "订单已送达"
            ];
            $this->money =  $res['data']['fee'] ?? 0;
            $this->penalMoney = $res['data']['cancel_amount'] ?? 0;
            $this->deliveryState = $res['data']['state'];
            $this->stateFormat = "等待骑士接单";
            $this->callState = 1;
            $this->setLog("【系统已处理】呼叫" . $this->channelName . '成功,等待骑士接单');
        } catch (\Exception $e) {
            $this->setLog("【配送异常】呼叫" . $this->channelName . "失败，原因：" . $e->getMessage() . '，详情请联系客服！');
            FacadesLog::error($e->getMessage() . '-' . $e->getFile() . '--' . $e->getLine());
            throw new BadRequestException($e->getMessage());
        }
    }

    public function qulaidaCall()
    {
        try {
            if ($this->deliveryState == 5 || !$this->thirdNo) {
                $this->deliverySn = getTakeOutNo();
                $res =  QulaidaService::createOrder($this->storeId,$this->orderSn );
                if ($res['code'] != 100) {
                    $this->setLog("【配送异常】呼叫" . $this->channelName . "失败，原因：" . $res['message']. '，详情请联系客服！');
                    throw new BadRequestException($res['message']);
                }
                $this->thirdNo = $res['data']['trade_no'];
                $this->save();
            }
            $res=QulaidaService::getOrderInfo($this->thirdNo);
            if ($res['code'] != 100) {
                $this->setLog("【配送异常】呼叫" . $this->channelName . "失败，原因：" . $res['message']. '，详情请联系客服！');
                throw new BadRequestException($res['message']);
            }
            $this->money =  $res['data']['merchant_pay_fee'] ?? 0;
            $this->penalMoney = $res['data']['tip'] ?? 0;
            $this->deliveryState = $res['data']['status'];
            $this->stateFormat = "等待骑士接单";
            $this->callState = 1;
            $this->setLog("【系统已处理】呼叫" . $this->channelName . '成功,等待骑士接单');
        } catch (\Exception $e) {
            $this->setLog("【配送异常】呼叫" . $this->channelName . "失败，原因：" . $e->getMessage() . '，详情请联系客服！');
            FacadesLog::error($e->getMessage() . '-' . $e->getFile() . '--' . $e->getLine());
            throw new BadRequestException($e->getMessage());
        }
    }
    public function shunfengCall()
    {
        try {
            if ($this->deliveryState == 5 || !$this->thirdNo) {
                $this->deliverySn = getTakeOutNo();
                $orderInfo=$this->order;
                $order=[
                    'uniacid'=>$this->uniacid,
                    'storeId'=>$this->storeId,
                    'outTradeNo'=>$this->deliverySn,
                    'takeNo'=>$this->order->pickNo,
                    'createdAt'=>time(),
                    'userNote'=>$this->order->notes,
                    'receivedName'=>$orderInfo->address['contact'].$orderInfo->address['call'],
                    'receivedTel'=>$orderInfo->address['mobile'],
                    'lng'=>$orderInfo->address['lng'],
                    'lat'=>$orderInfo->address['lat'],
                    'receivedAddress'=>$orderInfo->address['address'],
                    'money'=>$this->order->goodsMoney,
                    'num'=>$this->order->goodsNum,
                    'product_detail'=>$this->order->goodsFormat
                ];

                $res =  ShunfengService::sftcAddOrder($order);
                if ($res['error_code'] != 0) {
                    $this->setLog("【配送异常】呼叫" . $this->channelName . "失败，原因：" . $res['error_msg']. '，详情请联系客服！');
                    throw new BadRequestException($res['error_msg']);
                }
                $this->thirdNo = $res['result']['sf_order_id'];
                $this->save();
            }
            $this->money =  $res['result']['total_price'] ?? 0;
            $this->penalMoney =  0;
            $this->deliveryState = $res['result']['status'];
            $this->stateFormat = "等待骑士接单";
            $this->callState = 1;
            $this->setLog("【系统已处理】呼叫" . $this->channelName . '成功,等待骑士接单');
        } catch (\Exception $e) {
            $this->setLog("【配送异常】呼叫" . $this->channelName . "失败，原因：" . $e->getMessage() . '，详情请联系客服！');
            FacadesLog::error($e->getMessage() . '-' . $e->getFile() . '--' . $e->getLine());
            throw new BadRequestException($e->getMessage());
        }
    }
    public function fengniaoCall()
    {
        try {
            if ($this->deliveryState == 5 || !$this->thirdNo) {
                $this->deliverySn = getTakeOutNo();
                $orderInfo=$this->order;
                $order=[
                    'uniacid'=>$this->uniacid,
                    'storeId'=>$this->storeId,
                    'outTradeNo'=>$this->uniacid,
                    'takeNo'=>$this->order->pickNo,
                    'receivedName'=>$orderInfo->address['contact'].$orderInfo->address['call'],
                    'receivedTel'=>$orderInfo->address['mobile'],
                    'lng'=>$orderInfo->address['lng'],
                    'lat'=>$orderInfo->address['lat'],
                    'receivedAddress'=>$orderInfo->address['address'],
                    'id'=>$this->order->id,
                ];
                $res =  FengniaoService::addFengniaoOrder($order);
                if ($res['code'] != 200) {
                    $this->setLog("【配送异常】呼叫" . $this->channelName . "失败，原因：" . $res['msg']. '，详情请联系客服！');
                    throw new BadRequestException($res['msg']);
                }
                $this->thirdNo = $res['business_data']['order_id'];
                $this->save();
            }
            $this->deliveryState =  0;
            $this->stateFormat = "等待骑士接单";
            $this->callState = 1;
            $this->setLog("【系统已处理】呼叫" . $this->channelName . '成功,等待骑士接单');
        } catch (\Exception $e) {
            $this->setLog("【配送异常】呼叫" . $this->channelName . "失败，原因：" . $e->getMessage() . '，详情请联系客服！');
            FacadesLog::error($e->getMessage() . '-' . $e->getFile() . '--' . $e->getLine());
            throw new BadRequestException($e->getMessage());
        }
    }
    public function uuCall()
    {
        try {
            if ($this->deliveryState == 5 || !$this->thirdNo) {
                $this->deliverySn = getTakeOutNo();
                $orderInfo=$this->order;
                $order=[
                    'uniacid'=>$this->uniacid,
                    'storeId'=>$this->storeId,
                    'outTradeNo'=>$this->order->orderSn,
                    'takeNo'=>$this->order->pickNo,
                    'receivedName'=>$orderInfo->address['contact'].$orderInfo->address['call'],
                    'receivedTel'=>$orderInfo->address['mobile'],
                    'lng'=>$orderInfo->address['lng'],
                    'lat'=>$orderInfo->address['lat'],
                    'receivedAddress'=>$orderInfo->address['address'],
                    'id'=>$this->order->id,
                ];
                $res =  UuService::addUuptOrder($order);
                if ($res['return_code'] != 'ok') {
                    $this->setLog("【配送异常】呼叫" . $this->channelName . "失败，原因：" . $res['return_msg']. '，详情请联系客服！');
                    throw new BadRequestException($res['return_msg']);
                }
                $this->thirdNo = $res['ordercode'];
                $this->save();
            }
            $this->deliveryState =  0;
            $this->stateFormat = "等待骑士接单";
            $this->callState = 1;
            $this->setLog("【系统已处理】呼叫" . $this->channelName . '成功,等待骑士接单');
        } catch (\Exception $e) {
            $this->setLog("【配送异常】呼叫" . $this->channelName . "失败，原因：" . $e->getMessage() . '，详情请联系客服！');
            FacadesLog::error($e->getMessage() . '-' . $e->getFile() . '--' . $e->getLine());
            throw new BadRequestException($e->getMessage());
        }
    }
    public function dadaCall()
    {
        try {
            if ($this->deliveryState == 5 || !$this->thirdNo) {
                $this->deliverySn = getTakeOutNo();
                $orderInfo=$this->order;
                $order=[
                    'uniacid'=>$this->uniacid,
                    'storeId'=>$this->storeId,
                    'outTradeNo'=>$orderInfo->orderSn,
                    'takeNo'=>$this->order->pickNo,
                    'receivedName'=>$orderInfo->address['contact'].$orderInfo->address['call'],
                    'receivedTel'=>$orderInfo->address['mobile'],
                    'lng'=>$orderInfo->address['lng'],
                    'lat'=>$orderInfo->address['lat'],
                    'receivedAddress'=>$this->endAddress['address'] . $this->endAddress['description'],
                    'id'=>$this->order->id,
                ];
                $res =  DadaService::addDataOrder($order);
                if ($res['status'] != 'success') {
                    $this->setLog("【配送异常】呼叫" . $this->channelName . "失败，原因：" . $res['msg']. '，详情请联系客服！');
                    throw new BadRequestException($res['msg']);
                }
//                {
//                    "status": "success",
//                              "result": {
//                                    "distance": 1004.0,
//                                "fee": 123.22,
//                                "deliverFee": 123.22,
//                                "insuranceFee": 0.0,
//                                "tips": 1.0
//                              },
//                              "code": 0,
//                              "msg": "成功",
//                              "success": true,
//                              "fail": false
//                }
                $this->thirdNo = '';
                $this->save();
            }
            $this->money =  $res['result']['deliverFee'];
            $this->deliveryState =  0;
            $this->stateFormat = "等待骑士接单";
            $this->callState = 1;
            $this->setLog("【系统已处理】呼叫" . $this->channelName . '成功,等待骑士接单');
        } catch (\Exception $e) {
            $this->setLog("【配送异常】呼叫" . $this->channelName . "失败，原因：" . $e->getMessage() . '，详情请联系客服！');
            FacadesLog::error($e->getMessage() . '-' . $e->getFile() . '--' . $e->getLine());
            throw new BadRequestException($e->getMessage());
        }
    }


    public function shansongCall()
    {
        try {
            if ($this->deliveryState == 5 || !$this->thirdNo) {
                $this->deliverySn = getTakeOutNo();
                $orderInfo=$this->order;
                $order=[
                    'uniacid'=>$this->uniacid,
                    'storeId'=>$this->storeId,
                    'outTradeNo'=>$this->uniacid,
                    'takeNo'=>$this->order->pickNo,
                    'receivedName'=>$orderInfo->address['contact'].$orderInfo->address['call'],
                    'receivedTel'=>$orderInfo->address['mobile'],
                    'lng'=>$orderInfo->address['lng'],
                    'lat'=>$orderInfo->address['lat'],
                    'receivedAddress'=>$orderInfo->address['address'],
                    'description'=>$orderInfo->address['description'],
                    'id'=>$this->order->id,
                    'notes'=>$this->order->notes,
                ];
                $res =  ShansongService::sanAddOrder($order);
                if ($res['status'] != 200) {
                    $this->setLog("【配送异常】呼叫" . $this->channelName . "失败，原因：" . $res['msg']. '，详情请联系客服！');
                    throw new BadRequestException($res['msg']);
                }
                $this->thirdNo = $res['data']['orderNumber'];
                $this->save();
            }
            $this->money =  $res['data']['totalAmount']; ;
            $this->deliveryState =  0;
            $this->stateFormat = "等待骑士接单";
            $this->callState = 1;
            $this->setLog("【系统已处理】呼叫" . $this->channelName . '成功,等待骑士接单');
        } catch (\Exception $e) {
            $this->setLog("【配送异常】呼叫" . $this->channelName . "失败，原因：" . $e->getMessage() . '，详情请联系客服！');
            FacadesLog::error($e->getMessage() . '-' . $e->getFile() . '--' . $e->getLine());
            throw new BadRequestException($e->getMessage());
        }
    }

    public function setLog($text)
    {
        $this->log = collect($this->log)->push(['time' => date("Y-m-d H:i:s"), 'text' => $text])->sortByDesc('time')->values();
    }


    public function getDeliveryTypeFormatAttribute()
    {
        return $this->deliveryType == 1 ? '平台配送' : "门店配送";
    }

    public function getCallTypeFormatAttribute()
    {
        return $this->deliveryType == 0 ? '手动呼叫' : "自动呼叫";
    }

    public function getAppointmentFormatAttribute()
    {
        return  $this->appointment == 0 ? "即时单" : "预约单";
    }

    public function getCallStateFormatAttribute()
    {
        if ($this->callState == 1) {
            return $this->stateFormat;
        } else {
            return '配送异常';
        }
    }

    public function maiYaTianClose()
    {
        $app = MaiyatianService::storeApp($this->storeId, $this->uniacid);
        $res =  $app->getClient()->postJson("/order/precancel", ['origin_id' => $this->orderSn])->toArray();
        if ($res['status'] != 1) {
            throw new BadRequestException($res['msg']);
        }
        $this->penalMoney = $res['data']['cancel_amount'] ?? 0;
        $res =  $app->getClient()->postJson("/delivery/cancel", ['origin_id' => $this->orderSn])->toArray();
        if ($res['status'] != 1) {
            throw new BadRequestException($res['msg']);
        }
        return true;
    }

    public function maKeClose()
    {
        try {
            $storeId = $this->deliveryType == 1 ? 0 : $this->storeId;
            $app = MakeService::storeApp($storeId, $this->uniacid);
            $res = $app->getClient()->post('index.php/apis/v2/cancel_order', ['body' => [
                "token" => $app->getAccessToken()->getToken(),
                'order_num' => $this->thirdNo
            ]])->toArray();
            if ($res['error_code'] != 0) {
                throw new BadRequestException($res['msg']);
            }
            $this->stateFormat = '订单已被取消';
            $this->callState = 2;
            $this->deliveryState = 'cancel';
            $this->setLog("【订单已取消】" . $this->channelName . '订单已成功取消');
            $this->save();
            return true;
        } catch (\Exception $e) {
            $this->setLog($this->channelName . "订单取消失败，原因：" . $e->getMessage());
            $this->callState = 2;
            throw new BadRequestException($e->getMessage());
        }
    }

    public function waisongbangClose()
    {
        try {
            $app = WaisongBangService::storeApp($this->storeId, $this->uniacid);
            $res = $app->getClient()->post('/OpenApi/order/cancel/{$this->deliverySn}', [])->toArray();
            if ($res['error_code'] != 0) {
                throw new BadRequestException($res['msg']);
            }
            return true;
        } catch (\Exception $e) {
            $this->setLog($this->channelName . "订单取消失败，原因：" . $e->getMessage());
            throw new BadRequestException($e->getMessage());
        }
    }

    public function RellySavesMoneyClose()
    {
        try {
            $uniacid = $this->uniacid;
            $storeId = $this->storeId;
            $row = DeliveryStore::where('uniacid', $uniacid)
                ->where('storeId', $storeId)->first();
            if ($row->deliveryType == 1) {
                $config = Channel::where('uniacid', $uniacid)
                    ->where('storeId', 0)
                    ->where('type', 4)->first();
            }
            if ($row->deliveryType == 2) {
                $config = Channel::where('uniacid', $uniacid)
                    ->where('storeId', $storeId)
                    ->where('type', 4)->first();
            }
            if (empty($model)) {
                throw  new BadRequestHttpException('当前门店或者店铺没有授权');
            }
            $res = ReallysavesMoney::orderCancel($config['appid'], $config['secret'], $this->orderSn);
            if ($res['error_code'] != 0) {
                throw new BadRequestException($res['msg']);
            }
            return true;
        } catch (\Exception $e) {
            $this->setLog($this->channelName . "订单取消失败，原因：" . $e->getMessage());
            throw new BadRequestException($e->getMessage());
        }
    }

    public function qulaidaClose()
    {
        try {
            $res = QulaidaService::repealOrder($this->thirdNo);
            $res=json_decode($res,true);
            if ($res['code'] != 200) {
                throw new BadRequestException($res['message']);
            }
            return true;
        } catch (\Exception $e) {
            $this->setLog($this->channelName . "订单取消失败，原因：" . $e->getMessage());
            throw new BadRequestException($e->getMessage());
        }
    }
    public function shunfengClose()
    {
        try {
            $uniacid = $this->uniacid;
            $storeId = $this->storeId;
            $res = ShunfengService::cancelShunfengOrder($uniacid,$storeId,$this->orderSn);
            $res=json_decode($res,true);
            if ($res['error_code'] != 0) {
                throw new BadRequestException($res['message']);
            }
            return true;
        } catch (\Exception $e) {
            $this->setLog($this->channelName . "订单取消失败，原因：" . $e->getMessage());
            throw new BadRequestException($e->getMessage());
        }
    }
    public function fengniaoClose()
    {
        try {
            $res = FengniaoService::CancelFengniaoOrder($this->orderSn);
            $res=json_decode($res,true);
            if ($res['code'] != 200) {
                throw new BadRequestException($res['msg']);
            }
            return true;
        } catch (\Exception $e) {
            $this->setLog($this->channelName . "订单取消失败，原因：" . $e->getMessage());
            throw new BadRequestException($e->getMessage());
        }
    }
    public function uuClose()
    {
        try {
            $res = UuService::cancelUuptOrder($this->orderSn);
            $res=json_decode($res,true);
            if ($res['return_code'] != 'ok') {
                throw new BadRequestException($res['return_msg']);
            }
            return true;
        } catch (\Exception $e) {
            $this->setLog($this->channelName . "订单取消失败，原因：" . $e->getMessage());
            throw new BadRequestException($e->getMessage());
        }
    }
    public function dadaClose()
    {
        try {
            $res = DadaService::cancelDataOrder($this->orderSn);
            $res=json_decode($res,true);
            if ($res['code'] != 200) {
                throw new BadRequestException($res['message']);
            }
            return true;
        } catch (\Exception $e) {
            $this->setLog($this->channelName . "订单取消失败，原因：" . $e->getMessage());
            throw new BadRequestException($e->getMessage());
        }
    }
    public function shansongClose()
    {
        try {
            $res = ShansongService::cancelSanOrder($this->orderSn);
            $res=json_decode($res,true);
            if ($res['status'] != 200) {
                throw new BadRequestException($res['msg']);
            }
            return true;
        } catch (\Exception $e) {
            $this->setLog($this->channelName . "订单取消失败，原因：" . $e->getMessage());
            throw new BadRequestException($e->getMessage());
        }
    }
}
