<?php

namespace App\Listeners\Store;

use App\Events\MemberRegisteredEvent;
use App\Events\OrderMessageEvent;
use App\Events\StoreMessageEvent;
use App\Models\MemberAccountLog;
use App\Models\ShortLink;
use App\Models\Tables\Area;
use App\Models\Tables\Table;
use App\Models\Tables\Type;
use App\Services\ConfigService;
use App\Services\MemberAccountService;
use App\Services\ShortLinkService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Store;
use Illuminate\Support\Facades\Config;

class WorkMessageListener implements ShouldQueue
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\MemberRegisteredEvent  $event
     * @return void
     *
     *{
     *    "msgtype": "markdown",
     *    "markdown": {
     *        "content": "实时新增用户反馈<font color=\"warning\">132例</font>，请相关同事注意。\n
     *         >类型:<font color=\"comment\">用户反馈</font>
     *         >普通用户反馈:<font color=\"comment\">117例</font>
     *         >VIP用户反馈:<font color=\"comment\">15例</font>"
     *    }
     *}
     */
    public function handle(StoreMessageEvent $event)
    {
        try {
            $order = $event->order;
            $msgType = $event->type;
            //Log::error($type);
            $config = ConfigService::getStoreConfig('robot_webhook_address', $order->storeId);
            if (empty($config)) {
                return true;
            }
            //实付金额
            $money =  $order->subOrder->money ?? $order->money ?? '';
            //应付金额
            $sellMoney =  $order->subOrder->sellMoney ?? $order->sellMoney ?? '';
            //取单号
            $pickNo = $order->subOrder->pickNo ?? $order->pickNo;
            //桌位号
            $tableName = $order->subOrder->table->name ?? $order->name;
            //桌位区域
            $tableArea = $order->subOrder->table->area->name ?? $order->table->area->name;
            if(empty($tableArea)){
                $area=Area::find($order->areaId);
                $tableArea=$area->name;
            }
            //桌位类型
            $tableType = $order->subOrder->table->type->name ?? $order->table->type->name;
            if(empty($tableType)){
                $typeData=Type::find($order->typeId);
                $tableType=$typeData->name;
            }
            $diningType = $order->subOrder->diningType ?? $order->diningType ?? '';
            //订单类型
            if ($order->type == 1 || $order->orderIndex->type == 1 || $order->type == 3) {
                $type =   $order->subOrder->orderTypeFormat ?? $order->orderTypeFormat ?? "";
            } else {
                $type =   $order->subOrder->diningTypeFormat ?? $order->diningTypeFormat ?? "";
                if ($order->subOrder->state == 6) {
                    return true;
                }
                if ($diningType == 4) {
                    $type = $type . '(' . $tableArea . '-' . $tableType . '-' . $tableName . ')';
                }
            }
            //订单状态
            $stateFormat = $order->subOrder->stateFormat ?? $order->stateFormat ?? '';
            //支付时间
            $createTime = $order->payTime ?? $order->orderIndex->payTime ?? '';
            $createTime = $createTime ?: $order->created_at;
            $orderSn = $order->orderSn ?? $order->orderIndex->orderSn ?? '';
            //支付方式
            $payTypeForamt = $order->payTypeFormat ?? $order->orderIndex->payTypeFormat ?? '';
            //订单来源
            $score = $order->scoreFormat ?? $order->orderIndex->scoreFormat ?? '';
            $url = $config['url'];
            if ($msgType == "newOrder" || $msgType == "inStoreNewOrder" || $msgType == "receive" || $msgType == "pay") {
                $data = [
                    'msgtype' => "markdown",
                    "markdown" => [
                        'content' => "您有新的订单，请及时处理。\n
    状态:<font color=\"comment\">{$stateFormat}</font>
    类型:<font color=\"comment\">{$type}</font>
    流水号:<font color=\"comment\">{$pickNo}</font>
    支付金额:<font color=\"comment\">{$money}</font>
    支付方式:<font color=\"comment\">{$payTypeForamt}</font>
    支付时间:<font color=\"comment\">{$createTime}</font>
    订单来源:<font color=\"comment\">{$score}</font>
    所属门店:<font color=\"comment\">{$order->store->name}</font>
    订单号:<font color=\"comment\">{$orderSn}</font>\n",
                    ]
                ];
                //                if ($diningType == 6) {
                //                    $store = Store::where('uniacid', $order->uniacid)->find($order->storeId);
                //                    if ($store) {
                //                        $model = ShortLink::where('uniacid', $order->uniacid)
                //                            ->where('storeId', $order->storeId)
                //                            ->where('type', 'takeScreen')
                //                            ->first();
                //                        if (empty($model)) {
                //                            $model = ShortLinkService::takeScreen($store);
                //                        }
                //                        $domain = Config::get('app.url');
                //                        $url = $domain. "/admin/#/workbench/takeMeal?id=" . $model->shortLink;
                //                        $url='[取号]('.$url.')';
                //
                //                        $data['markdown']['content'].=$url;
                //                        file_put_contents("log.txt", $data['markdown']['content'].PHP_EOL, FILE_APPEND);
                //
                //                    }
                //
                //                }
                if ($order->diningType == 0 && $order->scene == 1) {
                    $contact = $order->address['mobile'] . '(' . $order->address['contact'] . ')';
                    $address = $order->address['address'] . $order->address['description'];
                    $data = [
                        'msgtype' => "markdown",
                        "markdown" => [
                            'content' => "您有新的订单，请及时处理。\n
    状态:<font color=\"comment\">{$stateFormat}</font>
    类型:<font color=\"comment\">{$type}</font>
    流水号:<font color=\"comment\">{$pickNo}</font>
    支付金额:<font color=\"comment\">{$money}</font>
    支付方式:<font color=\"comment\">{$payTypeForamt}</font>
    支付时间:<font color=\"comment\">{$createTime}</font>
    订单来源:<font color=\"comment\">{$score}</font>
    配送信息:<font color=\"comment\">{$address}</font>
    <font color=\"comment\">{$contact}</font>
    所属门店:<font color=\"comment\">{$order->store->name}</font>
    订单号:<font color=\"comment\">{$orderSn}</font>",
                        ]
                    ];
                }
            } elseif ($msgType == "refundApply") {
                $data = [
                    'msgtype' => "markdown",
                    "markdown" => [
                        'content' => "有用户申请订单退款了，请及时处理。\n
    状态:<font color=\"comment\">{$stateFormat}</font>
    类型:<font color=\"comment\">{$type}</font>
    流水号:<font color=\"comment\">{$pickNo}</font>
    支付金额:<font color=\"comment\">{$money}</font>
    支付方式:<font color=\"comment\">{$payTypeForamt}</font>
    支付时间:<font color=\"comment\">{$createTime}</font>
    订单来源:<font color=\"comment\">{$score}</font>
    所属门店:<font color=\"comment\">{$order->store->name}</font>
    订单号:<font color=\"comment\">{$orderSn}</font>",
                    ]
                ];
                Log::error($data);
            } elseif ($msgType == "deliveryAbnormal") {
                $reason = collect($order->deliveryOrder->log)->first();
                Log::error($reason);
                $data = [
                    'msgtype' => "markdown",
                    "markdown" => [
                        'content' => "有订单配送异常了，请及时处理。\n
    状态:<font color=\"comment\">{$stateFormat}</font>
    类型:<font color=\"comment\">{$type}</font>
    流水号:<font color=\"comment\">{$pickNo}</font>
    支付金额:<font color=\"comment\">{$money}</font>
    原因:<font color=\"comment\">{$reason['text']}</font>
    订单来源:<font color=\"comment\">{$score}</font>
    所属门店:<font color=\"comment\">{$order->store->name}</font>
    订单号:<font color=\"comment\">{$orderSn}</font>",
                    ]
                ];
                Log::error($data);
            } elseif ($msgType == "waiter") {
                $data = [
                    'msgtype' => "markdown",
                    "markdown" => [
                        'content' => $tableArea . '-' . $tableType . '-' . $tableName."呼叫服务员，请及时处理。"
                    ]
                ];
                Log::error($data);
            }
            $res = Http::asJson()->post($url, $data)->body();
            Log::error("---WorkMessageListener----");
            Log::error($res);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return true;
        }
    }

    public function shouldQueue(StoreMessageEvent $event)
    {
        return true;
    }
}
