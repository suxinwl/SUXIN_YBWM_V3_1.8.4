<?php

namespace App\Http\Controllers\Channel\Drinks;

use App\Http\Controllers\Channel\ApiController;
use App\Http\Requests\Admin\ChangePassword;
use App\Models\Ad;
use App\Models\Drinks\Drinks;
use App\Models\Drinks\Log;
use App\Models\Drinks\Order;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class LogController extends ApiController
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
        $list = Log::where('uniacid', $this->uniacid())->with(['user', 'drink', 'order'])
            ->when($request->contacts, function ($q) use ($request) {
                return $q->where("contacts", "like", "%{$request->name}%");
            })->when($storeId, function ($q) use ($storeId) {
                return $q->where('storeId', $storeId);
            })
            ->when($request->mobile, function ($q) use ($request) {
                return $q->where("mobile", "like", "%{$request->mobile}%");
            })->when($user, function ($q) use ($user) {
                if ($user->isAdmin == 0) {
                    if (empty($user->storeId)) {
                        $q->where('storeId', 0);
                    } else {
                        $q->whereIn('storeId', $user->storeId);
                    }
                }
                return $q;
            })->when($request->state, function ($q) use ($request) {
                if ($request->state == 'deposit') {
                    return $q->where('type', 1);
                } elseif ($request->state == 'fetch') {
                    return $q->where('type', 2);
                } else {
                    return $q;
                }
            })->when($request->drinksOrderId, function ($q) use ($request) {
                return $q->where('drinksOrderId', $request->drinksOrderId);
            })
            ->orderBy('id', 'desc')
            ->paginate(Request()->pageSize ?? 20, '*', 'pageNo');
        return $this->success($list);
    }

    public function store(Request $request)
    {
        try {
            $model = new Log();
            $model->fill($request->all());
            $model->uniacid = $this->uniacid();
            $model->storeId = $this->storeId();
            $model->type = 2;
            $model->score = $this->appType();
            $model->adminId = $this->userId();
            if ($model->order->state == 2) {
                return $this->failed('该存酒批次已过期');
            }
            if ($model->order->residue < $model->num) {
                return $this->failed('取酒数量超出可取数量');
            }
            $model->order->residue = $model->order->residue - $model->num;
            if ($model->order->residue == 0) {
                $model->order->state = 2;
            }
            $model->residue = $model->order->residue;
            $model->save();
            $model->order->save();
            return $this->success([], '取酒成功');
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }

    public function pass(Request $request, $id)
    {
        $model = Log::where('uniacid', $this->uniacid())
            ->where('storeId', $this->storeId())
            ->find($id);
        if (!$model) {
            return $this->failed('数据不存在');
        }
        if ($model->order->state == 2) {
            return $this->failed('该存酒批次已过期');
        }
        if ($model->order->residue < $model->num) {
            return $this->failed('取酒数量超出可取数量');
        }
        $model->order->residue = $model->order->residue - $model->num;
        if ($model->order->residue == 0) {
            $model->order->state = 2;
        }
        $model->residue = $model->order->residue;
        $model->state = 1;
        $model->save();
        $model->order->save();
        return $this->success([], '操作成功');
    }
    public function refuse(Request $request, $id)
    {
        $model = Log::where('uniacid', $this->uniacid())
            ->where('storeId', $this->storeId())
            ->find($id);
        if (!$model) {
            return $this->failed('数据不存在');
        }
        $model->residue = $model->order->residue;
        $model->state = 2;
        $model->save();
        $model->order->save();
        return $this->success([], '操作成功');
    }
}
