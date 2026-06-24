<?php

namespace App\Http\Controllers\Channel\Discount;

use App\Exports\NewSubReceiveExport;
use App\Exports\PayGiftReceiveExport;
use App\Http\Controllers\Channel\ApiController;
use App\Models\GoodsActivity\Activity;
use App\Models\GoodsActivity\Goods;
use App\Models\GoodsActivity\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class VipGoodsController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $list = Activity::where('uniacid', $this->uniacid())
            ->where('storeId', $this->storeId())
            ->where('type', 6)
            ->with(['stores'])
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
        DB::beginTransaction();
        // try {
        $model = new Activity();
        $model->fill($request->all());
        $model->uniacid = $this->uniacid();
        $model->type = 6;
        $model->storeId = $this->isolateStore();
        if ($model->storeId > 0) {
            $model->storeType = 2;
            $model->storeIds = [$this->storeId()];
        }
        // $ids = Activity::where('uniacid', $this->uniacid())
        //     ->when($model->storeIds && in_array($model->storeType, [2, 3]), function ($q) use ($model) {
        //         return $q->where(function ($q) use ($model) {
        //             return $q->whereHas('stores', function ($q) use ($model) {
        //                 return $q->whereIn('storeId', $model->storeIds ?? [0]);
        //             });
        //         })->orWhere(function ($q) use ($model) {
        //             return $q->where('storeType', 1)->where('uniacid', $model->uniacid);
        //         });
        //     })->first();
        // ->where(function ($q) use ($model) {
        //     return $q->where(function ($q) use ($model) {
        //         return $q->where('startTime', '>=', $model->startTime)->where('endTime', '<=', $model->endTime);
        //     })->orWhere(function ($q) use ($model) {
        //         return $q->where(function ($q) use ($model) {
        //             return $q->where('startTime', '<=', $model->startTime)->where('endTime', '>=', $model->startTime);
        //         })->orWhere(function ($q) use ($model) {
        //             return $q->where('startTime', '<=', $model->endTime)->where('endTime', '>=', $model->endTime);
        //         });
        //     });
        // })

        // if (!empty($ids)) {
        //     return $this->failed($ids->startTime . '至' . $ids->endTime . '已经有活动');
        // }
        $model->save();
        $ids = collect($model->goodsData['goods'])->pluck('specMd5')->all();
        $goods = Goods::whereIn('specMd5', $ids)
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
            })
            ->get();
        if (!empty($goods->toArray())) {
            return $this->failed(collect($model->goodsData['goods'])->whereIn('specMd5', collect($goods)->pluck('specMd5')->all())->pluck('name')->implode(',') . '已被添加到其它活动');
        }
        $model->stores()->sync($model->storeIds ?? []);
        $goodslist = [];
        foreach ($model->goodsData['goods'] as $key => $goods) {
            $goods['startTime'] = $model->startTime;
            $goods['endTime'] = $model->endTime;
            $goods['activityId'] = $model->id;
            $goods['type'] = $model->type;
            $goods['uniacid'] = $model->uniacid;
            $goods['userType'] = $model->userType;
            $goods['scenario'] = json_encode($model->scenario, 320);
            $goods['discountRule'] = json_encode($goods['discountRule'] ?? [], 320);
            unset($goods['name'], $goods['price'], $goods['id'], $goods['discount']);
            $goodslist[] = $goods;
            $spuIds[] = $goods['spuId'];
        }
        if ($goodslist) {
            Goods::insert($goodslist);
            collect($spuIds)->unique()->each(function ($spuId, $key) {
                Cache::tags("goodsDiscount:$spuId")->flush();
            });
        }
        Store::where('activityId', $model->id)->update(['uniacid' => $this->uniacid(), 'type' => $model->storeType]);
        DB::commit();
        return $this->success([], '添加成功');
        // } catch (\Exception $e) {
        //     DB::rollBack();
        //     Log::error($e->getMessage().$e->getFile().$e->getLine());
        //     return $this->failed($e->getMessage());
        // }
    }

    public function show(Request $request, $id)
    {
        try {
            $model = Activity::where("uniacid", $this->uniacid())->find($id);
            if (empty($model)) {
                return $this->failed('数据不存在');
            }
            return $this->success($model);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return $this->failed('失败');
        }
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $model = Activity::where("uniacid", $this->uniacid())->find($id);
            if (empty($model)) {
                return $this->failed('数据不存在');
            }
            $model->fill($request->all());
            $model->uniacid = $this->uniacid();
            $model->storeId = $this->isolateStore();
            if ($model->storeId > 0) {
                $model->storeType = 2;
                $model->storeIds = [$this->storeId()];
            }
            // $ids = Activity::where('uniacid', $this->uniacid())
            //     ->where('id', '!=', $model->id)
            //     ->when($model->storeIds && in_array($model->storeType, [2, 3]), function ($q) use ($model) {
            //         return $q->where(function ($q) use ($model) {
            //             return $q->whereHas('stores', function ($q) use ($model) {
            //                 return $q->whereIn('storeId', $model->storeIds ?? [0]);
            //             });
            //         })->orWhere(function ($q) use ($model) {
            //             return $q->where('storeType', 1)->where('id', '!=', $model->id)->where('uniacid', $model->uniacid);
            //         });
            //     })
            //     ->when($model->goodsData, function ($q) use ($model) {
            //         $ids = collect($model->goodsData['goods'])->pluck('id')->all();
            //         return $q->whereHas('goods', function ($q) use ($model, $ids) {
            //             return $q->whereIn('goods', $model->ids ?? [0])->where('activityId', '!=', $model->id)->where('type', $model->type);
            //         });
            //     })
            //     ->where(function ($q) use ($model) {
            //         return $q->where(function ($q) use ($model) {
            //             return $q->where('startTime', '>=', $model->startTime)->where('endTime', '<=', $model->endTime);
            //         })->orWhere(function ($q) use ($model) {
            //             return $q->where(function ($q) use ($model) {
            //                 return $q->where('startTime', '<=', $model->startTime)->where('endTime', '>=', $model->startTime);
            //             })->orWhere(function ($q) use ($model) {
            //                 return $q->where('startTime', '<=', $model->endTime)->where('endTime', '>=', $model->endTime);
            //             });
            //         });
            //     })->first();
            // if (!empty($ids)) {
            //     return $this->failed($ids->startTime . '至' . $ids->endTime . '已经有活动');
            // }
            $model->save();
            $ids = collect($model->goodsData['goods'])->pluck('specMd5')->all();
            $goods = Goods::whereIn('specMd5', $ids)
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
                })
                ->where("activityId", '!=', $model->id)
                ->get();
            if (!empty($goods->toArray())) {
                return $this->failed(collect($model->goodsData['goods'])->whereIn('specMd5', collect($goods)->pluck('specMd5')->all())->pluck('name')->implode(',') . '已被添加到其它活动');
            }
            $goodslist = [];
            Goods::where('activityId', $model->id)->delete();
            foreach ($model->goodsData['goods'] as $key => $goods) {
                $goods['startTime'] = $model->startTime;
                $goods['endTime'] = $model->endTime;
                $goods['activityId'] = $model->id;
                $goods['type'] = $model->type;
                $goods['uniacid'] = $model->uniacid;
                $goods['userType'] = $model->userType;
                $goods['scenario'] = json_encode($model->scenario, 320);
                $goods['discountRule'] = json_encode($goods['discountRule'] ?? [], 320);
                unset($goods['name'], $goods['price'], $goods['id'], $goods['discount']);
                $goodslist[] = $goods;
                $spuIds[] = $goods['spuId'];
            }
            if ($goodslist) {
                Goods::insert($goodslist);
                collect($spuIds)->unique()->each(function ($spuId, $key) {
                    Cache::tags("goodsDiscount:$spuId")->flush();
                });
            }
            $model->stores()->sync($model->storeIds ?? []);
            Store::where('activityId', $model->id)->update(['uniacid' => $this->uniacid(), 'type' => $model->storeType]);
            DB::commit();
            return $this->success([], '修改成功');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failed($e->getMessage());
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $idArray = array_filter(explode(',', $id), function ($item) {
                return is_numeric($item);
            });
            $models = Activity::where('uniacid', $this->uniacid())->whereIn('id', $idArray)->get();
            foreach ($models as $key => $model) {
                $model->stores()->sync([]);
                $model->goods()->delete();
                $model->delete();
                foreach ($model->goodsData['goods'] as $key => $goods) {
                    Cache::tags("goodsDiscount:" . $goods['spuId'])->flush();
                }
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
        $model = Activity::where('id', $id)->first();
        if (!$model) {
            return $this->failed(__('base.status_error'));
        }
        $model->state = $model->state == 0 ? 1 : 0;
        $model->save();
        Goods::where('activityId', $model->id)->update(['state' => $model->state]);
        foreach ($model->goodsData['goods'] as $key => $goods) {
            Cache::tags("goodsDiscount:" . $goods['spuId'])->flush();
        }
        return $this->success([]);
    }


    //数据--用户数据导出
    public function orderDataExport(Request $request)
    {
    }
}
