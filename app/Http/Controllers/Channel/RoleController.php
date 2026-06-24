<?php

namespace App\Http\Controllers\Channel;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\Roles\Show;
use App\Models\Admin\Apply;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, Role $role_model)
    {
        $uniacid = $this->uniacid();
        $apply = Apply::select('createUserId')->find($uniacid);
        if ($apply->createUserId == $this->user()->id || $this->user()->id == 1) {
            $res = $role_model->where(function ($q) use ($uniacid) {
                return $q->where('uniacid', $uniacid);
            })->orderBy('id', 'desc')->paginate($request->pageSize ?? 10, '*', 'pageNo');
        } else {
            $res = $role_model->where(function ($q) use ($uniacid) {
                return $q->where('uniacid', $uniacid);
            })->orderBy('id', 'desc')->paginate($request->pageSize ?? 10, '*', 'pageNo');
        }
        return $this->success($res);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function list(Request $request, Role $role_model)
    {
        $uniacid = $this->uniacid();
        $apply = Apply::select('createUserId')->find($uniacid);
        if ($apply->createUserId == $this->user()->id || $this->user()->id == 1) {
            $res = $role_model->select(['id', 'name'])->where(function ($q) use ($uniacid) {
                return $q->where('uniacid', $uniacid);
            })->orderBy('id', 'desc')->get();
        } else {
            $res = $role_model->select(['id', 'name'])->where(function ($q) use ($uniacid) {
                return $q->where('uniacid', $uniacid);
            })->orderBy('id', 'desc')->get();
        }
        return $this->success($res);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Role $role_model)
    {
        $role_model = $role_model->create([
            'uniacid' => $this->uniacid(),
            'name' => $request->name,
            'module' => 'channel',
            'appList' => [],
            'cashierRole' => $request->cashierRole,
            'storRole' => $request->storRole,
        ]);
        $role_model->menus()->sync($request->menu_id ?? []);
        $role_model->permissions()->sync($request->permission_id ?? []);
        return $this->success([], __('base.success'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Role $role_model, $id)
    {
        $info = $role_model->with(['menus', 'permissions'])->where(['uniacid' => $this->uniacid(), 'id' => $id])->first();
        return $this->success(new Show($info));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Role $role_model, $id)
    {
        $role_model = $role_model->where(['uniacid' => $this->uniacid(), 'id' => $id])->first();
        if (!$role_model) {
            return $this->failed(__("base.nodata"));
        }
        $role_model->cashierRole = $request->cashierRole;
        $role_model->storRole = $request->storRole;
        $role_model->name = $request->name;
        $role_model->appList = $request->appList;
        $role_model->save();
        $role_model->menus()->sync($request->menu_id ?? []);
        $role_model->permissions()->sync($request->permission_id ?? []);
        return $this->success([], __('base.success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Role $role_model, $id)
    {
        $idArray = array_filter(explode(',', $id), function ($item) {
            return is_numeric($item);
        });
        foreach ($idArray as $v) {
            $role_model = $role_model->find($v);
            $role_model->menus()->detach();
            $role_model->permissions()->detach();
            $role_model->refresh();
        }
        $role_model->destroy($idArray);
        return $this->success([], __('base.success'));
    }
}
