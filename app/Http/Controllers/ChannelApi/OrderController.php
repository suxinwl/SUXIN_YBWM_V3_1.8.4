<?php

namespace App\Http\Controllers\ChannelApi;

use App\Http\Requests\Cart\CartRequest;
use App\Http\Resources\ChannelApi\Order\OrderListResources;
use App\Models\Coupon\MemberCoupon;
use App\Models\Kuaishou;
use App\Models\Order\Discount;
use App\Models\Order\OrderGoods;
use App\Models\Order\OrderIndex;
use App\Models\Order\TakeOutOrder;
use App\Models\Store;
use App\Models\TakeOut\Cart;
use App\Models\TakeOut\CartList;
use App\Models\TakeOut\Checkout;
use App\Models\TiktokStoreList;
use App\Models\TiktokVerifyList;
use App\Services\ConfigService;
use App\Services\OrderService;
use App\Services\StoreGeoService;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use App\Models\Douyin;
use App\Models\PartnerOrder;

class OrderController extends ApiController
{
    public function index(Request $request)
    {
        $uniacid = $this->uniacid();
        $userId = $this->userId();
        $list = OrderIndex::select(['id', 'type', 'orderSn', 'uniacid', 'isSub'])
            ->where("uniacid", $this->uniacid())
            ->where("isShow", 1)
            ->where('isSub', 0)
            ->when($request->state, function ($q) use ($request) {
                if ($request->state == 'inStore') {
                    return $q->whereIn('type', [1, 4]);
                }
                if ($request->state == 'storedValue') {
                    return $q->where('type', 2)->paid();
                }
                if ($request->state == 'persionPay') {
                    return $q->where('type', 3)->paid();
                }
                if ($request->state == 'couponPack') {
                    return $q->where('type', 6)->paid();
                }
            })
            ->where(function ($q) use ($uniacid, $userId) {
                return $q->where('userId', $userId)->orWhere(function ($q) use ($uniacid, $userId) {
                    return $q->where('isSub', 0)->whereHas('orderUser', function ($q) use ($uniacid, $userId) {
                        return $q->where('userId', $userId);
                    });
                });
            })
            ->orderBy('id', 'desc')
            ->paginate($request->size ?? 10, '*', 'page');
        return $this->success(new OrderListResources($list));
    }

    public function backlog(Request $request)
    {
        $list = TakeOutOrder::where("uniacid", $this->uniacid())
            ->whereIn('state', [2, 3, 4, 5])
            ->where("userId", $this->userId())
            ->orderBy('id', 'desc')
            ->paginate($request->size ?? 10, '*', 'page');
        return $this->success($list);
    }


    public  function store(Request $request)
    {
        try {
            DB::beginTransaction();
            $checkout = Cache::get('TakeCheckout:' . $this->userId());
            if (empty($checkout)) {
                throw new BadRequestException('请先到收银台结账');
            }

            $checkout->check();
            $data = $checkout->toArray();
            unset($data['reservationTime'], $data['addressList']);
            $model = new TakeOutOrder();
            $model->fill($data);
            $model->source = $this->appType();
            $model->state = 1;
            $model->uniacid = $this->uniacid();
            $model->pickNo = '';
//            if($request->diningType==2){
//                $model->scene=30;
//            }
            $model->diningType = $request->diningType?:0;
            $model->nextPrintTime = $checkout->NextPrintTime;
            $model->autoReceive = $checkout->autoReceive;
            $model->receivePrint = $checkout->receivePrint;
            $model->orderSn = getTakeOutNo();
            $config = $checkout->store->storeSetting;
            if ($model->scene == 2 && $model->appointment == 0) {
                $model->serverTime =  date("Y-m-d H:i:s", time() + ($config['takeMakeTime'] * $checkout->carList->goodsCount) * 60);
            } elseif ($model->scene == 1 && $model->appointment == 0) {
                $model->serverTime =  date("Y-m-d H:i:s", time() + (($checkout->delivery['minutes'] + $config['outMakeTime'] * $checkout->carList->goodsCount) * 60));
            }

            $model->service_charge=$data['serviceMoneyArr']['service_charge'];
                $model->service_money=$data['serviceMoneyArr']['service_money'];
            $model->save();
            $model->setLog($model->sourceFormat . '用户下单' . $model->user->nickname . "({$model->user->mobile})");
            foreach ($checkout->goodsList as $key => $goods) {
                $goodsItem = $goods->toArray();
                unset($goodsItem['id']);
                $goodsItem['name'] = $goods->goods->name;
                $goodsItem['logo'] = $goods->goods->logo;
                $goodsItem['diningType'] = $model->diningType;
                $goodsItem['scene'] =  $model->scene;
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
            foreach ($checkout->discounts as $key => $discount) {
                $discount['uniacid'] = $model->uniacid;
                $discount['orderId'] = $model->id;
                $discount['orderSn'] = $model->orderSn;
                $discount['storeId'] = $model->storeId;
                $discount['userId'] = $model->userId;
                Discount::create($discount);
            }
            $model->goods()->saveMany($orderGoods);
            if ($model->couponId) {
                MemberCoupon::where('id', $model->couponId)->update([
                    'state' => 2,
                    'updated_at' => date("Y-m-d H:i:s", time()),
                    'orderId' => $model->id,
                    'orderSn' => $model->orderSn
                ]);
            }
            Cache::set("newSub:{$this->userId()}", 1);
            Cache::delete('TakeCheckout:' . $this->userId());
            DB::commit();
            return $this->success(['orderSn' => $model->orderSn, 'state' => $model->state]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return $this->failed($e->getMessage());
        }
    }


    public function show(Request $request, $id)
    {
        $uniacid = $this->uniacid();
        $userId = $this->userId();
        $model = OrderIndex::select(['id', 'type', 'orderSn', 'uniacid'])
            ->where("uniacid", $this->uniacid())
            ->where('orderSn', $id)
            ->first();
        if (empty($model)) {
            throw new BadRequestException('数据不存在');
        }
        $model->setAppends(['subOrder']);
        $model->selfCode='https://api.pwmqr.com/qrcode/create/?url='.$model->subOrder->orderSn;
        return $this->success($model);
    }


    /**
     * 申请退款
     */
    public function refundApply(Request $request, $id)
    {
        OrderService::refundApply($id, $request->refundCause);
        return $this->success();
    }


    /**
     * 关闭订单
     */
    public function close(Request $request, $id)
    {
        OrderService::close($id, '用户取消订单');
        return $this->success();
    }


    /**
     * 完成订单
     */
    public function complete(Request $request, $id)
    {
        OrderService::complete($id, '用户完成订单');
        return $this->success();
    }

    //验券准备
    public function prepare(Request $request)
    {
        $uniacid = $this->uniacid();
        $code = trim($request->code);
        if (!$code) {
            return $this->failed(__('请输入抖音团购券码'));
        }
        $data = Douyin::prepare($uniacid, $code);
        //file_put_contents('tiktok_prepare.log', json_encode($data) . PHP_EOL, FILE_APPEND);
        if ($data['data']['error_code'] == 0) {
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
        $order_type = $request->order_type ?: 1;
        $storeId = $this->storeId() ?: $request->storeId;
        if (!$storeId) {
            return $this->failed(__("请先配置门店关联设置门店ID"));
        }
        $unique = TiktokStoreList::where(['uniacid' => $uniacid, 'storeId' => $storeId])->where('order_type', $order_type)->first();
        if (empty($unique)) {
            return $this->failed(__("请先配置门店关联设置门店ID"));
        }
        $verify_data = $request->verify_data;
        //file_put_contents('tiktok_verify.log', json_encode($verify_data) . PHP_EOL, FILE_APPEND);
        $verify_token = $verify_data['data']['verify_token'];
        $encryptedcodes = array_column($verify_data['data']['certificates'], 'encrypted_code');
        $pod_id = $unique['poi_id'];

        if ($order_type == 1) {
            $data = Douyin::verify($uniacid, $verify_token, $pod_id, $encryptedcodes);
        } else {
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
    //获取抖音核销列表
    public function getTiktokVerifyList(Request $request)
    {
        $uniacid = $this->uniacid();
        $storeId = $this->storeId() ?: $request->storeId;
        $list = TiktokVerifyList::with(['store', 'admin'])->where('uniacid', $uniacid)
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
            })->get();
        return $this->success($list);
    }
}
