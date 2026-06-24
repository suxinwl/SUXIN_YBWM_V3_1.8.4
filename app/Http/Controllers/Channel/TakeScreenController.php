<?php

namespace App\Http\Controllers\Channel;

use App\Events\StoreMessageEvent;
use App\Http\Controllers\Controller;
use App\Http\Resources\Channel\TakeScreen\TakeScreenListResources;
use Illuminate\Http\Request;
use App\Models\HandleLog;
use App\Models\Order\OrderIndex;
use App\Models\Order\TakeScreen;
use App\Models\ShortLink;
use App\Models\Store;
use App\Services\InStoreOrderService;
use App\Services\OrderService;
use App\Services\ShortLinkService;
use App\Traits\StatisticsTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class TakeScreenController extends ApiController
{
    use StatisticsTrait;
    // GET 索引/列表
    public function index(Request $request)
    {
        $timeArr = $this->timeArr(true);
        $storeId = $this->storeId();
        $isolate = $this->isolate();
        $list = TakeScreen::where('uniacid', $this->uniacid())
            ->when($request->state, function ($q) use ($request) {
                if ($request->state == "all") {
                    return $q->whereIn('state', [3, 4]);
                }
                if ($request->state == "making") {
                    return $q->where('state', 3);
                }
                if ($request->state == "maked") {
                    return $q->where('state', 4);
                }
                if ($request->state == "complete") {
                    return $q->where('state', 6);
                }
            })->when($request->source, function ($q) use ($request) {
                return $q->where('source', $request->source);
            })->when($request->diningType, function ($q) use ($request) {
                if ($request->diningType == "ziti") {
                    return $q->whereIn('diningType', [1, 2]);
                }
                if ($request->diningType == "kuaican") {
                    return $q->whereIn('diningType', [6]);
                }
                return $q->where('diningType', $request->diningType);
            })
//            ->when($isolate, function ($q) use ($storeId, $isolate) {
//                return $q->whereHas('store', function ($q) use ($storeId) {
//                    return $q->where('isolate', 1)->where('storeId', $storeId);
//                });
//            })
//            ->when(!$isolate, function ($q) use ($storeId, $isolate) {
//                return $q->whereHas('store', function ($q) use ($storeId) {
//                    return $q->where('isolate', 0)->when($storeId, function ($q) use ($storeId) {
//                        return $q->where('storeId', $storeId);
//                    });
//                });
//            })
            ->where('storeId', $storeId)
            ->where('created_at', '>=', $timeArr['startTime'])
            ->where('created_at', '<=', $timeArr['endTime'])
            ->orderBy($request->state == "complete" ? "updated_at" : 'created_at', $request->state == "complete" ? "desc" : 'asc')
            ->paginate($request->pageSize ?? 20, '*', 'pageNo');
        return $this->success(new TakeScreenListResources($list));
    }

    // GET /create 创建页展示
    public function update(Request $request, $id)
    {
        $store = Store::where('uniacid', $this->uniacid())->find($id);
        if (empty($store)) {
            throw  new BadRequestException('数据不存在');
        }
        $model = ShortLink::where('uniacid', $this->uniacid())
            ->where('storeId', $this->storeId())
            ->where('type', 'takeScreen')
            ->first();
        if (empty($model)) {
            $model = ShortLinkService::takeScreen($store);
        }
        $url = Request()->getSchemeAndHttpHost() . "/admin/#/workbench/takeMeal?id=" . $model->shortLink;
        return $this->success($url);
    }
    // GET /create 创建页展示
    public function call(Request $request, $id)
    {
        $model = TakeScreen::where('uniacid', $this->uniacid())
            ->where('storeId', $this->storeId())
            ->find($id);
        if (empty($model)) {
            return $this->failed('数据不存在');
        }
        $orderIndex = OrderIndex::where('orderSn', $model->orderSn)->first();
        if (empty($orderIndex)) {
            return $this->failed('订单不存在');
        }
        Event(new StoreMessageEvent($orderIndex, 'call'));
        return $this->success([], '叫号成功');
    }

    public function complete(Request $request, $id)
    {
        $model = TakeScreen::where('uniacid', $this->uniacid())
            ->where('storeId', $this->storeId())
            ->find($id);
        if (empty($model)) {
            return $this->failed('数据不存在');
        }
        $orderIndex = OrderIndex::where('orderSn', $model->orderSn)->first();
        if (empty($orderIndex)) {
            return $this->failed('订单不存在');
        }
        if ($orderIndex->type == 4) {
            if ($model->state == 3) {
                InStoreOrderService::maked($orderIndex->subOrder->id);
            }
            InStoreOrderService::complete($orderIndex->subOrder->id);
        }
        if ($orderIndex->type == 1) {
            if ($model->state == 3) {
                OrderService::maked($orderIndex->subOrder->id);
            }
            OrderService::complete($orderIndex->subOrder->id);
        }
        return $this->success([], '取餐成功');
    }

    public function maked(Request $request, $id)
    {
        $model = TakeScreen::where('uniacid', $this->uniacid())
            ->where('storeId', $this->storeId())
            ->find($id);
        if (empty($model)) {
            return $this->failed('数据不存在');
        }
        $orderIndex = OrderIndex::where('orderSn', $model->orderSn)->first();
        if (empty($orderIndex)) {
            return $this->failed('订单不存在');
        }
        if ($orderIndex->type == 4) {
            InStoreOrderService::maked($orderIndex->subOrder->id);
        }
        if ($orderIndex->type == 1) {
            OrderService::maked($orderIndex->subOrder->id);
        }
        return $this->success();
    }

    public function count(Request $request)
    {
        $model = TakeScreen::with([])
            ->where('storeId', $this->storeId())
            ->where("uniacid", $this->uniacid())
            ->count()->first();
        $model->makeHidden(['packagingFormat', 'orderTime', 'minutes'])
            ->setAppends([]);
        return $this->success($model);
    }
}
