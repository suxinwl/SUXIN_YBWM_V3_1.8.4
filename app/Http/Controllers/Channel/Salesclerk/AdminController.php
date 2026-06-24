<?php

namespace App\Http\Controllers\Channel\Salesclerk;

use App\Http\Controllers\Channel\ApiController;
use App\Http\Requests\Admin\ChangePassword;
use App\Http\Resources\Admin\AdminResource\AdminListCollection;
use App\Http\Resources\Admin\AdminResource\Admin as AdminResource;
use App\Models\Admin;
use App\Models\Admin\Apply;
use App\Models\Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Requests\Salesclerk\Admin as AdminRequest;
use App\Models\Store\StoreBase;

class AdminController extends ApiController
{
    public function index(Request $request, Admin $admin_model)
    {
        // $uniacid = $this->uniacid();
        // $apply = Apply::select('createUserId')->find($uniacid);
        // if ($apply->createUserId == $this->user()->id || $this->user()->id == 1) {
        //     $res = $admin_model->with('roles')->where(function ($q) use ($uniacid, $apply) {
        //         return $q->where('uniacid', $uniacid)->orWhere('id', $apply->createUserId);
        //     })->orderBy('id', 'desc')->paginate($request->pageSize ?? 1, '*', 'pageNo');
        // } else {

        // }
        $storeId = $this->storeId();
        $isolate = $this->isolate();
        $res = $admin_model->with('role')
            ->where('uniacid', $this->uniacid())
            ->when($request->keyword, function ($q) use ($request) {
                return $q->where(function ($q) use ($request) {
                    return $q->where('mobile', "like", "%$request->keyword%")
                        ->orWhere("nickname", 'like', "%$request->keyword%");
                });
            })
            ->when($storeId, function ($q) use ($request, $isolate, $storeId) {
                return $q->whereHas('stores', function ($q) use ($isolate, $storeId) {
                    $q->when($isolate, function ($q) use ($storeId) {
                        return $q->where(StoreBase::make()->getTable() . '.isolate', 1)->where(StoreBase::make()->getTable() . '.id', $storeId);
                    })->when(!$isolate, function ($q) use ($storeId) {
                        return $q->where(StoreBase::make()->getTable().'.isolate', 0)->when($storeId, function ($q) use ($storeId) {
                            return $q->where(StoreBase::make()->getTable() . '.id', $storeId);
                        });
                    });
                });
            })
            ->when($request->roleId, function ($q) use ($request) {
                return $q->whereHas('role', function ($q) use ($request) {
                    return $q->where('id', $request->roleId);
                });
            })
            ->when($request->module, function ($q) use ($request) {
                return $q->whereHas('role', function ($q) use ($request) {
                    return $q->where('module', $request->module);
                });
            })
            ->when($request->appList, function ($q) use ($request) {
                return $q->whereHas('role', function ($q) use ($request) {
                    return $q->where('appList', 'like', "%{$request->appList}%");
                });
            })
            ->orderBy('id', 'desc')
            ->paginate($request->pageSize ?? 1, '*', 'pageNo');
        $list = new AdminListCollection($res, true);
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
            'username'      =>  $request->mobile ?? '',
            'password'      =>  Hash::make($request->password ?? '123456'),
            'nickname'      =>  $request->nickname ?? '',
            'avatar'        =>  $request->avatar ?? '',
            'mobile'        =>  $request->mobile ?? '',
            'role_id'       =>  $request->role_id,
            'uniacid' => $this->uniacid(),
            'storeId' => $request->storeId ?? [],
            'isAdmin' => 0,
            'subMessage' => $request->subMessage ?: 0,
            'operatorId' => $this->userId(),
            'storeId' => $request->storeId ?? []
        ]);
        $admin_model->stores()->sync($request->storeId ?? []);
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
        $info = $admin_model->with(['role', 'operator', 'stores' => function ($q) {
            return $q->select(['name']);
        }])->find($id);
        return $this->success($info);
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

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(AdminRequest $request, Admin $admin_model, $id)
    {
        if ($admin_model->where('username', $request->username)
            ->where('id', '<>', $id)
            ->exists()
        ) {
            return $this->failed(__('admins.username_existence'));
        }
        $admin_model = $admin_model->where('uniacid', $this->uniacid())->find($id);
        if (empty($admin_model)) {
            return $this->failed('数据不存在');
        }
        if (!empty($request->password)) {
            $admin_model->password = Hash::make($request->password ?? '123456');
        }
        $admin_model->username = $request->mobile ?? '';
        $admin_model->nickname = $request->nickname ?? '';
        $admin_model->avatar = $request->avatar ?? '';
        $admin_model->mobile = $request->mobile ?? '';
        $admin_model->storeId = $request->storeId ?? [];
        $admin_model->operatorId = $this->userId();
        $admin_model->subMessage = $request->subMessage ?: 0;
        $admin_model->role_id      =  $request->role_id;
        $admin_model->save();
        $admin_model->stores()->sync($admin_model->storeId);
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
            $admin_model->stores()->detach();
            $admin_model->refresh();
            $admin_model->forceDelete();
        }
        return $this->success([], __('base.success'));
    }
}
