<?php

namespace App\Http\Controllers\Channel\Drinks;

use App\Http\Controllers\Channel\ApiController;
use App\Http\Requests\Admin\ChangePassword;
use App\Models\Ad;
use App\Models\Drinks\Drinks;
use App\Models\Drinks\Storage as DrinksStorage;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class StorageController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $list = DrinksStorage::where('uniacid', $this->uniacid())
            ->when($request->name, function ($q) use ($request) {
                return $q->where("name", "like", "%{$request->name}%");
            })
            ->orderBy('sort', 'asc')
            ->orderBy('id', 'desc')
            ->paginate(Request()->pageSize ?? 20, '*', 'pageNo');
        return $this->success($list);
    }

    public function store(Request $request)
    {
        if(!$request->storeIds){
            return $this->failed('请选择分配门店');
        }
        try {
            $storage = new DrinksStorage();
            $storage->fill($request->all());
            $storage->uniacid = $this->uniacid();
            $storage->save();
            foreach ($storage->storeIds as $key => $storeId) {
                $model = new Drinks();
                $model->fill($request->all());
                $model->uniacid = $this->uniacid();
                $model->storeId = $storeId;
                $model->save();
            }
            return $this->success([], '添加成功');
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
            $models = DrinksStorage::where('uniacid', $this->uniacid())->whereIn('id', $idArray)->get();
            foreach ($models as $key => $model) {
                $model->delete();
            }
            return $this->success([], '删除成功');
        } catch (\Exception $e) {
            return $this->failed('删除失败');
        }
    }
}
