<?php

namespace App\Http\Controllers\Channel\Discount;

use App\Exports\GiftBigReceiveExport;
use App\Http\Controllers\Channel\ApiController;
use App\Http\Requests\Admin\ChangePassword;
use App\Models\Ad;
use App\Models\Coupon\Coupon;
use App\Models\FullSub\FullSub;
use App\Models\FullSub\Goods;
use App\Models\FullSub\Store;
use App\Models\GiftBig\GiftBig;
use App\Models\GiftBig\Receive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class GiftBigController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $list = GiftBig::where('uniacid', $this->uniacid())
            ->where('storeId', $this->storeId())
            ->when($request->subState, function ($q) use ($request) {
                if ($request->subState == "begin") {
                    return $q->where("startTime", ">", date("Y-m-d H:i:s", time()));
                }
                if ($request->subState == "start") {
                    return $q->where("startTime", "<", date("Y-m-d H:i:s", time()))->where("endTime", ">=", date("Y-m-d H:i:s", time()));
                }
                if ($request->subState == "end") {
                    return $q->where("endTime", "<", date("Y-m-d H:i:s", time()));
                }
            })
            ->when($request->name, function ($q) use ($request) {
                return $q->where("name", "like", "%{$request->name}%");
            })->when($request->startTime, function ($q) use ($request) {
                return $q->where("created_at", ">=", $request->startTime);
            })
            ->when($request->endTime, function ($q) use ($request) {
                return $q->where("created_at", "<=", $request->endTime);
            })
            ->orderBy('id', 'desc')
            ->paginate(Request()->pageSize ?? 20, '*', 'pageNo');
        return $this->success($list);
    }

    public function store(Request $request)
    {
        try {
            $model = new GiftBig();
            $model->fill($request->all());
            $ids = GiftBig::where('uniacid', $this->uniacid())
                ->where('storeId', $this->storeId())
                ->where(function ($q) use ($model) {
                    return $q->where(function ($q) use ($model) {
                        return $q->where('startTime', '>=', $model->startTime)->where('endTime', '<=', $model->endTime);
                    })->orWhere(function ($q) use ($model) {
                        return $q->where(function ($q) use ($model) {
                            return $q->where('startTime', '<=', $model->startTime)->where('endTime', '>=', $model->startTime);
                        })->orWhere(function ($q) use ($model) {
                            return $q->where('startTime', '<=', $model->endTime)->where('endTime', '>=', $model->endTime);
                        });
                    });
                })->first();
            if (!empty($ids)) {
                return $this->failed($ids->startTime . '至' . $ids->endTime . '已经有活动');
            }
            $model->fill($request->all());
            $model->uniacid = $this->uniacid();
            $model->storeId = $this->storeId();
            $model->save();
            return $this->success([], '添加成功');
        } catch (\Exception $e) {
            Log::error($e);
            return $this->failed($e->getMessage());
        }
    }


    public function show(Request $request, $id)
    {
        try {
            $model = GiftBig::where("uniacid", $this->uniacid())
                ->where('storeId', $this->storeId())
                ->find($id);
            if (empty($model)) {
                return $this->failed('数据不存在');
            }
            return $this->success($model);
        } catch (\Exception $e) {
            return $this->failed('失败');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $model = GiftBig::where("uniacid", $this->uniacid())
                ->where('storeId', $this->storeId())
                ->find($id);
            if (empty($model)) {
                return $this->failed('数据不存在');
            }
            $model->fill($request->all());
            $ids = GiftBig::where('uniacid', $this->uniacid())
                ->where('storeId', $this->storeId())
                ->where('id', '!=', $model->id)
                ->where(function ($q) use ($model) {
                    return $q->where(function ($q) use ($model) {
                        return $q->where('startTime', '>=', $model->startTime)->where('endTime', '<=', $model->endTime);
                    })->orWhere(function ($q) use ($model) {
                        return $q->where(function ($q) use ($model) {
                            return $q->where('startTime', '<=', $model->startTime)->where('endTime', '>=', $model->startTime);
                        })->orWhere(function ($q) use ($model) {
                            return $q->where('startTime', '<=', $model->endTime)->where('endTime', '>=', $model->endTime);
                        });
                    });
                })->first();
            if (!empty($ids)) {
                return $this->failed($ids->startTime . '至' . $ids->endTime . '已经有活动');
            }
            $model->uniacid = $this->uniacid();
            $model->storeId = $this->storeId();
            $model->save();
            return $this->success([], '修改成功');
        } catch (\Exception $e) {
            return $this->failed('修改失败');
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $idArray = array_filter(explode(',', $id), function ($item) {
                return is_numeric($item);
            });
            $models = GiftBig::where('uniacid', $this->uniacid())
                ->where('storeId', $this->storeId())
                ->whereIn('id', $idArray)->get();
            foreach ($models as $key => $model) {
                $model->delete();
            }
            return $this->success([], '删除成功');
        } catch (\Exception $e) {
            return $this->failed('删除失败');
        }
    }

    /**
     * 拉黑/洗白
     */
    public function state(Request $request, $id)
    {
        $model = GiftBig::where('id', $id)
            ->where("uniacid", $this->uniacid())
            ->where('storeId', $this->storeId())
            ->first();
        if (!$model) {
            return $this->failed(__('base.status_error'));
        }
        $model->status = $model->status == 0 ? 1 : 0;
        $model->save();
        return $this->success([]);
    }
    //新人专享礼包领取记录
    public function receive(Request $request)
    {
        $list = Receive::with([
            'activities' => function ($query) {
                $query->select('id', 'name', 'balanceSwitch', 'balance', 'integralSwitch', 'integral', 'couponSwitch', 'couponGive');
            },
            'member' => function ($query) {
                $query->select('id', 'nickname', 'mobile');
            }
        ])->when($request->name, function ($q) use ($request) {
            return $q->whereHas('member', function ($q) use ($request) {
                return $q->where('nickname', 'like', "%{$request->name}%")
                    ->orWhere('mobile', 'like', "%{$request->name}%");
            });
        })->when($request->startTime, function ($q) use ($request) {
            return $q->where('created_at', '>=', $request->startTime)->where('created_at', '<=', $request->endTime);
        })->where('uniacid', $this->uniacid())
            ->where("uniacid", $this->uniacid())
            ->where('storeId', $this->storeId())
            ->orderBy('id', 'desc')
            ->paginate(Request()->pageSize ?? 20, '*', 'pageNo');
        return $this->success($list);
    }

    //数据--用户数据导出
    public function orderDataExport(Request $request)
    {
        $params = $request->all();
        $uniacid = $request->header('uniacid');
        $params['uniacid'] = $uniacid;
        $params['storeId'] =  $this->storeId();
        return Excel::download(new GiftBigReceiveExport($params), 'giftBigReceiveExport.xlsx');
    }
}
