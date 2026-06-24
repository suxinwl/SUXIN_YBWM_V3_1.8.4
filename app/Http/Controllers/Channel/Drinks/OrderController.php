<?php

namespace App\Http\Controllers\Channel\Drinks;

use App\Http\Controllers\Channel\ApiController;
use App\Http\Requests\Admin\ChangePassword;
use App\Models\Ad;
use App\Models\Drinks\Drinks;
use App\Models\Drinks\Log;
use App\Models\Drinks\Order;
use App\Models\Printer;
use App\Models\PrinterLog;
use App\Services\ExcelService;
use App\Services\OrderService;
use App\Services\Print\DaquContent;
use App\Services\Print\FeieContent;
use App\Services\Print\JiaboContent;
use App\Services\Print\SpyunContent;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class OrderController extends ApiController
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
        $isolate =  $this->isolate();
        $list = Order::where('uniacid', $this->uniacid())->with([
            'user', 'drink', 'store'
        ])
            ->when($request->keyword || $request->userKeyword, function ($q) use ($request) {
                $keyword = $request->keyword ?? $request->userKeyword;
                return $q->where(function ($q) use ($keyword) {
                    return $q->where("contacts", "like", "%{$keyword}%")
                        ->orWhere("mobile", "like", "%{$keyword}%")
                        ->orWhere("orderSn", "like", "%{$keyword}%")
                        ->orWhere(function ($q) use ($keyword) {
                            return $q->whereHas('drink', function ($q) use ($keyword) {
                                return $q->where('name', 'like', "%{$keyword}%");
                            });
                        });
                });
            })->when($storeId, function ($q) use ($storeId) {
                return $q->where('storeId', $storeId);
            })
            ->when($request->drink, function ($q) use ($request) {
                return $q->whereHas('drink', function ($q) use ($request) {
                    return $q->where('name', 'like', "%{$request->drink}%");
                });
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
                if ($request->state == 'start') {
                    return $q->where('state', 1);
                } elseif ($request->state == 'expired') {
                    return $q->where('state', 3);
                } elseif ($request->state == 'over') {
                    return $q->where('state', 2);
                } else {
                    return $q;
                }
            })->when($request->startTime, function ($q) use ($request) {
                return $q->where("created_at", ">=", $request->startTime);
            })->when($request->endTime, function ($q) use ($request) {
                return $q->where("created_at", "<=", $request->endTime);
            })
            ->when($isolate, function ($q) use ($storeId, $isolate) {
                return $q->whereHas('store', function ($q) use ($storeId) {
                    return $q->where('isolate', 1)->where('storeId', $storeId);
                });
            })
            ->when(!$isolate, function ($q) use ($storeId, $isolate) {
                return $q->whereHas('store', function ($q) use ($storeId) {
                    return $q->where('isolate', 0)->when($storeId, function ($q) use ($storeId) {
                        return $q->where('storeId', $storeId);
                    });
                });
            })
            ->orderBy('id', 'desc');
        if ($request->export) {
            $list = $list->get();
            $header = [
                ['所属门店', 'store.name', 'text'],
                ['用户信息', 'userId', 'function', function ($model) {
                    return $model['user']['nickname'] . "({$model['user']['id']}) \r\n {$model['user']['mobile']}";
                }],
                ['酒品信息', 'drinkId', 'function', function ($model) {
                    $str = "名称：{$model['drink']['name']} \r\n";
                    $str .= "剩余数量：{$model['residue']}{$model['drink']['unit']} \r\n";
                    $str .= "存酒时间：{$model['created_at']} \r\n";
                    $str .= "到期时间：{$model['expiredTimeFormat']}";
                    return $str;
                }],
                ['变动时间', 'created_at', 'text'],
                ['操作人', 'admin.nickname', 'text'],
                ['状态', 'stateFormat', 'text'],
            ];
            return ExcelService::export($list, $header, '存酒记录.xls');
        }
        $lists = $list->paginate($request->pageSize ?? 20, '*', 'pageNo');
        return $this->success($lists);
    }

    public function show(Request $request, $id)
    {
        $model = Order::where('uniacid', $this->uniacid())
            ->with([
                'user', 'drink', 'store'
            ])
            ->find($id);
        if (!$model) {
            return $this->failed('数据不存在');
        }
        return $this->success($model);
    }

    public function store(Request $request)
    {
        try {
            $model = new Order();
            $model->fill($request->all());
            $model->uniacid = $this->uniacid();
            $model->storeId = $this->storeId();
            $model->score = $this->appType();
            $model->adminId = $this->userId();
            $model->save();
            Log::create([
                'uniacid' => $model->uniacid,
                'storeId' => $model->storeId,
                'userId' => $model->userId,
                'drinksId' => $model->drinksId,
                'drinksOrderId' => $model->id,
                'num' => $model->num,
                'score' => $this->appType(),
                'adminId' => $this->userId(),
                'type' => 1,
                'state' => 1,
                'residue' => $model->num,
                'orderSn' => $model->orderSn,
                'notes' => null
            ]);
            $drinkLog = Log::where('orderSn', $model->orderSn)->first();
            OrderService::otherPrintOrder(3, $drinkLog);
            return $this->success([], '存酒成功');
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }
}
