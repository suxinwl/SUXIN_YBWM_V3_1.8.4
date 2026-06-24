<?php

namespace App\Http\Controllers\Channel\InStore;

use App\Exports\InstoreOrderDataExport;
use App\Http\Controllers\Channel\ApiController;
use App\Http\Requests\Cart\CartRequest;
use App\Http\Resources\Channel\Order\InStoreOrderListResources;
use App\Http\Resources\Channel\Order\InStoreOrderResources;
use App\Http\Resources\ChannelApi\Order\OrderListResources;
use App\Models\CostomPay;
use App\Models\Coupon\MemberCoupon;
use App\Models\InStore\Cart as InStoreCart;
use App\Models\InStore\ChannelCart;
use App\Models\InStore\Coupon;
use App\Models\InStore\Order\Order;
use App\Models\InStore\Order\ParentOrder;
use App\Models\Order\Discount;
use App\Models\Order\OrderGoods;
use App\Models\Order\OrderIndex;
use App\Models\Order\PayLog;
use App\Models\Order\TakeOutOrder;
use App\Models\PayConfig;
use App\Models\Store;
use App\Models\Tables\Table;
use App\Models\TakeOut\Cart;
use App\Models\TakeOut\CartList;
use App\Models\TakeOut\Checkout;
use App\Services\ConfigService;
use App\Services\InStoreOrderService;
use App\Models\MemberAccount;
use App\Services\OrderNotifyService;
use App\Services\OrderService;
use App\Services\PayService;
use App\Services\StoreGeoService;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB as FacadesDB;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use App\Models\Coupon\Coupon as Coupons;
use function PHPUnit\Framework\isEmpty;
use App\Models\Store\StoreGoodsSku;
class OrderController extends ApiController
{
    public function index(Request $request)
    {
        $userId = $this->userId();
        $uniacid = $this->uniacid();
        $storeId = $this->storeId();
        $user = $this->user();
        $timeArr = $this->timeArr(true);
        $list = Order::with(['subOrder', 'orderIndex', 'goods', 'table'])
            ->when($storeId, function ($q) use ($storeId) {
                return $q->where('storeId', $storeId);
            })
            ->when($request->diningType, function ($q) use ($request) {
                return $q->where('diningType', $request->diningType);
            })
            ->when($user, function ($q) use ($user) {
                if ($user->isAdmin == 0) {
                    if (empty($user->storeId)) {
                        $q->where('storeId', 0);
                    } else {
                        $q->whereIn('storeId', $user->storeId);
                    }
                }
                return $q;
            })
            ->where('uniacid', $uniacid)
            ->when($request->state || empty($request->state), function ($q) use ($request) {
//                if (empty($request->state)) {
//                    return $q->where(function ($q) {
//                        return $q->where(function ($q) {
//                            return $q->whereIn('diningType', [5, 6])->whereIn('state', [2, 3, 4, 5, 6, 8, 10]);
//                        })->orWhere(function ($q) {
//                            return $q->where('diningType', 4)->whereIn('state', [1, 2]);
//                        })->orWhere(function ($q) {
//                            return $q->where('diningType', 4)->whereIn('state', [3, 4, 5, 6, 8, 10]);
//                        });
//                    });
//                }
                if (empty($request->state)) {
                    return $q->where(function ($q) {
                        return $q->where(function ($q) {
                            return $q->whereNull("prentOrderSn")->whereIn('diningType', [5, 6]);
                        })->orWhere(function ($q) {
                            return $q->whereNotNull("prentOrderSn")->where('diningType', 4)->whereIn('state', [1, 2]);
                        })->orWhere(function ($q) {
                            return $q->whereNull("prentOrderSn")->where('diningType', 4)->whereIn('state', [3, 5, 6]);
                        });
                    });
                }
                if ($request->state == 'unReceived') {
                    return $q->unReceived();
                }
                if ($request->state == 'making') {
                    return $q->whereIn('diningType', [5, 6])->where('state', 3)->whereNull("prentOrderSn");
                }
                if ($request->state == 'waiting') {
                    return $q->whereIn('diningType', [5, 6])->where('state', 4)->whereNull("prentOrderSn"); //待取单
                }
                if ($request->state == 'dining') {
                    return $q->where('diningType', 4)->where('state', 3)->whereNull("prentOrderSn");
                }
                if ($request->state == 'complete') {
                    return $q->where('state', 6)->whereNull("prentOrderSn");
                }
                if ($request->state == 'close') {
                    return $q->where('state', 0)->whereNull("prentOrderSn");
                }
                if ($request->state == 'refund') {
                    return $q->where('state', 8)->whereNull("prentOrderSn");
                }
            })
            ->when($request->keyword, function ($q) use ($request) {
                return $q->where(
                    function ($q) use ($request) {
                        return $q->where('orderSn', "like", "%$request->keyword%")
                            ->orWhere(FacadesDB::raw('CONCAT(pickFix, pickNo)'), "like", "%{$request->keyword}%");
                    }
                );
            })
            ->when($request->timeType, function ($q) use ($request, $timeArr) {
                return $q->where($request->timeChannel ?? 'created_at', '>=', $timeArr['startTime'])
                    ->where($request->timeChannel ?? 'created_at', '<=', $timeArr['endTime']);
            })
            ->when($request->tableKeyword, function ($q) use ($request) {
                return $q->whereHas('table', function ($q) use ($request) {
                    return $q->where('name', "like", "%{$request->tableKeyword}%");
                });
            })
            ->when($request->source, function ($q) use ($request) {
                return $q->where('source', appType($request->source));
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
                    if ($request->payType == 'cash') {
                        return $q->cash();
                    }
                    return $q;
                });
            })
            ->when($request->source, function ($q) use ($request) {
                return $q->where('source', appType($request->source));
            })
            ->when($request->userKeyword, function ($q) use ($request) {
                return $q->whereHas('user', function ($q) use ($request) {
                    return $q->where('mobile', "like", "%$request->userKeyword%")->orWhere('nickname', "like", "%$request->userKeyword%");
                });
            })
            ->where("goodsNum", ">", 0)
            ->where("uniacid", $this->uniacid())
            ->groupBy('orderSn')
            ->orderBy('id', 'desc')
            ->paginate($request->pageSize ?? 20, '*', 'pageNo');

        return $this->success(new InStoreOrderListResources($list));
    }


    public function count(Request $request)
    {
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
        return $this->success($model);
    }


    public function show(Request $request, $id)
    {
        $userId = $this->userId();
        $uniacid = $this->uniacid();
        $model = Order::with(['subOrder', 'orderIndex', 'goods', 'table', 'discounts'])
            ->where('orderSn', $id)
            ->where("uniacid", $this->uniacid())
            ->where('storeId', $this->storeId())
            ->first();
        if (empty($model)) {
            throw new BadRequestException('数据不存在');
        }
        $data = collect($model)->toArray();
        $data['discounts'] = collect($model->discounts)->keyBy('type')->toArray();


        return $this->success($data);
    }


    /**
     * 申请退款
     */
    public function refund(Request $request, $id)
    {
        InStoreOrderService::refund($id);
        return $this->success();
    }


    /**
     * 关闭订单
     */
    public function close(Request $request, $id)
    {
        InStoreOrderService::close($id, '用户取消订单');
        return $this->success();
    }


    /**
     * 完成订单
     */
    public function complete(Request $request, $id)
    {
        InStoreOrderService::complete($id, '用户完成订单');
        return $this->success();
    }
    /**
     * 接单
     */
    public function received(Request $request, $id)
    {
        InStoreOrderService::received($id, '门店手动接单，商品制作中');
        return $this->success();
    }

    /**
     * 制作完成
     */
    public function maked(Request $request, $id)
    {
        InStoreOrderService::maked($id);
        return $this->success();
    }

    /**
     * 叫号取餐
     */
    public function callNum(Request $request, $id)
    {
        InStoreOrderService::callNum($id);
        return $this->success();
    }

    public  function store(Request $request)
    {
        try {
            $lockKey = "Instore:" . $this->userId() . $request->diningType;
            $isLocked = Cache::lock($lockKey, 30);

            $checkoutKey = 'InstoreCheckout:Store:' . $this->storeId() . $this->userId() . $this->tableId() . ($request->diningType ?? 0) . $this->appType();

            DB::beginTransaction();
            $model = InStoreOrderService::createOrder($request, $this->storeId(), $this->userId(), $this->tableId(), $this->appType(), $this->uniacid());
            if ($model->payType == 1 && $model->diningType != 4) {
                if (empty($request->payType)) {
                    return $this->failed("请选择支付方式");
                }
                if ($request->payType == 'cash' || $model->money == 0) {
                    $order = [
                        'takeOutNo' => $model->orderIndex->orderSn,
                        'amount' => $request->money ?? $model->money,
                        'desc' => "代客下单",
                        'payTempId' => 0,
                        'trade_type' => 6,
                        'payChannel' => 2,
                        'payer' => ['openid' => null]
                    ];
                    $userId=$request->payUserId??0;

                    if (!OrderNotifyService::inStore($order, $order['takeOutNo'], $order['payTempId'],$userId)) {
                        DB::rollBack();
                        return $this->failed("支付失败");
                    };
                } elseif ($request->payType == 'authCode') {
                    if (empty($request->authCode)) {
                        DB::rollBack();
                        return $this->failed("请扫描用户付款码付款");
                    }
                    $orderIndex = OrderIndex::unpaid()->where('orderSn', $model->orderIndex->orderSn)->first();

                    if(!$orderIndex){
                        return $this->failed("请扫描用户付款码付款");
                    }
                    $order = [
                        'takeOutNo' => $model->orderIndex->orderSn,
                        'amount' => $model->money,
                        'desc' => "代客下单",
                        'balance' => $model->orderIndex->balance,
                        'auth_code' => $request->authCode,
                        'uniacid' => $model->orderIndex->uniacid,
                        'storeId' => $model->orderIndex->storeId,
                        'orderType' => $model->orderIndex->type,
                        'userId' => $model->orderIndex->userId,
                        'storePay' => $model->store->payChange,
                        'isolate' =>  $model->store->payChange->isolate
                    ];
                    $res = PayService::micropay($order);
                    if ($model->userId == 0) {
                        $model->userId = $res['userId'];
                        $model->orderIndex->userId = $res['userId'];
                        $model->save();
                        $model->orderIndex->save();
                    }
                    if (!OrderNotifyService::inStore($res, $order['takeOutNo'], $res['payTempId'])) {
                        DB::rollBack();
                        return $this->failed("支付失败");
                    };
                } elseif ($request->payType == 'balance') {
                    if (empty($request->payUserId)) {
                        DB::rollBack();
                        return $this->failed("请核对会员账号");
                    }

                    $model->orderIndex->userId = $request->payUserId;
                    $model->orderIndex->user->refresh();
                    $memberAccount=MemberAccount::where('userId',$request->payUserId)->first();
                    $order = [
                        "orderSn" => $model->orderSn,
                        'takeOutNo' => $model->orderSn,
                        'amount' => $model->money,
                        'desc' => "代客下单",
                        'auth_code' => $request->authCode,
                        'uniacid' => $model->uniacid,
                        'storeId' => $model->storeId,
                        'orderType' => $model->type,
                        'userId' => $request->payUserId,
                        'storePay' => $model->store->payChange,
                        //'balance' => $model->orderIndex->balance
                        'balance' => $memberAccount->balance
                    ];
                    $payConfig = PayConfig::where('uniacid', $order['uniacid'])
                        ->where('payType', 'balance')
                        ->first();
                    if (empty($payConfig)) {
                        DB::rollBack();
                        return $this->failed("暂不支持该支付方式");
                    }
                    $res = PayService::pay($order, $order['uniacid'], $payConfig->id, $this->appType());
                    if (!$res) {
                        DB::rollBack();
                        return $this->failed('支付失败');
                    }
                } elseif ($request->payType == 'costomPay') {
                    $costomPay = CostomPay::find($request->costomPayId);
                    if (!$costomPay) {
                        DB::rollBack();
                        return $this->failed('无效的支付渠道');
                    }
                    $order = [
                        'takeOutNo' => $model->orderIndex->orderSn,
                        'amount' => $request->money ?? $model->money,
                        'desc' => "代客下单",
                        'payTempId' => 0,
                        'trade_type' => $costomPay->payId,
                        'payChannel' => 2,
                        'costomPayId' => $costomPay->id,
                        'payer' => ['openid' => null]
                    ];
                    if (!OrderNotifyService::inStore($order, $order['takeOutNo'], $order['payTempId'])) {
                        DB::rollBack();
                        return $this->failed("支付失败");
                    };
                } else {
                    DB::rollBack();
                    return $this->failed('无效的支付渠道');
                }
            } else {
                if ($model->autoReceive) {
                    InStoreOrderService::received($model->id);
                }
            }
            $prentOrderSn = $model->prentOrderSn ?? $model->orderSn;
            $orderSn = $model->orderSn;
            $cacheModel=Cache::get($checkoutKey);
            if ($cacheModel->couponId) {
                MemberCoupon::where('id', $cacheModel->couponId)->update([
                    'state' => 2,
                    'updated_at' => date("Y-m-d H:i:s", time()),
                    'orderId' => $model->id,
                    'orderSn' => $orderSn
                ]);
            }
            Cache::delete($checkoutKey);
            optional($isLocked)->release();
            DB::commit();

            return $this->success(['orderSn' => $orderSn, 'prentOrderSn' => $prentOrderSn], '支付成功');
        } catch (\Exception $e) {
            DB::rollBack();

            optional($isLocked)->release();
            return $this->failed($e->getMessage());
        } finally {
            optional($isLocked)->release();
        }
    }

    public function pay(Request $request, $orderSn)
    {


        try {
            $checkoutKey = 'InstoreCheckout:Store:' . $this->storeId() . $this->userId() . $this->tableId() . ($request->diningType ?? 0) . $this->appType();
            $cacheModel=Cache::get($checkoutKey);
            $order = Order::where('storeId', $this->storeId())
                ->where('orderSn', $orderSn)
                ->where('isPay', 0)
                ->first();
            if (empty($order)) {
                throw new BadRequestException('订单已取消或者已支付');
            }
            if ($order->orderIndex->isSub == 1) {
                throw new BadRequestException('该订单不无法支付');
            }
            $order->couponId=$cacheModel->couponId;
            $order->save();
            if ($cacheModel->couponId) {
                $memberCoupon=MemberCoupon::where('id',$cacheModel->couponId)->first();
                $coupon=Coupons::where('id',$memberCoupon->couponId)->first();
                $couponMoney =$coupon->rule['money'];
                if (!$couponMoney) {
                    $goods=StoreGoodsSku::where('spuId',$coupon->goodsIds[0])->first();
                    $couponMoney =$goods->price;
                }
                MemberCoupon::where('id', $cacheModel->couponId)->update([
                    'state' => 2,
                    'updated_at' => date("Y-m-d H:i:s", time()),
                    'orderId' => $order->id,
                    'orderSn' => $orderSn
                ]);
                $discounts[] = [
                    'activityId' => $cacheModel->couponId,
                    'orderSn' => $orderSn,
                    'userId'=>$memberCoupon->userId,
                    "storeId" => $order->storeId,
                    'uniacid' => $order->uniacid,
                    'activityName' => $coupon->name,
                    'type' => 'coupon',
                    'reason' => '优惠券',
                    'money' => $couponMoney,
                    'title' => "券"
                ];
                Discount::insert($discounts);
            }
            if ($order->diningType == 4 && $order->payType == 2) {
                $subOrder = collect($order->subOrder)->whereIn('state', [2])->first();
                if ($subOrder) {
                    throw new BadRequestException('当前桌台还有未处理订单，请处理后结算');
                }
            }
            if ($request->payType == 'cash') {
                $order = [
                    'takeOutNo' => $order->orderSn,
                    'orderSn' => $order->orderSn,
                    'amount' => $request->money ?? $order->money,
                    'desc' => "代客下单",
                    'payTempId' => 0,
                    'trade_type' => 6,
                    'payChannel' => 2,
                    'payer' => ['openid' => null]
                ];
                if (!OrderNotifyService::inStore($order, $order['takeOutNo'], $order['payTempId'])) {
                    return $this->failed("支付失败");
                };
            } elseif ($request->payType == 'authCode') {
                $order = [
                    'takeOutNo' => $order->orderSn,
                    'orderSn' => $order->orderSn,
                    'amount' => $order->money,
                    'desc' => "代客下单",
                    'balance' => $order->balance,
                    'auth_code' => $request->authCode,
                    'uniacid' => $order->uniacid,
                    'storeId' => $order->storeId,
                    'orderType' => $order->type,
                    'userId' => $order->userId,
                    'storePay' => $order->store->payChange,
                    'isolate' =>  $order->store->payChange->isolate
                ];
                PayLog::updateOrCreate(['orderSn' => $order['orderSn']], [
                    'uniacid' => $order['uniacid'],
                    'storeId' => $order['storeId'],
                    'orderSn' => $order['orderSn'],
                    'paySn' => $order['takeOutNo']
                ]);
                $row=PayLog::where('orderSn',$order['orderSn'])->where('paySn',$order['takeOutNo'])->first();
                if(empty($row)||!$row){
                    throw new BadRequestException('订单异常,请重新发起支付');
                }
                $res = PayService::micropay($order);
                if (!OrderNotifyService::inStore($res, $order['orderSn'], $res['payTempId'])) {
                    return $this->failed("支付失败");
                };
            } elseif ($request->payType == 'balance') {
                if (empty($request->payUserId)) {
                    return $this->failed("请核对会员账号");
                }
                $order->orderIndex->userId = $request->payUserId;
                $order->orderIndex->user->refresh();
                $order = [
                    'takeOutNo' => $order->orderSn,
                    'orderSn' => $order->orderSn,
                    'amount' => $order->money,
                    'desc' => "代客下单",
                    'auth_code' => $request->authCode,
                    'uniacid' => $order->uniacid,
                    'storeId' => $order->storeId,
                    'orderType' => $order->type,
                    'userId' => $request->payUserId,
                    'storePay' => $order->store->payChange,
                    'balance' => $order->orderIndex->balance
                ];
                $payConfig = PayConfig::where('uniacid', $order['uniacid'])
                    ->where('payType', 'balance')
                    ->first();
                if (empty($payConfig)) {
                    return $this->failed("暂不支持该支付方式");
                }
                $res = PayService::pay($order, $order['uniacid'], $payConfig->id, $this->appType());
                if (!$res) {
                    return $this->failed('支付失败');
                }
            } elseif ($request->payType == 'costomPay') {
                $costomPay = CostomPay::find($request->costomPayId);
                if (!$costomPay) {
                    return $this->failed('无效的支付渠道');
                }
                $order = [
                    'takeOutNo' => $order->orderSn,
                    'orderSn' => $order->orderSn,
                    'amount' => $request->money ?? $order->money,
                    'desc' => "代客下单",
                    'payTempId' => 0,
                    'trade_type' => $costomPay->payId,
                    'payChannel' => 2,
                    'payer' => ['openid' => null],
                     'costomPayId' => $costomPay->id,
                ];
                if (!OrderNotifyService::inStore($order, $order['takeOutNo'], $order['payTempId'])) {
                    return $this->failed("支付失败");
                };
            } else {
                return $this->failed('无效的支付渠道');
            }
            DB::commit();
            return $this->success("支付成功");
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }

    public function notes(Request $request, $id)
    {
        $storeId = $this->storeId();
        $order =  Order::where('id', $id)->where('storeId', $storeId)->where("uniacid", $this->uniacid())->first();
        if (empty($order)) {
            throw new BadRequestException('数据不存在');
        }
        $order->storeNotes = $request->notes;
        $order->save();
        return $this->success();
    }

    public function give(Request $request, $orderSn)
    {
        try {
            DB::beginTransaction();
            $order = Order::where('orderSn', $orderSn)->whereNotIn('state', [0, 6, 7, 8])->first();
            if (empty($order)) {
                throw new BadRequestException('订单状态不正确');
            }
            $goodsList = OrderGoods::where('prentOrderSn', $orderSn)
                ->whereIn('id', collect($request->goods)->pluck('id')->all())
                ->get();
            if (empty($goodsList)) {
                throw new BadRequestException('商品不存在');
            }
            $printGoods = [];
            collect($request->goods)->each(function ($goodsItem) use ($goodsList, $request, &$printGoods) {
                $goods = collect($goodsList)
                    ->where('id', $goodsItem['id'])
                    ->first();
                if (empty($goods)) {
                    DB::rollBack();
                    throw new BadRequestException('该商品不存在');
                }
                if ($goodsItem['num'] <= 0) {
                    DB::rollBack();
                    throw new BadRequestException('数量必须大于0');
                }
                if (in_array($goods->discountType, [0, 6, 7, 8, 9])) {
                    if ($goods->state != 8) {
                        $model = new ChannelCart();
                        $model->fill(collect($goods)->toArray());
                        $model->uniacid = $goods->uniacid;
                        $model->storeId = $goods->storeId;
                        $model->userId = 0;
                        if ($request->type == 'backFood') {
                            $num = $goodsItem['num'] >= $goods->num ? $goods->num : $goodsItem['num'];
                            $giveModel = new ChannelCart();
                            $giveModel->fill(collect($goods)->toArray());
                            $giveModel->uniacid = $goods->uniacid;
                            $giveModel->storeId = $goods->storeId;
                            $giveModel->userId = 0;
                            $giveModel->discountNum = $num;
                            $giveModel->discountType = 0;
                            $giveModel->discountLabel = "退";
                            $giveModel->discountPrice = $goods->price;
                            $giveModel->num = $num;
                            $giveModel->price = $goods->price;
                            $giveModel = $giveModel->model(false, false);
                            $giveModel->discountMoney = bcadd($giveModel->getDiscountMoney(), $giveModel->materialMoney, 2);
                            $clone = new OrderGoods();
                            $clone->fill($goods->toArray());
                            $clone->fill(collect($giveModel)->toArray());
                            $clone->price = $goods->price;
                            $clone->discountLabel = "退";
                            $clone->notes = $request->notes;
                            $clone->reason = $request->reason;
                            $clone->state = 8;
                            $clone->save();
                            $printGoods[] = $clone->spuId;
                            $model->num = $goods->num - $num;
                            if ($goods->discountType == 6) {
                                $model->discountNum = $model->num;
                                $model = $model->model(false, false);
                            } else {
                                $model = $model->model(false);
                            }
                            $goods->num = $goods->num - $num;
                        }
                        if ($request->type == 'give') {
                            $num = $goodsItem['num'] >= $goods->num ? $goods->num : $goodsItem['num'];
                            $giveModel = new ChannelCart();
                            $giveModel->fill(collect($goods)->toArray());
                            $giveModel->uniacid = $goods->uniacid;
                            $giveModel->storeId = $goods->storeId;
                            $giveModel->userId = 0;
                            $giveModel->discountNum = $num;
                            $giveModel->discountType = 1;
                            $giveModel->discountLabel = "赠";
                            $giveModel->discountPrice = 0;
                            $giveModel->num = $num;
                            $giveModel = $giveModel->model(false, false);
                            $giveModel->discountMoney = bcadd($giveModel->getDiscountMoney(), $giveModel->materialMoney, 2);
                            $clone = new OrderGoods();
                            $clone->fill($goods->toArray());
                            $clone->fill(collect($giveModel)->toArray());
                            $clone->discountLabel = "赠";
                            $clone->notes = $request->notes;
                            $clone->reason = $goods->reason;
                            $clone->save();
                            $model->num = $goods->num - $num;
                            $goods->num = $goods->num - $num;
                            $model->notes = $request->notes;
                            $model->reason = $request->reason;
                            if ($goods->discountType == 6) {
                                $model->discountNum = $model->num;
                                $model = $model->model(false, false);
                            } else {
                                $model = $model->model(false);
                            }
                        }
                        if ($request->type == 'discount') {
                            if ($request->discount < 0) {
                                throw new BadRequestException('打折优惠必须大于0');
                            }
                            $model->discountType = 2;
                            $model->discountNum = $goods->num;
                            $model->num = $goods->num;
                            $model->discountLabel = "打" . bcdiv($request->discount, 10, 1) . '折';
                            $model->discountPrice = bcmul(bcdiv($goods->price, 100, 2), intval($request->discount), 2);
                            $model->notes = $request->notes;
                            $model->reason = $goods->reason;
                            $model = $model->model(false);
                        }
                        if ($request->type == 'sub') {
                            if ($request->discount < 0) {
                                throw new BadRequestException('减免优惠必须大于0');
                            }
                            if ($request->discount > $goods->money) {
                                throw new BadRequestException('减免优惠必须');
                            }
                            $model->discountNum = 0;
                            $model->discountType = 3;
                            $model->discountLabel = "减" . $request->discount . "元";
                            $model->num = $goods->num;
                            $model = $model->model(false);
                            $model->discountMoney = bcadd($model->getDiscountMoney(), $request->discount);
                            $model->money = bcsub($model->getMoney(), $request->discount, 2);
                            $model->notes = $request->notes;
                            $model->reason = $request->reason;
                        }
                        if ($goods->num <= 0) {
                            OrderGoods::where('id', $goods->id)->forceDelete();
                        } else {
                            $model->makeHidden(['goods', 'tableId', 'id', 'score']);
                            OrderGoods::where('id', $goods->id)->update(collect($model)->toArray());
                            OrderGoods::where('id', $goods->id)->update(['reason' => $request->reason]);
                        }
                    } else {
                        $model = new ChannelCart();
                        $model->fill(collect($goods)->toArray());
                        $model->uniacid = $goods->uniacid;
                        $model->storeId = $goods->storeId;
                        $model->userId = 0;
                        if ($request->type == "back") {
                            if ($goods->state == 8) {
                                OrderGoods::where('id', $goods->id)->update(['state' => 1]);
                            }
                            $goods->discountType = 0;
                            $goods->discountLabel = null;
                            $goods->discountPrice = 0;
                            $goods->discountNum = 0;
                            $model->num = $goods->num;
                            $model->discountType = 0;
                            $model->discountLabel = null;
                            $model->discountPrice = 0;
                            $model->discountNum = 0;
                            $model = $model->model(false);
                        }
                        if ($goods->num <= 0) {
                            OrderGoods::where('id', $goods->id)->forceDelete();
                        } else {
                            $model->makeHidden(['goods', 'tableId', 'id', 'score']);
                            OrderGoods::where('id', $goods->id)->update(collect($model)->toArray());
                            OrderGoods::where('id', $goods->id)->update(['reason' => $request->reason]);
                        }
                    }
                } else {
                    $model = new ChannelCart();
                    $model->fill(collect($goods)->toArray());
                    $model->uniacid = $goods->uniacid;
                    $model->storeId = $goods->storeId;
                    $model->userId = 0;
                    if ($request->type == "back") {
                        if ($goods->state == 8) {
                            OrderGoods::where('id', $goods->id)->update(['state' => 1]);
                        }
                        $goods->discountType = 0;
                        $goods->discountLabel = null;
                        $goods->discountPrice = 0;
                        $goods->discountNum = 0;
                        $model->num = $goods->num;
                        $model->discountType = 0;
                        $model->discountLabel = null;
                        $model->discountPrice = 0;
                        $model->discountNum = 0;
                        $model = $model->model(false);
                    } else {
                        $model->num = $goods->num;
                        $model->discountNum = $goods->num;
                        $goods->num = $goods->num + $goodsItem['num'];
                        $goods->discountNum = $goods->discountNum + $goodsItem['num'];
                        $model = $model->model(false);
                        if ($goods->discountType == 1) {
                            $model->discountMoney = bcadd($model->getDiscountMoney(), $model->materialMoney);
                        }
                        if ($goods->discountType == 2) {
                            $model->discountMoney = $model->getDiscountMoney();
                        }
                        if ($goods->discountType == 3) {
                            $model->discountNum = 0;
                            $goods->discountNum = 0;
                            $model = $model->model(false);
                            $model->discountMoney = $model->discountMoney;
                            $model->money = bcsub($model->getMoney(), $model->discountMoney, 2);
                            if ($model->money < 0) {
                                throw new BadRequestException('无法继续减免');
                            }
                        }
                    }
                    if ($goods->num <= 0) {
                        OrderGoods::where('id', $goods->id)->forceDelete();
                    } else {
                        $model->makeHidden(['goods', 'tableId', 'id', 'score']);
                        OrderGoods::where('id', $goods->id)->update(collect($model)->toArray());
                    }
                }
            });
            $order = Order::where("orderSn", $orderSn)->first();
            $order->changeData(true);
            DB::commit();
            if ($printGoods) {
                $goods = OrderGoods::whereIn('id', $printGoods)->get();
                InStoreOrderService::print($order->id, 6, $goods);
            }
            return $this->success([], '操作成功');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failed($e->getMessage());
        }
    }

    public function print(Request $request, $id)
    {
        InStoreOrderService::print($id, $request->orderType);
        return $this->success([], '操作成功');
    }

    public function  discount(Request $request, $orderSn)
    {
        try {
            DB::beginTransaction();
            $order = Order::where('orderSn', $orderSn)->whereNotIn('state', [0, 6, 7, 8])->first();
            if (empty($order)) {
                throw new BadRequestException('订单状态不正确');
            }
            $reason = $request->reason ?? null;
            if ($request->type == 'discount') {
                $discountMoney  = bcmul(bcdiv($order->money, 100, 4), intval($request->discount), 2);
                $discounts[] = [
                    'activityId' => 0,
                    'orderSn' => $orderSn,
                    "storeId" => $order->storeId,
                    'uniacid' => $order->uniacid,
                    'activityName' => '整单打' . bcdiv($request->discount, 10, 1) . "折",
                    'type' => 'manualDiscount',
                    'reason' => $reason,
                    'money' => bcsub($order->money, $discountMoney, 2),
                    'title' => "整单打折"
                ];
            }
            if ($request->type == 'sub') {
                $discountMoney  = $request->discount > $order->money ? $order->money : $request->discount;
                $discounts[] = [
                    'activityId' => 0,
                    'orderSn' => $orderSn,
                    "storeId" => $order->storeId,
                    'uniacid' => $order->uniacid,
                    'activityName' => '整单立减' . $discountMoney . "元",
                    'type' => 'manualDiscount',
                    'reason' => $reason,
                    'money' => $discountMoney,
                    'title' => "整单立减"
                ];
            }
            Discount::insert($discounts);
            $order->changeData();
            DB::commit();
            return $this->success([], '操作成功');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failed($e->getMessage());
        }
    }

    public function  wipeZero(Request $request, $orderSn)
    {
        try {
            DB::beginTransaction();
            $order = Order::where('orderSn', $orderSn)->whereNotIn('state', [0, 6, 7, 8])->first();
            if (empty($order)) {
                throw new BadRequestException('订单状态不正确');
            }
            if ($request->type == 'Y') {
                $discountMoney = fixY($order->money);
                $discounts[] =  [
                    'activityId' => 0,
                    'orderSn' => $orderSn,
                    "storeId" => $order->storeId,
                    'uniacid' => $order->uniacid,
                    'activityName' => '抹零' . $discountMoney . "元",
                    'type' => 'wipeZero',
                    'money' => $discountMoney,
                    'title' => "抹零"
                ];
            }
            if ($request->type == 'J') {
                $discountMoney = fixJ($order->money);
                $discounts[] = [
                    'activityId' => 0,
                    'orderSn' => $orderSn,
                    "storeId" => $order->storeId,
                    'uniacid' => $order->uniacid,
                    'activityName' => '抹零' . $discountMoney . "角",
                    'type' => 'wipeZero',
                    'money' => $discountMoney,
                    'title' => "抹零"
                ];
            }
            if ($request->type == 'F') {
                $discountMoney = fixF($order->money);
                $discounts[] = [
                    'activityId' => 0,
                    'orderSn' => $orderSn,
                    "storeId" => $order->storeId,
                    'uniacid' => $order->uniacid,
                    'activityName' =>  '抹零' . $discountMoney . "分",
                    'type' => 'wipeZero',
                    'money' => $discountMoney,
                    'title' => "抹零"
                ];
            }
            if ($request->type == 'custom') {
                $discounts[] = [
                    'activityId' => 0,
                    'orderSn' => $orderSn,
                    "storeId" => $order->storeId,
                    'uniacid' => $order->uniacid,
                    'activityName' => '抹零' . floatval($request->discount) . "元",
                    'type' => 'wipeZero',
                    'money' => floatval($request->discount),
                    'title' => "抹零"
                ];
            }
            Discount::insert($discounts);
            $order->changeData();
            DB::commit();
            return $this->success([], '操作成功');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failed($e->getMessage());
        }
    }

    public function  cancelDiscount(Request $request, $orderSn)
    {
        $order = Order::where('orderSn', $orderSn)->whereNotIn('state', [0, 6, 7, 8])->first();
        if (empty($order)) {
            throw new BadRequestException('订单状态不正确');
        }
        if ($request->type == 'wipeZero') {
            $order->discounts()->where('type', 'wipeZero')->delete();
        } else {
            $order->discounts()->whereIn('type', ['wipeZero', 'manualDiscount'])->delete();
        }
        $order->changeData();
        return $this->success([], '操作成功');
    }


    public function  free(Request $request, $orderSn)
    {
        try {
            DB::beginTransaction();
            $order = Order::where('orderSn', $orderSn)->whereNotIn('state', [0, 6, 7, 8])->first();
            if (empty($order)) {
                throw new BadRequestException('订单状态不正确');
            }
            Order::where('prentOrderSn', $orderSn)->update([
                'tableMoney' => 0
            ]);
            OrderGoods::where('prentOrderSn', $orderSn)->update([
                'discountNum' => DB::raw('num'),
                'discountType' => 1,
                'discountLabel' => "赠",
                'discountPrice' => 0,
                'discountMoney' => DB::raw('money + materialMoney')
            ]);
            OrderGoods::where('prentOrderSn', $orderSn)->update([
                'money' => 0
            ]);
            Discount::where('prentOrderSn', $orderSn)->delete();
            $discount['uniacid'] = $order->uniacid;
            $discount['orderId'] = $order->id;
            $discount['storeId'] = $order->storeId;
            $discount['userId'] = $order->userId;
            $discount['orderSn'] = $order->orderSn;
            $discount['prentOrderSn'] = $order->prentOrderSn;
            Discount::create(array_merge($discount, [
                'activityId' => 0,
                'activityName' => "免单",
                'type' => 'free',
                'money' => 0,
                'reason' => $request->reason,
                'title' => "免单"
            ]));
            Order::where('prentOrderSn', $orderSn)->update(['tableMoney' => 0]);
            MemberCoupon::whereIn('id', collect($order->subOrder)->pluck('couponId')->all())->update([
                'state' => 1,
                'orderId' => 0,
                'updated_at' => null,
            ]);
            $order->refresh();
            $order->changeData(true);
            $orderData = [
                'takeOutNo' => $order->orderSn,
                'amount' => 0,
                'desc' => "代客下单",
                'payTempId' => 0,
                'trade_type' => 6,
                'payChannel' => 2,
                'payer' => ['openid' => null]
            ];
            if (!OrderNotifyService::inStore($orderData, $orderData['takeOutNo'], $orderData['payTempId'])) {
                DB::rollBack();
                return $this->failed("免单失败");
            };
            DB::commit();
            return $this->success([], '操作成功');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failed($e->getMessage());
        }
    }

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
        return Excel::download(new InstoreOrderDataExport($params), 'orderData.xlsx');
    }

    public function  couponList(Request $request, $orderSn)
    {
        $order = Order::where('orderSn', $orderSn)->whereNotIn('state', [0, 6, 7, 8])->first();
        if (empty($order)) {
            throw new BadRequestException('订单状态不正确');
        }
        $model = new Coupon([
            'selectId' => $order->couponId,
            'uniacid' => $order->uniacid,
            'userId' => $request->userId,
            'scene' => 3,
            'carList' => $order->subGoods
        ]);
        $model->couponData;
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
                    return $this->success('小程序发货成功');

                }else{
                    throw  new BadRequestException('当前小程序暂无待发货订单');
                }
            }

        } catch (\Exception $e) {
            throw  new BadRequestException($e->getMessage());

        }
    }
}
