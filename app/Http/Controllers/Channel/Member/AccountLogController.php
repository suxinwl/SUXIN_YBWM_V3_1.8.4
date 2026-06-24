<?php

namespace App\Http\Controllers\Channel\Member;

use App\Http\Controllers\Channel\ApiController;
use App\Models\Member\Group;
use App\Models\Member\Vip;
use App\Models\Member\VipPower;
use App\Models\MemberAccountLog;
use Illuminate\Http\Request;


class AccountLogController extends ApiController
{
    public function show(Request $request, $type)
    {
        $list = MemberAccountLog::where('uniacid', $this->uniacid())
            ->where("userId", $request->userId)
            ->where('cat', $type)->orderBy('id', 'desc')
            ->paginate($request->pageSize ?? 30, '*', 'pageNo');
        return $this->success($list);
    }

    public function all(Request $request, $type)
    {
        $list = MemberAccountLog::with(['member'])
            ->where('uniacid', $this->uniacid())
            ->where('storeId', $this->storeId())
            ->where('cat', $type)->orderBy('id', 'desc')
            ->when($request->keyword, function ($q) use ($request) {
                return $q->whereHas('member', function ($q) use ($request) {
                    return $q->where('mobile', 'like', "%{$request->keyword}%");
                });
            })
            ->when($request->type || $request->type != null, function ($q) use ($request) {
                return $q->where('type', $request->type);
            })
            ->when($request->startTime && $request->endTime, function ($q) use ($request) {
                return $q->where('created_at', '>=', $request->startTime)->where('created_at', '<=', $request->endTime);
            })
            ->paginate($request->pageSize ?? 30, '*', 'pageNo');
        return $this->success($list);
    }
}
