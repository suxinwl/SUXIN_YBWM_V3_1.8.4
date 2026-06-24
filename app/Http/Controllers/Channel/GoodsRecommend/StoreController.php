<?php

namespace App\Http\Controllers\Channel\GoodsRecommend;

use App\Http\Controllers\Channel\ApiController;
use App\Models\GoodsRecommend\Recommend;
use App\Models\GoodsRecommend\Store;
use App\Models\Recipe\Recipe;
use App\Models\Recipe\RecipeStore;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class StoreController extends ApiController
{
    public function index(Request $request, $id)
    {
        $model = Recommend::where('uniacid', $this->uniacid())->find($id);
        if (empty($model)) {
            throw new BadRequestException('数据不存在');
        }
        $list = Store::with(['store'])->where('recommendId', $id)
            ->where('uniacid', $this->uniacid())
            ->when($request->name,function($q)use ($request){
                return $q->whereHas('store',function($q)use($request){
                    return $q->where('name','like',"%$request->name%");
                });
            })
            ->orderBy('id', 'desc')
            ->paginate($request->pageSize ?? 20, '*', 'pageNo');
        return $this->success($list);
    }

    public function store(Request $request, $id)
    {
        $model = Recommend::where('uniacid', $this->uniacid())->find($id);
        if (empty($model)) {
            throw new BadRequestException('数据不存在');
        }
        $ids = collect($model->store)->pluck('storeId')->all();
        $storeIds = $request->storeIds ?? [];
        $ids = array_diff($storeIds, array_intersect($storeIds, $ids));
        foreach ($ids as $key => $store) {
            $data[] = new Store([
                'uniacid' => $this->uniacid(),
                'recommendId' => $model->id,
                'storeId' => $store
            ]);
        }
        $model->store()->saveMany($data);
        return $this->success();
    }

    public function destroy(Request $request, $id)
    {
        try {
            $idArray = $request->storeIds ?? [];
            $model = Recommend::where('uniacid', $this->uniacid())->find($id);
            if (empty($model)) {
                throw new BadRequestException('数据不存在');
            }
            if ($idArray) {
                $models = Store::where('uniacid', $this->uniacid())
                    ->where('recommendId', $model->id)
                    ->whereIn('storeId', $idArray)->delete();
            }
            return $this->success([], '成功');
        } catch (\Exception $e) {
            return $this->failed('失败');
        }
    }
}
