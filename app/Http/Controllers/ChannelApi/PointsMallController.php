<?php

namespace App\Http\Controllers\ChannelApi;

use App\Enums\PayEnum;
use App\Models\MemberAccount;
use Illuminate\Http\Request;
use App\Models\PointsMall;
use App\Models\PointsMall\Checkout;
use App\Models\PointsMall\Order;
use App\Services\MemberAccountService;
use App\Services\OrderNotifyService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PointsMallController extends ApiController
{
    public function Index(Request $request)
    {
        $list = PointsMall::where('uniacid', $this->uniacid())
            ->where('storeId', $this->isolateStore())
            ->when($request->keyword, function ($q) use ($request) {
                return $q->where('name', 'like', "%$request->keyword%");
            })->when($request->type_id, function ($q) use ($request) {
                return $q->where('type_id', $request->type_id);
            })
            ->where('display', 1)
            ->where('state', 1)
            ->orderBy('sort', 'asc')
            ->paginate($request->size ?? 10, '*', 'page');
        return $this->success($list);
    }

    public function show(Request $request, $id)
    {
        $list = PointsMall::where('uniacid', $this->uniacid())
            ->where('display', 1)
            ->orderBy('sort', 'asc')
            ->where('id', $id)
            ->first();
        return $this->success($list);
    }


    public function checkout(Request $request)
    {
        $key = "PointsMall:" . $this->userId();
        $model = new Checkout([
            'addressId' => $request->addressId,
            'storeId' => $this->isolate() == 1 ? $this->isolateStore() :  $request->storeId,
            'userId' => $this->userId(),
            'uniacid' => $this->uniacid(),
            'goodsId' => $request->goodsId,
            'channel' => $this->channel(),
            'score' => $this->appType(),
            'isolateStore' => $this->isolateStore(),
            'diningType' => $request->diningType,
        ]);
        $model->check();
        Cache::set($key, $model->toArray(), 600);
        return $this->success($model->toArray());
    }

    public function store()
    {
        $model =  MemberAccount::where('uniacid', $this->uniacid())->where('userId', $this->userId())->first();
        if(empty($model)){
            return $this->failed('用户账号不存在');
        }
        $key = "PointsMall:" . $this->userId();
        if (!Cache::has($key)) {
            return $this->failed('请先初始化');
        }
        DB::beginTransaction();
        try {
            $chekcout = Cache::get($key);
            $order = Order::where('orderSn', $chekcout['order']['orderSn'])->where('state', 1)->first();
            if (!$order) {
                $order = Order::create($chekcout['order']);
            }
            if($order->points>$model->integral){
                return $this->failed('积分不足');
            }
//            if ($order->points > 0) {
//                if ($order->points > 0 && bcmul($order->moeny, 100) > 0 && (bcmul($order->moeny, 100) - 1) <= 0) {
//                    $res = MemberAccountService::pointsPay($order->orderSn, $order->userId);
//                    if (!$res) {
//                        DB::rollBack();
//                        return $this->failed('扣减积分失败');
//                    }
//                    if ($order->money == 0) {
//                        $res = OrderNotifyService::pointsMail([
//                            'trade_type' => PayEnum::POINTS,
//                            'payChannel' => 1
//                        ], $order->orderSn);
//                        if (!$res) {
//                            DB::rollBack();
//                            return $this->failed('下单失败');
//                        }
//                    }
//                }
//            }
            DB::commit();
            Cache::delete($key);
            return $this->success([], '下单成功');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failed($e->getMessage());
        }
    }
}
