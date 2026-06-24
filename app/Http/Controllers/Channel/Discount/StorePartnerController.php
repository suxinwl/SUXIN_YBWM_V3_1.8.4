<?php

namespace App\Http\Controllers\Channel\Discount;

use App\Http\Controllers\Channel\ApiController;
use App\Http\Requests\Admin\ChangePassword;
use App\Http\Resources\ChannelApi\User\Profix;
use App\Models\Ad;
use App\Models\Admin;
use App\Models\Member;
use App\Models\Member\Address;
use App\Models\MemberAddress;
use App\Models\MemberBind;
use App\Models\StorePartner;
use App\Models\StorePartnerOrder;
use App\Models\UserAccount;
use App\Services\ConfigService;
use App\Services\MenuService;
use App\Services\UserService;
use Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Tymon\JWTAuth\Facades\JWTAuth;
use DB;
class StorePartnerController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $timeArr = $this->timeArr(true);
        $model = StorePartner::where('uniacid', $this->uniacid())
            ->when($request->state, function ($q) use ($request) {
                if ($request->state == 'on') {
                    return $q->where('state', 1);
                } else {
                    return $q->where('state', 0);
                }
            })
            ->when($request->startTime, function ($q) use ($request) {
                return $q->where("created_at", ">=", $request->startTime);
            })
            ->when($request->timeType, function ($q) use ($request, $timeArr) {
                return $q->where('created_at', '>=', $timeArr['startTime'])
                    ->where('created_at', '<=', $timeArr['endTime']);
            })
            ->when($request->endTime, function ($q) use ($request) {
                return $q->where("created_at", "<=", $request->endTime);
            })->when($request->keyword, function ($q) use ($request) {
                return $q->whereHas('user', function ($q) use ($request) {
                    return $q->where(function ($q) use ($request) {
                        return $q->where('mobile', 'like', "%$request->keyword%")
                            ->orWhere('nickname', 'like', "%$request->keyword%")
                            ->orWhere('realname', 'like', "%$request->keyword%");
                    });
                });
            })
            ->paginate(Request()->pageSize ?? 20, '*', 'pageNo');
        return $this->success($model);
    }

    public  function auth(Request $request, $id)
    {
        $model = Partner::where('uniacid', $this->uniacid())
            ->where('state', 0)
            ->find($id);
        if (!$model) {
            return $this->failed('数据不存在');
        }
        $model->state = 1;
        $model->save();
        return $this->success([], '操作成功');
    }


    public  function refuse(Request $request, $id)
    {
        $model = Partner::where('uniacid', $this->uniacid())
            ->where('state', 0)
            ->find($id);
        if (!$model) {
            return $this->failed('数据不存在');
        }
        $model->state = 2;
        $model->save();
        return $this->success([], '操作成功');
    }

    public function destroy(Request $request, $id)
    {
        try {
            $idArray = array_filter(explode(',', $id), function ($item) {
                return is_numeric($item);
            });
            $models = Partner::where('uniacid', $this->uniacid())->whereIn('id', $idArray)->get();
            foreach ($models as $key => $model) {
                $model->delete();
            }
            return $this->success([], '删除成功');
        } catch (\Exception $e) {
            return $this->failed('删除失败');
        }
    }

    public function order(Request $request)
    {
        $timeArr = $this->timeArr(true);
        $storeId = $this->storeId();
        $model = StorePartnerOrder::with('store')->where('state', ">", 1)
            ->where('uniacid', $this->uniacid())
            ->when($request->startTime, function ($q) use ($request) {
                return $q->where("created_at", ">=", $request->startTime);
            })
            ->when($request->timeType, function ($q) use ($request, $timeArr) {
                return $q->where('created_at', '>=', $timeArr['startTime'])
                    ->where('created_at', '<=', $timeArr['endTime']);
            })
            ->when(isset($request->type) && $request->type != '' ,function($q)use($request){
                return $q->where('type', $request->type);
            })
            ->when($request->endTime, function ($q) use ($request) {
                return $q->where("created_at", "<=", $request->endTime);
            })->when($request->storeId, function ($q) use ($storeId) {
                return $q->whereHas('store', function ($q) use ($storeId) {
                    return $q->where('storeId', $storeId);
                });
            })->orderBy('id', 'desc')->paginate(Request()->pageSize ?? 20, '*', 'pageNo');
        $model=$model->toArray();
        $query= StorePartnerOrder::with('store')->where('state', ">", 1)
            ->where('uniacid', $this->uniacid())
            ->when($request->startTime, function ($q) use ($request) {
                return $q->where("created_at", ">=", $request->startTime);
            })
            ->when($request->timeType, function ($q) use ($request, $timeArr) {
                return $q->where('created_at', '>=', $timeArr['startTime'])
                    ->where('created_at', '<=', $timeArr['endTime']);
            })
            ->when($request->endTime, function ($q) use ($request) {
                return $q->where("created_at", "<=", $request->endTime);
            })->when($request->storeId, function ($q) use ($storeId) {
                return $q->whereHas('store', function ($q) use ($storeId) {
                    return $q->where('storeId', $storeId);
                });
            });

        $model['income']=StorePartnerOrder::with('store')->where('state', ">", 1)
            ->where('uniacid', $this->uniacid())
            ->when($request->startTime, function ($q) use ($request) {
                return $q->where("created_at", ">=", $request->startTime);
            })
            ->when($request->timeType, function ($q) use ($request, $timeArr) {
                return $q->where('created_at', '>=', $timeArr['startTime'])
                    ->where('created_at', '<=', $timeArr['endTime']);
            })
            ->when($request->endTime, function ($q) use ($request) {
                return $q->where("created_at", "<=", $request->endTime);
            })->when($request->storeId, function ($q) use ($storeId) {
                return $q->whereHas('store', function ($q) use ($storeId) {
                    return $q->where('storeId', $storeId);
                });
            })->where('type',1)->sum('money');
        $model['disburse']=StorePartnerOrder::with('store')->where('state', ">", 1)
            ->where('uniacid', $this->uniacid())
            ->when($request->startTime, function ($q) use ($request) {
                return $q->where("created_at", ">=", $request->startTime);
            })
            ->when($request->timeType, function ($q) use ($request, $timeArr) {
                return $q->where('created_at', '>=', $timeArr['startTime'])
                    ->where('created_at', '<=', $timeArr['endTime']);
            })
            ->when($request->endTime, function ($q) use ($request) {
                return $q->where("created_at", "<=", $request->endTime);
            })->when($request->storeId, function ($q) use ($storeId) {
                return $q->whereHas('store', function ($q) use ($storeId) {
                    return $q->where('storeId', $storeId);
                });
            })->where('type',0)->sum('money');

        $model['refund']=$query->when($request->storeId, function ($q) use ($storeId) {
            return $q->where('storeId', $storeId);
        })->where('isRefund',1)->sum('money');

        return $this->success($model);
    }

}
