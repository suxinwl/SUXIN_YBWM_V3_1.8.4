<?php

namespace App\Http\Controllers\Channel\Handover;

use App\Http\Controllers\Channel\ApiController;
use App\Http\Requests\Admin\ChangePassword;
use App\Models\Ad;
use App\Models\Handover\Handover;
use App\Models\Printer;
use App\Models\PrinterLog;
use App\Services\InStoreOrderService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class HandoverController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $model = Handover::where('uniacid', $this->uniacid())
            ->where('storeId', $this->storeId())
            ->where('adminId', $this->userId())
            ->where('state', 1)
            ->orderBy('id', 'desc')
            ->paginate(Request()->pageSize ?? 20, '*', 'pageNo');
        return $this->success($model);
    }

    public function store(Request $request)
    {
        $model = Handover::where('uniacid', $this->uniacid())
            ->where('storeId', $this->storeId())
            ->where('adminId', $this->userId())
            ->where('state', 0)
            ->first();
        if ($model) {
            return $this->failed('您还有未交班的记录');
        }
        $model = new Handover();
        $model->uniacid = $this->uniacid();
        $model->storeId = $this->storeId();
        $model->adminId = $this->userId();
        $model->startTime = date("Y-m-d H:i:s", time());
        $model->state = 0;
        $model->save();
        return $this->success($model, '开班成功');
    }

    public function show(Request $request, $id)
    {
        $model = Handover::where('uniacid', $this->uniacid())->find($id);

        if (empty($model)) {
            return $this->failed('数据不存在');
        }
        $model->setAppends(['contents']);
        return $this->success($model);
    }

    public function update(Request $request, $id)
    {
        $model = Handover::where('uniacid', $this->uniacid())
            ->where('storeId', $this->storeId())
            ->where('adminId', $this->userId())
            ->where('state', 0)
            ->find($id);
        if (!$model) {
            return $this->failed('当前班次已经交班');
        }

        $model->endTime = date("Y-m-d H:i:s", time());
        $model->contents = collect($model->contents)->toArray();
        $model->state = 1;
        $model->save();
        InStoreOrderService::print($id, 11);
        return $this->success($model, '交班成功');
    }

    public function destroy(Request $request, $id)
    {
    }

    public function starting(Request $request)
    {
        $model = Handover::where('uniacid', $this->uniacid())
            ->where('storeId', $this->storeId())
            ->where('adminId', $this->userId())
            ->where('state', 0)
            ->first();
        return $this->success($model);
    }
}
