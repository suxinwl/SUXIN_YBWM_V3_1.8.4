<?php

namespace App\Http\Controllers\Channel\Salesclerk;

use App\Http\Controllers\Channel\ApiController;
use App\Http\Controllers\Controller;
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
        $list = Role::where('uniacid', $this->uniacid())
            ->where('storeId', $this->storeId())
            ->when($request->module, function ($q) use ($request) {
                return $q->where('module', $request->module);
            })
            ->orderBy('id', 'desc')
            ->paginate($request->pageSize ?? 30, '*', 'pageNo');
        return $this->success($list);
    }

    public function list(Request $request, Role $role_model)
    {
        $list = Role::where('uniacid', $this->uniacid())
            ->where('storeId', $this->storeId())
            ->orderBy('id', 'desc')->get();
        $list = collect($list)->groupBy('module')->all();
        return $this->success($list);
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
            'appList' => $request->appList,
            'name' => $request->name,
            'uniacid' => $this->uniacid(),
            'module' => $request->module,
            'desc' => $request->desc,
            'cashierRole' => $request->cashierRole,
            'storRole' => $request->storRole,
            'storeId' => $this->storeId()
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
        $info = $role_model->with(['menus', 'permissions'])
            ->where('storeId', $this->storeId())
            ->find($id);
        if (empty($info)) {
            return $this->failed('数据不存在');
        }
        $info = $info->toArray();
        $info['menus'] = collect($info['menus'])->pluck('id')->all();
        return $this->success($info);
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
        $role_model = $role_model->find($id);
        $role_model->cashierRole = $request->cashierRole;
        $role_model->storRole = $request->storRole;
        $role_model->name = $request->name;
        $role_model->desc = $request->desc;
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
            $role_model->forceDelete();
        }
        return $this->success([], __('base.success'));
    }
}
