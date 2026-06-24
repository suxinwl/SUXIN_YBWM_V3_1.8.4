<?php

namespace App\Http\Controllers\Channel\Discount;

use App\Http\Controllers\Channel\ApiController;
use App\Http\Requests\Admin\ChangePassword;
use App\Models\Ad;
use App\Models\FullSub\FullSub;
use App\Models\FullSub\Goods;
use App\Models\FullSub\Store;
use App\Models\Order\Discount;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class FullSubController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $list = FullSub::where('uniacid', $this->uniacid())
            ->where('storeId', $this->storeId())
            ->orderBy('sort', 'asc')
            ->when($request->state, function ($q) use ($request) {
                if ($request->state == 'on') {
                    return $q->where('state', 1);
                } else {
                    return $q->where('state', 0);
                }
            })
            ->when($request->type, function ($q) use ($request) {
                return $q->where('type', $request->type);
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
            ->orderBy('sort', 'asc')
            ->paginate(Request()->pageSize ?? 20, '*', 'pageNo');
        return $this->success($list);
    }

    public function store(Request $request)
    {
        try {
            $model = new FullSub();
            $model->fill($request->all());
            $model->uniacid = $this->uniacid();
            $model->storeId = $this->isolateStore();
            if ($model->storeId > 0) {
                $model->storeType = 2;
                $model->storeIds = [$this->storeId()];
            }
            $ids = FullSub::where('uniacid', $this->uniacid())
                ->when($model->type, function ($q) use ($model) {
                    if (in_array($model->type, [1, 2])) {
                        return $q->whereIn('type', [1, 2]);
                    } elseif ($model->type == 3) {
                        return $q->where('type', 3);
                    }
                    return $q;
                })
                ->when($model->storeIds && in_array($model->storeType, [2, 3]), function ($q) use ($model) {
                    return $q->where(function ($q) use ($model) {
                        return $q->whereHas('stores', function ($q) use ($model) {
                            return $q->whereIn('storeId', $model->storeIds ?? [0]);
                        })->orWhere(function ($q) use ($model) {
                            return $q->where('storeType', 1)
                                ->where('uniacid', $model->uniacid)
                                ->where('storeId', $model->storeId);
                        });
                    });
                })->when($model->storeType == 1, function ($q) use ($model) {
                    return $q->where('storeType', 1)->where('uniacid', $model->uniacid)
                        ->where('storeId', $model->storeId);
                })
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
            $model->save();
            $model->stores()->sync($model->storeIds ?? [], ['uniacid', $this->uniacid()]);
            Store::where('fullsubId', $model->id)->update(['uniacid' => $this->uniacid(), 'type' => $model->storeType]);
            $model->goods()->sync($model->goodsIds ?? [], ['uniacid', $this->uniacid()]);
            Goods::where('fullsubId', $model->id)->update(['uniacid' => $this->uniacid(), 'type' => $model->goodsType]);
            return $this->success([], '添加成功');
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }

    public function show(Request $request, $id)
    {
        try {
            $model = FullSub::where("uniacid", $this->uniacid())->find($id);
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
            $model = FullSub::where("uniacid", $this->uniacid())->find($id);
            if (empty($model)) {
                return $this->failed('数据不存在');
            }
            $model->fill($request->all());
            $model->uniacid = $this->uniacid();
            if ($model->storeId > 0) {
                $model->storeType = 2;
                $model->storeIds = [$this->storeId()];
            }
            $ids = FullSub::where('uniacid', $this->uniacid())
                ->where('id', '!=', $model->id)
                ->when($model->type, function ($q) use ($model) {
                    if (in_array($model->type, [1, 2])) {
                        return $q->whereIn('type', [1, 2]);
                    } elseif ($model->type == 3) {
                        return $q->where('type', 3);
                    }
                    return $q;
                })->when($model->storeType == 1, function ($q) use ($model) {
                    return $q->where('storeType', 1)->where('uniacid', $model->uniacid)
                        ->where('storeId', $model->storeId);
                })
                ->when($model->storeIds && in_array($model->storeType, [2, 3]), function ($q) use ($model) {
                    return $q->where(function ($q) use ($model) {
                        return $q->whereHas('stores', function ($q) use ($model) {
                            return $q->whereIn('storeId', $model->storeIds ?? [0]);
                        })->orWhere(function ($q) use ($model) {
                            return $q->where('storeType', 1)->where('id', '!=', $model->id)
                                ->where('uniacid', $model->uniacid)->where('storeId', $model->storeId);
                        });
                    });
                })->where(function ($q) use ($model) {
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
            $model->save();
            $model->stores()->sync($model->storeIds ?? [], ['uniacid', $this->uniacid()]);
            Store::where('fullsubId', $model->id)->update(['uniacid' => $this->uniacid(), 'type' => $model->storeType]);
            $model->goods()->sync($model->goodsIds ?? [], ['uniacid', $this->uniacid()]);
            Goods::where('fullsubId', $model->id)->update(['uniacid' => $this->uniacid(), 'type' => $model->goodsType]);
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
            $models = FullSub::where('uniacid', $this->uniacid())->whereIn('id', $idArray)->get();
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
        $model = FullSub::where('id', $id)->first();
        if (!$model) {
            return $this->failed(__('base.status_error'));
        }
        $model->status = $model->status == 0 ? 1 : 0;
        $model->save();
        return $this->success([]);
    }


    //满减折扣记录
    public function receive()
    {
        $list = Discount::with('fullsub')->where('uniacid', $this->uniacid())
            ->where('storeId', $this->storeId())
            ->where('type', 'fullsub')
            ->orderBy('id', 'desc')
            ->paginate(Request()->pageSize ?? 20, '*', 'pageNo');
        return $this->success($list);
    }
}
