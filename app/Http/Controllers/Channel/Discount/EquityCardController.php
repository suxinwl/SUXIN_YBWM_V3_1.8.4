<?php

namespace App\Http\Controllers\Channel\Discount;

use App\Http\Controllers\Channel\ApiController;
use App\Models\EquityCard\Card;
use App\Models\EquityCard\Goods;
use App\Models\EquityCard\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class EquityCardController extends ApiController
{

    public function index(Request $request)
    {
        $list = Card::where('uniacid', $this->uniacid())
            ->where('storeId', $this->storeId())
            ->when($request->type, function ($q) use ($request) {
                return $q->where('type', $request->type);
            })->when($request->subState, function ($q) use ($request) {
                if ($request->subState == "begin") {
                    return $q->where("startTime", ">", date("Y-m-d H:i:s", time()));
                }
                if ($request->subState == "start") {
                    return $q->where(function ($q) {
                        return $q->whereNull('startTime')->orWhere(function ($q) {
                            return $q->where("startTime", "<", Carbon::now()->toDateTimeString())
                                ->where("endTime", ">=",  Carbon::now()->toDateTimeString());
                        });
                    });
                }
                if ($request->subState == "end") {
                    return $q->where("endTime", "<", date("Y-m-d H:i:s", time()));
                }
            })
            ->when($request->name, function ($q) use ($request) {
                return $q->where("name", "like", "%{$request->name}%");
            })->when($request->startTime, function ($q) use ($request) {
                return $q->where("created_at", "<=", $request->startTime);
            })
            ->when($request->endTime, function ($q) use ($request) {
                return $q->where("created_at", ">=", $request->endTime);
            })
            ->orderBy('sort', 'asc')
            ->orderBy('id', 'desc')
            ->paginate(Request()->pageSize ?? 20, '*', 'pageNo');
        return $this->success($list);
    }
    public function show(Request $request, $id)
    {
        $model = Card::where('uniacid', $this->uniacid())
            ->find($id);
        if (empty($model)) {
            return $this->failed($model);
        }
        return $this->success($model);
    }

    public function store(Request $request)
    {
        $model = new Card();
        $model->fill($request->all());
        $model->uniacid = $this->uniacid();
        $model->storeId = $this->storeId();
        $model->save();
        Cache::set('equityCard:' . $model->id, $model);
        return $this->success();
    }

    public function update(Request $request, $id)
    {
        $model = Card::where('uniacid', $this->uniacid())->find($id);
        if (!$model) {
            return $this->failed('数据不存在');
        }
        $model->fill($request->all());
        $model->uniacid = $this->uniacid();
        $model->storeId = $this->storeId();
        $model->save();
        Cache::set('equityCard:' . $model->id, $model);
        return $this->success();
    }

    public function destroy(Request $request, $id)
    {
        try {
            $idArray = array_filter(explode(',', $id), function ($item) {
                return is_numeric($item);
            });
            $models = Card::where('uniacid', $this->uniacid())->whereIn('id', $idArray)->get();
            foreach ($models as $key => $model) {
                $model->delete();
            }
            return $this->success([], '删除成功');
        } catch (\Exception $e) {
            return $this->failed('删除失败');
        }
    }
    public function state(Request $request, $id)
    {
        $model = Card::where('uniacid', $this->uniacid())->find($id);
        if (!$model) {
            return $this->failed('数据不存在');
        }
        $model->state = $model->state == 1 ? 0 : 1;
        $model->save();
    }

    public function order(Request $request)
    {
        $timeArr = $this->timeArr(true);
        $list = Order::where("uniacid", $this->uniacid())
            ->where('storeId', $this->storeId())
            ->with(['equityCard' => function ($q) {
                return $q->select([
                    'id', 'name', 'desc', 'imageType', 'image', 'textColor', 'themeColor', 'day'
                ]);
            }, 'user' => function ($q) {
                return $q->select([
                    'id', 'nickname', 'mobile', 'realname'
                ]);
            }])
            ->when($request->timeType, function ($q) use ($request, $timeArr) {
                return $q->where('created_at', '>=', $timeArr['startTime'])
                    ->where('created_at', '<=', $timeArr['endTime']);
            })
            ->when($request->orderSn, function ($q) use ($request) {
                return $q->where('orderSn', 'like', "%$request->orderSn%");
            })
            ->when($request->userKeyword, function ($q) use ($request) {
                return $q->whereHas('user', function ($q) use ($request) {
                    return $q->where(function ($q) use ($request) {
                        return $q->where('mobile', 'like', "%$request->userKeyword%")
                            ->orWhere('nickname', 'like', "%$request->userKeyword%")
                            ->orWhere('realname', 'like', "%$request->userKeyword%");
                    });
                });
            })
            ->when($request->equityCardKeyword, function ($q) use ($request) {
                return $q->whereHas('equityCard', function ($q) use ($request) {
                    return $q->where('name', 'like', "%$request->equityCardKeyword%");
                });
            })
            ->where('state', '>', 1)
            ->paginate(Request()->pageSize ?? 20, '*', 'pageNo');
        return $this->success($list);
    }
}
