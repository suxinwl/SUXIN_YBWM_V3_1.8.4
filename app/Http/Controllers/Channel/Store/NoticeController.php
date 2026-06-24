<?php

namespace App\Http\Controllers\Channel\Store;

use App\Http\Controllers\Channel\ApiController;
use App\Models\GoodsSku;
use App\Models\Recipe\RecipeStore;
use App\Models\Store;
use App\Models\Store\Notice;
use App\Models\Store\StoreCategory;
use App\Models\Store\StoreGoods;
use App\Models\Store\StoreGoodsSku;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class NoticeController extends ApiController
{
    public function index(Request $request)
    {
        $storeId = $this->storeId();
        $isolate = $this->isolate();
        $list = Notice::with(['stores'])
            ->where('uniacid', $this->uniacid())
            ->when($request->title, function ($q) use ($request) {
                return $q->where('title', 'like', "%{$request->title}%");
            })
            ->when($request->type, function ($q) use ($request) {
                return $q->where('type', $request->type);
            })
            ->when($request->type == 2, function ($q) use ($request, $storeId, $isolate) {
                $q->when($isolate, function ($q) use ($storeId, $isolate) {
                    return $q->whereHas('stores', function ($q) use ($storeId) {
                        return $q->where('isolate', 1)->where('storeId', $storeId);
                    });
                })->when(!$isolate, function ($q) use ($storeId, $isolate) {
                    return $q->whereHas('stores', function ($q) use ($storeId) {
                        return $q->where('isolate', 0)->when($storeId, function ($q) use ($storeId) {
                            return $q->where('storeId', $storeId);
                        });
                    });
                });
            })
            ->when($request->state, function ($q) use ($request) {
                return $q->where('state', $request->state == 'on' ? 1 : 0);
            })
            ->orderBy('sort', 'asc')
            ->paginate($request->pageSize ?? 20, '*', 'pageNo');
        return $this->success($list);
    }

    public function show(Request $request, $id)
    {
        $model = Notice::where('uniacid', $this->uniacid())->find($id);
        if (empty($model)) {
            throw new BadRequestException('数据不存在');
        }
        return $this->success($model);
    }


    public function store(Request $request)
    {
        $model = new Notice();
        $model->fill($request->all());
        $model->uniacid = $this->uniacid();
        if ($model->storeId) {
            $ids = Notice::where('uniacid', $this->uniacid())
                ->where('type', $model->type)
                ->when($model->storeId, function ($q) use ($model) {
                    return $q->whereHas('stores', function ($q) use ($model) {
                        return $q->whereIn('storeId', $model->storeId ?? [0]);
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
                    });
                })->first();
            if (!empty($ids)) {
                return $this->failed($ids->startTime . '至' . $ids->endTime . '已经有所选店铺的公告');
            }
        }
        if ($this->isolateStore() > 0) {
            $model->storeId = [$this->storeId()];
        }
        $model->save();
        $model->stores()->sync($model->storeId ?? []);
        return $this->success();
    }
    public function update(Request $request, $id)
    {
        $model = Notice::where('uniacid', $this->uniacid())->find($id);
        if (empty($model)) {
            throw new BadRequestException('数据不存在');
        }
        $model->fill($request->all());
        $model->uniacid = $this->uniacid();
        if ($model->storeId) {
            $ids = Notice::where('uniacid', $this->uniacid())
                ->where('id', '!=', $model->id)
                ->where('type', $model->type)
                ->when($model->storeId, function ($q) use ($model) {
                    return $q->whereHas('stores', function ($q) use ($model) {
                        return $q->whereIn('storeId', $model->storeId ?? [0]);
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
                    });
                })
                ->first();
            if (!empty($ids)) {
                return $this->failed($ids->startTime . '至' . $ids->endTime . '已经有所选店铺的公告');
            }
        }
        $model->save();
        $model->stores()->sync($model->storeId ?? []);
        return $this->success($model);
    }
    public function destroy(Request $request, $id)
    {
        $idArray = array_filter(explode(',', $id), function ($item) {
            return is_numeric($item);
        });
        Notice::whereIn('id', $idArray)->delete();
        return $this->success([], '删除成功');
    }
}
