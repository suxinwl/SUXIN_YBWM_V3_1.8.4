<?php

namespace App\Http\Controllers\Channel\Discount;

use App\Http\Controllers\Channel\ApiController;
use App\Http\Requests\Admin\ChangePassword;
use App\Models\Ad;
use App\Models\Coupon\Activity;
use App\Models\Coupon\Coupon;
use App\Models\FullSub\FullSub;
use App\Models\FullSub\Goods;
use App\Models\FullSub\Store;
use App\Models\ShortLink;
use App\Services\ShortLinkService;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class CouponActivityController extends ApiController
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
            ->when($request->type, function ($q) use ($request) {
                return $q->where('type', $request->type);
            })->when($request->subState, function ($q) use ($request) {
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
            $model = new Activity();
            $model->fill($request->all());
            $model->state = 1;
            $model->uniacid = $this->uniacid();
            $model->storeId = $this->isolateStore();
            $model->save();
            return $this->success([], '添加成功');
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
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
            return $this->failed('失败');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $model = Activity::where("uniacid", $this->uniacid())->find($id);
            if (empty($model)) {
                return $this->failed('数据不存在');
            }
            $model->fill($request->all());
            $model->uniacid = $this->uniacid();
            $model->storeId = $this->isolateStore();
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
            $models = Activity::where('uniacid', $this->uniacid())
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

    public function qrCode(Request $request, $id)
    {
        $model = Activity::where('uniacid', $this->uniacid())
            ->where('id', $id)
            ->first();
        if (empty($model)) {
            return $this->failed('领券活动不存在');
        }
        $link = ShortLink::where('uniacid', $this->uniacid())
            ->where('type', 'couponActivity')
            ->where('ident', $model->sn)
            ->first();
        if (empty($shortLink)) {
            $link = ShortLinkService::createCouponActivity($model);
        }
        $url = Request()->getSchemeAndHttpHost() . '/s/couponActivity/' . $this->uniacid() . '/'  . $link->shortLink . "?couponId={$model->id}&storeId={$model->storeId}&isolate=" . $this->isolate();
        $img =  QrCode::format('png')->size(400)->generate($url);    //format 是指定生成文件格式  默认格式是svg,可以直接在浏览器打开，png不能直接显示
        $code_url = 'data:image/png;base64,' . base64_encode($img);
        return $this->success($code_url);
    }
}
