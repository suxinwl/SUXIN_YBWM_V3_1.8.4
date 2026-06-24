<?php

namespace App\Http\Controllers\Channel\GoodsRecommend;

use App\Http\Controllers\Channel\ApiController;
use App\Models\GoodsRecommend\Goods;
use App\Models\GoodsRecommend\Recommend;
use App\Models\GoodsSku;
use App\Models\Recipe\RecipeCategory;
use App\Models\Recipe\Recipe;
use App\Models\Recipe\RecipeGoods;
use App\Models\Recipe\RecipeGoodsSku;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class GoodsController extends ApiController
{

    public function index(Request $request, $id, $type)
    {
        $recipe = Recommend::where('uniacid', $this->uniacid())->find($id);
        if (empty($recipe)) {
            throw new BadRequestException('数据不存在');
        }
        $list = Goods::with(['spu'])->where('uniacid', $this->uniacid())
            ->where('recommendId', $id)
            ->where('type', $type ?? 0)
            ->when($request->name, function ($q) use ($request) {
                return $q->whereHas('spu', function ($q) use ($request) {
                    return $q->where('name', 'like', "%$request->name%");
                });
            })
            ->paginate($request->pageSize ?? 20, '*', 'pageNo');
        return $this->success($list);
    }



    public function store(Request $request, $id, $type)
    {
        $recipe = Recommend::where('uniacid', $this->uniacid())->find($id);
        if (empty($recipe)) {
            throw new BadRequestException('数据不存在');
        }
        $ids = collect($recipe->goods()->where('type', $type)->get())->pluck('spuId')->all();
        $goodsIds = $request->goodsIds ?? [];
        $ids = array_diff($goodsIds, array_intersect($goodsIds, $ids));
        if ($ids) {
            foreach ($ids as $key => $spuId) {
                $data[] = new Goods([
                    'uniacid' => $recipe->uniacid,
                    'spuId' => $spuId,
                    'type' => $type
                ]);
            }
        }
        $recipe->goods()->saveMany($data);
        return $this->success();
    }

    public function destroy(Request $request, $id, $type)
    {
        try {
            $models = Recommend::where('uniacid', $this->uniacid())->find($id);
            if (empty($models)) {
                throw new BadRequestException('当前模板不存在');
            }
            $idArray = $request->goodsIds;
            $list = Goods::where('uniacid', $models->uniacid)
            ->where('recommendId', $models->id)
            ->whereIn('spuId', $idArray)
            ->where("type", $type)
            ->get();
            foreach ($list as $key => $model) {
                $model->forceDelete();
            }
            return $this->success([], '成功');
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }
}
