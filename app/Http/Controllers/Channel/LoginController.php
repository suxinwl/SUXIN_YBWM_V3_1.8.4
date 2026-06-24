<?php

namespace App\Http\Controllers\Channel;

use App\Http\Requests\Admin\loginPost;
use App\Http\Requests\Admin\MobileLoginPost;
use App\Http\Requests\Login\CheckCode;
use App\Http\Requests\Login\RetrievePassword;
use App\Http\Requests\Channel\RegiestRequest;
use App\Models\Config;
use App\Services\UserService;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\Admin;
use App\Models\Admin\AdminBind;
use App\Models\Admin\Apply;
use App\Services\ConfigService;
use App\Services\SmsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Overtrue\Socialite\SocialiteManager;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use App\Http\Controllers\Controller as BaseController;
use App\Http\Helpers\ApiResponse;
use App\Models\HandleLog;
use App\Http\Resources\Admin\AdminResource\Admin as AdminResource;

class LoginController extends BaseController
{
    use ApiResponse;

    /**
     * @api {POST} /api/admin/login
     * @apiName 用户登录
     * @apiGroup User
     */
    /**
     * @apiParam {String} [username]  用户名
     * @apiParam {String} [password]  密码
     *
     * @apiSuccess {String} state 状态.
     * @apiSuccess {Number} code 状态码.
     * @apiSuccess {String} msg 提示.
     * @apiSuccess {Object} data 数据.
     * @apiSuccess {Object} data.token 用户.
     */


    public function login(UserService $user_service, loginPost $request)
    {
        $uniacid=Request()->header('uniacid',0);
        if($uniacid){
            $apply = Apply::find($uniacid);
            if($apply->status==2){
                throw new BadRequestException('店铺已禁用,请联系管理员');
            }
            if($apply->endTime<date('Y-m-d H:i:s',time())){
                throw new BadRequestException('店铺已到期,请联系管理员续费');
            }
        }
        $info = $user_service->login('username', 'admin');
        $userInfo=$info['data']['user_info'];
        if($userInfo->status==2){
            throw new BadRequestException('用户已禁用');
        }
        if($userInfo->deleted_at>0){
            throw new BadRequestException('用户已删除');
        }
        $siteData = Config::getSystemSet('site');
        $info['data']['site_title'] = $siteData->site_title ?: '站点头部标题';
        return $info['status'] ? $this->success($info['data']) : $this->failed($info['msg']);
    }

    public function mobileLogin(UserService $user_service, MobileLoginPost $request)
    {
        $info = $user_service->mobileLogin('username', 'admin');
        $siteData = Config::getSystemSet('site');
        $info['data']['site_title'] = $siteData->site_title ?: '站点头部标题';
        return $info['status'] ? $this->success($info['data']) : $this->failed($info['msg']);
    }

    // 检测是否登陆
    public function check_login(UserService $user_service)
    {
        $info = $user_service->checkLogin('admin');
        return $info['status'] ? $this->success($info['data']) : $this->failed($info['msg']);
    }

    // 退出账号
    public function logout()
    {
        try {
            auth('admin')->logout();
        } catch (\Exception $e) {
            return $this->success(url(config('app.channelPath.platform')), __('base.success'));
        }
        return $this->success(url(config('app.channelPath.platform')), __('base.success'));
    }


    /**
     * 获取登录状态
     */
    public function wechatLoginState(Request $request)
    {
        if ($request->state) {
            $data = Cache::get($request->state);
            if ($data['type'] != 1) {
                throw new BadRequestException('微信未绑定用户，请登录后绑定');
            }
            return $this->success($data);
        } else {
            return $this->failed(__('base.nodata'));
        }
    }



    /**
     * 获取微信登录链接
     */
    public function getOpenWechat(Request $request)
    {
        $ident = 'openWechat';
        $channel = 2;
        $url = $request->headers->get('referer');
        Cache::put('login.referer', $url);
        $wxData = ConfigService::getSystemSet('platformWechat');
        return $this->success([
            'appid' => $wxData->appId,
            'redirect' => Url::to('channel/login/wechatLogin/channel/'),
        ]);
    }

    /**
     * 微信登录
     */
    public function wechatLogin(Request $request, $channel = 'admin')
    {
        try {
            if (!$request->state) {
                return $this->failed(__('base.error'));
            }
            $code = $request->code;
            $wxData = ConfigService::getSystemSet('platformWechat');
            $appid = $wxData->appId;
            $secret = $wxData->appSecret;
            $config = [
                'wechat' =>
                [
                    'client_id'         => $appid,
                    'client_secret' => $secret,
                    'redirect'             =>  Url::to('admin/login/wechatLogin')
                ]
            ];
            $socialite = new SocialiteManager($config);
            $user = $socialite->create('wechat')->userFromCode($code);
            $data = $user->getTokenResponse();
            $adminBind = AdminBind::where(['openid' => $data['openid'], 'type' => 'wechat', 'channel' => 'open'])->orWhere('unionid', $data['unionid'])->first();
            if ($adminBind) {
                $token = JWTAuth::fromUser($adminBind->admin);
                Cache::put($request->state, ['type' => 1, 'token' => $token, 'user_info' => new AdminResource($adminBind->admin)]);
            } else {
                Cache::put($request->state, ['type' => 2, 'state' => $request->state]);
            }
            return redirect(Cache::get('login.referer') . '#/login?weixinCode=' . $request->state);
        } catch (\Exception $e) {
            return $this->failed(__('base.error'));
        }
    }


    /**
     * 绑定微信登录关系
     */
    public function WechatBind(UserService $user_service, loginPost $request)
    {
        if (empty($request->state) || empty($data = Cache::get("data_" . $request->state))) {
            return  $this->failed(__('base.nocode'));
        }
        $info = $user_service->login('username', 'admin');
        $adminBind = AdminBind::where('unionid', $data['unionid'])->first();
        if ($adminBind) {
            return  $this->failed(__('base.bind_extens'));
        }
        if ($info['status']) {
            $bind = new AdminBind();
            $bind->userId = $info['data']['user_info']['id'];
            $bind->type = 'wechat';
            $bind->channel = 'open';
            $bind->unionid = $data['unionid'];
            $bind->openid = $data['openid'];
            $bind->data = json_encode($data, 320);
            $bind->save();
        }
        Cache::forget("data_" . $request->state);
        Cache::forget($request->state);
        return $this->success($info['data']);
    }

    /**
     * 验证短信验证码
     */
    public function checkCode(CheckCode $request)
    {
        $key = 'Retrieve.' . $request->phone;
        if ($request->code != Cache::get($key)) {
            return $this->failed(__("sms.code_error"));
        } else {
            Cache::forget($key);
            $key = 'RetrieveStatus.' . $request->phone;
            Cache::put($key, $request->phone, 500);
            return $this->success([], __('base.check_success'));
        }
    }

    /**
     * 修改密码
     */
    public function retrievePassword(RetrievePassword $request)
    {
        $key = 'Retrieve.' . $request->phone;
        if ($request->code != Cache::get($key)) {
            return $this->failed(__("sms.code_error"));
        }
        $phone = $request->phone;
        $user = Admin::where('mobile', $phone)->first();
        $user->password = Hash::make($request->password);
        $user->save();
        return $this->success([], '密码修改成功');
    }



    public function enterPosition(Request $request, JWTAuth $JWTAuth)
    {
        if ($request->applyId) {
            $apply = Apply::find($request->applyId);
            if ($apply->status != 1) {
                throw new BadRequestException('当前平台服务已到期或已被禁用，请联系管理员咨询');
            }
            $adminMode = new Admin();
            $list['url'] = URL::to('/platform/channel');
            $list['goBack'] = URL::to('/platform/');
            return $this->success($list, __('base.success'));
        }
    }

    public function regiset(RegiestRequest $request)
    {
        $config = ConfigService::getSystemSet('userSettings');
        if ($config->registerWay != 1) {
            return $this->failed('当前站点禁止注册');
        }
        if ($config->graphicsCode == 1 && !SmsService::checkCode('register', $request->mobile, $request->code)) {
            return $this->failed('验证码错误');
        }
        $plugins = getSysInfo()['authData'];
        $user = Admin::where(function ($q) use ($request) {
            return $q->where('mobile', $request->mobile)->orWhere('username', $request->mobile);
        })->where('status', 3)->first();
        if ($user) {
            $user->username = $request->mobile;
            $user->mobile = $request->mobile;
            $user->status = $config->audit == 2 ? 1 : 0;
            $user->save();
        } else {
            $user = Admin::create([
                'isAdmin' => 1,
                'username' => $request->mobile,
                'mobile' => $request->mobile,
                'status' => $config->audit == 2 ? 1 : 0,
                'password' => Hash::make($request->password),
                'nickname'      =>  $request->nickname ?? '',
                'avatar'        =>  $request->avatar ?? '',
                'role_id'       =>  0,
                'group_id'       => intval($config->register_meal_switch),
                'channel' => 2,
                'createStoreNum' => intval($config->createStoreNum),
                'data'           => $plugins
            ]);
            $user->roles()->sync($request->role_id ?? []);
        }
        if ($user->status == 1) {
            return $this->success([], '注册成功，请登录');
        } else {
            return $this->success([], '注册成功，请等待管理员审核');
        }
    }

    public function channelLogin(Request $request){
        $route = $request->route()->getAction()['as'];
        $channel =  explode('/', $request->path())[0];
        $user = auth('admin')->user();
        if ($user->isAdmin == 1) {
            $log = new HandleLog();
            $log->username = $user->username;
            $log->userId = intval($user->id);
            $log->uniacid = Request()->header('uniacid',0);
            $log->type = 1;
            $log->route = $route;
            $log->method = $request->method();
            $log->ip = $request->ip();
            $log->input = '';
            $log->created_at = date('Y-m-d H:i:s', time());
            $log->channel = $channel;
            $log->save();   # 记录日志
        }
        return $this->success();
    }
}
