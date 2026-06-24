<?php

namespace App\Http\Controllers\Channel\Discount;

use App\Http\Controllers\Channel\ApiController;
use App\Http\Requests\Admin\ChangePassword;
use App\Models\Ad;
use App\Models\Coupon\Coupon;
use App\Models\CouponPack\CouponPack;
use App\Models\CouponPack\CouponPackStore;
use App\Models\FullSub\FullSub;
use App\Models\FullSub\Goods;
use App\Models\FullSub\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class CouponPackController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $list = CouponPack::where('uniacid', $this->uniacid())
            ->where('storeId', $this->storeId())
            ->when($request->type, function ($q) use ($request) {
                return $q->where('type', $request->type);
            })->when($request->subState, function ($q) use ($request) {
                if ($request->subState == "begin") {
                    return $q->where("startTime", ">", date("Y-m-d H:i:s", time()));
                }
                if ($request->subState == "start") {
                    return $q->whereNull('startTime')->orWhere(function ($q) {
                        return $q->where("startTime", "<", Carbon::now()->toDateTimeString())
                            ->where("endTime", ">=",  Carbon::now()->toDateTimeString());
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
            ->orderBy('id', 'desc')
            ->paginate(Request()->pageSize ?? 20, '*', 'pageNo');
        return $this->success($list);
    }

    public function store(Request $request)
    {
        try {
            $model = new CouponPack();
            $model->fill($request->all());
            $model->uniacid = $this->uniacid();
            $model->storeId = $this->isolateStore();
            if ($model->storeId > 0) {
                $model->storeType = 2;
                $model->storeIds = [$this->storeId()];
            }
            $model->save();
            $model->stores()->sync($model->storeIds ?? []);
            CouponPackStore::where('couponPackId', $model->id)->update(['uniacid' => $model->uniacid, 'type' => $model->storeType]);
            return $this->success([], '添加成功');
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }


    public function show(Request $request, $id)
    {
        try {
            $model = CouponPack::where("uniacid", $this->uniacid())->find($id);
            if (empty($model)) {
                return $this->failed('数据不存在');
            }
            $model->weekArr=$model->weekArr?:[];
            return $this->success($model);
        } catch (\Exception $e) {
            return $this->failed('失败');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $model = CouponPack::where("uniacid", $this->uniacid())->find($id);
            if (empty($model)) {
                return $this->failed('数据不存在');
            }
            $model->fill($request->all());
            $model->uniacid = $this->uniacid();
            if ($model->storeId > 0) {
                $model->storeType = 2;
                $model->storeIds = [$this->storeId()];
            }
            $model->save();
            $model->stores()->sync($model->storeIds ?? []);
            CouponPackStore::where('couponPackId', $model->id)->update(['uniacid' => $model->uniacid, 'type' => $model->storeType]);
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
            $models = CouponPack::where('uniacid', $this->uniacid())->whereIn('id', $idArray)->get();
            foreach ($models as $key => $model) {
                $model->stores()->sync([]);
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
        $model = CouponPack::where('id', $id)->first();
        if (!$model) {
            return $this->failed(__('base.status_error'));
        }
        $model->state = $model->state == 0 ? 1 : 0;
        $model->save();
        return $this->success([]);
    }
}
