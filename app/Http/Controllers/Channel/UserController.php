<?php

namespace App\Http\Controllers\Channel;

use App\Http\Requests\Admin\ChangePassword;
use App\Http\Resources\Admin\AdminResource\Admin as AdminResourceAdmin;
use App\Models\Ad;
use App\Models\Admin;
use App\Models\Admin\AdminBind;
use App\Models\Admin\Apply;
use App\Models\AdminGroup;
use App\Models\Customer;
use App\Models\Menu;
use App\Models\Plug;
use App\Models\Role;
use App\Models\RoleMenu;
use App\Services\ConfigService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Services\MenuService;
use App\Services\SmsService;
use App\Traits\HelperTrait;
use DB;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Illuminate\Support\Facades\URL;
use Overtrue\Socialite\SocialiteManager;
use App\Models\ApplyPlugs;

class UserController extends ApiController
{
    use HelperTrait;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $model = new UserService();
        return $this->success($model->getUserInfo('admin'));
    }

    public function checkApply()
    {
        $count = Apply::withTrashed()->count();
        $auth = getSysInfo();
        if ($auth['account_type'] == 2 && $auth['account_number'] <= $count) {
            return $this->failed('平台创建数量已达到上限');
        }
        $admin = Admin::find($this->user()->id);
        if ($this->user()->id != 1 && ($admin->createStoreNum > 0 && $admin->adminApply->count() >= $admin->createStoreNum)) {
            return $this->failed($admin->username . __('base.apply_top'));
        }
        return $this->success();
    }

    public function profix(Request $request)
    {
        $user = Admin::find($this->user()->id);
        $user->nickname = $request->nickname ??  '';
        $user->avatar = $request->avatar ?? "";
        if ($request->superPassword) {
            $user->superPassword = Hash::make($request->superPassword);
        }
        $user->save();
        $user->refresh();
        return $this->success(new AdminResourceAdmin($user), '修改成功');
    }

    public function getWx(Request $request)
    {
        $url = $request->headers->get('referer');
        Cache::put('wxBind.referer', $url);
        $wxData = ConfigService::getSystemSet('platformWechat');
        $appid = $wxData->appId;
        $secret = $wxData->appSecret;
        $config = [
            'wechat' =>
            [
                'client_id'         => $appid,
                'client_secret' => $secret,
                'redirect'             =>  URL::to('channel/wxBind/' . $this->user()->id)
            ]
        ];
        $socialite = new SocialiteManager($config);
        $data['url'] = $socialite->create('wechat')->redirect();
        return $this->success($data);
    }

    public function wxBind(Request $request, $userId)
    {
        $code = $request->code;

        $wxData = ConfigService::getSystemSet('platformWechat');
        $appid = $wxData->appId;
        $secret = $wxData->appSecret;
        $config = [
            'wechat' =>
            [
                'client_id'         => $appid,
                'client_secret' => $secret,
                'redirect'             =>  URL::to('channel/wxBind/' . $userId)
            ]
        ];
        $socialite = new SocialiteManager($config);
        $user = $socialite->create('wechat')->userFromCode($code);
        $data = $user->getTokenResponse();
        $res = httpRequest("https://api.weixin.qq.com/sns/userinfo", [
            "access_token" => $data['access_token'],
            "openid" => $data['openid']
        ], [], 'get');
        $adminBind = AdminBind::where(['type' => 'wechat', 'channel' => 'open'])
            ->where('userId', $userId)
            ->delete();
        $bind = new AdminBind();
        $bind->userId = $userId;
        $bind->type = 'wechat';
        $bind->channel = 'open';
        $bind->unionid = $data['unionid'];
        $bind->openid = $data['openid'];
        $bind->data = $res;
        $bind->save();
        return redirect(Cache::get('wxBind.referer') . '#/workbench/setting');
    }

    public function loadMenus(Request $request, MenuService $menuService)
    {
        if ($this->storeId() && $this->user()->role->module != 'store' && $this->isolate() == 0) {
            $menus = $this->getChildren(Menu::where('roleLevel', 'like', '%2%')->where('is_type', 1)->orderBy('pid', 'asc')->orderBy('is_sort', 'asc')->get()->toArray());
        } else {
            $menus = $menuService->loadlMenus(true,$this->isolate());
        }
        return $this->success($menus);
    }

    public function changePassword(ChangePassword $request, Admin $admin_model)
    {
        $user = $this->user();
        $user->username = $request->username ??  $user->username;
        $logout = false;

        if (Hash::check($request->oldPassword, $user->password)) {
            $user->password =  Hash::make($request->password);
            $logout = true;
        } else {
            throw new BadRequestException('原密码不正确');
        }
        $user->save();
        if ($logout) {
            auth('admin')->logout();
        }
        return $this->success([], '密码修改成功');
    }

    public function changePasswordSms()
    {
        $mobile = $this->user()->mobile;
        $sms = new SmsService();
        if ($sms->retrieveSms($mobile)) {
            return $this->success([], __('sms.code_success'));
        } else {
            return $this->failed([], __('sms.error'));
        }
    }

    public function customerService()
    {
        $apply = Apply::find($this->uniacid());
        if (empty($apply)) {
            return $this->failed('数据不存在');
        }
        $customer = Customer::where('isDefault', 1)->first();
        $group = AdminGroup::with(['customer'])->find($apply->admin->group_id);
        $customer = $group->customer ?? $customer;
        return $this->success($customer);
    }
}
