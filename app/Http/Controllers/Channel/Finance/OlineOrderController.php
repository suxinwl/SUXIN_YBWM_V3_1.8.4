<?php

namespace App\Http\Controllers\Channel\Finance;

use App\Http\Controllers\Channel\ApiController;
use App\Http\Controllers\Controller as BaseController;
use App\Http\Helpers\ApiResponse;
use App\Http\Resources\Channel\Balance\BalanceResources;
use App\Models\Order\OrderIndex;
use App\Models\Statistics\Balance;
use App\Models\StatisticsDay;
use App\Models\StatisticsOrder;
use App\Traits\ChannelInitTrait;
use App\Traits\StatisticsTrait;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Resources\ChannelApi\Order\OrderListResources;
use App\Services\ExcelService;

class OlineOrderController extends ApiController
{
    use StatisticsTrait;
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
        $storeId = $this->storeId;
        $lists =  OrderIndex::where('uniacid', $this->uniacid())
            ->when(($storeId), function ($q) use ($storeId) {
                if (is_array($storeId)) {
                    $q->whereIn('storeId', $this->storeId);
                } else {
                    $q->where('storeId', $this->storeId);
                }
            })
            ->where('payType', '>', 10)
            ->where('payType', '<', 30)
            ->when($request->startTime, function ($q) use ($request) {
                return $q->where('payTime', '>=', $request->startTime);
            })
            ->when($request->mahId, function ($q) use ($request) {
                return $q->where('mchId', 'like', "%$request->mchId%");
            })
            ->when($request->endTime, function ($q) use ($request) {
                return $q->where('payTime', '<=', $request->endTime);
            })
            ->when($request->payChannel, function ($q) use ($request) {
                return $q->where('payChannel', $request->payChannel);
            })
            ->orderBy('id', 'desc')
            ->groupBy('thirdNo');
        if ($request->export) {
            $list = $lists->get();
            $list = collect($list)->map(function ($item) {
                $item->setAppends(['subOrder', 'payTypeFormat', 'payChannelFormat', 'payStateFormat', 'mchId']);
                return $item;
            });
            $header = [
                ['支付时间', 'payTime', 'text'],
                ['收款方', 'payChannelFormat', 'text'], // 规则不填默认text
                ['所属门店', 'subOrder', 'function', function ($model) {
                    return $model['subOrder']['store']['name'];
                }],
                ['支付金额', 'subOrder', 'function', function ($model) {
                    return $model['subOrder']['money'];
                }],
                ['支付方式', 'payTypeFormat', 'text'],
                ['商户号', 'mchId', 'text'],
                ['第三方订单号', 'thirdNo', 'text'],
                ['支付状态', 'payStateFormat', 'text'],
                ['订单状态', 'subOrder', 'function', function ($model) {
                    return $model['subOrder']['stateFormat'];
                }],
            ];
            return ExcelService::export($list, $header, '财务对账.xls');
        }
        $lists = $lists->paginate($request->pageSize ?? 20, '*', 'pageNo');
        return $this->success(new OrderListResources($lists));
    }
}
