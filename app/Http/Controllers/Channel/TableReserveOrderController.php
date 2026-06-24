<?php

namespace App\Http\Controllers\Channel;

use App\Enums\WorkEnum;
use App\Events\StoreMessageEvent;
use App\Http\Controllers\Channel\ApiController;
use App\Http\Requests\Admin\ChangePassword;
use App\Http\Resources\ChannelApi\User\Profix;
use App\Jobs\OrderStatisticsJob;
use App\Models\Ad;
use App\Models\Admin;
use App\Models\InStore\Cart;
use App\Models\Member;
use App\Models\Member\Address;
use App\Models\MemberAddress;
use App\Models\MemberBind;
use App\Models\Order\OrderIndex;
use App\Models\RefundOrder;
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
use App\Services\PayService;
use App\Services\StoreAccountService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Log;
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
            $user = $this->user();
            $storeId = $this->storeId();
            $isolate = $this->isolate();
            $timeArr = $this->timeArr(true);
            $list = Order::with(['type', 'area', 'store'])
                ->where("uniacid", $this->uniacid())
                ->orderBy('id', 'desc')
                ->when($request->payType, function ($q) use ($request) {
                    return $q->whereHas('orderIndex', function ($q) use ($request) {
                        if ($request->payType == 'wexin') {
                            return $q->weixin();
                        }
                        if ($request->payType == 'ali') {
                            return $q->ali();
                        }
                        if ($request->payType == 'balance') {
                            return $q->balance();
                        }
                        return $q;
                    });
                })
                ->when($isolate, function ($q) use ($storeId, $isolate) {
                    return $q->whereHas('store', function ($q) use ($storeId) {
                        return $q->where('isolate', 1)->where('storeId', $storeId);
                    });
                })
                ->when(!$isolate, function ($q) use ($storeId, $isolate) {
                    return $q->whereHas('store', function ($q) use ($storeId) {
                        return $q->where('isolate', 0)->when($storeId, function ($q) use ($storeId) {
                            return $q->where('storeId', $storeId);
                        });
                    });
                })
                ->when($request->timeType, function ($q) use ($request, $timeArr) {
                    return $q->where('created_at', '>=', $timeArr['startTime'])
                        ->where('created_at', '<=', $timeArr['endTime']);
                })
                ->when($request->source, function ($q) use ($request) {
                    return $q->where('source', appType($request->source));
                })
                ->when($request->userKeyword, function ($q) use ($request) {
                    return $q->whereHas('user', function ($q) use ($request) {
                        return $q->where('mobile', "like", "%$request->userKeyword%")
                            ->orWhere('nickname', "li1ke", "%$request->userKeyword%");
                    });
                })->when($user, function ($q) use ($user) {
                    if ($user->isAdmin == 0) {
                        if (empty($user->storeId)) {
                            $q->where('storeId', 0);
                        } else {
                            $q->whereIn('storeId', $user->storeId);
                        }
                    }
                    return $q;
                })->when($request->state, function ($q) use ($request) {
                    switch ($request->state) {
                        case 'close':
                            return $q->close();
                            break;
                        case 'unpaid':
                            return $q->unpaid();
                            break;
                        case 'unReceived':
                            return $q->unReceived();
                            break;
                        case 'making':
                            return $q->making();
                            break;
                        case 'complete':
                            return $q->complete();
                            break;
                        case 'refund':
                            return $q->refund();
                            break;
                        default:
                            return $q->where('state', ">", 1);
                    }
                })
                ->paginate($request->pageSize ?? 20, '*', 'pageNo');
            return $this->success($list);
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }

    public function show(Request $request, $orderSn)
    {
        try {
            $model = Order::with(['type', 'area', 'store'])
                ->where("uniacid", $this->uniacid())
                ->orderBy('id', 'desc')
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
            return $this->failed($e->getMessage());
        }
    }

    /**
     * 退款
     */
    public  function refund(Request $request, $orderSn)
    {
        DB::beginTransaction();
        try {
            $orderIndex = OrderIndex::paid()->where('orderSn', $orderSn)->where('uniacid', $this->uniacid())->first();
            if (empty($orderIndex)) {
                throw new BadRequestException('数据不存在');
            }
            $refundMoney = $orderIndex->subOrder->money;
            $adminId = $this->userId();
            $reason = $request->reason;
            $log = null;
            $order = [
                'takeOutNo' => $orderIndex->orderSn,
                'openid' => $orderIndex->payer,
                'transaction_id' => $orderIndex->thirdNo,
                'total_amount' => $orderIndex->personPayOrder->money,
                'refund_amount' => $refundMoney,
            ];
            if ($orderIndex->payChannel == 1) {
                if (!StoreAccountService::refundDirectly($orderIndex->storeId, $refundMoney, $this->userId(), $orderIndex->orderSn)) {
                    DB::rollBack();
                    throw  new BadRequestException('申请退款失败');
                }
            }
            if ($orderIndex->payType != 6) {
                $refundOrder = RefundOrder::where('takeOutNo', $orderIndex->orderSn)->first();
                if (empty($refundOrder)) {
                    $res = PayService::refund($order, $orderIndex->uniacid, $orderIndex->payTempId);
                } else {
                    $res = true;
                }
            } else {
                $res = true;
            }

            if ($res) {
                $orderIndex->subOrder->refundMoney = $refundMoney;
                $orderIndex->subOrder->reason = $reason;
                $orderIndex->state =  8;
                $orderIndex->subOrder->state = 8;
                $orderIndex->subOrder->save();
                $orderIndex->save();
                DB::commit();
                dispatch(new OrderStatisticsJob($orderIndex->orderSn));
                return $this->success(null, '退款成功');
            }
            DB::rollBack();
            return $this->failed('退款失败');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::info($e->getMessage());
            throw new BadRequestException($e->getMessage());
        }
    }

    public function received(Request $request, $orderSn)
    {
        $orderIndex = OrderIndex::paid()
            ->where('orderSn', $orderSn)
            ->where('uniacid', $this->uniacid())
            ->first();
        if (empty($orderIndex)) {
            throw new BadRequestException('数据不存在');
        }
        $orderIndex->subOrder->state = 3;
        $orderIndex->subOrder->reserveTime = Carbon::now()->toDateTimeString();
        $orderIndex->subOrder->save();
        return $this->success(null, '接单成功');
    }
}
