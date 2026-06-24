<?php

namespace App\Http\Controllers\Channel\InStore;

use App\Events\StoreMessageEvent;
use App\Http\Controllers\Channel\ApiController;
use App\Http\Requests\Admin\ChangePassword;
use App\Http\Resources\Channel\Tables\TablesListResources;
use App\Http\Resources\ChannelApi\User\Profix;
use App\Models\Ad;
use App\Models\Admin;
use App\Models\CostomPay;
use App\Models\InStore\Cart;
use App\Models\InStore\CartList;
use App\Models\InStore\ChannelCart;
use App\Models\InStore\FreezeOrder;
use App\Models\InStore\Order\Order;
use App\Models\InStore\StoreCheckout;
use App\Models\Member;
use App\Models\Member\Address;
use App\Models\MemberAddress;
use App\Models\MemberBind;
use App\Models\Order\OrderGoods;
use App\Models\Order\OrderIndex;
use App\Models\Order\User;
use App\Models\Tables\Table;
use App\Models\UserAccount;
use App\Services\ConfigService;
use App\Services\InStoreOrderService;
use App\Services\MenuService;
use App\Services\OrderNotifyService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Tymon\JWTAuth\Facades\JWTAuth;

use function PHPUnit\Framework\isEmpty;

class CheckoutController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $model = new CartList([
                "uniacid" => $this->uniacid(),
                'storeId' => $this->storeId(),
                'tableId' => $this->tableId(),
                'userId' => 0,
                'adminId' => $this->userId(),
                'score' => $this->appType(),
                'diningType' => $this->diningType()
            ]);
            collect($model->goodsList)->whereIn("discountType", [0, 6])->where('isTemp', 0)->each(function ($goods, $key) {
                $model = new ChannelCart();
                $model->fill($goods->toArray());
                $model->uniacid = $this->uniacid();
                $model->storeId = $this->storeId();
                $model->score = $this->appType();
                $model->userId = 0;
                $model->num  = $goods->num;
                $model = $model->model(false);
                $data = $model->toArray();
                unset($data['MD5'], $data['goods']);
                DB::table('instore_cart')->where('id', $goods->id)->update($data);
            });
            $checkoutKey = 'InstoreCheckout:Store:' . $this->storeId() . $this->userId() . $this->tableId() . $this->diningType() . $this->appType();
            $tableModel = Table::where('uniacid', $this->uniacid())->find($this->tableId());
            if (empty($model)) {
                return $this->failed('数据不存在');
            }
            if($tableModel->state==1){
                Cache::delete($checkoutKey);
            }
            $model =  Cache::get($checkoutKey);
            if (!empty($model)) {
                if ($request->userId != $model->userId) {
                    $model->coupon = 0;
                }
                $model->userId = $request->userId ?? 0;
            } else {

                $model = new StoreCheckout(
                    [
                        'uniacid' => $this->uniacid(),
                        'storeId' => $this->storeId(),
                        'userId' => $request->userId ?? 0,
                        'tableId' => $this->tableId(),
                        'score' => $this->appType(),
                        'diningType' => $request->diningType ?? 0,
                        'couponId' => $request->couponId ?? 0,
                        "adminId" => $this->userId()
                    ]
                );
                //$model->fill($request->all());
            }
            if (!$request->check === 'false') {
                $model->check();
            }
            $model->calculateDiscount();
            Cache::put($checkoutKey, $model);
            $data=$model->toArray();
            $res = ConfigService::getChannelConfig('basicSetting', $this->uniacid());
            if($res['service_charge']){
                $percentage=$res['service_charge'];
                $percentage /= 100;
                $service_money = bcmul($model->money,$percentage,2);
                $data['service_charge']=$res['service_charge'];
                $data['service_money']=$service_money;
                $data['money']=bcadd($data['money'],$service_money,2);
            }
            if($request->userId&&$request->orderSn){
                $orderIndex = OrderIndex::where('orderSn', $request->orderSn)->first();
                if($orderIndex){
                    $orderIndex->userId=$request->userId;
                    $orderIndex->save();
                }
                $order = Order::where('orderSn', $request->orderSn)
                    ->first();
                if($order){
                    $order->userId=$request->userId;
                    $order->save();
                }
            }
            return $this->success($data);
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }

    public function notes(Request $request)
    {
        $checkoutKey = 'InstoreCheckout:Store:' . $this->storeId() . $this->userId() . $this->tableId() . $this->diningType() . $this->appType();
        $model =  Cache::get($checkoutKey);
        if (empty($model)) {
            return $this->failed('请先初始化数据');
        }
        $model->notes = $request->notes ?? '';
        Cache::put($checkoutKey, $model);
        Cache::set("$checkoutKey".$this->tableId().'notes',$request->notes);
        return $this->success($model->toArray());
    }

    public function  discount(Request $request)
    {
        $checkoutKey = 'InstoreCheckout:Store:' . $this->storeId() . $this->userId() . $this->tableId() . $this->diningType() . $this->appType();
        $model =  Cache::get($checkoutKey);
        if (empty($model)) {
            return $this->failed('请先初始化数据');
        }
        $model->manualDiscount = $request->all() ?? null;
        $model->wipeZero = null;
        $model->calculateDiscount();
        Cache::put($checkoutKey, $model);
        return $this->success($model->toArray());
    }

    public function  wipeZero(Request $request)
    {
        $checkoutKey = 'InstoreCheckout:Store:' . $this->storeId() . $this->userId() . $this->tableId() . $this->diningType() . $this->appType();
        $model =  Cache::get($checkoutKey);
        if (empty($model)) {
            return $this->failed('请先初始化数据');
        }
        $model->wipeZero = $request->all() ?? null;
        $model->calculateDiscount();
        Cache::put($checkoutKey, $model);
        return $this->success($model->toArray());
    }

    public function  cancelDiscount(Request $request)
    {
        $checkoutKey = 'InstoreCheckout:Store:' . $this->storeId() . $this->userId() . $this->tableId() . $this->diningType() . $this->appType();
        $model =  Cache::get($checkoutKey);
        if (empty($model)) {
            return $this->failed('请先初始化数据');
        }
        if ($request->type == 'wipeZero') {
            $model->wipeZero = null;
        } else {
            $model->manualDiscount = null;
            $model->wipeZero = null;
        }
        $model->calculateDiscount();
        Cache::put($checkoutKey, $model);
        return $this->success($model->toArray());
    }

    public function  free(Request $request)
    {
        $checkoutKey = 'InstoreCheckout:Store:' . $this->storeId() . $this->userId() . $this->tableId() . $this->diningType() . $this->appType();
        $model =  Cache::get($checkoutKey);
        if (empty($model)) {
            return $this->failed('请先初始化数据');
        }
        DB::beginTransaction();
        try {
            foreach ($model->goodsList as $key => $goods) {
                $goods->discountType = 1;
                $goods->discountLabel = "赠";
                $goods->discountPrice = 0;
                $goods->discountNum = $goods->num;
                $goods->discountMoney = bcadd($goods->getDiscountMoney(), $goods->materialMoney);
                $goods->materialMoney = $goods->getMaterialMoney();
                $goods->sellMoney = $goods->getSellMoney();
                $goods->money = $goods->getMoney();
                $goods->save();
            }
            $model->free = 1;
            $model->freeReason = $request->reason;
            $model->calculateDiscount();
            Cache::put($checkoutKey, $model);
            $model = InStoreOrderService::createOrder($request, $this->storeId(), $this->userId(), $this->tableId(), $this->appType(), $this->uniacid());
            $order = [
                'takeOutNo' => $model->orderIndex->orderSn,
                'amount' => $model->money,
                'desc' => "代客下单",
                'payTempId' => 0,
                'trade_type' => 6,
                'payChannel' => 2,
                'payer' => ['openid' => null]
            ];
            if (!OrderNotifyService::inStore($order, $order['takeOutNo'], $order['payTempId'])) {
                DB::rollBack();
                return $this->failed("免单失败");
            };
            DB::commit();
            Cache::delete($checkoutKey);
            return $this->success([], '免单成功');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failed($e->getMessage());
        }
    }

    public function coupon(Request $reques)
    {
        $checkoutKey = 'InstoreCheckout:Store:' . $this->storeId() . $this->userId() . $this->tableId() . $this->diningType() . $this->appType();
        $model =  Cache::get($checkoutKey);
        if (empty($model)) {
            return $this->failed('请先初始化数据');
        }
        $model->couponId = $reques->couponId  ?? 0;
        Cache::put($checkoutKey, $model);
        return $this->success($model->toArray());
    }

    public function costomPay(Request $reques)
    {
        $list = CostomPay::where('uniacid', $this->uniacid())
            ->where('state', 1)->get();
        return $this->success($list);
    }
}
