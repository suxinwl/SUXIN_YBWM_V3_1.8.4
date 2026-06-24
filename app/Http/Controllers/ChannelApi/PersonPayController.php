<?php

namespace App\Http\Controllers\ChannelApi;

use App\Http\Requests\Admin\ChangePassword;
use App\Http\Resources\ChannelApi\User\Profix;
use App\Models\Ad;
use App\Models\Admin;
use App\Models\Coupon\MemberCoupon;
use App\Models\Member;
use App\Models\Member\Address;
use App\Models\MemberAddress;
use App\Models\MemberBind;
use App\Models\Order\Discount;
use App\Models\PersionPay\Checkout;
use App\Models\PersionPayOrder;
use App\Models\UserAccount;
use App\Services\ConfigService;
use App\Services\MenuService;
use App\Services\UserService;
use Cache;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache as FacadesCache;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Tymon\JWTAuth\Facades\JWTAuth;

class PersonPayController extends ApiController
{
    public function show(Request $request, $id)
    {
        $store =  DB::table('store')->select(['id', 'name', 'lat', 'lng', 'address'])->where('uniacid', $this->uniacid())->where('id', $id)->first();
        if (empty($store)) {
            return $this->failed('门店不存在');
        }
        $data['store'] = $store;
        $data['config'] = ConfigService::getChannelConfig('PersonPayConfig', $this->uniacid(),$this->storeId());
        return $this->success($data);
    }

    public function store(Request $request)
    {
        if ($request->money <= 0) {
            return $this->failed('支付金额不正确');
        }
        $key = "personPay:" . $this->userId();
        $checkout = FacadesCache::get($key);
        if($request->couponId==0){
            $checkout['money']=$request->money;
            $checkout['sellMoney']=$request->money;
        }
        if (!$checkout) {
            return $this->failed('请先初始化');
        }
        $order = new PersionPayOrder($checkout);
        $order->orderSn = getTakeOutNo();
        $order->remarks = $request->remarks ?? '';
        $order->score = $this->appType();
        $order->save();
        foreach ($checkout['discounts'] as $key => $discount) {
            $discount['uniacid'] = $checkout['uniacid'];
            $discount['orderId'] = 0;
            $discount['orderSn'] = $order->orderSn;
            $discount['storeId'] = $checkout['storeId'];
            $discount['userId'] = $checkout['userId'];
            Discount::create($discount);
        }
        if ($order->couponId) {
            MemberCoupon::where('id', $order->couponId)->update([
                'state' => 2,
                'updated_at' => date("Y-m-d H:i:s", time()),
                'orderId' => 0,
                'orderSn' => $order->orderSn
            ]);
        }
        FacadesCache::delete($key);
        return $this->success($order);
    }

    public function checkout(Request $request)
    {
        $model =   new Checkout([
            'uniacid' => $this->uniacid(),
            'storeId' => $this->storeId(),
            'userId' => $this->userId(),
            'couponId' => $request->couponId ?? 0,
            'sellMoney' => $request->money
        ]);
        FacadesCache::set("personPay:" . $this->userId(), $model->toArray(), 600);
        return $this->success($model);
    }
}
