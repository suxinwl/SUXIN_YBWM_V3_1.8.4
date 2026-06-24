<?php

namespace App\Http\Controllers\Channel;

use App\Models\PrinterLog;
use Illuminate\Http\Request;

class PrinterLogController extends ApiController
{
    public function index(Request $request)
    {
        $storeId = $this->storeId();
        $user = $this->user();
        $list = PrinterLog::with(['store'])
            ->where('uniacid', $this->uniacid())
            ->orderBy('id', 'desc')
            ->when($request->keyword, function ($q) use ($request) {
                return $q->where('order_sn', 'like', "%$request->keyword%")
                    ->orWhere('sn', 'like', "%$request->keyword%");
            })->when($user, function ($q) use ($user) {
                if ($user->isAdmin == 0) {
                    if (!empty($user->storeId)) {
                        $q->whereIn('storeId', $user->storeId);
                    }
                }
                return $q;
            })->when($this->storeId(), function ($q) use ($storeId) {
                return $q->where('storeId', $storeId);
            })->when($request->startTime && $request->endTime, function ($q) use ($request) {
                $q->where('created_at', '>=', $request->startTime);
                $q->where('created_at', '<=', $request->endTime);
                return $q;
            })
            ->paginate($request->pageSize ?? 20, '*', 'pageNo');
        return $this->success($list);
    }
}
