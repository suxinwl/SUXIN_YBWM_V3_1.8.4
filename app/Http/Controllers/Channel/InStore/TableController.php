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
use App\Services\TableService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Tymon\JWTAuth\Facades\JWTAuth;

use function PHPUnit\Framework\isEmpty;

class TableController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $uniacid = $this->uniacid();
            $storeId = $this->storeId();
            $inStoreSetting = ConfigService::getStoreConfig('inStoreSetting', $storeId);
            if (
                empty(isEmpty($inStoreSetting)) ||
                $inStoreSetting['pickupSwitch'] == 0
                || empty($inStoreSetting['orderMode'])
            ) {
                return $this->failed('堂食业务已关闭');
            }
            $model = Table::with([
                'type' => function ($q) use ($uniacid, $storeId) {
                    return $q->where('uniacid', $uniacid)->where('storeId', $storeId);
                },
                'area' => function ($q) use ($uniacid, $storeId) {
                    return $q->where('uniacid', $uniacid)->where('storeId', $storeId);
                }
            ])
                ->where("uniacid", $this->uniacid())
                ->when($request->name, function ($q) use ($request) {
                    return $q->where("name", "like", "%{$request->name}%");
                })->when($request->areaId, function ($q) use ($request) {
                    return $q->where("areaId", $request->areaId);
                })
                ->when($request->typeId, function ($q) use ($request) {
                    return $q->where("typeId", $request->typeId);
                })
                ->when($request->state, function ($q) use ($request) {
                    if ($request->state == 'free') {
                        return $q->where('state', 0);
                    }
                    if ($request->state == 'order') {
                        return $q->where('state', 1);
                    }
                    if ($request->state == 'settle') {
                        return $q->where('state', 2);
                    }
                    if ($request->state == 'machine') {
                        return $q->where('state', 3);
                    }
                    if ($request->state == 'prepare') {
                        return $q->where('state', 4);
                    }
                })
                ->where('storeId', $storeId)
                ->orderByWith('area', 'sort', 'asc')
                ->orderByWith('type', 'sort', 'asc')
                ->paginate(Request()->pageSize ?? 20, '*', 'pageNo');
            return $this->success(new TablesListResources($model));
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }
    public function count(Request $request)
    {
        $model = Table::count()->where("storeId", $this->storeId())
            ->where("uniacid", $this->uniacid())
            ->when($request->areaId, function ($q) use ($request) {
                return $q->where("areaId", $request->areaId);
            })
            ->when($request->typeId, function ($q) use ($request) {
                return $q->where("areaId", $request->typeId);
            })
            ->first();
        return $this->success($model);
    }

    public function update(Request $request, $id)
    {
        $uniacid = $this->uniacid();
        $model = Table::with([
            'store' => function ($q) use ($uniacid) {
                return $q->select(["id", 'name', 'lat', 'lng']);
            },
            'type' => function ($q) use ($uniacid) {
                return $q->where('uniacid', $uniacid);
            },
            'area' => function ($q) use ($uniacid) {
                return $q->where('uniacid', $uniacid);
            }
        ])
            ->where("uniacid", $this->uniacid())
            ->find($id);
        if (empty($model)) {
            return $this->failed('数据不存在');
        }
        $model->state = 1;
        $model->people = $request->people ?? 0;
        $model->scan = 0;
        $model->openTime = date("Y-m-d H:i:s", time());
        // if ($this->diningType() == 4) {
        //     $model->expiredTime = date("Y-m-d H:i:s", intval($model->store->inStoreSetting['order']['cleanTime'] * 60) + time());
        // }
        $model->save();
        return $this->success();
    }

    public function show(Request $request, $id)
    {
        try {
            $uniacid = $this->uniacid();
            $model = Table::with([
                'store' => function ($q) use ($uniacid) {
                    return $q->select(["id", 'name', 'lat', 'lng']);
                },
                'type' => function ($q) use ($uniacid) {
                    return $q->where('uniacid', $uniacid);
                },
                'area' => function ($q) use ($uniacid) {
                    return $q->where('uniacid', $uniacid);
                }
            ])->where("uniacid", $this->uniacid())->where('storeId', $this->storeId())->find($id);
            if (empty($model)) {
                return $this->failed('数据不存在');
            }
            $model->setAppends(['diningType']);
            if (
                empty($model->store->inStoreSetting) ||
                $model->store->inStoreSetting['pickupSwitch'] == 0
                || empty($model->store->inStoreSetting['orderMode'])
            ) {
                return $this->failed('堂食业务已关闭');
            }
            $model = collect($model)->toArray();
            $model['diningType'] = 4;
            return $this->success($model);
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }

    public function callWaiter(Request $request, $id)
    {
        $model = Table::find($id);
        if (empty($model)) {
            return $this->failed('数据不存在');
        }
        Event(new StoreMessageEvent($model, 'waiter'));
        return $this->success('呼叫成功');
    }

    public function changePeople(Request $request, $id)
    {
        $model = Table::find($id);
        if (empty($model)) {
            return $this->failed('数据不存在');
        }
        $model->people = $request->people;
        $model->save();
        if ($model->orderSn) {
            Order::where('orderSn', $model->orderSn)->update(['people' => $model->people, 'tableMoney' => 0]);
            Order::where('orderSn', $model->orderSn)->update(TableService::TableMoney($id, $model->people));
            Order::where('prentOrderSn', $model->orderSn)->update(['people' => $model->people, 'tableMoney' => 0]);
            Order::where('prentOrderSn', $model->orderSn)->where('addNum', 1)->limit(1)->update(TableService::TableMoney($id, $model->people));
            $order = Order::where('orderSn', $model->orderSn)->first();
            $order->changeData(true);
        }
        return $this->success();
    }

    public function  backTable(Request $request, $id)
    {
        $model = Table::when($request->orderSn, function ($q) use ($request) {
            return $this->where('orderSn', $request->orderSn);
        })->find($id);
        if (empty($model)) {
            return $this->failed('数据不存在');
        }
        if ($model->orderSn) {
            if ($model->order->payType == 2) {
                InStoreOrderService::backTable($model->orderSn, $request->notes);
            } else {
                InStoreOrderService::refund($model->orderSn, $request->notes);
            }
        }
        Table::where('id', $model->id)->update([
            'state' => 0,
            'people' => 0,
            'orderSn' => null,
            'expiredTime' => null,
            'scan' => 0,
            "openTime" => null
        ]);
        ChannelCart::where('tableId', $model->id)->where('diningType', 4)->delete();
        return $this->success(null, '操作成功');
    }

    /**
     * 换台
     */
    public function  changeTable(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $model = Table::where('uniacid', $this->uniacid())->find($id);
            if (empty($model)) {
                return $this->failed('数据不存在');
            }
            $toModel  = Table::where('uniacid', $this->uniacid())->find($request->toTable);
            $toModel->state = $model->state;
            $toModel->people = $model->people;
            $toModel->orderSn = $model->orderSn;
            $toModel->orderSn = $model->orderSn;
            $toModel->expiredTime = $model->expiredTime;
            $toModel->openTime = $model->openTime;
            $toModel->scan = $model->scan;
            $toModel->save();
            Table::where('id', $model->id)->update([
                'state' => 0,
                'people' => 0,
                'orderSn' => null,
                'expiredTime' => null,
                'scan' => 0,
                "openTime" => null
            ]);
            if ($model->orderSn) {
                $order = Order::where('uniacid', $this->uniacid())
                    ->where('storeId', $model->storeId)
                    ->where(function ($q) use ($model) {
                        return $q->where('prentOrderSn', $model->orderSn)->orWhere('orderSn', $model->orderSn);
                    })->first();
                $orderId=$order->id;
                 Order::where('uniacid', $this->uniacid())
                    ->where('storeId', $model->storeId)
                    ->where(function ($q) use ($model) {
                        return $q->where('prentOrderSn', $model->orderSn)->orWhere('orderSn', $model->orderSn);
                    })->update(['tableId' => $request->toTable]);
            }
            ChannelCart::where('tableId', $model->id)->where('diningType', 4)->update([
                'tableId' => $toModel->id
            ]);
            InStoreOrderService::print($orderId, 10, [], $id, $request->toTable);
            DB::commit();
            return $this->success(null, '转台成功');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failed($e->getMessage());
        }
    }

    /**
     * 并台
     */
    public function  combine(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $model = Table::where('uniacid', $this->uniacid())->find($id);
            if (empty($model)) {
                return $this->failed('数据不存在');
            }
            $toModel  = Table::where('uniacid', $this->uniacid())->find($request->toTable);
            if (empty($toModel)) {
                return $this->failed('数据不存在');
            }
            $toModel->state = $model->state;
            $toModel->people = $model->people + $toModel->people;
            $toModel->save();
            Order::where('uniacid', $this->uniacid())
                ->where('storeId', $model->storeId)
                ->where(function ($q) use ($model) {
                    return $q->where('prentOrderSn', $model->orderSn);
                })->update([
                    'prentOrderSn' => $toModel->orderSn,
                ]);
            OrderGoods::where('uniacid', $this->uniacid())
                ->where('storeId', $model->storeId)
                ->where(function ($q) use ($model) {
                    return $q->where('prentOrderSn', $model->orderSn);
                })->update([
                    'prentOrderSn' => $toModel->orderSn,
                ]);
            User::where('orderSn', $model->orderSn)->update([
                'orderSn' => $toModel->orderSn,
            ]);
            Order::where('uniacid', $this->uniacid())
                ->where('storeId', $model->storeId)
                ->where(function ($q) use ($model) {
                    return $q->where('orderSn', $model->orderSn);
                })->update([
                    'state' => 0,
                ]);
            OrderIndex::where('uniacid', $this->uniacid())
                ->where('storeId', $model->storeId)
                ->where(function ($q) use ($model) {
                    return $q->where('orderSn', $model->orderSn);
                })->update([
                    'state' => 0,
                ]);

            Order::where('orderSn', $toModel->orderSn)->update(['people' => $toModel->people, 'tableMoney' => 0]);
            Order::where('orderSn', $toModel->orderSn)->update(TableService::TableMoney($toModel->id, $toModel->people));
            Order::where('prentOrderSn', $toModel->orderSn)->update(['people' => $toModel->people, 'tableMoney' => 0]);
            Order::where('prentOrderSn', $toModel->orderSn)->where('addNum', 1)->limit(1)->update(TableService::TableMoney($toModel->id, $toModel->people));
            $order = Order::where('orderSn', $toModel->orderSn)->first();
            $order->changeData();
            Table::where('id', $model->id)->update([
                'state' => 0,
                'people' => 0,
                'orderSn' => null,
                'expiredTime' => null,
                'scan' => 0,
                "openTime" => null
            ]);
            ChannelCart::where('tableId', $model->id)->where('diningType', 4)->update([
                'tableId' => $toModel->id
            ]);
            DB::commit();
            return $this->success(null, '并台成功');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failed($e->getMessage());
        }
    }
}
