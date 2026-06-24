<?php

namespace App\Http\Controllers\Channel\Discount;

use App\Exports\WindowCouponReceiveExport;
use App\Http\Controllers\Channel\ApiController;
use App\Models\Coupon\MemberCoupon;
use App\Models\NewSub\Goods;
use App\Models\NewSub\Store;
use App\Models\NewSub\NewSub;
use App\Models\WindowCoupon\Coupon;
use App\Models\WindowCoupon\CouponReceive;
use App\Models\WindowCoupon\Stores;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class WindowCouponController extends ApiController
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
            ->when($request->pos, function ($q) use ($request) {
                return $q->where('pos', $request->pos);
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
            ->when($request->receiveType, function ($q) use ($request) {
                return $q->where("receiveType", $request->receiveType);
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
            if ($model->storeId > 0) {
                $model->storeType = 2;
                $model->storeIds = [$this->storeId()];
            }
            $ids = Coupon::where('uniacid', $this->uniacid())
                ->where('pos', $model->pos)
                ->when($model->storeIds && in_array($model->storeType, [2, 3]), function ($q) use ($model) {
                    return $q->where(function ($q) use ($model) {
                        return $q->where(function ($q) use ($model) {
                            return $q->whereHas('stores', function ($q) use ($model) {
                                return $q->whereIn('storeId', $model->storeIds ?? [0])->where('type', $model->storeType);
                            })->orWhere(function ($q) use ($model) {
                                return $q->where('storeType', 1)
                                    ->where('uniacid', $model->uniacid)
                                    ->where('storeId', $model->storeId)
                                    ->where('pos', $model->pos);
                            });
                        });
                    });
                })
                ->when($model->storeType == 1, function ($q) use ($model) {
                    return $q->where('storeType', 1)->where('id', '!=', $model->id)
                        ->where('uniacid', $model->uniacid)
                        ->where('storeId', $model->storeId)
                        ->where('pos', $model->pos);
                })
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
            $model->stores()->sync($model->storeIds ?? []);
            Stores::where('windowCouponId', $model->id)->update(['uniacid' => $this->uniacid(), 'type' => $model->storeType]);
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
            if ($model->storeId > 0) {
                $model->storeType = 2;
                $model->storeIds = [$this->storeId()];
            }
            $ids = Coupon::where('uniacid', $this->uniacid())
                ->where('id', '!=', $model->id)
                ->where('pos', $model->pos)
                ->when($model->storeIds && in_array($model->storeType, [2, 3]), function ($q) use ($model) {
                    return $q->where(function ($q) use ($model) {
                        return $q->whereHas('stores', function ($q) use ($model) {
                            return $q->whereIn('storeId', $model->storeIds ?? [0])->where('type', $model->storeType);
                        })->orWhere(function ($q) use ($model) {
                            return $q->where('storeType', 1)->where('id', '!=', $model->id)
                                ->where('uniacid', $model->uniacid)
                                ->where('storeId', $model->storeId)
                                ->where('pos', $model->pos);
                        });
                    });
                })->when($model->storeType == 1, function ($q) use ($model) {
                    return $q->where('storeType', 1)->where('id', '!=', $model->id)
                        ->where('uniacid', $model->uniacid)
                        ->where('storeId', $model->storeId)
                        ->where('pos', $model->pos);
                })
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
            $model->stores()->sync($model->storeIds ?? []);
            Stores::where('windowCouponId', $model->id)->update(['uniacid' => $this->uniacid(), 'type' => $model->storeType]);
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

    /**
     * 拉黑/洗白
     */
    public function state(Request $request, $id)
    {
        $model = Coupon::where('id', $id)->first();
        if (!$model) {
            return $this->failed('活动不存在');
        }
        $model->status = $model->status == 0 ? 1 : 0;
        $model->save();
        return $this->success([]);
    }

    //弹窗优惠券发放
    public function receive(Request $request)
    {
        $list = CouponReceive::with(['member', 'window'])->where('uniacid', $this->uniacid())
            ->when($request->name, function ($q) use ($request) {
                return $q->whereHas('member', function ($q) use ($request) {
                    return $q->where('nickname', 'like', "%{$request->name}%")
                        ->orWhere('mobile', 'like', "%{$request->name}%");
                });
            })->when($request->startTime, function ($q) use ($request) {
                return $q->where('created_at', '>=', $request->startTime)->where('created_at', '<=', $request->endTime);
            })->when($request->activityId, function ($q) use ($request) {
                return $q->where("windowCouponId", $request->activityId);
            })
            ->orderBy('id', 'desc')
            ->paginate($request->pageSize ?? 20, '*', 'pageNo');
        return $this->success($list);
    }
    //数据--用户数据导出
    public function orderDataExport(Request $request)
    {
        $params = $request->all();
        $uniacid = $request->header('uniacid');
        $params['uniacid'] = $uniacid;
        $params['storeId'] = $this->storeId();
        return Excel::download(new WindowCouponReceiveExport($params), 'windowCouponReceive.xlsx');
    }
}
