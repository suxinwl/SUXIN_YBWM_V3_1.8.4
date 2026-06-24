<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\loginPost;
use App\Http\Requests\Admin\MobileLoginPost;
use App\Http\Requests\Login\CheckCode;
use App\Http\Requests\Login\RetrievePassword;
use App\Models\Config;
use App\Models\Wechat\Kernel\Exceptions\Exception;
use App\Services\UserService;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\Admin;
use App\Models\Admin\AdminBind;
use App\Services\ConfigService;
use EasyWeChat\Factory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Overtrue\Socialite\SocialiteManager;
use Illuminate\Support\Facades\URL;
use Yansongda\Pay\Pay;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class LoginController extends ApiController
{
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
        $info = $user_service->login('username', 'admin', true);
        $siteData = Config::getSystemSet('site');
        $info['data']['site_title'] = $siteData->site_title ?: '站点头部标题';
        return $info['status'] ? $this->success($info['data']) : $this->failed($info['msg']);
    }

    public function mobileLogin(UserService $user_service, MobileLoginPost $request)
    {
        $info = $user_service->mobileLogin('username', 'admin', true);
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
            return $this->success([], __('base.success'));
        }
        return $this->success([], __('base.success'));
    }


    /**
     * 获取登录状态
     */
    public function wechatLoginState(Request $request)
    {
        if ($request->state) {
            return $this->success(Cache::get($request->state));
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
        $channel = 1;
        $wxData = ConfigService::getSystemSet('platformWechat');
        if ($channel == 2) {
            $url = Url::to('api/login/wechatLogin/channel/');
        } else {
            $url = Url::to('api/login/wechatLogin/admin/');
        }
        return $this->success([
            'appid' => $wxData->appId,
            'redirect' => $url,
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
            $appid = $wxData->AppID;
            $secret = $wxData->AppSecret;
            $config = [
                'wechat' =>
                [
                    'client_id'         => $appid,
                    'client_secret' => $secret,
                    'redirect'             =>  Url::to('api/login/wechatLogin')
                ]
            ];
            $socialite = new SocialiteManager($config);
            $user = $socialite->create('wechat')->userFromCode($code);
            $data = $user->getTokenResponse();
            $res = httpRequest("https://api.weixin.qq.com/sns/userinfo?access_token={$data['access_token']}&openid={$data['openid']}");
            if ($res) {
                Cache::put('data_' . $request->state, json_decode($res, true));
            }
            $adminBind = AdminBind::where(['openid' => $data['openid'], 'type' => 'wechat', 'channel' => 'open'])->orWhere('unionid', $data['unionid'])->first();
            if ($adminBind) {
                $token = JWTAuth::fromUser($adminBind->admin);
                Cache::put($request->state, ['type' => 1, 'token' => $token]);
            } else {
                Cache::put($request->state, ['type' => 2, 'state' => $request->state]);
            }
            if ($channel == 'admin') {
                return redirect(config('app.channelPath.admin') . '/#/login?weixinCode=' . $request->state);
            } else {
                return redirect(config('app.channelPath.channel') . '/#/login?weixinCode=' . $request->state);
            }
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
        $key = 'RetrieveStatus.' . $request->phone;
        $phone = Cache::get($key);
        if (empty($phone)) {
            return $this->success([], __('base.error'));
        }
        $user = Admin::where('mobile', $phone)->first();
        $user->password = Hash::make($request->password);
        $user->save();
        return $this->success([], __('base.password_success'));
    }

    public function enterPosition(Request $request, JWTAuth $JWTAuth)
    {
        if ($request->applyId) {
            $adminMode = new Admin();
            $list['url'] = URL::to('/platform/channel');
            $list['goBack'] = URL::to('/administrators/#/platformSite/platformSite/list');
            return $this->success($list, __('base.success'));
        }
    }
}
