<?php

namespace App\Http\Controllers\Channel\Finance;

use App\Http\Controllers\Channel\ApiController;
use App\Http\Controllers\Controller as BaseController;
use App\Http\Helpers\ApiResponse;
use App\Http\Resources\Channel\Balance\BalanceResources;
use App\Models\Order\OrderIndex;
use App\Models\Statistics\Balance;
use App\Models\StatisticsDay;
use App\Traits\ChannelInitTrait;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Tymon\JWTAuth\Facades\JWTAuth;

class BalanceController extends ApiController
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
    public function index()
    {
        $model = new Balance(['uniacid' => $this->uniacid(), 'storeId' => $this->storeId]);
        return $this->success($model);
    }



    public function show(Request $request, $id)
    {
        $storeId = $this->storeId;
        $model = StatisticsDay::where('uniacid', $this->uniacid())
            ->when($storeId, function ($q) use ($storeId) {
                if (is_array($storeId)) {
                    $q->whereIn('storeId', $this->storeId);
                } else {
                    $q->where('storeId', $this->storeId);
                }
                return $q;
            })->where('id', $id)
            ->first();
        $list = OrderIndex::with(['store' => function ($q) {
            return $q->select(['id', 'name']);
        }])->when($request->type, function ($q) use ($request) {
            return $q->where('type', $request->type);
        })
            ->where('uniacid', $this->uniacid())
            ->where('storeId', $model->storeId)
            ->where('payType', 0)
            ->whereIn('state', [8, 6])
            ->where('created_at', '>=', date("Y-m-d 00:00:00", strtotime($model->day)))
            ->where('created_at', '<=', date("Y-m-d 23:59:59", strtotime($model->day)))
            ->orderBy('id', 'desc')
            ->paginate($request->pageSize ?? 20, '*', 'pageNo');
        return $this->success(new BalanceResources($list));
    }
}
