<?php

namespace App\Http\Controllers\Channel\Table;

use App\Http\Controllers\Channel\ApiController;
use App\Http\Requests\Admin\ChangePassword;
use App\Models\Ad;
use App\Models\Tables\Area;
use App\Models\Tables\Table;
use App\Models\Tables\Type;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class TypeController extends ApiController

{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $list = Type::where('uniacid', $this->uniacid())
            ->where('storeId', $this->storeId())
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
        try {
            $model = new Type();
            $model->fill($request->all());
            $model->uniacid = $this->uniacid();
            $model->save();
            return $this->success([], '添加成功');
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }

    public function show(Request $request, $id)
    {
        try {
            $model = Type::where("uniacid", $this->uniacid())->find($id);
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
            $model = Type::where("uniacid", $this->uniacid())->find($id);
            if (empty($model)) {
                return $this->failed('数据不存在');
            }
            $model->fill($request->all());
            $model->uniacid = $this->uniacid();
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
            $models = Type::where('uniacid', $this->uniacid())->whereIn('id', $idArray)->get();
            foreach ($models as $key => $model) {
                $model->delete();
            }
            return $this->success([], '删除成功');
        } catch (\Exception $e) {
            return $this->failed('删除失败');
        }
    }
}
