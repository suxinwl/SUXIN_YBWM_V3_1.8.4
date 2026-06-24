<?php

namespace App\Http\Controllers\Channel;

use App\Enums\PayEnum;
use App\Events\OrderMessageEvent;
use App\Events\StoreMessageEvent;
use App\Services\Pay\WechatPay;
use App\Http\Controllers\Controller as BaseController;
use App\Models\Delivery\Order;
use App\Models\Order\OrderIndex;
use App\Services\Delivery\MaiyatianService;
use App\Services\Delivery\MakeService;
use App\Services\Delivery\waisongbang;
use App\Services\OrderNotifyService;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use App\Services\Delivery\WaisongBangService;
use App\Models\Order\TakeOutOrder;
class NotifyController extends BaseController
{
    public function make(Request $request, $uniacid)
    {
        try {
            $message = $request->all();
            Log::error($message);
            $state = [
                'cancel' => "订单已被取消",
                'payed' => '等待骑士接单',
                'accepted' => "骑士已接单",
                'wait_to_shop' => "等待骑士取件",
                'geted' => "骑士配送中",
                'gotoed' => "订单已送达"
            ];
            if (!isset($state[$message['status']])) {
                echo "success";
                return;
            }
            $order = Order::where("thirdNo", $message['order_no'])->first();
            if (empty($order) || $order->deliveryState == $message['status']) {
                echo "success";
                return;
            }
            $order->expiredTime = null;
            $order->deliveryState = $message['status'];
            $order->stateFormat = $state[$message['status']];
            $dateState = [
                'accepted' => "【骑士已接单】由{$order->channelName}的骑士{$message['rider_name']}{$message['rider_mobile']}配送，等待骑士前往门店取货",
                'wait_to_shop' => "【骑士配送中】由{$order->channelName}的骑士{$message['rider_name']}{$message['rider_mobile']}已成功取货，正在配送中",
                'gotoed' => "【订单已完成】骑士已送达，完成配送"
            ];
            if (isset($dateState[$message['status']])) {
                $order->setLog($dateState[$message['status']]);
            }
            if ($message['status'] == "cancel") {
                $order->callState = 2;
                $order->setLog("【配送异常】订单配送异常原因：" . $order->channelName . "订单已取消");
            }
            if ($message['status'] != 'payed') {
                $order->expiredTime  = null;
            }
            if (in_array($message['status'], ['accepted', 'wait_to_shop', 'geted', 'gotoed'])) {
                if ($message['status'] == 'accepted') {
                    Event(new OrderMessageEvent($order->order, 'delivery'));
                }
                $order->rider = ['name' => $message['rider_name'], 'mobile' => $message['rider_mobile'], 'lat' => $message['rider_lat'], 'lng' => $message['rider_lng']];
            }
            $order->order->state = $order->order->state <= 5 ? 5 : $order->order->state;
            $order->order->save();
            $order->save();
            if ($message['status'] == 'gotoed') {
                OrderService::complete($order->order->id);
            }
            echo "success";
            return;
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }

    public function maiyatian(Request $request)
    {
        try {
            $message = $request->all();
            Log::error($message);
            $state = [
                10 => '等待骑士接单',
                20 => "骑士已接单",
                30 => "等待骑士取件",
                40 => "骑士配送中",
                50 => "订单已送达",
                60 => "订单已被取消",
                70 => "配送失败",
            ];
            $channelType = [
                'mtps' => "美团企客",
                'fengniao' => '美团企客',
                'fengka' => "蜂鸟配送",
                'dada' => "达达",
                'shunfeng' => "顺丰",
                'bingex' => "闪送",
                'uupt' => "UU跑腿"
            ];
            if (!isset($state[$message['status']])) {
                echo "success";
                return;
            }
            $order = Order::where("deliverySn", $message['origin_id'])->first();
            if (empty($order) || $order->deliveryState == $message['status']) {
                echo "success";
                return;
            }
            $dateState = [
                20 => "【骑士已接单】由{$order->channelName}的骑士{$message['rider_name']}{$message['rider_phone']}配送，等待骑士前往门店取货",
                40 => "【骑士配送中】由{$order->channelName}的骑士{$message['rider_name']}{$message['rider_phone']}已成功取货，正在配送中",
                50 => "【订单已完成】骑士已送达，完成配送"
            ];
            if (isset($dateState[$message['status']])) {
                $order->setLog($dateState[$message['status']]);
            }
            if ($message['status'] == 60) {
                $order->callState = 2;
                $order->setLog("订单配送异常原因：" . $order->channelName . "订单已取消");
            }
            if ($message['status'] == 70) {
                $order->callState = 2;
                $order->setLog("订单配送异常原因：" . $message['reason']);
            }
            if ($message['status'] == 20) {
                $order->money = $message['amount'];
                $order->channelType = $channelType[$message['logistic']];
            }
            if (in_array($message['status'], [20, 30, 40, 50])) {
                $app = MaiyatianService::storeApp($order->storeId, $order->uniacid);
                $res = $app->getClient()->postJson('/delivery/trail', [
                    'json' => [
                        'origin_id' => $message['origin_id']
                    ]
                ])->toArray();
                if ($res['status'] == 1) {
                    $message['rider_lat'] = $res['data']['gcj02_lat'];
                    $message['rider_lng'] = $res['data']['gcj02_lng'];
                }
                $order->rider = ['name' => $message['rider_name'], 'mobile' => $message['rider_phone'], 'lat' => $message['rider_lat'], 'lng' => $message['rider_lng']];
            }

            $order->expiredTime = null;
            $order->deliveryState = $message['status'];
            $order->stateFormat = $state[$message['status']];
            $order->save();
            $order->order->state = $order->order->state <= 5 ? 5 : $order->order->state;
            $order->order->save();
            echo "success";
            if ($message['status'] == 50) {
                OrderService::complete($order->order->id);
            }
            if ($message['status'] == 60) {
                Event(new StoreMessageEvent($order->order, 'deliveryAbnormal'));
            }
            return;
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }

    public function waisongbang(Request $request)
    {
        try {
            $message = $request->all();
            Log::error($message);
            $state = [
                1 => "骑士已接单",
                2 => "等待骑士取件",
                3 => "骑士配送中",
                4 => "订单已送达",
                5 => "订单已被取消"
            ];
            $order = Order::where("thirdNo", $message['order_id'])->first();
            if (empty($order) || $order->deliveryState == $message['state']) {
                echo "success";
                return;
            }
            $dateState = [
                1 => "【骑士已接单】由{$order->channelName}的骑士{$message['driver_name']}{$message['driver_phone']}配送，等待骑士前往门店取货",
                3 => "【骑士配送中】由{$order->channelName}的骑士{$message['driver_name']}{$message['driver_phone']}已成功取货，正在配送中",
                4 => "【订单已完成】骑士已送达，完成配送"
            ];
            if (isset($dateState[$message['state']])) {
                $order->setLog($dateState[$message['state']]);
            }
            if ($message['state'] == 5) {
                $order->callState = 2;
                $order->setLog("订单配送异常原因：" . $order->channelName . "订单已取消");
            }
            if ($message['state'] == 1) {
                $order->money = $message['fee'];
                $order->channelType = $message['way_name'];
            }
            if (in_array($message['state'], [2, 3, 4])) {
                $app = WaisongBangService::storeApp($order->storeId, $order->uniacid);
                $res = $app->getClient()->postJson('/OpenApi/deliver/pos_track/' . $message['delivery_uuid'], [])->toArray();
                if ($res['code'] == 0) {
                    $message['rider_lat'] = $res['data']['track_horseman'][1];
                    $message['rider_lng'] = $res['data']['track_horseman'][0];
                }
                $order->rider = ['name' => $message['driver_name'], 'mobile' => $message['driver_phone'], 'lat' => $message['rider_lat'] ?? null, 'lng' => $message['rider_lng'] ?? null];
            }

            $order->expiredTime = null;
            $order->deliveryState = $message['state'];
            $order->stateFormat = $state[$message['state']];
            $order->save();
            $order->order->state = $order->order->state <= 5 ? 5 : $order->order->state;
            $order->order->save();
            if ($message['state'] == 4) {
                OrderService::complete($order->order->id);
            }
            if ($message['status'] == 5) {
                Event(new StoreMessageEvent($order->order, 'deliveryAbnormal'));
            }
            echo "success";
            return;
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }

    public function reallySavesMoney(Request $request)
    {
        try {
            $postStr = file_get_contents('php://input');
           // file_put_contents('reallymoeny.log', $postStr . PHP_EOL, FILE_APPEND);
            $data = json_decode($postStr, true)['data'];
            $data = json_decode($data, true);
            if ($data['type'] == 1) {
                $response = json_decode($data['param'], true);;
                $array = array('otherInfo' => json_encode($response));
                $state = [
                    20 => "待接单",
                    30 => "等待骑士取件",
                    40 => "骑士配送中",
                    50 => "订单已送达",
                    60 => "订单已被取消",
                    70 => "配送异常"
                ];
                $order = Order::where("orderSn", $response['outOrderNo'])->first();
                if (empty($order)) {
                    echo "success";
                    return;
                }
                $dateState = [
                    30 => "【骑士已接单】由{$order->channelName}的骑士{$response['courierName']}{$response['courierMobile']}配送，等待骑士前往门店取货",
                    40 => "【骑士配送中】由{$order->channelName}的骑士{$response['courierName']}{$response['courierMobile']}已成功取货，正在配送中",
                    50 => "【订单已完成】骑士已送达，完成配送"
                ];
                if (isset($dateState[$response['sendStatus']])) {
                    $order->setLog($dateState[$response['sendStatus']]);
                }
                if ($response['sendStatus'] == 60 || $response['sendStatus'] == 70) {
                    $order->callState = 2;
                    $order->setLog("订单配送异常原因：" . $order->channelName . "订单已取消");
                    Event(new StoreMessageEvent($order->order, 'deliveryAbnormal'));
                }
                if ($response['sendStatus'] == 1) {
                    $order->money = $response['discountLastMoney'] ?: $order->money;
                    $order->channelType = $response['typeDesc'];
                }
                if (in_array($response['sendStatus'], [30, 40, 50])) {
                    $order->rider = [
                        'name' => $response['courierName'],
                        'mobile' => $response['courierMobile'],
                        'lat' => null,
                        'lng' => null
                    ];
                }

                $order->expiredTime = null;
                $order->deliveryState = $response['sendStatus'];
                $order->stateFormat = $state[$response['sendStatus']];
                $order->thirdNo = $response['thirdPartyOrderNo'];
                $order->save();
                $order->order->state = $order->order->state <= 5 ? 5 : $order->order->state;
                $order->order->save();
                if ($response['sendStatus'] == 40) {
                    OrderService::complete($order->order->id);
                }
                if ($response['sendStatus'] == 60 || $response['sendStatus'] == 70) {
                    Event(new StoreMessageEvent($order->order, 'deliveryAbnormal'));
                }
            }
            echo json_encode(['status' => 'success']);
            die;
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }

    public function qulaida(Request $request)
    {
        try {
            $postStr = file_get_contents('php://input');
            //file_put_contents('qulaida.log', $postStr . PHP_EOL, FILE_APPEND);
            $response = json_decode($postStr, true);
            $state = [
                4 => "待接单",
                5 => "骑士配送中",
                6 => "订单已送达",
                7 => "订单已被取消",
            ];
            $order = Order::where("orderSn", $response['order_no'])->first();
            if (empty($order)) {
                echo "success";
                return;
            }
            $dateState = [
                4 => "【骑士已接单】由{$order->channelName}的骑士{$response['courier_name']}{$response['courier_tel']}配送，等待骑士前往门店取货",
                5 => "【骑士配送中】由{$order->channelName}的骑士{$response['courier_name']}{$response['courier_tel']}已成功取货，正在配送中",
                6 => "【订单已完成】骑士已送达，完成配送"
            ];
            if (isset($dateState[$response['state']])) {
                $order->setLog($dateState[$response['state']]);
            }
            if ($response['state'] == 7) {
                $order->callState = 2;
                $order->setLog("订单配送异常原因：" . $order->channelName . "订单已取消");
                Event(new StoreMessageEvent($order->order, 'deliveryAbnormal'));
            }
            if ($response['state'] == 4) {
                $order->money = $response['pay_fee'] ?: $order->money;
                $order->channelType = '派味猫';
            }
            if (in_array($response['state'], [4, 5, 6])) {
                $order->rider = [
                    'name' => $response['courier_name'],
                    'mobile' => $response['courier_tel'],
                    'lat' => explode(',',$response['courier_tag'])[0]?:null,
                    'lng' => explode(',',$response['courier_tag'])[1]?:null,
                ];
            }

            $order->expiredTime = null;
            $order->deliveryState = $response['state'];
            $order->stateFormat = $state[$response['state']];
            $order->thirdNo = $response['trade_no'];
            $order->save();
            $order->order->state = $order->order->state <= 5 ? 5 : $order->order->state;
            $order->order->save();
            if ($response['order_status'] == 6) {
                OrderService::complete($order->order->id);
            }
            if ($response['sendStatus'] == 7) {
                Event(new StoreMessageEvent($order->order, 'deliveryAbnormal'));
            }
            return 'success';
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }
    public function shunfeng(Request $request)
    {
        try {
            $postStr = file_get_contents('php://input');
            file_put_contents('shunfeng.log', $postStr . PHP_EOL, FILE_APPEND);
            $data = json_decode($postStr, true);
            if ($data['error_code'] ==0) {
                $response =$data['result'];
                $state = [
                    1 => "待接单",
                    10 => "等待骑士取件",
                    15 => "骑士配送中",
                    17 => "订单已送达",
                    2 => "订单已被取消",
                    31 => "配送异常"
                ];
                $order = Order::where("orderSn", $response['out_order_id'])->first();
                if (empty($order)) {
                    echo "success";
                    return;
                }
                $dateState = [
                    10 => "【骑士已接单】由{$order->channelName}的骑士{$response['courierName']}{$response['courierMobile']}配送，等待骑士前往门店取货",
                    15 => "【骑士配送中】由{$order->channelName}的骑士{$response['courierName']}{$response['courierMobile']}已成功取货，正在配送中",
                    17 => "【订单已完成】骑士已送达，完成配送"
                ];
                if (isset($dateState[$response['order_status']])) {
                    $order->setLog($dateState[$response['order_status']]);
                }
                if ($response['order_status'] == 2 || $response['order_status'] == 31) {
                    $order->callState = 2;
                    $order->setLog("订单配送异常原因：" . $order->channelName . "订单已取消");
                    Event(new StoreMessageEvent($order->order, 'deliveryAbnormal'));
                }
                if ($response['order_status'] == 1) {
                    $order->money = $response['total_price'] ?: $order->money;
                    $order->channelType = '顺丰同城';
                }
                if (in_array($response['order_status'], [10, 12, 15,17])) {
                    $order->rider = [
                        'name' => $response['rider_name'],
                        'mobile' => $response['rider_phone'],
                        'lat' => null,
                        'lng' => null
                    ];
                }

                $order->expiredTime = null;
                $order->deliveryState = $response['order_status'];
                $order->stateFormat = $state[$response['order_status']];
                $order->thirdNo = $response['order_id'];
                $order->save();
                $order->order->state = $order->order->state <= 5 ? 5 : $order->order->state;
                $order->order->save();
                if ($response['order_status'] == 17) {
                    OrderService::complete($order->order->id);
                }
                if ($response['sendStatus'] == 2 || $response['sendStatus'] == 31) {
                    Event(new StoreMessageEvent($order->order, 'deliveryAbnormal'));
                }
            }
            return '{"error_code":0,"error_msg":"success"}';
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }

    }
    public function fengniao(Request $request)
    {
        $postStr = file_get_contents('php://input');
        file_put_contents('fengniao.log', $postStr . PHP_EOL, FILE_APPEND);
    }
    public function uu(Request $request)
    {
        $postStr = file_get_contents('php://input');
        file_put_contents('uu.log', $postStr . PHP_EOL, FILE_APPEND);
    }
    public function dada(Request $request)
    {
        $postStr = file_get_contents('php://input');
        file_put_contents('dada.log', $postStr . PHP_EOL, FILE_APPEND);
    }
    public function shansong(Request $request)
    {
        $postStr = file_get_contents('php://input');
        file_put_contents('shansong.log', $postStr . PHP_EOL, FILE_APPEND);
    }

    public function kuaidi(Request $request)
    {
        $param = $_POST['param'] ?? '';
        $sign = $_POST['sign'] ?? '';
        $taskId = $_POST['taskId'] ?? '';

        if (empty($param) || empty($sign)) {
            http_response_code(400);
            die("参数不完整");
        }

        $decodedParam = urldecode($param);
        $paramData = json_decode($decodedParam, true); // 转为关联数组
        file_put_contents('kuaidi100.log', date('Y-m-d H:i:s') . " 回调数据: " . print_r($paramData, true), FILE_APPEND);

        $data = $paramData['data'];
        $status = $data['status'] ?? 0; // 物流状态码
        $kuaidinum = $paramData['kuaidinum']; // 快递单号
// 根据 $status 处理不同物流状态
        $order =  TakeOutOrder::where('expressNumber', $kuaidinum)->first();
        switch ($status) {
            case 1:
                // 已揽件
                $order->setlog('快递已揽件,快递单号'.$kuaidinum.',骑手信息'.$paramData['data']['courierName'].'('.$paramData['data']['courierMobile'].')');
                break;
            case 2:
                // 运输中
                $order->setlog('快递运输中,快递单号'.$kuaidinum.',骑手信息'.$paramData['data']['courierName'].'('.$paramData['data']['courierMobile'].')');
                break;
            case 3:
                // 派送中
                $order->setlog('快递派送中,快递单号'.$kuaidinum.',骑手信息'.$paramData['data']['courierName'].'('.$paramData['data']['courierMobile'].')');
                break;
            case 4:
                // 已签收
                $order->setlog('快递已签收,快递单号'.$kuaidinum.',骑手信息'.$paramData['data']['courierName'].'('.$paramData['data']['courierMobile'].')');
                break;
            case 9:
                // 已取消
                $order->sate=4;
                $order->save();
                $order->setlog('快递已取消');
                break;
            default:
                // 其他状态
        }

// 8. 返回成功响应（快递100要求必须返回 "success"）
        echo "success";

    }
}
