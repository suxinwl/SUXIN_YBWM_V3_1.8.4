<?php

namespace App\Http\Controllers\Channel;
use App\Models\Express;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use App\Exports\FinancialExport;
use App\Exports\FlowRecordExport;
use App\Exports\LedgerDetailsExport;
use App\Exports\WithdrawalListExport;
use App\Exports\OrderDataExport;
use App\Exports\StoreAccountExport;
use App\Exports\StoredValueConsumptionExport;
use App\Exports\TiktokOrderDataExport;
use App\Http\Requests\Cart\CartRequest;
use App\Models\Douyin;
use App\Models\Hardware;
use App\Models\InStore\Order\Order;
use App\Models\Kuaishou;
use App\Models\Order\OrderGoods;
use App\Models\Order\OrderIndex;
use App\Models\Order\TakeOutOrder;
use App\Models\Printer;
use App\Models\PrinterLog;
use App\Models\RefundOrder;
use App\Models\Store;
use App\Models\Tables\Table;
use App\Models\TakeOut\Cart;
use App\Models\TakeOut\CartList;
use App\Models\TakeOut\Checkout;
use App\Models\TiktokStoreList;
use App\Models\TiktokVerifyList;
use App\Services\ConfigService;
use App\Services\OpenWechat\ChannelOpenWechat;
use App\Services\OrderService;
use App\Services\InStoreOrderService;
use App\Services\PrinterService;
use App\Services\StoreGeoService;
use App\Traits\StatisticsTrait;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB as FacadesDB;
use Illuminate\Support\Facades\Http;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class OrderController extends ApiController
{
    public function index(Request $request)
    {
        $user = $this->user();
        $storeId = $this->storeId();
        $isolate = $this->isolate();
        $timeArr = $this->timeArr(true);
        $list = TakeOutOrder::where("uniacid", $this->uniacid())
            ->when($request->scene, function ($q) use ($request) {
                return $q->where('scene', $request->scene);
            })
            ->when($request->keyword, function ($q) use ($request) {
                return $q->where(
                    function ($q) use ($request) {
                        return $q->where('orderSn', "like", "%$request->keyword%")
                            ->orWhere(FacadesDB::raw('CONCAT(pickFix, pickNo)'), "like", "%$request->keyword%")
                            ->orWhere('mobile', "like", "%$request->keyword%");
                    }
                );
            })
            ->when($request->payType, function ($q) use ($request) {
                return $q->whereHas('orderIndex', function ($q) use ($request) {
                    if ($request->payType == 'wexin') {
                        return $q->weixin();
                    }
                    if ($request->payType == 'ali') {
                        return $q->ali();
                    }
                    if ($request->payType == 'balance') {
                        return $q->balance();
                    }
                    return $q;
                });
            })
            ->when($request->timeType, function ($q) use ($request, $timeArr) {
                return $q->where($request->timeChannel ?? 'created_at', '>=', $timeArr['startTime'])
                    ->where($request->timeChannel ?? 'created_at', '<=', $timeArr['endTime']);
            })
            ->when($request->source, function ($q) use ($request) {
                return $q->where('source', appType($request->source));
            })
            ->when($request->appointment, function ($q) use ($request) {
                if ($request->appointment == "instant") {
                    return $q->where('appointment', 0);
                }
                if ($request->appointment == "appointment") {
                    return $q->where('appointment', 1);
                }
            })
            ->when($request->userKeyword, function ($q) use ($request) {
                return $q->whereHas('user', function ($q) use ($request) {
                    return $q->where('mobile', "like", "%$request->userKeyword%")
                        ->orWhere('nickname', "li1ke", "%$request->userKeyword%");
                });
            })->when($user, function ($q) use ($user) {
                if ($user->isAdmin == 0) {
                    if (empty($user->storeId)) {
                        $q->where('storeId', 0);
                    } else {
                        $q->whereIn('storeId', $user->storeId);
                    }
                }
                return $q;
            })->when($isolate, function ($q) use ($storeId, $isolate) {
                return $q->whereHas('store', function ($q) use ($storeId) {
                    return $q->where('isolate', 1)->where('storeId', $storeId);
                });
            })
            ->when(!$isolate, function ($q) use ($storeId, $isolate) {
                return $q->whereHas('store', function ($q) use ($storeId) {
                    return $q->where('isolate', 0)->when($storeId, function ($q) use ($storeId) {
                        return $q->where('storeId', $storeId);
                    });
                });
            })
            ->when($request->state, function ($q) use ($request) {
                switch ($request->state) {
                    case 'close':
                        return $q->close();
                        break;
                    case 'unpaid':
                        return $q->unpaid();
                        break;
                    case 'unReceived':
                        return $q->unReceived();
                        break;
                    case 'making':
                        return $q->making();
                        break;
                    case 'waiting':
                        return $q->waiting();
                        break;
                    case 'delivery':
                        return $q->delivery();
                        break;
                    case 'complete':
                        return $q->complete();
                        break;
                    case 'refundApply':
                        return $q->refundApply();
                        break;
                    case 'refund':
                        return $q->refund();
                        break;
                    case 'afterSale':
                        return $q->afterSale();
                        break;
                    case 'reject':
                        return $q->reject();
                        break;
                    case 'deliveryAbnormal':
                        return $q->deliveryAbnormal();
                        break;
                    default:
                        return $q;
                }
            })
            ->orderBy('id', 'desc')
            ->paginate($request->pageSize ?? 10, '*', 'pageNo');
        return $this->success($list);
    }


    public function count(Request $request)
    {
        $user = $this->user();
        $storeId = $this->storeId();
        $isolate = $this->isolate();
        $timeArr = $this->timeArr(true);
        $model = TakeOutOrder::with([])->where("uniacid", $this->uniacid())
            ->when($request->scene, function ($q) use ($request) {
                return $q->where('scene', $request->scene);
            })
            ->when($isolate, function ($q) use ($storeId, $isolate) {
                return $q->whereHas('store', function ($q) use ($storeId) {
                    return $q->where('isolate', 1)->where('storeId', $storeId);
                });
            })
            ->when(!$isolate, function ($q) use ($storeId, $isolate) {
                return $q->whereHas('store', function ($q) use ($storeId) {
                    return $q->where('isolate', 0)->when($storeId, function ($q) use ($storeId) {
                        return $q->where('storeId', $storeId);
                    });
                });
            })
            ->count()->first();
        $model->makeHidden(['store', 'goods', 'orderIndex', 'user', 'deliveryOrder', 'payGift', 'takeScreen'])->setAppends([]);
        $data['takeout'] = $model;
        $model = Order::with([])
            ->where("uniacid", $this->uniacid())
            ->when($request->scene, function ($q) use ($request) {
                return $q->where('scene', $request->scene);
            })
            ->when($request->storeId, function ($q) use ($request) {
                return $q->where('storeId', $request->storeId);
            })
            ->count()->first();
        $model->makeHidden(['goods', 'table', 'store', 'subOrder', 'user', 'admin', 'takeScreen'])->setAppends([]);
        $data['inStore'] = $model;
        return $this->success($data);
    }


    public function show(Request $request, $id)
    {
        $user = $this->user();
        $storeId = $this->storeId();
        $isolate = $this->isolate();
        $timeArr = $this->timeArr(true);
        $order =  TakeOutOrder::where('id', $id)
            ->with([
                'goods', 'orderIndex', 'store', 'user', 'deliveryOrder', 'payGift', 'log'
            ])
            ->when($isolate, function ($q) use ($storeId, $isolate) {
                return $q->whereHas('store', function ($q) use ($storeId) {
                    return $q->where('isolate', 1)->where('storeId', $storeId);
                });
            })
            ->when(!$isolate, function ($q) use ($storeId, $isolate) {
                return $q->whereHas('store', function ($q) use ($storeId) {
                    return $q->where('isolate', 0)->when($storeId, function ($q) use ($storeId) {
                        return $q->where('storeId', $storeId);
                    });
                });
            })
            ->where("uniacid", $this->uniacid())
            ->first();
        if (empty($order)) {
            throw new BadRequestException('数据不存在');
        }
        return $this->success($order);
    }



    public function notes(Request $request, $id)
    {
        $storeId = $this->storeId();
        $order =  TakeOutOrder::where('id', $id)->where('storeId', $storeId)->where("uniacid", $this->uniacid())->first();
        if (empty($order)) {
            throw new BadRequestException('数据不存在');
        }
        $order->storeNotes = $request->notes;
        $order->save();
        return $this->success([], '备注成功');
    }


    /**
     * 接单
     */
    public function received(Request $request, $id)
    {
        OrderService::received($id, '门店手动接单，商品制作中');
        return $this->success([], '接单成功');
    }

    /**
     * 制作完成
     */
    public function maked(Request $request, $id)
    {
        OrderService::maked($id);
        return $this->success([], '制作成功');
    }

    /**
     * 发货
     */
    public function delivery(Request $request, $id)
    {
        if($request->expressNumber){
            $order =  TakeOutOrder::where('id', $id)->where("uniacid", $this->uniacid())->first();
            if($order){
                $order->expressNumber=trim($request->expressNumber);
                $order->state=5;
                $order->save();
                $order->setlog('快递已发货');
            }
            return $this->success([], '发货成功');
        }
        if($request->kuaidicom){
            $order =  TakeOutOrder::where('id', $id)->where("uniacid", $this->uniacid())->first();
            $data=Express::addOrder($request->kuaidicom,$order);
            if($data['returnCode']==200){
                $order->expressNumber=$data['data']['kuaidinum'];
                $order->state=5;
                $order->save();
                $order->setlog('快递已发货,快递单号'.$data['data']['kuaidinum']);
                return $this->success([], '发货成功');
            }else{
                throw new BadRequestException($data['message']);
            }
        }
        OrderService::delivery($id, $request->deliveryType, intval($request->channel));
        return $this->success([], '配送成功');
    }

    /**
     * 订单完成
     */
    public function complete(Request $request, $id)
    {
        OrderService::complete($id);
        return $this->success([], '订单已完成');
    }


    /**
     * 退款
     */
    public function refund(Request $request, $id)
    {
        try {
            $orderIndex = OrderIndex::whereHas('order', function ($q) use ($id) {
                return $q->whereIn('state', [2, 3, 4, 5, 6, 7])->where('id', $id);
            })->paid()->first();
            if (empty($orderIndex)) {
                throw new BadRequestException('数据不存在');
            }
            $refundMoney = $request->refundMoney>0?$request->refundMoney:$orderIndex->order->money;
            if ($refundMoney > $orderIndex->order->money){
                throw new BadRequestException('退款金额不能大于实付金额');
            };
            if (OrderService::refund($id, $refundMoney, $this->user()->id, $request->refundCause)) {
                return $this->success([], '操作完成,已退款');
            }
            return $this->success([], '退款成功');
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }

    /**
     * 拒绝退款
     */
    public function reject(Request $request, $id)
    {
        try {
            $orderIndex = OrderIndex::whereHas('order', function ($q) use ($id) {
                return $q->refundApply()->where('id', $id);
            })->paid()->first();
            if (empty($orderIndex)) {
                throw new BadRequestException('数据不存在');
            }
            if (OrderService::rejectRefund($id, $orderIndex->order->money, $request->notes ?? '平台拒绝退款', $this->user()->id, $this->user()->realName ?? $this->user->username)) {
                return $this->success([], '操作成功');
            }
            return $this->failed('操作失败');
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }

    //订单打印
    public function printOrder(Request $request, $id)
    {
        $scene = $request->scene ?: 1;
        switch ($scene) {
            case 1;
                if (OrderService::print($id)) {
                    return $this->success([], '已发送打印指令');
                };
                break;
            case 3:
                $orderType = $request->order_type ?: 5;
                if (InStoreOrderService::print($id, $orderType)) {
                    return $this->success([], '已发送打印指令');
                };
                break;
            case 4:
                if (InStoreOrderService::print($id, 8)) {
                    return $this->success([], '已发送打印指令');
                };
                break;
            case 5:
                if (InStoreOrderService::print($id, 9)) {
                    return $this->success([], '已发送打印指令');
                };
                break;
            case 6: //预结单
                if (InStoreOrderService::print($id, 4)) {
                    if ($request->orderSn) {
                        Table::where("orderSn", $request->orderSn)->update(["state" => 4]);
                    }
                    return $this->success([], '已发送打印指令');
                };
                break;
            case 7: //补打结账单
                if (InStoreOrderService::print($id, 7)) {
                    return $this->success([], '已发送打印指令');
                };
                break;
            case 11: //补打交班
                if (InStoreOrderService::print($id, 11)) {
                    return $this->success([], '已发送打印指令');
                };
                break;
            case 13: //补打制作总单
                if (InStoreOrderService::print($id, 13)) {
                    return $this->success([], '已发送打印指令');
                };
                break;
            case 14: //补打制作分单
                if (InStoreOrderService::print($id, 14)) {
                    return $this->success([], '已发送打印指令');
                };
                break;
            case 15: //补打制作分单
                if (OrderService::print($id, 15)) {
                    return $this->success([], '已发送打印指令');
                };
                break;
            case 16: //补打制作分单
                if (OrderService::print($id, 16)) {
                    return $this->success([], '已发送打印指令');
                };
                break;
            case 17: //补打制作分单
                if (OrderService::print($id, 17)) {
                    return $this->success([], '已发送打印指令');
                };
                break;
            case 18: //补打制作分单
                if (OrderService::print($id, 18)) {
                    return $this->success([], '已发送打印指令');
                };
                break;
            default:
                if (OrderService::print($id)) {
                    return $this->success([], '已发送打印指令');
                };
                break;
        }
        return $this->failed([], '打印指令发送失败');
    }

    //打印测试
    public function printTest(Request $request, $id)
    {
        $params = Hardware::where('id', $id)->first();
        if ($params) {
            $params = empty($params) ? array() : $params->toArray();
            if ($params['type'] == 1) {
                if ($params['vendor'] == 'feie') {
                    Printer::printTest($params, 1);
                }
                if ($params['vendor'] == 'esLink') {
                    Printer::printTest($params, 2);
                }
                if ($params['vendor'] == 'spyun') {
                    Printer::printTest($params, 4);
                }
                if ($params['vendor'] == 'daqu') {
                    Printer::printTest($params, 5);
                }
                if ($params['vendor'] == 'jiabo') {
                    Printer::printTest($params, 6);
                }
                if ($params['vendor'] == 'xinye') {
                    Printer::printTest($params, 7);
                }
            }
            if ($params['type'] == 2) {
                if ($params['vendor'] == 'feie') {
                    Printer::printTest($params, 3);
                }
                if ($params['vendor'] == 'xinye') {
                    Printer::printTest($params, 8);
                }
            }
            if ($params['type'] == 3) {
                if ($params['config']['model'] == 2) {
                    $data = [
                        'agent_id' => '202007291001',
                        'agent_secret' => "11476900311476900311476900311111",
                        'msg' => "新款云音响连接成功",
                        'sbx_id' => $params['config']['sn']
                    ];
                    $res = Http::asJson()->post('http://iot.solomo-info.com:9306/admin/common/msgpush', $data)->getBody()->getContents();
                } else {
                    $data = [
                        'cmd' => 'voice',
                        'msg' => '云音响连接成功',
                        'msgid' => time() . rand(1, 999999)
                    ];

                    if (substr($params['config']['sn'], 1,2) == 'MS') {
                        $url = 'http://cs.mqlinks.com/txmsgpush/';
                        $map = [
                            'sbx_id' => $params['config']['sn'],
                            'agent_id' => json_encode($data, true),
                        ];
                    } else {
                        $url = 'http://cs.mqlinks.com/msgpush2/qos0.php';
                        $map = [
                            'sbx_id' => $params['config']['sn'],
                            'agent_id' => base64_encode(json_encode($data, true)),
                        ];
                    }
                    $res = Http::asJson()->post($url, $map)->body();
                }
            }
            return $this->success([], '指令发送成功');
        } else {
            return $this->failed(__('设备不存在'));
        }
    }

    /**
     * 关闭订单
     */
    public function close(Request $request, $id)
    {
        OrderService::close($id);
        return $this->success();
    }


    //数据--订单数据导出
    public function orderDataExport(Request $request)
    {
        $params = $request->all();
        $uniacid = $this->uniacid();
        $user = $this->user();
        $params['user'] = $user;
        $params['uniacid'] = $uniacid;
        $params['timeType'] = $params['timeType'] ?: 2;
        $params['timeChannel'] = 'created_at';
        $timeArr = $this->timeArr(true, $params['timeType']);
        $params['timeArr'] = $timeArr;
        return Excel::download(new OrderDataExport($params), 'orderData.xlsx');
    }

    public function cancelPrint(Request $request)
    {
        $data = Printer::delPrinterSqs($request->id);
        return $this->success([], '待打印订单已清理');
    }
    public function changePrint(Request $request)
    {
        $params = Hardware::where('id', $request->id)->first();
        $display = $params->display == 1 ? 2 : 1;
        $msg = $params->display == 1 ? '打印机已禁用' : '打印机已启用';
        $params->display = $display;
        $params->save();
        return $this->success([], $msg);
    }

    public function refundNew(Request $request, $orderSn)
    {
        $refundOrder = RefundOrder::create([
            'orderSn' => $orderSn,
            'goodsMoney' => $request->goodsMoney ?? 0,
            'boxMoney' => $request->boxMoney ?? 0,
            "deliveryMoney" => $request->deliveryMoney ?? 0,
            "tableMoney" => $request->deliveryMoney ?? 0,
            'state' => 0
        ]);
    }

    //抖音核销
    //验券准备
    public function prepare(Request $request)
    {
        $uniacid = $this->uniacid();
        $code = trim($request->code);
        $order_type = $request->order_type ?: 1;
        if (!$code) {
            return $this->failed(__('请输入抖音团购券码'));
        }
        if ($order_type == 1) {
            try {
                $takeOutOrder = TakeOutOrder::with(['orderIndex'=> function ($q) use ($uniacid,$code) {
                    return $q->where('uniacid', $uniacid)->where('orderSn', $code);
                }])->whereIn('state', [2, 3, 4])->where('orderSn', $code)->first();
                if (empty($takeOutOrder)) {
                    throw new BadRequestException('数据不存在');
                }
                return $this->success($takeOutOrder,'验券成功');
            } catch (\Exception $e) {
                return $this->failed($e->getMessage());
            }
        } elseif($order_type == 2) {
            $data = Douyin::prepare($uniacid, $code);
        }elseif($order_type == 3) {
            $data = Kuaishou::prepare($code);
        }

        if ($data['data']['error_code'] == 0) {
            if (!$data['data']['certificates']) {
                return $this->failed('无可用团购券');
            }
            return $this->success($data, '验券成功');
        } else {
            return $this->failed(__($data['data']['description']));
        }
    }
    //验券
    public function verify(Request $request)
    {
        $uniacid = $this->uniacid();
        $code = trim($request->code);
        $storeId = $this->storeId() ?: $request->storeId;
        $order_type = $request->order_type ?: 1;
        if($order_type == 1)  {
            try {
                $takeOutOrder = TakeOutOrder::with(['orderIndex'=> function ($q) use ($uniacid,$code) {
                    return $q->where('uniacid', $uniacid)->where('orderSn', $code);
                }])->whereIn('state', [2, 3, 4])->where('orderSn', $code)->first();
                if (empty($takeOutOrder)) {
                    throw new BadRequestException('数据不存在');
                }
                OrderService::complete($takeOutOrder->id);
                return $this->success('订单已核销');
            } catch (\Exception $e) {
                return $this->failed($e->getMessage());
            }
        }
        $unique = TiktokStoreList::where(['uniacid' => $uniacid, 'storeId' => $storeId])->where('order_type', $order_type)->first();
        if (empty($unique)) {
            return $this->failed(__("请先配置门店关联设置门店ID"));
        }
        $verify_data = $request->verify_data;
        $verify_token = $verify_data['data']['verify_token'];
        $encryptedcodes = array_column($verify_data['data']['certificates'], 'encrypted_code');
        $pod_id = $unique->poi_id;
        if ($order_type == 2) {
            $data = Douyin::verify($uniacid, $verify_token, $pod_id, $encryptedcodes);
        } elseif($order_type == 3)  {
            $data = Kuaishou::verify($uniacid, $verify_token, $pod_id, $encryptedcodes, $verify_data['data']['order_id']);
        }
        if ($data['data']['error_code'] == 0) {
            $content = '';
            foreach ($verify_data['data']['certificates'] as $k => $v) {
                $content .= $v['sku']['title'];
            }
            TiktokVerifyList::create([
                'uniacid' => $this->uniacid(),
                'storeId' => $this->storeId(),
                'poi_id' => $unique->poi_id,
                'poi_name' => $unique->poi_name,
                'code' => $code,
                'content' => $content,
                'state' => 1,
                'verify_id' => $data['data']['verify_results'][0]['verify_id'],
                'certificate_id' => $data['data']['verify_results'][0]['certificate_id'],
                'userId' => $this->userId(),
                'order_type' => $order_type
            ]);
            return $this->success($data, '验券成功');
        } else {
            return $this->failed(__($data['data']['description']));
        }
    }

    //撤销核销
    public function revokeVerify(Request $request)
    {
        $uniacid = $this->uniacid();
        $res = TiktokVerifyList::where('id', $request->id)->first();
        if (!$res) {
            return $this->failed(__('数据不存在'));
        }
        $data = Douyin::cancel($uniacid, $res->verify_id, $res->certificate_id);
        if ($data['data']['error_code'] == 0 && $data['data']['description'] == 'success') {
            $res->state = 2;
            $res->save();
            return $this->success($data, '验券撤销成功');
        } else {
            return $this->failed(__($data['data']['description']));
        }
    }

    //获取抖音第三方门店列表
    public function getStoreList(Request $request)
    {
        $uniacid = $this->uniacid();
        $storeId = $this->storeId();
        $order_type = $request->order_type ?: 1;
        $query = TiktokStoreList::with('store')
            ->where('order_type', $order_type)->where('uniacid', $uniacid);
        if ($storeId) {
            $query = $query->where('storeId', $storeId);
        }
        $list = $query->get();
        return $this->success($list);
    }

    //获取抖音核销列表
    public function getTiktokVerifyList(Request $request)
    {
        $uniacid = $this->uniacid();
        $order_type = $request->order_type ?: 1;
        $storeId = $this->storeId() ?: $request->storeId;
        $list = TiktokVerifyList::with(['store', 'admin'])
            ->where('uniacid', $uniacid)
            ->where('order_type', $order_type)
            ->when($request->code, function ($q) use ($request) {
                return $q->where("code", "like", "%{$request->code}%");
            })
            ->when($request->storeId, function ($q) use ($request, $storeId) {
                return $q->where("storeId", $storeId);
            })
            ->when($request->startTime, function ($q) use ($request) {
                return $q->where("created_at", ">=", $request->startTime);
            })
            ->when($request->endTime, function ($q) use ($request) {
                return $q->where("created_at", "<=", $request->endTime);
            })->orderBy('id', 'desc')
            ->paginate($request->pageSize ?? 20, '*', 'pageNo');
        return $this->success($list);
    }

    public function tiktokOrderDataExport(Request $request)
    {
        $params = $request->all();
        $uniacid = $this->uniacid();
        $user = $this->user();
        $order_type = $request->order_type ?: 1;
        $params['order_type'] = $order_type;
        $params['user'] = $user;
        $params['uniacid'] = $uniacid;
        $params['timeType'] = $params['timeType'] ?: 2;
        $params['timeChannel'] = 'created_at';
        $timeArr = $this->timeArr(true, $params['timeType']);
        $params['timeArr'] = $timeArr;
        return Excel::download(new TiktokOrderDataExport($params), 'tiktokOrder.xlsx');
    }


    //财务对账
    public function financialExport(Request $request)
    {
        $params = $request->all();
        $uniacid = $this->uniacid();
        $user = $this->user();
        $params['user'] = $user;
        $params['uniacid'] = $uniacid;
        $params['timeType'] = $params['timeType'] ?: 2;
        $params['timeChannel'] = 'created_at';
        $timeArr = $this->timeArr(true, $params['timeType']);
        $params['timeArr'] = $timeArr;
        return Excel::download(new FinancialExport($params), 'financialExport.xlsx');
    }
    //储值消费
    public function storedValueConsumptionExport(Request $request)
    {
        $params = $request->all();
        $uniacid = $this->uniacid();
        $user = $this->user();
        $params['user'] = $user;
        $params['uniacid'] = $uniacid;
        $params['timeType'] = $params['timeType'] ?: 2;
        $params['timeChannel'] = 'created_at';
        $timeArr = $this->timeArr(true, $params['timeType']);
        $params['timeArr'] = $timeArr;
        return Excel::download(new StoredValueConsumptionExport($params), 'storedValueConsumptionExport.xlsx');
    }
    //门店账户
    public function storeAccountExport(Request $request)
    {
        $params = $request->all();
        $uniacid = $this->uniacid();
        $user = $this->user();
        $params['user'] = $user;
        $params['uniacid'] = $uniacid;
        $params['timeType'] = $params['timeType'] ?: 2;
        $params['timeChannel'] = 'created_at';
        $timeArr = $this->timeArr(true, $params['timeType']);
        $params['timeArr'] = $timeArr;
        return Excel::download(new StoreAccountExport($params), 'storeAccountExport.xlsx');
    }
    //流水记录
    public function flowRecordExport(Request $request)
    {
        $params = $request->all();
        $uniacid = $this->uniacid();
        $user = $this->user();
        $params['user'] = $user;
        $params['uniacid'] = $uniacid;
        $params['timeType'] = $params['timeType'] ?: 2;
        $params['timeChannel'] = 'created_at';
        $timeArr = $this->timeArr(true, $params['timeType']);
        $params['timeArr'] = $timeArr;
        return Excel::download(new FlowRecordExport($params), 'flowRecordExport.xlsx');
    }
    //分账明细
    public function ledgerDetailsExport(Request $request)
    {
        $params = $request->all();
        $uniacid = $this->uniacid();
        $user = $this->user();
        $params['user'] = $user;
        $params['uniacid'] = $uniacid;
        $params['timeType'] = $params['timeType'] ?: 2;
        $params['timeChannel'] = 'created_at';
        $timeArr = $this->timeArr(true, $params['timeType']);
        $params['timeArr'] = $timeArr;
        return Excel::download(new LedgerDetailsExport($params), 'ledgerDetailsExport.xlsx');
    }
    //提现列表
    public function withdrawalListExport(Request $request)
    {
        $params = $request->all();
        $uniacid = $this->uniacid();
        $user = $this->user();
        $params['user'] = $user;
        $params['uniacid'] = $uniacid;
        $params['timeType'] = $params['timeType'] ?: 2;
        $params['timeChannel'] = 'created_at';
        $timeArr = $this->timeArr(true, $params['timeType']);
        $params['timeArr'] = $timeArr;
        return Excel::download(new WithdrawalListExport($params), 'ledgerDetailsExport.xlsx');
    }

    //小程序发货
    public function wxDelivery(Request $request)
    {
        try {
            $uniacid = $this->uniacid();
            $config = ChannelOpenWechat::getConfig($uniacid, 'mini');
            $app = ChannelOpenWechat::miniProgram($uniacid);
            $res = $app->httpPostJson('wxa/sec/order/is_trade_managed', ['appid' => $config->authorizer_appid]);

            if ($res['errcode'] != 0 || $res['is_trade_managed'] == false) {
                throw  new BadRequestException('小程序暂未开通小程序发货关联');
            }
            $orderList=$app->httpPostJson('wxa/sec/order/get_order_list', ['order_state' => 1]);

            if ($orderList['errcode'] != 0 ) {
                throw  new BadRequestException($orderList['errmsg']);
            }else{
                $order_list=$orderList['order_list'];
                if($order_list){
                    foreach ($order_list as $order){
                        date_default_timezone_set('Asia/Shanghai');
                        $data=[
                            'order_key'=>[
                                'order_number_type'=>2,
                                'transaction_id'=>$order['transaction_id'],
                            ],
                            'delivery_mode'=>1,
                            'logistics_type'=>2,
                            'shipping_list'=>[
                                ['item_desc'=>'小程序订单发货']
                            ],
                            'upload_time'=>date("Y-m-d\TH:i:s").'.000+0800',
                            'payer'=>[
                                'openid'=>$order['openid']
                            ]
                        ];
                        $a=$orderList=$app->httpPostJson('wxa/sec/order/upload_shipping_info', $data);

                    }
                    return $this->success([],'发货成功');
                }else{
                    throw  new BadRequestException('当前小程序暂无待发货订单');
                }
            }

        } catch (\Exception $e) {
            throw  new BadRequestException($e->getMessage());

        }
    }


    public function selfVerify(Request $request, $id)
    {
        try {
            $orderIndex = OrderIndex::whereHas('order', function ($q) use ($id) {
                return $q->whereIn('state', [2, 3, 4, 5, 6, 7]);
            })->where('orderSn', $id)->paid()->first();
            if (empty($orderIndex)) {
                throw new BadRequestException('数据不存在');
            }
            OrderService::complete($orderIndex->order->id);
            return $this->success('订单已核销');
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }

    }
}
