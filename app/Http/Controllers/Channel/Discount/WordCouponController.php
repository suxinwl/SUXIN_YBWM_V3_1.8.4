<?php

namespace App\Http\Controllers\Channel\Discount;

use App\Exports\WindowCouponReceiveExport;
use App\Http\Controllers\Channel\ApiController;
use App\Models\WordCoupon\Coupon;
use App\Models\WordCoupon\Receive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class WordCouponController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $list = Coupon::where('uniacid', $this->uniacid())
            ->where('storeId', $this->storeId())
            ->when($request->state, function ($q) use ($request) {
                if ($request->state == 'on') {
                    return $q->where('state', 1);
                } else {
                    return $q->where('state', 0);
                }
            })
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
            })
            ->when($request->startTime, function ($q) use ($request) {
                return $q->where("startTime", "<=", $request->startTime);
            })
            ->when($request->endTime, function ($q) use ($request) {
                return $q->where("endTime", ">=", $request->endTime);
            })
            ->orderBy('id', 'desc')
            ->paginate(Request()->pageSize ?? 20, '*', 'pageNo');
        return $this->success($list);
    }

    public function store(Request $request)
    {
        try {
            $model = new Coupon();
            $model->fill($request->all());
            $model->uniacid = $this->uniacid();
            $model->storeId = $this->isolateStore();
            $ids = Coupon::where('uniacid', $this->uniacid())
                ->where('storeId', $this->storeId())
                ->where(function ($q) use ($model) {
                    return $q->where(function ($q) use ($model) {
                        return $q->where(function ($q) use ($model) {
                            return $q->where('startTime', '>=', $model->startTime)->where('endTime', '<=', $model->endTime);
                        })->orWhere(function ($q) use ($model) {
                            return $q->where(function ($q) use ($model) {
                                return $q->where('startTime', '<=', $model->startTime)->where('endTime', '>=', $model->startTime);
                            })->orWhere(function ($q) use ($model) {
                                return $q->where('startTime', '<=', $model->endTime)->where('endTime', '>=', $model->endTime);
                            });
                        });
                    });
                })->first();
            if (!empty($ids)) {
                return $this->failed($ids->startTime . '至' . $ids->endTime . '已经有活动');
            }
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
            $model = Coupon::where("uniacid", $this->uniacid())->find($id);
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
            $model = Coupon::where("uniacid", $this->uniacid())->find($id);
            if (empty($model)) {
                return $this->failed('数据不存在');
            }
            $model->fill($request->all());
            $model->uniacid = $this->uniacid();
            $ids = Coupon::where('uniacid', $this->uniacid())
                ->where('storeId', $model->storeId)
                ->where('id', '!=', $model->id)
                ->where(function ($q) use ($model) {
                    return $q->where(function ($q) use ($model) {
                        return $q->where(function ($q) use ($model) {
                            return $q->where('startTime', '>=', $model->startTime)->where('endTime', '<=', $model->endTime);
                        })->orWhere(function ($q) use ($model) {
                            return $q->where(function ($q) use ($model) {
                                return $q->where('startTime', '<=', $model->startTime)->where('endTime', '>=', $model->startTime);
                            })->orWhere(function ($q) use ($model) {
                                return $q->where('startTime', '<=', $model->endTime)->where('endTime', '>=', $model->endTime);
                            });
                        });
                    });
                })->first();
            if (!empty($ids)) {
                return $this->failed($ids->startTime . '至' . $ids->endTime . '已经有活动');
            }
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
            $models = Coupon::where('uniacid', $this->uniacid())->whereIn('id', $idArray)->get();
            foreach ($models as $key => $model) {
                $model->delete();
            }
            return $this->success([], '删除成功');
        } catch (\Exception $e) {
            return $this->failed('删除失败');
        }
    }

    public function receive(Request $request)
    {
        $list = Receive::where('uniacid', $this->uniacid())
            ->where('storeId', $this->storeId())
            ->with(['user', 'wordCoupon'])
            ->when($request->startTime, function ($q) use ($request) {
                return $q->where("created_at", "<=", $request->startTime);
            })
            ->when($request->endTime, function ($q) use ($request) {
                return $q->where("created_at", ">=", $request->endTime);
            })
            ->when($request->wordCouponId, function ($q) use ($request) {
                return $q->where('wordCouponId', $request->wordCouponId);
            })->orderBy('id', 'desc')
            ->when($request->keyword, function ($q) use ($request) {
                return $q->whereHas('user', function ($q) use ($request) {
                    return $q->where('mobile', 'like', "%{$request->keyword}%")->orWhere(
                        'nickname',
                        'like',
                        "%{$request->keyword}%"
                    );
                });
            })
            ->paginate(Request()->pageSize ?? 20, '*', 'pageNo');
        return $this->success($list);
    }

    /**
     * 拉黑/洗白
     */
    public function state(Request $request, $id)
    {
        $model = Coupon::where('id', $id)->first();
        if (!$model) {
            return $this->failed('活动不存在');
        }
        $model->state = $model->state == 0 ? 1 : 0;
        $model->save();
        return $this->success([]);
    }
}
