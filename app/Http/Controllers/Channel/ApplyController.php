<?php

namespace App\Http\Controllers\Channel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Apply\ApplyRequest;
use App\Http\Resources\Admin\Apply\ApplyListCollection;
use App\Http\Resources\Admin\Apply\ApplyResoutces;
use App\Models\Admin\Advertisement;
use App\Models\BulkOrder;
use App\Models\OrderSummary;
use Illuminate\Http\Request;
use App\Models\Admin\Apply;
use App\Models\Admin;
use App\Models\Admin\ApplyTop;
use App\Models\ApplyPlugs;
use App\Models\Plug;
use App\Models\SmsAccount;
use App\Services\ConfigService;
use Illuminate\Bus\Batch;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Redis;

class ApplyController extends ApiController
{

    use SoftDeletes;
    //概况
    public function survey(Request $req, Apply $apply_model)
    {
        return $this->success();
    }
    public function index(Request $req, Apply $apply_model)
    {
        $user = $this->user();
        $topIds = ApplyTop::where('adminId', $this->userId())->orderBy('created_at', 'desc')->get();
        $topIds = collect($topIds)->pluck('uniacid')->all();
        $data = Apply::select(['apply.*'])->with([
            'admin' => function ($q) {
                return $q->select(['id', 'username', 'mobile', 'nickname']);
            }, 'muster',
            'adminTop' => function ($q) use ($user) {
                return $q->where('adminId', $user->id);
            }
        ])->when($req->keyword, function ($q) use ($req) {
            return $q->where(function ($q) use ($req) {
                return   $q->where('applyName', 'like', "%$req->keyword%");
            });
        })->when($req->status, function ($q) use ($req) {
            if ($req->status == 'normal') {
                $q->normal();
            };
            if ($req->status == 'audit') {
                $q->audit();
            }
            if ($req->status == 'pass') {
                $q->pass();
            }
            if ($req->status == 'rejected') {
                $q->rejected();
            }
            if ($req->status == 'black') {
                $q->black();
            }
            if ($req->status == 'overdue') {
                $q->overdue();
            }
            return $q;
        })->when($user, function ($q) use ($user) {
            if ($user->id == 1) {
                return $q;
            } elseif ($user->isAdmin == 1 && $user->id != 1) {
                return $q->where('createUserId', $user->id);
            }
            return $q->where('id', 0);
        })->leftJoin('apply_top', function ($join) use ($user) {
            return $join->on('apply_top.uniacid', '=', 'apply.id')
                ->where('apply_top.adminId', '=', $user->id);
        })
            ->groupBy('apply.id')
            ->when($topIds, function ($q) use ($topIds) {
                return $q->orderBy('apply_top.created_at', 'desc')->orderBy('apply.sort', 'asc')->orderBy('apply.id', 'desc');
            })->when(!$topIds, function ($q) {
                return $q->orderBy('apply.sort', 'asc')
                    ->orderBy('apply.id', 'desc');
            })
            ->paginate($req->pageSize ?? 30, '*', 'pageNo');
        $applyList=new ApplyListCollection($data);
        $advertisement=Advertisement::where('title','店铺到期提醒')->first();
        if($advertisement){
            if($applyList->total()==1){
                foreach ($applyList as $v){
                    $apply =  Apply::where('createUserId',$user->id)->first();
                    if($apply->timeType==2&&$apply->endTime<date('Y-m-d H:i:s',time())){
                        $advertisement->display=1;
                        $advertisement->save();
                    }
                }
            }else{
                $advertisement->display=2;
                $advertisement->save();
            }
        }
        return $this->success(new ApplyListCollection($data));
    }

    /**
     * 回收站
     */
    public function recycle(Request $req, Apply $apply_model)
    {
        $user = $this->user();
        $apply_model = Apply::onlyTrashed()->with(['admin' => function ($q) {
            return $q->select(['id', 'username', 'mobile']);
        }, 'muster'])->when($user, function ($q) use ($user) {
            if ($user->id == 1) {
                return $q;
            } elseif ($user->isAdmin == 1 && $user->id != 1) {
                return $q->where('createUserId', $user->id);
            }
            return $q->where('id', 0);
        });
        if (!empty($req->keyword)) {
            $admins = Admin::where(function ($q) use ($req) {
                $q->orWhere('mobile', 'like', "%$req->keyword%")
                    ->orWhere('username', 'like', "%$req->keyword%")
                    ->orWhere('nickname', 'like', "%$req->keyword%");
                return $q;
            })->get();
            if ($admins) {
                $adminids = collect($admins)->pluck('id');
            } else {
                $adminids = [];
            }
            $apply_model->where(function ($q) use ($req, $adminids) {
                return   $q->where('applyName', 'like', "%$req->keyword%")
                    ->orWhere(function ($q) use ($adminids) {
                        return $q->whereIn('createUserId', $adminids);
                    });
            });
        }
        if ($this->user()->role_id != 0) {
            $apply_model->where('createUserId', $this->user()->id);
        }
        if (!empty($req->startTime) && !empty($req->endTime)) {
            $apply_model->where('startTime', '>=', $req->startTime)
                ->where('endTime', '<=', $req->endTime);
        }
        $data = $apply_model->orderBy('deleted_at', 'desc')->paginate($req->pageSize ?? 30, '*', 'pageNo');
        return $this->success(new ApplyListCollection($data));
    }


    public function restore($id)
    {
        $idArray = array_filter(explode(',', $id), function ($item) {
            return is_numeric($item);
        });
        Apply::withTrashed()->whereIn('id', $idArray)->restore();
        return $this->success([], __('base.success'));
    }

    /**
     * 回收站删除
     */
    public function del(Request $request, $id)
    {
        $idArray = array_filter(explode(',', $id), function ($item) {
            return is_numeric($item);
        });
        Apply::withTrashed()->whereIn('id', $idArray)->forceDelete($idArray, true);
        return $this->success([], '删除成功');
    }

    /**
     * 创建
     */
    public function store(ApplyRequest $request)
    {
        DB::beginTransaction();
        try {
            $count = Apply::withTrashed()->count();
            $auth = getSysInfo();
            if ($auth['account_type'] == 2 && $auth['account_number'] <= $count) {
                return $this->failed('该平台的创建店铺数量已达到上限');
            }
            $admin = Admin::find($this->user()->id);
            if ($admin->createStoreNum > 0 && $admin->adminApply->count() >= $admin->createStoreNum) {
                return $this->failed('该账号的创建店铺数量已达到上限');
            }
            $config = ConfigService::getSystemSet('storeSetting');
            $apply_model = new Apply();
            $apply_model->applyName = $request->applyName;
            $apply_model->applyImage = $request->applyImage;
            $apply_model->notes = $request->notes ?: '';
            $apply_model->address = $request->address ?: "";
            $apply_model->status = $config->auditState == 1 ? 6 : 1;
            $apply_model->payChange = 1;
            $apply_model->createUserId = $this->userId();
            $apply_model->save();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return $this->failed(__($e->getMessage()));
        }
        return $this->success(new ApplyResoutces($apply_model), '店铺创建成功');
    }

    /**
     * 详情
     */
    public function show(Apply $apply_model, $id)
    {
        $info = $apply_model->find($id);
        return $this->success(new ApplyResoutces($info));
    }

    /**
     * 更新
     */
    public function update(ApplyRequest $request, Apply $apply_model, $id)
    {
        DB::beginTransaction();
        try {
            $admin = Admin::find(intval($request->createUserId ?: $this->user()->id));
            if ($admin->createStoreNum > 0 && $admin->adminApply->count() > $admin->createStoreNum) {
                return $this->failed($admin->username . __('base.apply_top'));
            }
            $apply_model = $apply_model->find($id);
            $apply_model->applyName = $request->applyName;
            $apply_model->applyImage = $request->applyImage;
            $apply_model->notes = $request->notes ?: '';
            $apply_model->address = $request->address ?: "";
            //$config = ConfigService::getSystemSet('storeSetting');
            $apply_model->status = $apply_model->status == 5 ? 6 : $apply_model->status;
            $apply_model->save();
            DB::commit();
            return $this->success([], __('base.success'));
        } catch (\Exception $e) {
            DB::rollback();
            return $this->failed(__($e->getMessage()));
        }
    }

    /**
     * 拉黑/洗白
     */
    public function state(Request $request, $id)
    {
        $apply_model = Apply::whereIn('status', [1, 2])->where('id', $id)->first();
        if (!$apply_model) {
            return $this->failed(__('base.status_error'));
        }
        $apply_model->status = $apply_model->status == 1 ? 2 : 1;
        $apply_model->save();
        return $this->success([], __('base.success'));
    }


    /**
     * 拉黑/洗白
     */
    public function top(Request $request, $id)
    {
        $model = ApplyTop::where('uniacid', $id)->where('adminId', $this->userId())->first();
        if ($model) {
            $model->delete();
        } else {
            ApplyTop::create([
                'uniacid' => $id,
                'adminId' => $this->userId()
            ]);
        }
        return $this->success([], __('base.success'));
    }

    /**
     * Remove the specified resource from storage.
     *  删除
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, Apply $model)
    {
        $idArray = array_filter(explode(',', $id), function ($item) {
            return is_numeric($item);
        });
        $model->destroy($idArray);
        return $this->success([], '删除成功');
    }

    public function plugins()
    {
        $id = $this->uniacid();
        $applyPlugs = ApplyPlugs::with('plug')
            ->where('state', 1)
            ->whereHas('plug', function ($q) {
                return $q->where('status', 1);
            })
            ->when($this->isolate(),function($q){
                return $q->whereHas('plug',function($q){
                    return $q->whereNotIn('appName',['mini','alipay','wechat','xcxcjzs','cjhb']);
                });
            })
            ->where('uniacid', $this->uniacid())
            ->get();
        return $this->success(collect($applyPlugs)->pluck('plug')->pluck('appName')->all(), '成功');
    }
}
