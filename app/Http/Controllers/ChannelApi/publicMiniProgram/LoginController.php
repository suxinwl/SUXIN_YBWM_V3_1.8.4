<?php

namespace App\Http\Controllers\ChannelApi\publicMiniProgram;

use App\Http\Controllers\ChannelApi\ApiController;
use App\Http\Requests\Wechat\LoginRequest;
use App\Http\Resources\ChannelApi\User\Profix;
use App\Models\Member;
use App\Models\MemberBind;
use App\Models\MemberSubscribe;
use App\Models\publicMiniProgram\PublicMiniprogramModel;
use App\Services\AliMini\ChannelMini;
use App\Services\KuaiShou\KsMiniProgram;
use App\Services\OpenWechat\ChannelOpenWechat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use mysql_xdevapi\Exception;
use Tymon\JWTAuth\Facades\JWTAuth;

class LoginController extends ApiController
{
    private function getApp()
    {
        // 获取公域微信小程序配置
        $model = PublicMiniprogramModel::where('type', '=', 1)->first();
        // 换取接口凭证

    }
    private function getLoginInfo($code)
    {
        $model = PublicMiniprogramModel::where('type', '=', 1)->first();
        $url = "/sns/jscode2session";
        $params = [
            "appid" => $model->appid,
            'secret' => $model->secret_key,
            'js_code' => $code,
            'grant_type' => 'authorization_code'
        ];
        return $this->wxApi($url, 'get',$params);
    }
    /**
     * 骗审接口
     */
    public function appStore(Request $request)
    {
        $type = $request->type;
        if(empty($type)){
            return $this->failed('获取小程序配置失败！');
        }
        $model = PublicMiniprogramModel::where('type','=', $type)->first();
        return $this->success($model);
    }
    /**
     * 登录
     */
    public function Login(LoginRequest $request)
    {
        $uniacid = $this->uniacid();
        $storeId = $this->isolateStore();
        $code = $request->code; //接收前端传入的code值
        // StatisticsDay::where('uniacid', $this->uniacid())->where('day', date("Y-m-d", time()))
        //     ->increment('pv', 1);
        if ($this->appType() == 1) {
            // 获取微信小程序实例
//            $app = $this->getApp();
//            $app = ChannelOpenWechat::miniProgram($this->uniacid() ?? 1);
            // 获取登录session
            $loginInfo = $this->getLoginInfo($code);
//            $loginInfo = $app->auth->session($code);
            if (isset($loginInfo->errcode)) {
                return $this->failed($loginInfo);
            }
            if ($loginInfo->errcode != 0){
                return $this->failed($loginInfo);
            }
            // 获取登录信息
            $session_key = $loginInfo->session_key;
            $openId = $loginInfo->openid;
            $unionid = $loginInfo->unionid;
            $user = MemberBind::with('member')->where('openid', $openId)
                ->whereHas('member', function ($q) use ($uniacid, $storeId) {
                    return $q->where('uniacid', $uniacid)->where('storeId', $storeId);
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
                    ->whereHas('member', function ($q) use ($uniacid, $storeId) {
                        return $q->where('uniacid', $uniacid)->where('storeId', $storeId);
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
                    if ($memberSub) {
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
                'uniacid' => $uniacid,
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
                ->whereHas('member', function ($q) use ($uniacid, $storeId) {
                    return $q->where('uniacid', $uniacid)->where('storeId', $storeId);
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
                'uniacid' => $uniacid,
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
     * @params Mixin
     * */
    private function wxApi($url, $method,$params)
    {
        $host = "https://api.weixin.qq.com";
        // 初始化cURL会话
        $ch = curl_init();
        // 设置cURL选项
        curl_setopt($ch, CURLOPT_URL, $host.$url); // 目标URL
        if ($method == 'post'){
            curl_setopt($ch, CURLOPT_POST, true); // 发起POST请求
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params)); // POST参数
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // 返回响应而不是输出
        // 执行cURL会话
        $response = curl_exec($ch);
        // 关闭cURL会话
        curl_close($ch);
        // 打印响应内容
//        echo $response;
        try {
            $res = json_decode($response);
            return $res;
        }catch (\JsonException $e){
            return $response;
        }
    }
}
