<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\ApiController;
use App\Http\Requests\Admin\Admin as AdminRequest;
use App\Http\Requests\Admin\AdminGroup;
use App\Http\Resources\Admin\AdminResource\AdminListCollection;
use App\Http\Resources\Admin\AdminResource\Admin as AdminResource;
use App\Http\Resources\Admin\AdminResource\AdminCollection;
use App\Models\Admin;
use App\Models\Admin\Apply;
use App\Models\Config;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminController extends ApiController
{
    public function index(Request $request, Admin $admin_model)
    {

        $data = $admin_model->with(['role'])
            ->where('isAdmin', 1)
            ->where('id', '>', 1)
            ->when($request->status, function ($q) use ($request) {
                if ($request->status == 'audit') {
                    $q->where('status', 0);
                } elseif ($request->status == 'pass') {
                    $q->where('status', 1);
                } elseif ($request->status == 'back') {
                    $q->where('status', 2);
                } else {
                    $q->whereIn('status', [1, 2]);
                }
                return $q;
            })->when($request->keyword, function ($q) use ($request) {
                return $q->where('nickname', 'like', "%$request->keyword%")
                    ->orWhere('mobile', 'like', "%$request->keyword%");
            })->when($request->groupId, function ($q) use ($request) {
                return $q->where('group_id', $request->groupId);
            });
        if (!empty($request->startTime) && !empty($request->endTime)) {
            $data = $data->where(function ($q) use ($request) {
                return $q->where('created_at', '>=', $request->startTime)
                    ->where('created_at', '<=', $request->endTime);
            });
        }
        $data = $data->orderBy('id', 'desc')->paginate($request->pageSize ?? 20, '*', 'pageNo');
        $list = new AdminCollection($data);
        return $this->success($list);
    }


    public function list(Request $request, Admin $admin_model)
    {
        $data = $admin_model->with(['role'])
            ->where(function ($q) {
                return $q->where(['isAdmin' => 1, 'status' => 1])->orwhere('id', 1);
            });
        if ($request->keyword) {
            $data->where(function ($q) use ($request) {
                $q->where('nickname', 'like', "%$request->keyword%")
                    ->orWhere('mobile', 'like', "%$request->keyword%");
            });
        }
        $data = $data->orderBy('id', 'asc')->paginate($request->pageSize ?? 9999, '*', 'pageNo');
        $list = new AdminListCollection($data);
        return $this->success($list);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(AdminRequest $request, Admin $admin_model)
    {
        $admin_model = $admin_model->create([
            'username' => $request->username,
            'password'      =>  Hash::make($request->password ?? '123456'),
            'nickname'      =>  $request->nickname ?? '',
            'avatar'        =>  $request->avatar ?? '',
            'mobile'        =>  $request->username ?? '',
            'role_id'       =>  0,
            'group_id'       => intval($request->group_id) ?? 0,
            'channel'       => 1,
            'isAdmin' => 1,
            'createStoreNum' => $request->createStoreNum,
            'data'           => $request->data
        ]);
        $admin_model->roles()->sync($request->role_id ?? []);
        return $this->success([], __('base.success'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Admin $admin_model, $id)
    {
        $info = new AdminResource($admin_model->with('roles')->find($id));
        return $this->success($info);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(AdminRequest $request, Admin $admin_model, $id)
    {
        $admin_model = $admin_model->find($id);
        if (empty($id)) {
            return $this->success([], __('base.nodata'));
        }
        if (!empty($request->password)) {
            $admin_model->password = Hash::make($request->password ?? '123456');
        }
        $admin_model->nickname = $request->nickname ?? '';
        $admin_model->avatar = $request->avatar ?? '';
        $admin_model->username = $request->username;
        $admin_model->mobile = $request->username;
        $admin_model->group_id = $request->group_id ?: 0;
        $admin_model->createStoreNum = $request->createStoreNum;
        $admin_model->data = $request->data;
        $admin_model->status = intval($request->status);
        $admin_model->save();
        $admin_model->roles()->sync($request->role_id ?? []);
        return $this->success([], __('base.success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Admin $admin_model, $id)
    {
        $idArray = array_filter(explode(',', $id), function ($item) {
            return is_numeric($item);
        });
        foreach ($idArray as $v) {
            $admin_model = $admin_model->find($v);
            Apply::query()->whereIn('id', $idArray)->update(['createUserId' => 1]);
            if ($admin_model) {
                $admin_model->roles()->detach();
                $admin_model->delete();
            }
            // $admin_model->apply()->update(['createUserId'=>1]);
        }
        return $this->success([], __('base.success'));
    }

    public function state(Admin $admin_model, $id)
    {
        $admin_model = $admin_model->find($id);
        if (empty($id)) {
            return $this->success([], __('base.nodata'));
        }
        if (in_array($admin_model->status, [1, 2])) {
            $admin_model->status = $admin_model->status == 1 ? 2 : 1;
            $admin_model->save();
            return $this->success([], '操作成功');
        }
        return $this->success([], __('操作成功'));
    }

    public function audit(Request $request, Admin $admin_model, $id)
    {
        $idArray = array_filter(explode(',', $id), function ($item) {
            return is_numeric($item);
        });
        $status = $request->type == 1 ? 1 : 3;
        foreach ($idArray as $v) {
            $admin_model = $admin_model->where(['id' => $v, 'status' => 0])->first();
            if ($admin_model) {
                $admin_model->status = $status;
                $admin_model->save();
            }
        }
        return $this->success([], __('base.success'));
    }


    public function plugins()
    {
        $plugins = getSysInfo()['authData'];
        $userPlugins = $this->user()->data ?: [];
        if ($this->user()->id == 1) {
            $userPlugins = getSysInfo()['authData'];
        }
        foreach ($userPlugins as $key => $v) {
            if (empty($v)) {
                $v = [];
            }
            if (empty($plugins[$key])) {
                $plugins[$key] = [];
            }
            $intersect[$key]  = Config::plugLang(array_intersect($v, $plugins[$key]));
        }
        return $this->success($intersect, __('base.success'));
    }


    /**
     * 回收站
     */
    public function recovery(Request $req)
    {
        $apply_model = Admin::onlyTrashed();
        if (!empty($req->keyword)) {
            $apply_model->where(function ($q) use ($req) {
                return   $q->where('username', 'like', "%$req->keyword%")
                    ->orWhere('nickname', 'like', "%$req->keyword%")
                    ->orWhere('mobile', 'like', "%$req->keyword%");
            });
        }
        if (!empty($req->startTime) && !empty($req->endTime)) {
            $apply_model->where('deleted_at', '>=', $req->startTime)
                ->where('deleted_at', '<=', $req->endTime);
        }
        $data = $apply_model->orderBy('deleted_at', 'desc')->paginate($req->pageSize ?? 30, '*', 'pageNo');
        return $this->success(new AdminCollection($data));
    }

    /**
     * 回收站删除
     */
    public function del(Request $request, $id)
    {
        $idArray = array_filter(explode(',', $id), function ($item) {
            return is_numeric($item);
        });
        Admin::withTrashed()->whereIn('id', $idArray)->forceDelete($idArray, true);
        return $this->success([], __('base.success'));
    }

    /**
     * 回收站恢复
     */

    public function restore($id)
    {
        $idArray = array_filter(explode(',', $id), function ($item) {
            return is_numeric($item);
        });
        Admin::withTrashed()->whereIn('id', $idArray)->restore();
        return $this->success([], __('base.success'));
    }
}
