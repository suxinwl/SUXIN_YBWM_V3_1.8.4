<?php

namespace App\Http\Controllers\Channel\Prints;

use App\Http\Controllers\Channel\ApiController;
use App\Models\PrintRule;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Reader\Xls\MD5;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class RuleController extends ApiController
{


    public function store(Request $request, $id)
    {
        try {
            $model = new PrintRule();
            $model->uniacid = $this->uniacid();
            $model->storeId = $this->storeId();
            $model->type = $request->type;
            $model->config = $request->config;
            $model->name = $request->name;
            $model->scene= $request->scene;
            $model->printId= $request->pid;
            $model->md5Str= 1;
            $model->save();
            return $this->success([], '添加成功');
        } catch (\Exception $e) {
            return $this->failed('添加失败');
        }
    }

    public function show(Request $request, $id)
    {
        try {
            $model = PrintRule::where('uniacid', $this->uniacid())
                ->where('printId', $id)
                ->first();
            return $this->success($model);
        } catch (\Exception $e) {
            return $this->failed('失败');
        }
    }



    public function update(Request $request, $id)
    {
        try {
            $model = PrintRule::where('uniacid', $this->uniacid())->find($id);
            if (!$model) {
                return $this->failed('数据不存在');
            }
            $model->fill($request->all());
            $model->storeId = $this->storeId();
            $model->save();
            return $this->success([], '更新成功');
        } catch (\Exception $e) {
            return $this->failed('更新失败');
        }
    }
}
