<?php

namespace App\Http\Controllers\Channel\Delivery;

use App\Http\Controllers\Channel\ApiController;
use App\Models\Delivery\Rule;
use App\Models\Delivery\Channel;
use App\Models\Delivery\Order;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class OrderController extends ApiController
{

    public function index(Request $request)
    {
        $storeId = $this->storeId();
        $list = Order::with(['store', 'order'])
            ->where("uniacid", $this->uniacid())
            ->when($request->keyword, function ($q) use ($request) {
                return $q->where(
                    function ($q) use ($request) {
                        return $q->where('orderSn', "like", "%$request->keyword%")
                        ->orWhere('thirdNo', "like", "%$request->keyword%");
                    }
                );
            })
            ->when($storeId, function ($q) use ($storeId) {
                return $q->where('storeId', $storeId);
            })
            ->when($request->state, function ($q) use ($request) {
                switch ($request->state) {
                    default:
                        return $q;
                }
            })
            ->orderBy('id', 'desc')
            ->paginate($request->pageSize ?? 10, '*', 'pageNo');
        return $this->success($list);
    }

    public function show(Request $request, $id)
    {
        $storeId = $this->storeId();
        $order = Order::where("stoerId", $this->storeId())->where('uniacid', $this->uniacid())->find($id);
        if (empty($order)) {
            throw new BadRequestException('数据不存在');
        }
        return $this->success($order);
    }



    public function call(Request $request, $id)
    {
        $storeId = $this->storeId();
        $order = Order::where("stoerId", $this->storeId())->where('uniacid', $this->uniacid())->find($id);
        if (empty($order)) {
            throw new BadRequestException('数据不存在');
        }
        $order->call();
        $order->save();
        return $this->success();
    }


    public function clear(Request $request, $id)
    {
        $storeId = $this->storeId();
        $order = Order::where("stoerId", $this->storeId())->where('uniacid', $this->uniacid())->find($id);
        if (empty($order)) {
            throw new BadRequestException('数据不存在');
        }
        $order->clear();
        $order->save();
        return $this->success();
    }
}
