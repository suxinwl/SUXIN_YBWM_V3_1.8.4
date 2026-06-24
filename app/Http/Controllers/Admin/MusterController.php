<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\ApiController;
use App\Http\Controllers\Controller as BaseController;
use App\Http\Helpers\ApiResponse;
use App\Http\Requests\Admin\MusterRequest;
use App\Http\Resources\Admin\Muster\MusterListCollection;
use App\Models\Setmeal;
use App\Models\Wechat\Kernel\Exceptions\BadRequestException;
use Illuminate\Http\Request;

class MusterController extends ApiController
{
    public function index(Request $req, Setmeal $model)
    {
        $model = $model->withCount(['apply']);
        if (!empty($req->keyword)) {
            $model = $model->where('title', 'like', '%' . $req->keyword . '%');
        }
        if ($req->state == 'pass') {
            $model = $model->where('state', 1);
        }
        if (!empty($req->startTime) && !empty($req->endTime)) {
            $model  = $model->where('created_at', '>=', $req->startTime)
                ->where('created_at', '<=', $req->endTime);
        }
        $list = $model->orderBy('type', 'desc')->orderBy('sort', 'asc')->paginate($req->pageSize ?? 30);
        return $this->success(new MusterListCollection($list));
    }

    public function store(MusterRequest $req, Setmeal $musterModel)
    {
        if ($req->type == 1) {
            $model = Setmeal::where('type', 1)->first();
            if ($model) {
                return $this->failed('体验套餐创建数量已达上限');
            }
        }
        Setmeal::create($req->all());
        return $this->success([]);
    }



    public function show($id)
    {
        return $this->success(Setmeal::withCount(['apply'])->find($id));
    }

    public function update($id, MusterRequest $req, Setmeal $model)
    {
        $model = Setmeal::find($id);
        if (empty($model)) {
            return $this->failed('数据不存在');
        }
        $data = $req->all();
        unset($data['apply_count']);
        $model->fill($data);
        $model->save();
        return $this->success([]);
    }

    /**
     * Remove the specified resource from storage.
     *  删除
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Setmeal $model, $id)
    {
        $idArray = array_filter(explode(',', $id), function ($item) {
            return is_numeric($item);
        });
        foreach ($idArray as $v) {
            $data = $model->find($v);
            if (!empty($data->apply->toarray())) {
                throw new BadRequestException('当前套餐下已有平台，无法删除');
            }
            $data->destroy($idArray);
        }
        return $this->success([], __('base.success'));
    }
}
