<?php

namespace App\Http\Controllers\Channel\Drinks;

use App\Http\Controllers\Channel\ApiController;
use App\Http\Requests\Admin\ChangePassword;
use App\Models\Ad;
use App\Models\Drinks\Drinks;
use App\Models\Drinks\DrinksBase;
use App\Models\Drinks\Storage;
use App\Models\Printer;
use App\Models\PrinterLog;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class DrinksController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $storeId = $this->storeId();
        $user = $this->user();
        $list = Drinks::where('uniacid', $this->uniacid())->orderBy('sort', 'asc')
            ->when($request->name, function ($q) use ($request) {
                return $q->where("name", "like", "%{$request->name}%");
            })->when($storeId, function ($q) use ($storeId) {
                return $q->where('storeId', $storeId);
            })
            ->when($request->name, function ($q) use ($request) {
                return $q->where("name", "like", "%{$request->name}%");
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
            ->orderBy('id', 'desc')
            ->paginate(Request()->pageSize ?? 20, '*', 'pageNo');
        return $this->success($list);
    }

    public function store(Request $request)
    {
        try {
            $model = new Drinks();
            $model->fill($request->all());
            $model->uniacid = $this->uniacid();
            $model->storeId = $this->storeId();
            $model->save();
            if($model->storeId){
                Storage::create(
                    [
                        'sort'=>$request->sort,
                        'uniacid'=>$this->uniacid(),
                        'storeIds'=>[$this->storeId()],
                        'name'=>$request->name,
                        'logo'=>$request->logo,
                        'unit'=>$request->unit,
                        'day'=>$request->day,
                        'desc'=>$request->desc,
                    ]
                );
            }
            return $this->success([], '添加成功');
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }

    public function show(Request $request, $id)
    {
        try {
            $model = Drinks::where("uniacid", $this->uniacid())->find($id);
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
            $model = Drinks::where("uniacid", $this->uniacid())->find($id);
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

    /**
     * 拉黑/洗白
     */
    public function state(Request $request, $id)
    {
        $model = Drinks::where('id', $id)->first();
        if (!$model) {
            return $this->failed(__('base.status_error'));
        }
        $model->state = $model->state == 0 ? 1 : 0;
        $model->save();
        return $this->success([]);
    }

    public function destroy(Request $request, $id)
    {
        try {
            $idArray = array_filter(explode(',', $id), function ($item) {
                return is_numeric($item);
            });
            $models = Drinks::where('uniacid', $this->uniacid())->whereIn('id', $idArray)->get();
            foreach ($models as $key => $model) {
                $model->delete();
            }
            return $this->success([], '删除成功');
        } catch (\Exception $e) {
            return $this->failed('删除失败');
        }
    }

    public function statistics(Request $request)
    {
        $storeId=$this->storeId();
        $list = Drinks::where('uniacid', $this->uniacid())
            ->where('storeId',$storeId)
            ->orderBy('sort', 'asc')
            ->orderBy('id', 'desc')
            ->paginate(10, '*', 'pageNo');
        return $this->success($list);
    }
    //打印取酒订单
    public function printDrinksOrder(Request $request)
    {
        $id = $request->id;
        $type = $request->type ?: 1;
        if ($type == 1) {
            $drinkLog = \App\Models\Drinks\Order::where('uniacid', $this->uniacid())
                ->with([
                    'user', 'drink', 'store'
                ])
                ->find($id);
        }
        if ($type == 2) {
            $drinkLog = \App\Models\Drinks\Log::where('uniacid', $this->uniacid())
                ->with([
                    'user', 'drink', 'store'
                ])
                ->find($id);
        }
        OrderService::otherPrintOrder(3,$drinkLog);
        return $this->success([], '已发送打印指令');
    }
}
