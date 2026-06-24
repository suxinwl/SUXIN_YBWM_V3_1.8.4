<?php

namespace App\Http\Controllers\Admin;

use App\Events\ApplyEvent;
use App\Http\Requests\Apply\ApplyRequest;
use App\Http\Resources\Admin\Apply\ApplyListCollection;
use App\Http\Resources\Admin\Apply\ApplyResoutces;
use Illuminate\Http\Request;
use App\Models\Admin\Apply;
use App\Models\Admin;
use App\Models\Admin\Index;
use App\Models\ApplyPlugs;
use App\Models\Plug;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class ApplyController extends ApiController
{
    use SoftDeletes;
    //概况
    public function survey(Request $req, Apply $apply_model)
    {
        $model = new Index();
        return $this->success($model);
    }
    public function index(Request $req, Apply $apply_model)
    {
        $apply_model = Apply::with([
            'admin' => function ($q) {
                return $q->select(['id', 'username', 'mobile', 'nickname']);
            }, 'muster'
        ]);
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
        if (!empty($req->musterId)) {
            $apply_model->where('musterId', $req->musterId);
        }
        $apply_model->when($req->status, function ($q) use ($req) {
            if ($req->status == 'normal') {
                $q->normal(); //正常
            };
            if ($req->status == 'audit') {
                $q->audit(); //待审核
            }
            if ($req->status == 'pass') {
                $q->pass(); //审核通过
            }
            if ($req->status == 'rejected') {
                $q->rejected(); //审核驳回
            }
            if ($req->status == 'black') {
                $q->black(); //黑名单
            }
            if ($req->status == 'overdue') {
                $q->overdue(); //过期
            }
            return $q;
        });

        if ($req->experience) {
            $apply_model->whereHas('muster', function ($q) {
                return $q->where('type', 1);
            });
        }

        if (!empty($req->startTime) && !empty($req->endTime)) {
            $apply_model->where('endTime', '>=', $req->startTime)
                ->where('endTime', '<=', $req->endTime);
        }
        $data = $apply_model->groupBy('id')->orderBy('sort', 'asc')->orderBy('id', 'desc')->paginate($req->pageSize ?? 30, '*', 'pageNo');
        return $this->success(new ApplyListCollection($data));
    }


    /**
     * 回收站
     */
    public function recycle(Request $req, Apply $apply_model)
    {
        $apply_model = Apply::onlyTrashed()->with(['admin' => function ($q) {
            return $q->select(['id', 'username', 'mobile']);
        }])->select('apply.*')->leftJoin('admins as ab', 'ab.id', '=', 'apply.createUserId')
            ->leftJoin('admins as ac', 'ac.uniacid', '=', 'apply.id');
        if (!empty($req->keyword)) {
            $apply_model->where(function ($q) use ($req) {
                return   $q->where('apply.applyName', 'like', "%$req->keyword%")
                    ->orWhere('ab.mobile', 'like', "%$req->keyword%")
                    ->orWhere('ab.username', 'like', "%$req->keyword%")
                    ->orWhere('ac.mobile', 'like', "%$req->keyword%")
                    ->orWhere('ac.username', 'like', "%$req->keyword%");
            });
        }
        if ($this->user()->role_id != 0) {
            $apply_model->where('apply.createUserId', $this->user()->id);
        }
        if (!empty($req->startTime) && !empty($req->endTime)) {
            $apply_model->where('apply.startTime', '>=', $req->startTime)
                ->where('apply.endTime', '<=', $req->endTime);
        }
        $data = $apply_model->orderBy('apply.deleted_at', 'desc')
            ->groupBy('id')
            ->paginate($req->pageSize ?? 30, '*', 'pageNo');
        return $this->success($data);
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
        Apply::withTrashed()->whereIn('id', $idArray)->forceDelete();
        return $this->success([], __('base.success'));
    }

    /**
     * 创建
     */
    public function store(ApplyRequest $request, Apply $apply_model)
    {
        DB::beginTransaction();
        try {
            $count = Apply::withTrashed()->count();
            $auth = getSysInfo();
            if ($auth['account_type'] == 2 && $auth['account_number'] <= $count) {
                return $this->failed('平台创建数量已达到上限');
            }
            $apply_model->applyName = $request->applyName;
            $apply_model->applyImage = $request->applyImage;
            $apply_model->musterId = $request->musterId;
            $apply_model->storeNum = $apply_model->master->storeNum;
            $apply_model->storeNumInfinite =  0;
            $apply_model->sort = $request->sort ?: 0;
            $apply_model->applyType = 0;
            $apply_model->createCount = 0;
            $apply_model->payChange = 1;
            $apply_model->startTime = $request->timeType == 1 ? date('Y-m-d H:i:s', time()) : $request->startTime;
            $apply_model->endTime = $request->timeType == 1 ? date('Y-m-d H:i:s', strtotime('2099-01-01')) : $request->endTime;
            $apply_model->status = $request->status ?: 1;
            $apply_model->day = 0;
            $apply_model->type = $request->type ?: 1;
            $apply_model->storeNum = $request->storeNum ?: $apply_model->muster->storeNum ?: 0;
            $apply_model->adminId = 0;
            $apply_model->plugType = 2;
            $apply_model->plugStr = [
                'channel' => [],
                'pluges' => [],
                'service' => []
            ];
            $apply_model->timeType = $request->timeType;
            $apply_model->createUserId = empty($request->createUserId) ? $this->user()->id : $request->createUserId;
            $apply_model->notes = $request->notes ?: '';
            // $apply_model->copyrightSwitch = $request->copyrightSwitch;
            // $apply_model->copyright = $request->copyright;
            // $apply_model->attachmentType = $request->attachmentType;
            // $apply_model->attachmentData = $request->attachmentData;
            // $apply_model->address = $request->address;
            $apply_model->save();
            $apply_model->refresh();
            $apply_model->refreshPlugs();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return $this->failed(__($e->getMessage()));
        }
        return $this->success([], __('base.success'));
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
    public function update(Request $request, Apply $apply_model, $id)
    {
        DB::beginTransaction();
        try {
            $apply_model = Apply::find($id);
            $apply_model->fill($request->all());
            $apply_model->startTime = $request->timeType == 1 ? date('Y-m-d H:i:s', time()) : $request->startTime;
            $apply_model->endTime = $request->timeType == 1 ? date('Y-m-d H:i:s', strtotime('2099-01-01')) : $request->endTime;
            $apply_model->createUserId = empty($request->createUserId) ? $this->user()->id : $request->createUserId;
            $musetrModify = $apply_model->isDirty('musterId');
            if($request->timeType == 1){
                $apply_model->status=1;
            }
            if($request->timeType == 2&&$request->endTime>date('Y-m-d H:i:s', time())){
                $apply_model->status=1;
            }
            $apply_model->save();
            if ($musetrModify) {
                $apply_model->refreshPlugs();
            }
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
        return $this->success([], '平台删除成功');
    }


    public function plugins($id)
    {
        $applyPlugs = Plug::with(['applyPlug' => function ($q) use ($id) {
            return $q->where("uniacid", $id);
        }])->where('status', 1)->whereNotIn('appName',['douyin','miniPlay','dividend','seckill','clubTogether','buyUp'])->get();
        return $this->success(collect($applyPlugs)->groupBy('appType'), '成功');
    }

    public function authPlug($id)
    {
        $apply_model = Apply::find($id);
        if (!$apply_model) {
            return $this->failed("数据不存在");
        }
        DB::beginTransaction();
        try {
            $plugIds = Request()->plugId;
            ApplyPlugs::where('uniacid', $id)->delete();
            foreach ($plugIds as $key => $plugId) {
                $plugsData[] = new ApplyPlugs([
                    "plugId" => $plugId,
                    'source' => 2,
                    "state" => 1,
                    "display" => 1
                ]);
            }
            $apply_model->plugs()->saveMany($plugsData);
            DB::commit();
            return $this->success([], __('base.success'));
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failed("授权失败");
        }
    }

    public function display($id)
    {
        $plugId = Request()->plugId;
        $applyPlugs = ApplyPlugs::where('uniacid', $id)->where('plugId', $plugId)->first();
        if (empty($applyPlugs)) {
            return $this->failed("数据不存在");
        }
        $applyPlugs->display = !$applyPlugs->display;
        $applyPlugs->save();
        return $this->success([], '成功');
    }

    /**
     * 审核
     */
    public function autio(Request $request, $id)
    {
        $model = Apply::audit()->where('id', $id)->first();
        if (!$model) {
            return $this->failed("数据不存在");
        }
        if ($request->type == 2) {
            $model->status = 5;
            $model->notes = $request->notes;
        } else {
            $model->status = 1;
        }
        $model->save();
        return $this->success([], __('base.success'));
    }
}
