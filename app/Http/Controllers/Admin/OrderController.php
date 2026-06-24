<?php

namespace App\Http\Controllers\Admin;

use App\Http\Resources\Admin\Sms\SmsPayCollection;
use App\Models\Admin\Order;
use App\Models\SmsOrder;
use Illuminate\Http\Request;


class OrderController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $list = Order::with(['apply' => function ($q) use ($request) {
            return $q->withTrashed();
        }])->where(function ($q) use ($request) {
            if ($request->keyword) {
                $q->whereHas('apply', function ($q) use ($request) {
                    if ($request->keyword) {
                        return $q->where('applyName', 'like', "%$request->keyword%");
                    }
                })->orWhere('outTradeNo', 'like', "%$request->keyword%");
            }
            if ($request->type) {
                $q->where('type', $request->type);
            }
            if ($request->state == 'pay') {
                $q->where('state', 1);
            } elseif ($request->state == 'unpay') {
                $q->where('state', 0);
            }
            if (!empty($request->startTime) && !empty($request->endTime)) {
                $q->where(function ($q) use ($request) {
                    return $q->where('created_at', '>=', $request->startTime)
                        ->where('created_at', '<=', $request->endTime);
                });
            }
            return $q;
        })->orderBy('id', 'desc')->paginate($request->pageSize ?? 20, '*', 'pageNo');
        return $this->success($list);
    }
}
