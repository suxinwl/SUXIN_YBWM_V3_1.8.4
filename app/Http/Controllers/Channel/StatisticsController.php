<?php

namespace App\Http\Controllers\Channel;

use App\Exports\StatisticsOrderDataExport;
use App\Exports\StoreValuestatisticsExport;
use App\Http\Resources\Channel\Menus\Menus;
use App\Models\Coupon\MemberCoupon;
use App\Models\MemberAccount;
use App\Models\Spec;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use App\Imports\SpecsImport;
use App\Models\GoodsCat;
use App\Models\Order\OrderGoods;
use App\Models\Statistics\IndexData;
use App\Models\Statistics\MemberData;
use App\Models\Statistics\MemberStatisticsData;
use App\Models\Statistics\NewOrderData;
use App\Models\Statistics\OrderData;
use App\Models\Statistics\StoredValueData;
use App\Models\StatisticsDay;
use App\Services\ExcelService;
use App\Traits\StatisticsTrait;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class StatisticsController extends ApiController
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

    public function index()
    {
        $model = new IndexData(['uniacid' => $this->uniacid(), 'storeId' => $this->storeId]);
        return $this->success($model);
    }

    public function member()
    {
        $model = new MemberData(['uniacid' => $this->uniacid(), 'storeId' => $this->storeId]);
        return $this->success($model);
    }

    public function order()
    {
        $model = new OrderData(['uniacid' => $this->uniacid(), 'storeId' => $this->storeId ]);
        return $this->success($model);
    }

    public function newOrder()
    {
        $model = new NewOrderData(['uniacid' => $this->uniacid(), 'storeId' => $this->storeId]);
        return $this->success($model);
    }

    public function memberStatistics()
    {
        $model = new MemberStatisticsData(['uniacid' => $this->uniacid(), 'storeId' => $this->storeId,'isolate'=>$this->isolate()]);
        return $this->success($model);
    }

    public function storedValue()
    {
        $model = new StoredValueData(['uniacid' => $this->uniacid()]);
        return $this->success($model);
    }


    public function storedValueView(Request $request, $id)
    {
        $model = StatisticsDay::select([
            'id',
            'uniacid',
            'startBalance',
            'storedValueCapital',
            'storedValueGive',
            'sysSubStoredValue',
            'storedValue',
            'day',
            DB::raw("IFNULL(sysSubStoredValue + balanceMoney,0) as balanceMoney"),
            DB::raw("IFNULL(storedValue + startBalance - sysSubStoredValue - balanceMoney,0) as balance"),
        ])->where('uniacid', $this->uniacid())->find($id);
        if (empty($model)) {
            return $this->failed('数据不存在');
        }
        $model->setAppends(['income', 'spending']);
        return $this->success($model);
    }

    public function goods(Request $request)
    {
        $uniacid = $this->uniacid();
        $storeId = $this->storeId;
        $user = $this->user();
        $timeArr  = $this->timeArr(true);
        $models = OrderGoods::withTrashed()->select(['id', 'spuId', 'uniacid', 'storeId', 'name', 'logo', 'attrData'])->addSelect([
            DB::raw("IFNULL(sum(sellMoney),0) as sellMoney"),
            DB::raw("IFNULL(sum(money),0) as money"),
            DB::raw("IFNULL(sum(discountMoney),0) as discountMoney"),
            DB::raw("IFNULL(sum(num),0) as num"),
        ])->with(['goodsCat'])
            ->where('uniacid', $uniacid)
            ->whereIn('state', [6, 10])
            ->when($request->scene, function ($q) use ($request) {
                return $q->where("scene", $request->scene);
            })->when($timeArr, function ($q) use ($request, $timeArr) {
                return $q->where("completionTime", '>=', $timeArr['startTime'])
                    ->where("completionTime", '<=', $timeArr['endTime']);
            })
            ->when($storeId, function ($q) use ($storeId) {
                return $q->where("storeId", $storeId);
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
            })
            ->groupBy($request->groupBySpec ? 'specMd5' : 'spuId')->orderBy('sellMoney', "desc");
        if ($request->export) {
            $list = $models->get();
            $header = [
                ['商品名称', 'name', 'text'], // 规则不填默认text
                ['商品分类', 'attrData', 'function', function ($model) use ($request) {
                    return collect($model['goodsCat'])->pluck('name')->implode(',');
                }],
                ['商品规格', 'attrData', 'function', function ($model) use ($request) {
                    if ($request->groupBySpec == 'specMd5') {
                        return $model['attrData']['spec'] ?? '-';
                    } else {
                        return "-";
                    }
                }],
                ['商品销量', 'num', 'text'],
                ['商品销售额', 'sellMoney', 'text'],
                ['商品实收', 'money', 'text'],
                ['优惠金额', 'discountMoney', 'text'],
            ];
            return ExcelService::export($list, $header, '商品概况.xls');
        }
        $models = $models->paginate($request->pageSize ?? 20, '*', 'pageNo');
        return $this->success($models);
    }

    public function goodsCat(Request $request)
    {
        $uniacid = $this->uniacid();
        $storeId = $this->storeId;
        $user = $this->user();
        $timeArr  = $this->timeArr(true);
        $models = GoodsCat::select(['id', 'name'])->where("uniacid", $this->uniacid())
            ->withSum(['orderGoods as sales' => function ($q) use ($storeId, $uniacid, $request, $user, $timeArr) {
                return
                    $q->when($request->scene, function ($q) use ($request) {
                        return $q->where("scene", $request->scene);
                    })->when($timeArr, function ($q) use ($request, $timeArr) {
                        return $q->where(OrderGoods::make()->getTable() . ".completionTime", '>=', $timeArr['startTime'])
                            ->where(OrderGoods::make()->getTable() . ".completionTime", '<=', $timeArr['endTime']);
                    })
                    ->withTrashed()
                    ->whereIn('state', [6, 10])
                    ->when($storeId, function ($q) use ($storeId) {
                        return $q->where("storeId", $storeId);
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
                    });
            }], 'num')
            ->withSum(['orderGoods as sellMoney' => function ($q) use ($storeId, $uniacid, $request, $user, $timeArr) {
                return
                    $q->when($request->scene, function ($q) use ($request) {
                        return $q->where("scene", $request->scene);
                    })->when($timeArr, function ($q) use ($request, $timeArr) {
                        return $q->where(OrderGoods::make()->getTable() . ".completionTime", '>=', $timeArr['startTime'])
                            ->where(OrderGoods::make()->getTable() . ".completionTime", '<=', $timeArr['endTime']);
                    })
                    ->withTrashed()
                    ->whereIn('state', [6, 10])
                    ->when($storeId, function ($q) use ($storeId) {
                        return $q->where("storeId", $storeId);
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
                    });
            }], 'sellMoney')
            ->withSum(['orderGoods as money' => function ($q) use ($storeId, $uniacid, $request, $user, $timeArr) {
                return
                    $q->when($request->scene, function ($q) use ($request) {
                        return $q->where("scene", $request->scene);
                    })->when($timeArr, function ($q) use ($request, $timeArr) {
                        return $q->where(OrderGoods::make()->getTable() . ".completionTime", '>=', $timeArr['startTime'])
                            ->where(OrderGoods::make()->getTable() . ".completionTime", '<=', $timeArr['endTime']);
                    })
                    ->when($storeId, function ($q) use ($storeId) {
                        return $q->where("storeId", $storeId);
                    })
                    ->withTrashed()
                    ->whereIn('state', [6, 10])
                    ->when($user, function ($q) use ($user) {
                        if ($user->isAdmin == 0) {
                            if (empty($user->storeId)) {
                                $q->where('storeId', 0);
                            } else {
                                $q->whereIn('storeId', $user->storeId);
                            }
                        }
                        return $q;
                    });
            }], 'money')
            ->withSum(['orderGoods as discountMoney' => function ($q) use ($storeId, $uniacid, $request, $user, $timeArr) {
                return
                    $q->when($request->scene, function ($q) use ($request) {
                        return $q->where("scene", $request->scene);
                    })->when($timeArr, function ($q) use ($request, $timeArr) {
                        return $q->where(OrderGoods::make()->getTable() . ".completionTime", '>=', $timeArr['startTime'])
                            ->where(OrderGoods::make()->getTable() . ".completionTime", '<=', $timeArr['endTime']);
                    })
                    ->when($storeId, function ($q) use ($storeId) {
                        return $q->where("storeId", $storeId);
                    })
                    ->withTrashed()
                    ->whereIn('state', [6, 10])
                    ->when($user, function ($q) use ($user) {
                        if ($user->isAdmin == 0) {
                            if (empty($user->storeId)) {
                                $q->where('storeId', 0);
                            } else {
                                $q->whereIn('storeId', $user->storeId);
                            }
                        }
                        return $q;
                    });
            }], 'discountMoney')
            ->having("sales", ">", 0)->orderBy("sales", "desc");
        if ($request->export) {
            $list = $models->get();
            $header = [
                ['商品分类名称', 'name', 'text'], // 规则不填默认text
                ['商品销量', 'sales', 'text'],
                ['商品销售额', 'sellMoney', 'text'],
                ['商品实收', 'money', 'text'],
            ];
            return ExcelService::export($list, $header, '商品概况.xls');
        }
        $models = $models->paginate($request->pageSize ?? 30, '*', 'pageNo');
        return $this->success($models);
    }


    public function storeValuestatisticsExport(Request $request)
    {
        $params = $request->all();
        $uniacid = $this->uniacid();
        $user = $this->user();
        $params['user'] = $user;
        $params['uniacid'] = $uniacid;
        $params['timeType'] = $params['timeType'] ?: 7;
        $params['timeChannel'] = 'created_at';
        $timeArr = $this->timeArr(true, $params['timeType']);
        $params['timeArr'] = $timeArr;
        return Excel::download(new StoreValuestatisticsExport($params), 'StoreValuestatistics.xlsx');
    }

    public function orderExport(Request $request)
    {
        $params = $request->all();
        $uniacid = $this->uniacid();
        $user = $this->user();
        $params['user'] = $user;
        $params['uniacid'] = $uniacid;
        $params['timeType'] = $params['timeType'] ?: -1;
        $params['timeChannel'] = 'created_at';
        $timeArr = $this->timeArr(true, $params['timeType']);
        $params['timeArr'] = $timeArr;
        return Excel::download(new StatisticsOrderDataExport($params), 'orderData.xlsx');
    }


    public function getToker(Request $request)
    {
        switch ($request->byType){
            case '1';
            $order='balance';
            break;
            case '2';
                $order='integral';
                break;
            case '3';
                $order='exp';
                break;
            default:
                    $order='balance';
                break;
        }
        //DB::connection()->enableQueryLog();#开启执行日志
        $uniacid = $this->uniacid();
        $thard=MemberAccount::with('member')->select(DB::raw("IFNULL(sum(if(id > 0 ,balance,0)),0) as balance,IFNULL(sum(if(id > 0,integral,0)),0) as integral,IFNULL(sum(if(id > 0 ,exp,0)),0) as exp"))->where('uniacid', $uniacid)
            ->first();
        $memberCoupon=MemberCoupon::with('member')->select(DB::raw("IFNULL(sum(if(state = 0 ,1,0)),0) as expire,IFNULL(sum(if(state = 1,1,0)),0) as notused,IFNULL(sum(if(state = 2 ,1,0)),0) as used"))
            ->where('uniacid', $uniacid)->first();
        $data['expire']=$memberCoupon['expire'];
        $data['notused']=$memberCoupon['notused'];
        $data['used']=$memberCoupon['used'];
        $data['balance']=$thard['balance'];
        $data['integral']=$thard['integral'];
        $data['exp']=$thard['exp'];
        $statisticsDay=MemberAccount::with(['member'=> function ($q) use ($uniacid) {
            return $q->where('uniacid', $uniacid);
        }])->withCount('coupon')->where('uniacid', $uniacid)
        ->whereHas('member', function ($q) use ($uniacid) {
            return $q->where('id','>',0)->where('uniacid', $uniacid);
        })
      ->orderBy($order,'desc')->paginate($request->pageSize ?? 20, '*', 'pageNo');
        $data=['thard'=>$data,'statisticsDay'=>$statisticsDay];
        return $this->success($data);
    }
}
