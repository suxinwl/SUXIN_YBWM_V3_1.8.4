<?php

namespace App\Http\Controllers\ChannelApi\InStore;

use App\Events\StoreMessageEvent;
use App\Http\Controllers\ChannelApi\ApiController;
use App\Http\Requests\Cart\CartRequest;
use App\Http\Resources\ChannelApi\Order\OrderListResources;
use App\Models\Coupon\MemberCoupon;
use App\Models\InStore\Order\Order;
use App\Models\InStore\Order\OrderBase;
use App\Models\InStore\Order\ParentOrder;
use App\Models\Order\Discount;
use App\Models\Order\OrderGoods;
use App\Models\Order\OrderIndex;
use App\Models\Order\TakeOutOrder;
use App\Models\Partner;
use App\Models\PartnerOrder;
use App\Models\Store;
use App\Models\Tables\Table;
use App\Models\TakeOut\Cart;
use App\Models\TakeOut\CartList;
use App\Models\TakeOut\Checkout;
use App\Services\ConfigService;
use App\Services\InStoreOrderService;
use App\Services\OrderService;
use App\Services\StoreGeoService;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use App\Models\Member;
class OrderController extends ApiController
{
    public function index(Request $request)
    {
        $userId = $this->userId();
        $uniacid = $this->uniacid();
        $list = Order::with(['subOrder', 'orderIndex', 'goods', 'table'])
            ->where(function ($q) use ($uniacid, $userId) {
                return $q->whereHas('subOrder', function ($q) use ($userId) {
                    return $q->where('userId', $userId);
                })->orWhere(function ($q) use ($uniacid, $userId) {
                    return $q->whereNull('prentOrderSn')->where('uniacid', $uniacid)->where('userId', $userId);
                });
            })
            ->where("uniacid", $this->uniacid())
            ->orderBy('id', 'desc')
            ->paginate($request->size ?? 10, '*', 'page');
        return $this->success($list);
    }


    public  function store(Request $request)
    {

        try {
            DB::beginTransaction();
            $checkout = Cache::get('InstoreCheckout:' . $this->userId());
            if (empty($checkout)) {
                throw new BadRequestException('请先到收银台结账');
            }
            if ($checkout->diningType == 4) {
                $lockKey = 'InstoreCheckout:' . $checkout->uniacid . $checkout->storeId . $checkout->tableId . $checkout->diningType;
            } else {
                $lockKey = 'InstoreCheckout:' . $checkout->uniacid . $checkout->storeId . $checkout->tableId . $checkout->diningType . $checkout->userId;
            }
            $isLocked = Cache::lock($lockKey,10);
            if (!$isLocked->get()) {
                throw new BadRequestException('订单提交中,请勿重复提交');
            }
            $checkout->check();
            $table = Table::where('id', $checkout->tableId)->first();
            if ($table->order && $table->order->state == 2) {
                optional($isLocked)->release();
                throw new BadRequestException('您有未完成的订单');
            }
            $data = $checkout->toArray();
            unset($data['reservationTime'], $data['addressList']);
            $orderSn=getTakeOutNo();
            $model = new Order();
            $model->fill($data);
            $model->source = $this->appType();
            $model->state = $model->payType == 2 && $model->autoReceive == 0 ? 2 : 1;
            $model->uniacid = $this->uniacid();
            $model->pickNo = '';
            $model->autoReceive = $checkout->autoReceive;
            $model->receivePrint = $checkout->receivePrint;
            $model->orderSn = $orderSn;
            $model->serverTime = null;
            $model->prentOrderSn = $checkout->prentOrderSn;
            $model->goodsNum = $model->payType == 2 ? $checkout->goodsNum : 0;


            $model->service_charge=$data['serviceMoneyArr']['service_charge'];
            $model->service_money=$data['serviceMoneyArr']['service_money'];
            if ($model->couponId) {
                MemberCoupon::where('id', $model->couponId)->update([
                    'state' => 2,
                    'updated_at' => date("Y-m-d H:i:s", time()),
                    'orderId' => $model->id
                ]);
            }
            if (empty($model->prentOrderSn) && $model->diningType == 4 && $model->payType == 2) {
                $pOrder = new Order();
                $pickNo = $pOrder->getPickNo();
                $pOrder->fill($model->toArray());
                $pOrder->orderSn = getTakeOutNo();
                $pOrder->pickFix = $pOrder->getPickFix();
                $pOrder->pickNo = $pickNo;
                $pOrder->userId = $model->userId;
                $pOrder->save();
                $model->prentOrderSn = $pOrder->orderSn;
                $model->pickFix = $pOrder->pickFix;
                $model->pickNo = $pickNo;
                $model->prentOrderSn = $pOrder->orderSn;
                if ($model->payType == 2) {
                    Table::where('id', $model->tableId)
                        ->where('state', 0)
                        ->update([
                            'orderSn' => $pOrder->orderSn,
                            'state' => 1,
                            'scan' => 1,
                        ]);
                }
            }
            $model->save();
            if($checkout->discounts){
                foreach ($checkout->discounts as $key => $discount) {
                    $discount['uniacid'] = $model->uniacid;
                    $discount['orderId'] = $model->id ?? 0;
                    $discount['storeId'] = $model->storeId;
                    $discount['userId'] = $this->userId();
                    $discount['prentOrderSn'] = $model->prentOrderSn;
                    $discount['orderSn'] = $model->orderSn;
                    Discount::create($discount);
                }
            }

            $model->setLog($model->sourceFormat . '用户下单' . $model->user->nickname . "({$model->user->mobile})");
            if ($model->payType == 2) {
                $model->perentOrder->state = $model->state;
                $model->perentOrder->changeData();
                $model->perentOrder->orderIndex->state = 1;
                $model->perentOrder->orderIndex->save();
                foreach ($checkout->goodsList as $key => $goods) {
                    $goodsItem = $goods->toArray();
                    unset($goodsItem['id']);
                    $goodsItem['name'] = $goods->goods->name;
                    $goodsItem['logo'] = $goods->goods->logo;
                    $goodsItem['orderSn'] = $model->orderSn;
                    $goodsItem['prentOrderSn'] = $model->prentOrderSn;
                    $orderGoods[] = new OrderGoods($goodsItem);
                    $goods->delete();
                }
                foreach ($checkout->tradeinGoodsData as $key => $goods) {
                    $goodsItem = $goods->toArray();
                    unset($goodsItem['id']);
                    $goodsItem['name'] = $goods->goods->name;
                    $goodsItem['logo'] = $goods->goods->logo;
                    $goodsItem['diningType'] = $model->diningType;
                    $goodsItem['scene'] =  $model->scene;
                    $orderGoods[] = new OrderGoods($goodsItem);
                    $goods->delete();
                }
                $model->goods()->saveMany($orderGoods);
                $model->refresh();
                if ($model->autoReceive == 1) {
                    InStoreOrderService::received($model->id, '门店自动接单，商品制作中');
                } else {
                    Event(new StoreMessageEvent($model->orderIndex, 'inStoreNewOrder'));
                }
            } else {
                if ($model->diningType != 4) {
                    optional($isLocked)->release();
                } elseif (empty($model->prentOrderSn)) {
                    $model->prentOrderSn = getTakeOutNo();
                    Cache::set($model->orderSn . ":prentOrderSn", $model->prentOrderSn);
                    OrderIndex::where('orderSn', $model->orderSn)->update(['isSub' => 1]);
                    $table = DB::table('table')->find($model->tableId);
                    if ($table->state != 1) {
                        throw new BadRequestException('桌位状态已改变，无法提交');
                    }
                }
                Cache::set($model->orderSn . ":goodsList", $checkout->goodsList, 3600 * 24);
                Cache::set($model->orderSn . ":tradeinGoodsData", $checkout->tradeinGoodsData, 3600 * 24);
            }
            $prentOrderSn = $model->prentOrderSn ?? $model->orderSn;

//            //分銷訂單創建
//            if ($this->userId()) {
//                $userId = $this->userId();
//                $uniacid = $this->uniacid();
//                $storeId = $this->storeId();
//                PartnerOrder::createPartnerOrder($uniacid,$storeId,$userId,$this->user->partnerId,$model->money,$orderSn);
//            }
            DB::commit();
            Cache::delete('InstoreCheckout:' . $this->userId());
            return $this->success(['orderSn' => $orderSn, 'payType' => $model->payType, 'prentOrderSn' => $prentOrderSn]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error($e->getMessage());
            optional($isLocked)->release();
            return $this->failed($e->getMessage());
        } finally {
            optional($isLocked)->release();
        }
    }


    public function show(Request $request, $id)
    {
        $userId = $this->userId();
        $uniacid = $this->uniacid();
        $model = Order::with(['subOrder', 'orderIndex', 'goods', 'table'])
            ->where(function ($q) use ($uniacid, $userId) {
                return $q->whereHas('subOrder', function ($q) use ($userId) {
                    return $q->where('userId', $userId);
                })->orWhere(function ($q) use ($uniacid, $userId) {
                    return $q->whereNull('prentOrderSn')->where('uniacid', $uniacid)->where('userId', $userId);
                });
            })
            ->where('orderSn', $id)
            ->where("uniacid", $this->uniacid())
            ->orderBy('id', 'desc')
            ->first();
        if (empty($model)) {
            throw new BadRequestException('数据不存在');
        }
        return $this->success($model);
    }

    public function startOrder()
    {
        $userId = $this->userId();
        $uniacid = $this->uniacid();
        $list = OrderBase::select(['id', 'storeId', 'tableId', 'orderSn'])
            ->where("uniacid", $this->uniacid())
            ->where("diningType", 4)
            ->whereIn("state", [2, 3])
            ->whereHas('users', function ($q) use ($userId, $uniacid) {
                return $q->where('uniacid', $uniacid)->where('userId', $userId);
            })
            ->orderBy('id', 'desc')
            ->first();
        return $this->success($list);
    }

    /**
     * 申请退款
     */
    public function refundApply(Request $request, $id)
    {
        InStoreOrderService::refundApply($id);
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
}
