<?php

namespace App\Http\Controllers\ChannelApi\InStore;

use App\Http\Controllers\ChannelApi\ApiController;
use App\Http\Requests\Cart\CartRequest;
use App\Models\Goods\SetmealGoodsIds;
use App\Models\Member\Address;
use App\Models\Order\TakeOutOrder;
use App\Models\Store;
use App\Models\InStore\Cart;
use App\Models\InStore\CartList;
use App\Models\InStore\Checkout;
use App\Models\Tables\Table;
use App\Services\ConfigService;
use App\Services\StoreGeoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use App\Models\Store\StoreGoods;
use App\Models\Store\StoreGoodsSku;
class CartController extends ApiController
{
    public function index(Request $request)
    {
        $model = new CartList([
            "uniacid" => $this->uniacid(),
            'storeId' => $this->storeId(),
            'tableId' => $this->tableId(),
            'userId' => $this->userId(),
            'score' => $this->appType(),
            'diningType' => $this->diningType()
        ]);
        $model->fill($request->all());
        // collect($model->goodsList)->each(function ($goods, $key) {
        //     $goods->save();
        // });
        return $this->success($model->toArray());
    }

    public function price(Request $request)
    {
        $model = new Cart();
        $model->fill($request->all());
        $model->uniacid = $this->uniacid();
        $model->storeId = $this->storeId();
        $model->userId = $this->userId();
        $model->score = $this->appType();
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
        if ($request->diningType == 1) {
            $lockKey = 'InstoreCheckout:' . $this->uniacid() . $request->storeId . $request->tableId . $request->diningType;
        } else {
            $lockKey = 'InstoreCheckout:' . $this->uniacid() . $request->storeId . $request->tableId . $request->diningType . $this->userId();
        }
        $isLocked = Cache::lock($lockKey, 10);
        if (!$isLocked->get()) {
            throw new BadRequestException('有其它操作正在进行,请稍后再试');
        }
        try {

            $model = new Cart();
            $model->fill($request->all());
            $model->uniacid = $this->uniacid();
            $model->storeId = $this->storeId();
            $model->userId = $this->userId();
            $model->num = $request->num;
            $model->score = $this->appType();
            $model = $model->model(true,$this->tableId(),$request->specMd5,$request->setMealData,$request->attrData);
            $model->save();
            if ($model->num == 0) {
                $model->delete();
            }
            $cart = new CartList([
                "uniacid" => $this->uniacid(),
                'storeId' => $this->storeId(),
                'tableId' => $this->tableId(),
                'userId' => $this->userId(),
                'score' => $this->appType(),
                'diningType' => $this->diningType()
            ]);
            $count = collect($cart->goodsList)->where('spuId', $model->spuId)->sum('num');
            if ($isLocked) {
                optional($isLocked)->release();
            }
            if($this->tableId()){
                $table=Table::where('id',$this->tableId())->first();
                if($table->state==0){
                    $table->state=1;
                    $table->scan=1;
                    $table->openTime = date("Y-m-d H:i:s", time());
                    $table->save();
                }
            }
            return $this->success(['cart' => $cart->toArray(), 'count' => $count]);
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        } finally {
            optional($isLocked)->release();
        }
    }

    public function clear(Request $request)
    {
        if ($request->diningType == 4) {
            $lockKey = 'InstoreCheckout:' . $this->uniacid() . $request->storeId . $request->tableId . $request->diningType;
        } else {
            $lockKey = 'InstoreCheckout:' . $this->uniacid() . $request->storeId . $request->tableId . $request->diningType . $this->userId();
        }
        $isLocked = Cache::lock($lockKey, 10);
        if (!$isLocked->get()) {
            throw new BadRequestException('有其它操作正在进行,请稍后再试');
        }
        $userId = $this->userId();
        Cart::where("uniacid", $this->uniacid())
            ->where('storeId', $this->storeId())
            ->when($request->diningType == 4, function ($q) use ($request) {
                return $q->where('tableId', $request->tableId);
            })
            ->when($request->diningType != 4, function ($q) use ($request, $userId) {
                return $q->where('userId', $userId);
            })
            ->delete();
        if ($isLocked) {
            optional($isLocked)->release();
        }
        $cart = new CartList([
            "uniacid" => $this->uniacid(),
            'storeId' => $this->storeId(),
            'tableId' => $this->tableId(),
            'userId' => $this->userId(),
            'score' => $this->appType(),
            'diningType' => $this->diningType()
        ]);
        return $this->success(['cart' => $cart->toArray()], '成功');
    }


    public  function checkout(Request $request)
    {
        if (empty(intval($this->storeId()))) {
            return $this->failed('缺少参数门店Id');
        }
        $model = new Checkout(
            [
                'uniacid' => $this->uniacid(),
                'storeId' => $this->storeId(),
                'userId' => $this->userId(),
                'tableId' => $this->tableId(),
                'score' => $this->appType(),
                'diningType' => $request->diningType ?? 0,
                'couponId' => $request->couponId ?? 0,
            ]
        );
        $model->fill($request->all());
        $model->check();
        Cache::put('InstoreCheckout:' . $this->userId(), $model);
        return $this->success($model->toArray());
    }

    public function packAll(Request $request)
    {
        if ($request->diningType == 4) {
            $lockKey = 'InstoreCheckout:' . $this->uniacid() . $request->storeId . $request->tableId . $request->diningType;
        } else {
            $lockKey = 'InstoreCheckout:' . $this->uniacid() . $request->storeId . $request->tableId . $request->diningType . $this->userId();
        }
        $isLocked = Cache::lock($lockKey, 10);
        if (!$isLocked->get()) {
            throw new BadRequestException('有其它操作正在进行,请稍后再试');
        }
        $userId = $this->userId();
        Cart::where("uniacid", $this->uniacid())
            ->where('storeId', $this->storeId())
            ->when($request->diningType == 4, function ($q) use ($request) {
                return $q->where('tableId', $request->tableId);
            })
            ->when($request->diningType != 4, function ($q) use ($request, $userId) {
                return $q->where('userId', $userId);
            })
            ->update(['pack' => $request->type == 'back' ? 0 : 1]);
        if ($isLocked) {
            optional($isLocked)->release();
        }
        $model = new Checkout(
            [
                'uniacid' => $this->uniacid(),
                'storeId' => $this->storeId(),
                'userId' => $this->userId(),
                'tableId' => $this->tableId(),
                'score' => $this->appType(),
                'diningType' => $request->diningType ?? 0,
                'couponId' => $request->couponId ?? 0,
            ]
        );
        return $this->success($model->toArray());
    }
}
