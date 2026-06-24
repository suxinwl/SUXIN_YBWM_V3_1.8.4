<?php

namespace App\Http\Controllers\Channel\Finance;

use App\Http\Controllers\Channel\ApiController;
use App\Http\Controllers\Controller as BaseController;
use App\Http\Helpers\ApiResponse;
use App\Http\Resources\Channel\Balance\BalanceResources;
use App\Jobs\ProfitSharingJob;
use App\Models\Order\Bill;
use App\Models\Order\OrderIndex;
use App\Models\Order\Profit;
use App\Models\Statistics\Balance;
use App\Models\StatisticsDay;
use App\Traits\ChannelInitTrait;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Tymon\JWTAuth\Facades\JWTAuth;

class ProfitController extends ApiController
{
    public $storeId;
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if ($this->user()->isAdmin == 0) {
                if ($this->storeId()) {
                    $this->storeId = [$this->storeId()];
                } else {
                    $this->storeId = $this->user()->storeId;
                }
            }
            $this->storeId = $this->storeId();
            return $next($request);
        });
    }
    public function index(Request $request)
    {
        $storeId = $this->storeId();
        $user = $this->user();
        $list = Profit::where("uniacid", $this->uniacid())
            ->with(['store'])
            ->where('sharingState', ">", 0)
            ->when($storeId, function ($q) use ($storeId) {
                return $q->where('storeId', $storeId);
            })
            ->when($request->orderSn, function ($q) use ($request) {
                return $q->where('orderSn', "like", "{$request->orderSn}%");
            })
            ->when($request->orderType, function ($q) use ($request) {
                return $q->where('orderType', $request->orderType);
            })
            ->when($request->sharingState, function ($q) use ($request) {
                if ($request->sharingState == "success") {
                    return $q->where('sharingState', 1);
                }
                if ($request->sharingState == "lose") {
                    return $q->where('sharingState', 2);
                }
                if ($request->sharingState == "wait") {
                    return $q->where('sharingState', 3);
                }
            })
            ->when($user, function ($q) use ($user) {
                if ($user->isAdmin == 0) {
                    if (empty($user->storeId)) {
                        $q->where('storeId', 0);
                    } else {
                        $q->whereIn('storeId', $user->storeId);
                    }
                }
                return $q;
            })->orderBy('id', 'desc')
            ->paginate($request->pageSize ?? 10, '*', 'pageNo');
        return $this->success($list);
    }

    public function unfreeze(Request $request, $id)
    {
        $bill = Bill::where("uniacid", $this->uniacid())->find($id);
        if (empty($bill)) {
            return $this->failed('数据不存在');
        }
        dispatch(new ProfitSharingJob($bill->id,'unfreeze'));
        return $this->success("成功");
    }

    public function profitsharing(Request $request, $id)
    {
        $bill = Bill::where("uniacid", $this->uniacid())->find($id);
        if (empty($bill)) {
            return $this->failed('数据不存在');
        }
        dispatch(new ProfitSharingJob($bill->id));
        return $this->success("成功");
    }
}
