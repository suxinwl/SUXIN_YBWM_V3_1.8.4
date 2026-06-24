<?php

namespace App\Http\Controllers\ChannelApi;

use App\Http\Requests\Cart\CartRequest;
use App\Models\GoodsSpu;
use App\Models\Member\Address;
use App\Models\Order\TakeOutOrder;
use App\Models\Store;
use App\Models\TakeOut\Cart;
use App\Models\TakeOut\CartList;
use App\Models\TakeOut\Checkout;
use App\Services\ConfigService;
use App\Services\StoreGeoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use App\Models\Goods\SetmealGoodsIds;
use App\Models\Store\StoreGoods;
use App\Models\Store\StoreGoodsSku;
use App\Models\Region;
class CartController extends ApiController
{
    public function index(Request $request)
    {
        $model = new CartList([
            "uniacid" => $this->uniacid(),
            'storeId' => $this->storeId(),
            'userId' => $this->userId(),
            'scene' => $request->scene,
            'diningType' => $request->diningType,
        ]);
        $model->fill($request->all());
        if ($request->isChange) {
            collect($model->goodsList)->whereIn('discountType', [0, 6])->each(function ($goods) {
                $model = new Cart();
                $model->fill($goods->toArray());
                $model->uniacid = $this->uniacid();
                $model->storeId = $this->storeId();
                $model->userId = $this->userId();
                $model->num = 0;
                $model = $model->model();
                DB::table('takeout_cart')->where('id', $model->id)->update($model->toArray());
            });

            $model = new CartList([
                "uniacid" => $this->uniacid(),
                'storeId' => $this->storeId(),
                'userId' => $this->userId(),
                'scene' => $request->scene,
                'diningType' => $request->diningType,
            ]);
        }

        return $this->success($model->toArray());
    }

    public function price(Request $request)
    {
        $model = new Cart();
        $model->fill($request->all());
        $model->uniacid = $this->uniacid();
        $model->storeId = $this->storeId();
        $model->userId = $this->userId();
        $model->num = $request->num;
        if ($request->specMd5) {
            $model = $model->model(false);
            return $this->success(['money' => $model->money, 'sellMoney' => $model->sellMoney, 'discountLabel' => $model->sku->discount['discountLabel'] ?? null]);
        }
        return $this->success($model->getMaterialMoney());
    }


    public function store(CartRequest $request)
    {
        if($request->setMealData){
            foreach ($request->setMealData as $v){
                $re=SetmealGoodsIds::where('id',$v['id'])->first();
                $res=StoreGoodsSku::where('spuId',$re->spuId)->where('storeId',$request->storeId)->first();
                if($res){
                    if($res->surplusInventory<$v['num']){
                        throw new BadRequestException($v['name']."门店库存不足");
                    }
                }

            }
        }
        $model = new Cart();
        $model->fill($request->all());
        $model->uniacid = $this->uniacid();
        $model->storeId = $this->storeId();
        $model->userId = $this->userId();
        $model->num = $request->num;
        $model->diningType = $request->diningType;
        $model = $model->model(true,$request->specMd5,$request->setMealData,$request->attrData,$request->diningType);
        $model->save();
        Cart::where('MD5', $request->specMd5)
            ->where("uniacid", $this->uniacid)
            ->where("storeId", $this->storeId)
            ->delete();
        if ($model->num == 0) {
            $model->delete();
        }
        $cart = new CartList([
            "uniacid" => $this->uniacid(),
            'storeId' => $this->storeId(),
            'userId' => $this->userId(),
            'diningType' => $request->diningType
        ]);
        $count = collect($cart->goodsList)->where('spuId', $model->spuId)->sum('num');
        return $this->success(['cart' => $cart->toArray(), 'count' => $count]);
    }

    public function clear()
    {
        Cart::where("uniacid", $this->uniacid())->where('storeId', $this->storeId())->where('userId', $this->userId())->delete();
        $cart = new CartList([
            "uniacid" => $this->uniacid(),
            'storeId' => $this->storeId(),
            'userId' => $this->userId(),
        ]);
        return $this->success(['cart' => $cart->toArray()], '成功');
    }


    public  function checkout(Request $request)
    {
        if (empty(intval($this->storeId()))) {
            return $this->failed('缺少参数门店Id');
        }
        $model = new CartList([
            "uniacid" => $this->uniacid(),
            'storeId' => $this->storeId(),
            'userId' => $this->userId(),
            'scene' => 2
        ]);
        collect($model->goodsList)->whereIn('discountType', [0, 6])->each(function ($goods) {
            $model = new Cart();
            $model->fill($goods->toArray());
            $model->uniacid = $this->uniacid();
            $model->storeId = $this->storeId();
            $model->userId = $this->userId();
            $model->num = 0;
            $model = $model->model();
            DB::table('takeout_cart')->where('id', $model->id)->update($model->toArray());
        });
        $model = new Checkout(
            [
                'uniacid' => $this->uniacid(),
                'storeId' => $this->storeId(),
                'userId' => $this->userId(),
                'addressId' => $request->addressId ?? 0,
                'couponId' => $request->couponId ?? 0,
                'tradeinGoodsId' => $request->tradeinGoodsId ?? 0
            ]
        );
        $model->fill($request->all());
        Cache::put('TakeCheckout:' . $this->userId(), $model);
        $data=$model->toArray();
        return $this->success($data);
    }


    public  function address(Request $request)
    {
        $checkout = Cache::get('TakeCheckout:' . $this->userId());
        if (empty($checkout)) {
            throw new BadRequestException('请先到收银台结账');
        }
        $list = $checkout->addressList;
        if($request->diningType==30){
            $express_delivery = ConfigService::getChannelConfig('express_delivery', $this->uniacid());
            if($express_delivery['rangeType']>1){
                if($express_delivery['rangeType']==2){
                    foreach ($list as $k=>$v){
                        $list[$k]['disable']=1;
                        $province=Region::where('name',$v->province)->first();
                        $city=Region::where('name',$v->city)->first();
                        $district=Region::where('name',$v->district)->first();
                        $zuhe=[$province->id,$city->id,$district->id];
                        foreach ($express_delivery['address'] as $vo){
                            if($zuhe==$vo){
                                $list[$k]['disable']=0;
                            }
                        }
                    }
                }
                if($express_delivery['rangeType']==3){
                    foreach ($list as $k=>$v){
                        $list[$k]['disable']=1;
                        $province=Region::where('name',$v->province)->first();
                        $city=Region::where('name',$v->city)->first();
                        $district=Region::where('name',$v->district)->first();
                        $zuhe=[$province->id,$city->id,$district->id];
                        foreach ($express_delivery['address'] as $vo){
                            if($zuhe!==$vo){
                                $list[$k]['disable']=0;
                            }
                        }
                    }
                }

            }
        }

        return $this->success($list);
    }
}
