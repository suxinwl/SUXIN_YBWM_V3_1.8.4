<?php

namespace App\Http\Controllers\Channel\GoodsRecommend;

use App\Http\Controllers\Channel\ApiController;
use App\Models\GoodsRecommend\Recommend;
use App\Models\Recipe\Category;
use App\Models\Recipe\InstoreGoods;
use App\Models\Recipe\Recipe;
use App\Models\Recipe\RecipeSpu;
use App\Models\Recipe\TakeoutGoods;
use App\Models\Recipe\TakeoutSku;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class RecommendController extends ApiController
{
    public function index(Request $request)
    {
        $list = Recommend::where('uniacid', $this->uniacid())
            ->when($request->name, function ($q) use ($request) {
                return $q->where('name', 'like', "%$request->name%");
            })
            ->orderBy('sort', 'asc')->orderBy('id', 'desc')->paginate($request->pageSize ?? 20, '*', 'pageNo');
        return $this->success($list);
    }

    public function store(Request $request)
    {
        $model = new Recommend();
        $model->fill($request->all());
        $model->uniacid = $this->uniacid();
        $model->save();
        return $this->success();
    }

    public function show(Request $request, $id)
    {
        $model = Recommend::where('uniacid', $this->uniacid())->find($id);
        if (empty($model)) {
            throw new BadRequestException('数据不存在');
        }
        return $this->success($model);
    }

    public function update(Request $request, $id)
    {
        $model = Recommend::where('uniacid', $this->uniacid())->find($id);
        if (empty($model)) {
            throw new BadRequestException('数据不存在');
        }
        $model->fill($request->all());
        $model->uniacid = $this->uniacid();
        $model->save();
        return $this->success();
    }

    public function destroy(Request $request, $id)
    {
        try {
            $idArray = array_filter(explode(',', $id), function ($item) {
                return is_numeric($item);
            });
            $models = Recommend::where('uniacid', $this->uniacid())->whereIn('id', $idArray)->get();
            foreach ($models as $key => $model) {
                $model->delete();
            }
            return $this->success([], '成功');
        } catch (\Exception $e) {
            return $this->failed('失败');
        }
    }

    public function state(Request $request, $id)
    {
        $model = Recommend::where('uniacid', $this->uniacid())->find($id);
        if (empty($model)) {
            throw new BadRequestException('数据不存在');
        }
        $model->state = intval(!$model->state);
        $model->save();
        return $this->success([], '操作成功');
    }
}
