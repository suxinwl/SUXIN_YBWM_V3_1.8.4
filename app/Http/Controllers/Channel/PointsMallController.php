<?php

namespace App\Http\Controllers\Channel;

use Illuminate\Http\Request;
use App\Models\PointsMall;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class PointsMallController extends ApiController
{
    public function Index(Request $request)
    {
        $query = PointsMall::with(['category'])
        ->where('storeId',$this->storeId())
        ->where('uniacid', $this->uniacid());
        if ($request->name) {
            $query = $query->where('name', 'like', "%$request->name%");
        }
        if ($request->type_id) {
            $query = $query->where('type_id', $request->type_id);
        }
        if ($request->startTime && $request->endTime) {
            $query = $query->whereBetween('created_at', [$request->startTime, $request->endTime]);
        }
        $list = $query->orderBy('sort', 'asc')->paginate($request->pageSize ?? 20, '*', 'pageNo');
        return $this->success($list);
    }

    public function store(Request $request)
    {
        PointsMall::create(
            [
                'sort' => $request->sort,
                'product_type' => $request->product_type,
                'type_id' => $request->type_id,
                'name' => $request->name,
                'introduction' => $request->introduction,
                'icon' => $request->icon,
                'original_price' => $request->original_price ?? 0,
                'stock' => $request->stock ?? 0,
                'sales' => $request->sales ?? 0,
                'integral' => $request->integral ?? 0,
                'money' => $request->money ?? 0,
                'balance' => $request->balance ?? 0,
                'limit_type' => $request->limit_type ?? 0,
                'limit_num' => $request->limit_num ?? 0,
                'delivery_switch' => $request->delivery_switch,
                'delivery_fee' => $request->delivery_fee ?? 0 ,
                'self_switch' => $request->self_switch ?? 0,
                'hot_switch' => $request->hot_switch ?? 0,
                'display' => $request->display,
                'uniacid' => $this->uniacid(),
                'body' => $request->body,
                'storeId'=>$this->storeId(),
                'coupon_collection' => $request->coupon_collection ?? [],
                'deliveryChannel' => $request->deliveryChannel ?? [],
                'storeType' => $request->storeType ?? 1,
                'storeIds' => $request->storeIds ?? [],
                'state' => $request->state ?? 1
            ]
        );
        return $this->success([],'操作成功');
    }

    public function show(Request $request, $id)
    {
        $model = PointsMall::where('uniacid', $this->uniacid())->find($id);
        if (empty($model)) {
            throw new BadRequestException('数据不存在');
        }
        return $this->success($model);
    }


    public function update(Request $request, $id)
    {
        try {
            $model = PointsMall::where('uniacid', $this->uniacid())->find($id);
            if (empty($model)) {
                throw new BadRequestException('数据不存在');
            }
            $model->fill($request->all());
            $model->uniacid = $this->uniacid();
            $model->save();
            return $this->success([], '保存成功');
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }

    public function state(Request $request, $id)
    {
        try { 
            $model = PointsMall::where('uniacid', $this->uniacid())->find($id);
            if (empty($model)) {
                throw new BadRequestException('数据不存在');
            }
            $model->state = $model->state == 1 ? 0 : 1;
            $model->save();
            return $this->success([], '保存成功');
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }



    public function destroy(Request $request, $id)
    {
        try {
            $idArray = array_filter(explode(',', $id), function ($item) {
                return is_numeric($item);
            });
            $models = PointsMall::where('uniacid', $this->uniacid())
                ->whereIn('id', $idArray)->get();
            foreach ($models as $key => $model) {
                $model->delete();
            }
            return $this->success([], '删除成功');
        } catch (\Exception $e) {
            return $this->failed('删除失败');
        }
    }
}
