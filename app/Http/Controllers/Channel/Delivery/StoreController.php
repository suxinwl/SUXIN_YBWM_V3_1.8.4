<?php

namespace App\Http\Controllers\Channel\Delivery;

use App\Http\Controllers\Channel\ApiController;
use App\Models\Delivery\Rule;
use App\Models\Delivery\Store;
use App\Models\GoodsRecommend\Goods;
use App\Models\GoodsRecommend\Recommend;
use App\Models\GoodsSku;
use App\Models\Recipe\RecipeCategory;
use App\Models\Recipe\Recipe;
use App\Models\Recipe\RecipeGoods;
use App\Models\Recipe\RecipeGoodsSku;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class StoreController extends ApiController
{

    public function index(Request $request)
    {
        $storeId = $this->storeId();
        $user = $this->user();
        $list = Store::select(['id', 'name', 'storeId', 'ruleId', 'deliveryType', 'updated_at'])->with(['store' => function ($q) {
            return $q->select(['id', 'name']);
        }, 'rule' => function ($q) {
            return $q->select(['id', 'name']);
        }])->where('uniacid', $this->uniacid())
            ->when($request->name, function ($q) use ($request) {
                return $q->where('name', 'like', "%$request->name%");
            })
            ->when($storeId, function ($q) use ($storeId) {
                return $q->where('storeId', $storeId);
            })->when($user, function ($q) use ($user) {
                if ($user->isAdmin == 0) {
                    if (empty($user->storeId)) {
                        $q->where('storeId', 0);
                    } else {
                        $q->whereIn('storeId', $user->storeId);
                    }
                }
                return $q;
            })
            ->paginate($request->pageSize ?? 20, '*', 'pageNo');
        return $this->success($list);
    }

    public function show(Request $request, $id)
    {
        try {
            $storeId = $this->storeId();
            $model = Store::with(['store' => function ($q) {
                return $q->select(["id", 'name', 'address', 'lat', 'lng']);
            }])->where('uniacid', $this->uniacid())
                ->where(function ($q) use ($storeId, $id) {
                    return $q->where('storeId', $storeId)->orWhere('id', $id);
                })
                ->first();
            if (!$model) {
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
            $model = Store::where('uniacid', $this->uniacid())->find($id);
            if (!$model) {
                return $this->failed('数据不存在');
            }
            $model->fill($request->all());
            $model->uniacid = $this->uniacid();
            $model->save();
            return $this->success([], '更新成功');
        } catch (\Exception $e) {
            return $this->failed('更新失败');
        }
    }
}
