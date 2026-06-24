<?php

namespace App\Http\Controllers\Channel\InStore;

use App\Events\StoreMessageEvent;
use App\Http\Controllers\Channel\ApiController;
use App\Http\Requests\Admin\ChangePassword;
use App\Http\Resources\Channel\Tables\TablesListResources;
use App\Http\Resources\ChannelApi\User\Profix;
use App\Models\Ad;
use App\Models\Admin;
use App\Models\InStore\Cart;
use App\Models\InStore\ChannelCart;
use App\Models\InStore\FreezeOrder;
use App\Models\InStore\Order\Order;
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
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Tymon\JWTAuth\Facades\JWTAuth;

use function PHPUnit\Framework\isEmpty;

class FreezeOrderController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $list = FreezeOrder::where('uniacid', $this->uniacid())->where('storeId', $this->storeId())->where('userId', $this->userId())->paginate($request->pageSize ?? 20, '*', 'pageNo');
            return $this->success($list);
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }

    public function store(Request $request)
    {
        $checkoutKey = 'InstoreCheckout:Store:' .$this->storeId(). $this->userId() . $this->tableId() . 6 . $this->appType();
        $checkout = Cache::get($checkoutKey);
        if (empty($checkout) || $checkout->goodsNum == 0) {
            throw new BadRequestException('请先添加商品');
        }
        $model = FreezeOrder::create([
            'uniacid' => $this->uniacid(),
            'storeId' => $this->storeId(),
            'userId' => $this->userId(),
            'checkout' => collect($checkout)->toArray(),
            'goods' => collect($checkout->goodsList)->toArray(),
        ]);
        ChannelCart::where('uniacid', $this->uniacid())->where('storeId', $this->storeId())->whereIn('id', collect($checkout->goodsList)->pluck('id')->all())->delete();
        return $this->success([], '挂单成功');
    }

    public function unFreeze(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            $freeze = FreezeOrder::where('uniacid', $this->uniacid())->where('storeId', $this->storeId())->find($id);
            if (empty($freeze)) {
                return $this->failed('数据不存在');
            }
            $checkoutKey = 'InstoreCheckout:Store:' .$this->storeId(). $this->userId() . $this->tableId() . 6 . $this->appType();
            $checkout = Cache::get($checkoutKey);
            if ($checkout && $checkout->goodsNum > 0) {
                $model = FreezeOrder::create([
                    'uniacid' => $this->uniacid(),
                    'storeId' => $this->storeId(),
                    'userId' => $this->userId(),
                    'checkout' => collect($checkout)->toArray(),
                    'goods' => collect($checkout->goodsList)->toArray(),
                ]);
                ChannelCart::where('uniacid', $this->uniacid())->where('storeId', $this->storeId())->whereIn('id', collect($checkout->goodsList)->pluck('id')->all())->delete();
            }
            $goodsList =[];
            foreach ($freeze->goods as $key => $goods) {
                unset($goods['id'], $goods['goods']);
                $goods['attrData']=json_encode($goods['attrData'],320);
                $goodsList[] = $goods;
            }
            ChannelCart::insert($goodsList);
            $freeze->delete();
            DB::commit();
            return $this->success('取单成功');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failed($e->getMessage());
        }
    }


    public function destroy(Request $request, $id)
    {
        $freeze = FreezeOrder::where('uniacid', $this->uniacid())->where('storeId', $this->storeId())->find($id);
        if (empty($freeze)) {
            return $this->failed('数据不存在');
        }
        $freeze->delete();
        return $this->success([], '删除成功');
    }
}
