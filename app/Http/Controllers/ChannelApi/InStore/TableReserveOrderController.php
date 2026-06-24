<?php

namespace App\Http\Controllers\ChannelApi\InStore;

use App\Enums\WorkEnum;
use App\Events\StoreMessageEvent;
use App\Http\Controllers\ChannelApi\ApiController;
use App\Http\Requests\Admin\ChangePassword;
use App\Http\Resources\ChannelApi\User\Profix;
use App\Models\Ad;
use App\Models\Admin;
use App\Models\InStore\Cart;
use App\Models\Member;
use App\Models\Member\Address;
use App\Models\MemberAddress;
use App\Models\MemberBind;
use App\Models\Table\ReserveOrder;
use App\Models\Tables\Area;
use App\Models\Tables\Table;
use App\Models\Tables\Type;
use App\Models\TablesReserve\Checkout;
use App\Models\TablesReserve\Order;
use App\Models\UserAccount;
use App\Services\ConfigService;
use App\Services\MemberAccountService;
use App\Services\MenuService;
use App\Services\UserService;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Tymon\JWTAuth\Facades\JWTAuth;

class TableReserveOrderController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $list = Order::with(['type', 'area', 'store'])->where("userId", $this->userId())
                ->where("uniacid", $this->uniacid())
                ->where('state', ">", 1)
                ->orderBy('id', 'desc')
                ->paginate($request->size ?? 10, '*', 'page');
            return $this->success($list);
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }

    public function show(Request $request, $orderSn)
    {
        try {
            $model = Order::with(['type', 'area', 'store'])->where("userId", $this->userId())
                ->where("uniacid", $this->uniacid())
                ->orderBy('id', 'desc')
                ->where('orderSn', $orderSn)
                ->first();
            if (!$model) {
                return $this->failed('数据不存在');
            }
            return $this->success($model);
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            $model = new Checkout([
                'uniacid' => $this->uniacid(),
                'userId' => $this->userId(),
                'storeId' => $this->storeId(),
                'typeId' => intval($request->typeId),
                'areaId' => intval($request->areaId),
                'notes' => $request->notes,
                'score' => $this->appType(),
                'person' => $request->person,
                'mobile' => $request->mobile,
                'num' => $request->num ?? 1,
                'contact' => $request->contact,
                'appointmentTime' => $request->appointmentTime,
            ]);
            $order = $model->order;
            $order->save();
            if ($order->money == 0) {
                $res = MemberAccountService::pay($order->orderSn, 0, 0, $order->userId);
                if (!$res) {
                    DB::rollBack();
                    return $this->failed('预定失败');
                }
            }
            DB::commit();
            return $this->success($order->orderSn);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return $this->failed($e->getMessage());
        }
    }
}
