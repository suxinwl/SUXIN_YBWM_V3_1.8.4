<?php

namespace App\Http\Controllers\Channel\Prints;

use App\Http\Controllers\Channel\ApiController;
use App\Models\Hardware;
use App\Models\Printer;
use App\Models\PrintRule;
use App\Models\Print\PrintTemplate;
use App\Models\Store;
use Http;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class PrintTemplateController extends ApiController
{
    public function store(Request $request)
    {
        try {
            $model = PrintTemplate::where('uniacid', $this->uniacid())
                ->where('storeId', $this->storeId())
                ->where('print_type', $request->print_type)
                ->first();
            if ($model) {
                return $this->failed('类型添加重复');
            }
            $model = new PrintTemplate();
            $model->fill($request->all());
            $model->uniacid = $this->uniacid();
            $model->storeId = $this->storeId();
            $model->save();
            return $this->success([], '添加成功');
        } catch (\Exception $e) {
            return $this->failed('添加失败');
        }
    }

    public function show(Request $request, $id)
    {
        $model = PrintTemplate::where('uniacid', $this->uniacid())
            ->where('storeId', $this->storeId())
            ->where('print_type', $id)
            ->first();
        return $this->success($model);
    }
    public function update(Request $request, $id)
    {
        try {
            $model = PrintTemplate::where('uniacid', $this->uniacid())
                ->where('storeId', $this->storeId())
                ->where('print_type', $id)
                ->first();
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

    //恢复默认模板
    public function restoreDefault(Request $request)
    {
        $model = PrintTemplate::where('uniacid', $this->uniacid())
            ->where('print_type', $request->id)
            ->where('storeId', $this->storeId())
            ->first();
        if ($model) {
            $model->delete();
        }
        return $this->success([], '更新成功');
    }
}
