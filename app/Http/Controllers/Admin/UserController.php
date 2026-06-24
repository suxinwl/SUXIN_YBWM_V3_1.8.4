<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\ChangePassword;
use App\Models\Admin;
use App\Models\Config;
use App\Services\MenuService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Auth;

class UserController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $model = new UserService();
        $data = $model->getUserInfo('admin');
        return $this->success($data);
    }

    public function loadMenus(Request $request, MenuService $menuService)
    {
        $menuService->clearCache();
        return $this->success(['list' => $menuService->loadlMenus()]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }



    public function changePassword(ChangePassword $request, Admin $admin_model)
    {
        $user = auth('admin')->user();
        $user->username = $request->username;
        $user->nickname = $request->nickname;
        $logout = false;
        if (!empty($request->old_password) || !empty($request->password_confirmation)) {
            $user->password =  Hash::make($request->password);
            $logout = true;
        }
        $user->save();
        if ($logout) {
            auth('admin')->logout();
        }
        return $this->success(['logout' => $logout], $logout == true ? '密码修改成功,请重新登陆' : '账号资料修改成功');
    }
}
