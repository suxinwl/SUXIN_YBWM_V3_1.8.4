<?php

namespace App\Http\Controllers\ChannelApi;

use Alipay\EasySDK\Kernel\Factory as AliFactory;
use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\MemberBind;
use Illuminate\Http\Request;
use EasyWechat\Factory;
use App\Models\Wechat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\ChannelApi\ApiController;
use App\Http\Requests\DecryptRequest;
use App\Http\Requests\Wechat\LoginRequest;
use App\Http\Requests\Wechat\RegisterRequest;
use App\Http\Resources\ChannelApi\User\Profix;
use App\Models\Admin\Apply;
use App\Models\ChannelConfig;
use App\Models\MemberSubscribe;
use App\Models\MiniVersion;
use App\Models\OpenWechatAuth;
use App\Models\StatisticsDay;
use App\Models\Wechat\Kernel\Contracts\Config;
use App\Services\ConfigService;
use App\Services\AliMini\ChannelMini;
use App\Services\KuaiShou\KsMiniProgram;
use App\Services\OpenWechat\ChannelOpenWechat;
use App\Services\UserService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class LoginController extends ApiController
{

    /**
     * 登录
     */
    public function version(Request $request)
    {
        $model = OpenWechatAuth::where('uniacid', $this->uniacid())->where('type', 'mini')->first();
        $viersion = MiniVersion::where('appid', $model->authorizer_appid)->where('version', $request->version)->first();
        if ($viersion && $viersion->state == 9) {
            return  $this->success(['release' => true]);
        } else {
            return  $this->success(['release' => false]);
        }
    }

    /**
     * 登录
     */
    public function Login(LoginRequest $request)
    {
        $uniacid = $this->uniacid();
        $storeId = $this->storeId();
        if($uniacid){
            $apply = Apply::find($uniacid);
            if($apply->status==2){
                throw new BadRequestException('店铺已禁用,请联系管理员');
            }
            if($apply->endTime<date('Y-m-d H:i:s',time())){
                throw new BadRequestException('店铺已到期,请联系管理员续费');
            }
        }
        $code = $request->code; //接收前端传入的code值
        // StatisticsDay::where('uniacid', $this->uniacid())->where('day', date("Y-m-d", time()))
        //     ->increment('pv', 1);
        if ($this->appType() == 1) {
            $app = ChannelOpenWechat::miniProgram($this->uniacid());
            $loginInfo = $app->auth->session($code);
            if (isset($loginInfo['errcode'])) {
                return $this->failed($loginInfo['errmsg']);
            }
            $session_key = $loginInfo['session_key'];
            $openId = $loginInfo['openid'];
            $unionid = $loginInfo['unionid'];
            $user = MemberBind::with('member')->where('openid', $openId)
                ->whereHas('member', function ($q) use ($uniacid) {
                    return $q->where('uniacid', $uniacid);
                })
                ->where('type', $this->appType())
                ->first();
            if ($user) {
                if ($user->member->state != 1) {
                    return $this->failed('该用户已被拉黑', 402);
                }
                if (empty($user->unionid) && !empty($unionid)) {
                    $user->unionid = $unionid;
                    $user->save();
                }
                Cache::set("userLoginOpenid:" . $this->appType() . ':' . $user->userId, $openId);
                $token = JWTAuth::fromUser($user->member);
                return $this->success(['session_key' => $session_key, 'token' => $token, 'userInfo' => new Profix($user->member)], __("base.success"));
            } elseif ($unionid) {
                $user = MemberBind::with('member')
                    ->whereHas('member', function ($q) use ($uniacid) {
                        return $q->where('uniacid', $uniacid);
                    })
                    ->where('unionid', $unionid)
                    ->first();
                if ($user) {
                    $memberBind = new MemberBind();
                    $memberBind->userId = $user->userId;
                    $memberBind->type = $this->appType();
                    $memberBind->unionid = $unionid;
                    $memberBind->openid = $openId;
                    $memberBind->nickname = $user->nickname;
                    $memberBind->mobile = $user->mobile ?: '';
                    $memberBind->avatar = $user->avatar;
                    $memberBind->data = json_encode([], 320);
                    $memberBind->save();
                    $memberBind->refresh();
                    if ($user->member->state != 1) {
                        return $this->failed('该用户已被拉黑', 402);
                    }
                    $memberSub = MemberSubscribe::where('unionid', $unionid)->first();
                    if ($memberSub&&$storeId) {
                        $memberBind = new MemberBind();
                        $memberBind->userId = $user->userId;
                        $memberBind->type = 2;
                        $memberBind->unionid = $memberSub->$unionid ?? '';
                        $memberBind->openid = $memberSub->openid ?? '';
                        $memberBind->nickname = $user->nickname;
                        $memberBind->mobile = $user->mobile ?: '';
                        $memberBind->avatar = $user->avatar;
                        $memberBind->uniacid = $uniacid;
                        $memberBind->storeId = $storeId;
                        $memberBind->data = json_encode([], 320);
                        $memberBind->save();
                    }
                    $token = JWTAuth::fromUser($user->member);
                    Cache::set("userLoginOpenid:" . $this->appType() . ':' . $user->userId, $openId);
                    return $this->success(['session_key' => $session_key, 'token' => $token, 'userInfo' => new Profix($user->member)], __("base.success"));
                }
            }
            $user = Member::create([
                'nickname' => $res['nick_name'] ?? '用户_' . rand(1000000, 9999999),
                'mobile' => $request->mobile ?? '',
                'avatar' => $res['avatar'] ?? '',
                'uniacid' => $uniacid,
                'score' => $this->appType(),
                'storeId' => $storeId,
                'password' => Hash::make('123456'),
                'state' => 1,
            ]);
            $memberBind = new MemberBind();
            $memberBind->userId = $user->id ?: 0;
            $memberBind->type =  $this->appType();
            $memberBind->unionid = $unionid;
            $memberBind->openid = $openId;
            $memberBind->nickname = '';
            $memberBind->mobile = $request->mobile ?? '';
            $memberBind->avatar = '';
            $memberBind->uniacid = $uniacid;
            $memberBind->storeId = $storeId;
            $memberBind->data = json_encode([], 320);
            $memberBind->save();
            Cache::set("userLoginOpenid:" . $this->appType() . ':' . $user->id, $openId);
            $token = JWTAuth::fromUser($user);
            return $this->success(['session_key' => $session_key, 'token' => $token, 'userInfo' => new Profix($user)]);
        } elseif ($this->appType() == 3 || $this->appType() == 12) {
            ChannelMini::setOptions($this->uniacid());
            $res = ChannelMini::login($code);
            $openId = $res['user_id'];
            $user = MemberBind::with(['member' => function ($q) use ($uniacid) {
                return $q->where('uniacid', $uniacid);
            }])->where('openid', $openId)
                ->whereHas('member', function ($q) use ($uniacid) {
                    return $q->where('uniacid', $uniacid);
                })
                ->where('type', $this->appType())
                ->first();
            if ($user) {
                if ($user->member->state != 1) {
                    return $this->failed('该用户已被拉黑', 402);
                }
                if (empty($user->unionid) && !empty($unionid)) {
                    $user->unionid = $unionid;
                    $user->save();
                }
                Cache::set("userLoginOpenid:" . $this->appType() . ':' . $user->userId, $openId);
                $token = JWTAuth::fromUser($user->member);
                return $this->success(['session_key' => null, 'token' => $token, 'userInfo' => new Profix($user->member)], __("base.success"));
            }
            $user = Member::create([
                'nickname' => $res['nick_name'] ?? '用户_' . rand(1000000, 9999999),
                'mobile' => $request->mobile ?? '',
                'avatar' => $res['avatar'] ?? '',
                'uniacid' => $this->uniacid(),
                'score' => $this->appType(),
                'storeId' => $storeId,
                'password' => Hash::make('123456'),
                'state' => 1,
            ]);
            $memberBind = new MemberBind();
            $memberBind->userId = $user->id ?: 0;
            $memberBind->type =  $this->appType();
            $memberBind->unionid = '';
            $memberBind->openid = $openId;
            $memberBind->nickname = '';
            $memberBind->mobile = $request->mobile ?? '';
            $memberBind->avatar = '';
            $memberBind->uniacid = $uniacid;
            $memberBind->storeId = $storeId;
            $memberBind->data = json_encode([], 320);
            $memberBind->save();
            Cache::set("userLoginOpenid:" . $this->appType() . ':' . $user->id, $openId);
            $token = JWTAuth::fromUser($user);
            return $this->success(['session_key' => null, 'token' => $token, 'userInfo' => new Profix($user)], __("base.success"));
        } elseif ($this->appType() == 8) {
            $app = KsMiniProgram::miniProgram($this->uniacid());
            $loginInfo = $app->auth->session($code);
            if ($loginInfo['result'] != 1) {
                return $this->failed($loginInfo['error_msg']);
            }
            $session_key = $loginInfo['session_key'];
            $openId = $loginInfo['open_id'];
            $unionid = '';
            $user = MemberBind::with('member')->where('openid', $openId)->where('type', $this->appType())->first();
            if ($user) {
                if ($user->member->state != 1) {
                    return $this->failed('该用户已被拉黑', 402);
                }
                $token = JWTAuth::fromUser($user->member);
                return $this->success(['token' => $token, 'userInfo' => new Profix($user->member)], __("base.success"));
            } else {
                return  $this->success([
                    'session_key' => $session_key,
                    'openid' => $openId,
                    'unionid' => '',
                ]);
            }
        }
    }


    /**
     * 注册
     */
    public function register(RegisterRequest $request)
    {
        $type = $this->appType();
        switch ($type) {
            case 1:
                $data = UserService::miniRegister($request, $type);
                break;
            case 2:
                $data = UserService::wechatRegister($request, $type);
                break;
            case 5:
                $data = UserService::h5Register($request, $type);
                break;
            case 8:
                $data = UserService::ksRegister($request, $type);
                break;
            default:
                return $this->failed('非法客户端');
                break;
        }
        Cache::set("userLoginOpenid:" . $this->appType() . ':' . $data['userInfo']['id'], $request->openid);
        return $this->success($data);
    }


    /**
     * 获取授权连接
     */
    public function getAuthorizationUrl(Request $request)
    {
        $app = ChannelOpenWechat::officialAccount($this->uniacid());
        $url =  $request->refererUrl;
        if (empty($url)) {
            $url = $request->headers->get('referer');
        }
        $url =  $app->oauth->redirect($url);
        return $this->success($url);
    }




    /**
     * 公众号登录
     */
    public function wechatLogin(Request $request, $uniacid)
    {
        $app = ChannelOpenWechat::officialAccount($uniacid);
        $url =  $request->refererUrl;
        if (empty($url)) {
            $url = $request->headers->get('referer');
        }
        $url =  $app->oauth->redirect($url);
    }

    /**
     * 手机号登录
     */
    public function mobileLogin(Request $request)
    {
        $uniacid = $request->header('uniacid');
        $code = $request->code; //接收前端传入的code值
        $endSmscode = Cache::get('registerSms' . $request->mobile);
        if ($code != $endSmscode) {
            throw new BadRequestException(__("sms.code_error"));
        }
        $data = UserService::h5Register($request);
        $this->success($data);
    }


    //解密
    public function Decrypt(DecryptRequest $request)
    {
        $app = ChannelOpenWechat::miniProgram($this->uniacid());
        $res = $app->encryptor->decryptData($request->sessionKey, $request->iv, $request->data);
        return $this->success($res);
    }


    public function jssdk()
    {
        $uniacid = Request()->header('uniacid');
        $app = ChannelOpenWechat::officialAccount($uniacid);
        $url =  Request()->url;
        $config =  $app->jssdk->buildConfig([], false, false, false, [], $url);
        return $this->success($config);
    }
}
