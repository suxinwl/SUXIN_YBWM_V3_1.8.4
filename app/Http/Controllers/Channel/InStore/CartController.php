<?php

namespace App\Http\Controllers\Channel\InStore;

use App\Http\Controllers\Channel\ApiController;
use App\Http\Requests\Cart\CartRequest;
use App\Models\Member\Address;
use App\Models\Order\TakeOutOrder;
use App\Models\Store;
use App\Models\InStore\Cart;
use App\Models\InStore\CartList;
use App\Models\InStore\ChannelCart;
use App\Models\InStore\Checkout;
use App\Models\InStore\FreezeOrder;
use App\Models\InStore\StoreCheckout;
use App\Models\InStore\TempCart;
use App\Services\ConfigService;
use App\Services\InStoreOrderService;
use App\Services\StoreGeoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use function PHPUnit\Framework\isEmpty;

class CartController extends ApiController
{
    public function index(Request $request)
    {
        $model = new CartList([
            "uniacid" => $this->uniacid(),
            'storeId' => $this->storeId(),
            'tableId' => $this->tableId(),
            'userId' => 0,
            'adminId' => $this->userId(),
            'score' => $this->appType(),
            'diningType' => $this->diningType()
        ]);
        $model->fill($request->all());
        return $this->success($model->toArray());
    }

    public function price(Request $request)
    {
        $model = new ChannelCart();
        $model->fill($request->all());
        $model->uniacid = $this->uniacid();
        $model->storeId = $this->storeId();
        $model->score = $this->appType();
        $model->userId = 0;
        $model->num = $request->num;
        if ($request->specMd5) {
            $model = $model->model(false);
            return $this->success(['money' => $model->money, 'sellMoney' => $model->sellMoney, 'discountLabel' => $model->sku->discount['discountLabel'] ?? null]);
        }
        return $this->success($model->getMaterialMoney());
    }


    public function store(CartRequest $request)
    {
        if ($request->diningType == 4) {
            $lockKey = 'InstoreCheckout:' . $this->uniacid() . $request->storeId . $request->tableId . $request->diningType;
        } else {
            $lockKey = 'InstoreCheckout:' . $this->uniacid() . $request->storeId . $request->tableId . $request->diningType . $this->userId();
        }
        $isLocked = Cache::lock($lockKey, 1);
        if (!$isLocked->get()) {
            throw new BadRequestException('有其它操作正在进行,请稍后再试');
        }
        if ($request->isTemp) {
            $model = new TempCart();
        } else {
            $model = new ChannelCart();
        }
        $model->fill($request->all());
        $model->uniacid = $this->uniacid();
        $model->storeId = $this->storeId();
        $model->userId = 0;
        $model->adminId = $this->userId();
        $model->score = $this->appType();
        $model->num = $request->num;
        $model = $model->model(true,$request->tableId,$request->specMd5,$request->setMealData,$request->attrData,$this->userId());
        $model->save();
        if ($model->num == 0) {
            $model->delete();
        }
        $cart = new CartList([
            "uniacid" => $this->uniacid(),
            'storeId' => $this->storeId(),
            'tableId' => $this->tableId(),
            'userId' => 0,
            'score' => $this->appType(),
            'adminId' => $this->userId(),
            'diningType' => $this->diningType()
        ]);
        $count = collect($cart->goodsList)->where('spuId', $model->spuId)->sum('num');
        if ($isLocked) {
            optional($isLocked)->release();
        }
        if ($this->appType() == 10) {
            $checkoutKey = 'InstoreCheckout:Store:' . $this->storeId() . $this->userId()  . $this->tableId() . $this->diningType() . $this->appType();
            $checkout = Cache::get($checkoutKey);
            return $this->success($checkout->toArray());
        }
        return $this->success(['cart' => $cart->toArray(), 'count' => $count]);
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
            ->where('diningType', $request->diningType)
            ->when($request->diningType == 4, function ($q) use ($request) {
                return $q->where('tableId', $request->tableId);
            })
            ->when($request->diningType != 4, function ($q) use ($request, $userId) {
                return $q->where('adminId', $userId);
            })
            ->delete();
        if ($isLocked) {
            optional($isLocked)->release();
        }
        $checkoutKey = 'InstoreCheckout:Store:' . $this->storeId() . $this->userId() . $this->tableId() . $this->diningType() . $this->appType();
        Cache::delete($checkoutKey);
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
        $checkoutKey = 'InstoreCheckout:Store:' . $this->storeId() . $this->userId() . $request->tableId() . $request->diningType . $this->appType();
        $model = Cache::get($checkoutKey);
        if ($model) {
            $model->userId = $request->userId ?? 0;
            $model->diningType = $request->diningType ?? 0;
            $model->couponId = $request->couponId ?? 0;
            $model->packaging = $request->packaging ?? 0;
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
                    "packaging" => $request->packaging ?? 0,
                    "adminId" => $this->userId()
                ]
            );
            $model->fill($request->all());
            if (!$request->check === 'false') {
                $model->check();
            }
            Cache::put($checkoutKey, $model);
        }

        return $this->success($model->toArray());
    }

    public function give(Request $request)
    {
        try {
            DB::beginTransaction();
            $checkoutKey = 'InstoreCheckout:Store:' . $this->storeId() . $this->userId() . $this->tableId() . $this->diningType() . $this->appType();
            $model =  Cache::get($checkoutKey);
            if (empty($model)) {
                return $this->failed('请先初始化数据');
            }
            collect($request->goods)->each(function ($goodsItem) use ($model, $request) {
                $goods = collect($model->goodsList)->where('id', $goodsItem['id'])->first();
                if (empty($goods)) {
                    DB::rollBack();
                    throw new BadRequestException('请先将改商品添加至购物车');
                }
                if (in_array($goods->discountType, [0,1,2,3])) {
                    if ($goodsItem['num'] <= 0) {
                        DB::rollBack();
                        throw new BadRequestException('赠送数量必须大于0');
                    }
                    if ($goods->discountType == 0) {
                        if ($request->type == "give") {
                            $num = $goodsItem['num'] >= $goods->num ? $goods->num : $goodsItem['num'];
                            $goods->num =  $goods->num - $num;
                            $clone = $goods->replicate();
                            $clone->discountNum = $num;
                            $clone->notes = $request->notes;
                            $clone->num = $num;
                            $clone->discountType = 1;
                            $clone->discountLabel = "赠";
                            $clone->discountPrice = 0;
                            $clone->materialMoney = $clone->getMaterialMoney();
                            $clone->sellMoney = $clone->getSellMoney();
                            $clone->discountMoney = $clone->sellMoney;
                            $clone->money = 0;
                            $clone->boxMoney = $clone->getBoxMoney();
                            $clone->MD5 = $clone->getMd5() . time();
                            $clone->save();
                            if (in_array($goods->discountType, [0, 2, 3, 6, 7, 8, 9,10]) && $goods->isTemp == 0) {
                                $goods->getDiscount(true);
                            }
                        } elseif ($request->type == "discount") {
                            $goods->discountNum = $goods->num;
                            $goods->discountType = 2;
                            $goods->discountLabel = "打" . bcdiv($request->discount, 10, 1) . '折';
                            $goods->discountPrice = bcmul(bcdiv($goods->price, 100,4), intval($request->discount), 2);
                            $goods->discountMoney = $goods->getDiscountMoney();
                        } elseif ($request->type == "sub") {
                            $goods->discountNum = 0;
                            $goods->discountType = 3;
                            $goods->discountLabel = "减" . $request->discount . "元";
                            $goods->discountPrice = 0;
                            $goods->discountMoney = bcadd($goods->getDiscountMoney(), $request->discount, 2);
                            $goods->money = bcsub($goods->getMoney(), $request->discount, 2);
                        } else {
                            $goods->money = $goods->getMoney();
                        }
                        $goods->materialMoney = $goods->getMaterialMoney();
                        $goods->sellMoney = $goods->getSellMoney();
                        $goods->boxMoney = $goods->getBoxMoney();
                        $goods->reason = $request->reason;
                        $goods->save();
                        if ($goods->num == 0) {
                            $goods->delete();
                        }
                    } else {
                        if ($request->type == "back") {
                            $goods->discountType = 0;
                            $goods->discountLabel = null;
                            $goods->discountPrice = 0;
                            $goods->discountNum = 0;
                            $goods->reason = null;
                            $goods->discountMoney = 0;
                            $goods->money = $goods->getMoney();
                        } else {
                            $goods->num = $goods->num + $goodsItem['num'];
                            if ($goods->discountType == 1) {
                                $goods->discountNum = $goods->discountNum + $goodsItem['num'];
                                $goods->discountMoney = bcadd($goods->getDiscountMoney(), $goods->materialMoney);
                            }
                            if ($goods->discountType == 2) {
                                $goods->discountNum = $goods->discountNum + $goodsItem['num'];
                                $goods->discountMoney = $goods->getDiscountMoney();
                            }
                            if ($goods->discountType == 3) {
                                $goods->discountMoney = $goods->discountMoney;
                                $goods->money = bcsub($goods->getMoney(), $goods->discountMoney, 2);
                                if ($goods->money < 0) {
                                    throw new BadRequestException('无法继续减免');
                                }
                            } else {
                                $goods->money = $goods->getMoney();
                            }
                        }
                        $goods->getDiscount(false);
                        $goods->materialMoney = $goods->getMaterialMoney();
                        $goods->sellMoney = $goods->getSellMoney();
                        $goods->boxMoney = $goods->getBoxMoney();
                        if ($goods->num <= 0) {
                            $goods->delete();
                        } else {
                            $goods->save();
                        }
                    }
                }
            });
            DB::commit();
            return $this->success([], '操作成功');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failed($e->getMessage());
        }
    }

    public function pack(Request $request)
    {
        if ($request->type == "back") {
            ChannelCart::whereIn("id", $request->ids)->update(['pack' => 0]);
        } else {
            ChannelCart::whereIn("id", $request->ids)->update(['pack' => 1]);
        }
        return $this->success([], '操作成功');
    }

    public function packAll(Request $request)
    {
        $checkoutKey = 'InstoreCheckout:Store:' . $this->storeId() . $this->userId() . $this->tableId() . $this->diningType() . $this->appType();
        $model =  Cache::get($checkoutKey);
        if (empty($model)) {
            return $this->failed('请先初始化数据');
        }
        if ($request->type == "back") {
            ChannelCart::whereIn("id", collect($model->goodsList)->pluck('id')->all())->update(['pack' => 0]);
        } else {
            ChannelCart::whereIn("id", collect($model->goodsList)->pluck('id')->all())->update(['pack' => 1]);
        }
        return $this->success([], '操作成功');
    }

    public function notes(Request $request)
    {
        if ($request->type == "back") {
            ChannelCart::whereIn("id", $request->ids)->update(['notes' => null]);
        } else {
            ChannelCart::whereIn("id", $request->ids)->update(['notes' => $request->notes]);
        }
        return $this->success([], '操作成功');
    }

    public function destroy(Request $request, $id)
    {
        try {
            $idArray = array_filter(explode(',', $id), function ($item) {
                return is_numeric($item);
            });
            Cart::where("uniacid", $this->uniacid())->where("storeId", $this->storeId())->where('id', $id)->delete();
            return $this->success([], '删除成功');
        } catch (\Exception $e) {
            return $this->failed('删除失败');
        }
    }

    public function temp(Request $request)
    {
        if ($request->diningType == 4) {
            $lockKey = 'InstoreCheckout:' . $this->uniacid() . $request->storeId . $request->tableId . $request->diningType;
        } else {
            $lockKey = 'InstoreCheckout:' . $this->uniacid() . $request->storeId . $request->tableId . $request->diningType . $this->userId();
        }
        $isLocked = Cache::lock($lockKey, 1);
        if (!$isLocked->get()) {
            throw new BadRequestException('有其它操作正在进行,请稍后再试');
        }
        $model = new TempCart();
        $model->fill($request->all());
        $model->uniacid = $this->uniacid();
        $model->storeId = $this->storeId();
        $model->userId = 0;
        $model->adminId = $this->userId();
        $model->score = $this->appType();
        $model->num = $request->num;
        $model = $model->model();
        $model->save();
        if ($model->num == 0) {
            $model->delete();
        }
        if ($isLocked) {
            optional($isLocked)->release();
        }
        if ($this->appType() == 10) {
            $checkoutKey = 'InstoreCheckout:Store:' . $this->storeId() . $this->userId()  . $this->tableId() . $this->diningType() . $this->appType();
            $checkout = Cache::get($checkoutKey);
            return $this->success($checkout->toArray());
        }
        $cart = new CartList([
            "uniacid" => $this->uniacid(),
            'storeId' => $this->storeId(),
            'tableId' => $this->tableId(),
            'userId' => 0,
            'score' => $this->appType(),
            'adminId' => $this->userId(),
            'diningType' => $this->diningType()
        ]);
        $count = collect($cart->goodsList)->where('spuId', $model->spuId)->sum('num');
        return $this->success(['cart' => $cart->toArray(), 'count' => $count]);
    }
}
