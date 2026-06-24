<?php

namespace App\Services;

use App\Http\Resources\Admin\AdminResource\Admin as AdminResource;
use App\Http\Resources\ChannelApi\User\Profix;
use App\Http\Resources\Home\UserResource\UserCheckLogin;
use App\Models\Admin;
use App\Models\Admin\Apply;
use App\Models\Admin\HandleLog;
use App\Models\Member;
use App\Models\MemberBind;
use App\Models\MemberSubscribe;
use App\Models\Plug;
use App\Models\SmsLog;
use App\Models\Staff;
use App\Models\Store;
use App\Models\User;
use App\Models\UserAccount;
use App\Models\UserWechat;
use App\Services\OpenWechat\ChannelOpenWechat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserService extends BaseService
{
    // login
    public function login($username = 'username', $auth = 'user', $onlyAdmin = false)
    {
        $request = Request();
        //$credentials = request([$username, 'password']);
        $appType = $request->header('appType', 'pc');
        // 登陆成功修改时间和ip
        if ($auth == 'admin') {
            $admin_model = Admin::where('mobile', $request->username)->orWhere('username', $request->username)->first();
            if (!$admin_model) {
                throw new  BadRequestHttpException(__('auth.user_error'));
            }
            if (!Hash::check($request->password, $admin_model->password)) {
                throw new  BadRequestHttpException(__('auth.password'));
            }
            if ($admin_model->status == 0) {
                throw new  BadRequestHttpException('账号正在审核中,请稍后再试');
            }

            if ($admin_model->status == 2) {
                throw new  BadRequestHttpException('此账号已被拉黑,请联系管理员');
            }

            if ($admin_model->status == 3) {
                throw new  BadRequestHttpException('账号不存在,请先注册账号');
            }
            //$admin_model = Admin::where('mobile', $request->username)->first();
            if ($onlyAdmin && $admin_model->id != 1) {
                throw new  BadRequestHttpException('无登录权限');
            }
            if ($admin_model->uniacid) {
                $apply = Apply::find($admin_model->uniacid);
                if ($apply->admin->status != 1) {
                    throw new BadRequestException('当前平台服务已被禁用，请联系管理员咨询');
                }

                if ($apply->status != 1 || strtotime($apply->endTime) < time()) {
                    throw new BadRequestException('当前平台服务已到期或已被禁用，请联系管理员咨询');
                }
            }
            if (!$admin_model->isAdmin) {
                if ($appType == 'store' && !in_array('business', PlugService::applyPlug($admin_model->uniacid))) {
                    throw new BadRequestException('该账号暂无权限，请联系管理员');
                }
                if ($appType == 'cashier' && !in_array('cashier', PlugService::applyPlug($admin_model->uniacid))) {
                    throw new BadRequestException('该账号暂无权限，请联系管理员');
                }
                if ($admin_model->role->module == 'store') {
                    if (!in_array(appType($appType), $admin_model->role->appList)) {
                        throw new BadRequestException('该账号暂无权限，请联系管理员');
                    }
                }
            }
            $zs = getSysInfo();
            if ($admin_model->id == 1) {
                $admin_model->mobile = $zs['phone'];
            }
            $admin_model->login_time = now();
            $admin_model->last_login_time = $admin_model->login_time;
            $admin_model->ip = request()->getClientIp();
            $admin_model->save();
        }


        $token = JWTAuth::fromUser($admin_model);
        $key = 'login:Api:' . $admin_model->id . ':pc:token';
        Cache::set($key, $token);
        $route = $request->route()->getAction()['as'];
        $channel =  explode('/', $request->path())[0];
        $log = new HandleLog();
        $log->username = $admin_model->username;
        $log->userId = intval($admin_model->id);
        $log->uniacid = $admin_model->uniacid ?: $request->header('uniacid', 0);
        $log->type = 1;
        $log->route = $route;
        $log->method = $request->method();
        $log->ip = $request->ip();
        $log->input = '';
        $log->created_at = date('Y-m-d H:i:s', time());
        $log->channel = $channel;
        $log->save();   # 记录日志
        $data = [
            'token' => $token,
            'user_info' => new AdminResource($admin_model),
        ];
        return $this->format($data);
    }

    // login
    public function mobileLogin($username = 'mobile', $auth = 'user', $onlyAdmin = false)
    {
        $request = Request();
        //$credentials = request([$username, 'password']);
        // 登陆成功修改时间和ip
        if ($auth == 'admin') {
            $admin_model = Admin::where('mobile', $request->mobile)->first();
            if (!$admin_model) {
                throw new  BadRequestHttpException(__('auth.user_error'));
            }
            if (!SmsService::checkCode('register', $request->mobile, $request->verifyCode)) {
                throw new  BadRequestHttpException('验证码错误');
            };
            if ($admin_model->status == 0) {
                throw new  BadRequestHttpException('用户审核中');
            }

            if ($admin_model->status == 2) {
                throw new  BadRequestHttpException('此账号已被禁用,请联系管理员');
            }

            if ($admin_model->status == 3) {
                throw new  BadRequestHttpException('用户审核未通过');
            }
            //$admin_model = Admin::where('mobile', $request->username)->first();
            if ($onlyAdmin && $admin_model->id != 1) {
                throw new  BadRequestHttpException('无登录权限');
            }
            if ($admin_model->uniacid) {
                $apply = Apply::find($admin_model->uniacid);
                if ($apply->admin->status != 1) {
                    throw new BadRequestException('当前平台服务已被禁用，请联系管理员咨询');
                }

                if ($apply->status != 1) {
                    throw new BadRequestException('当前平台服务已到期或已被禁用，请联系管理员咨询');
                }
            }
            if (!env('APP_DEBUG')) {
                $zs = getSysInfo();
                if ($admin_model->id == 1) {
                    $admin_model->mobile = $zs['phone'];
                }
            }
            $admin_model->login_time = now();
            $admin_model->last_login_time = $admin_model->login_time;
            $admin_model->ip = request()->getClientIp();
            $admin_model->save();
        }

        $token = JWTAuth::fromUser($admin_model);
        $key = 'login:Api:' . $admin_model->id . ':pc:token';
        Cache::set($key, $token);
        $route = $request->route()->getAction()['as'];
        $channel =  explode('/', $request->path())[0];
        $log = new HandleLog();
        $log->username = $admin_model->username;
        $log->userId = intval($admin_model->id);
        $log->uniacid = $admin_model->uniacid ?: 0;
        $log->type = 1;
        $log->route = $route;
        $log->method = $request->method();
        $log->ip = $request->ip();
        $log->input = '';
        $log->created_at = date('Y-m-d H:i:s', time());
        $log->channel = $channel;
        $log->save();   # 记录日志
        $data = [
            'token' => $token,
            'user_info' => new AdminResource($admin_model),
        ];
        return $this->format($data);
    }

    // 获取用户信息
    public function getUserInfo($auth = 'user')
    {
        try {
            $info = auth($auth)->user();
            if ($auth == 'admin') {
                return new AdminResource($info);
            }
            return auth($auth)->user();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 微信用户注册逻辑
     */
    public static function miniRegister($request, $type = 1)
    {
        try {
            $uniacid = $request->header('uniacid');
            $code = $request->code; //接收前端传入的code值
            $openId = $request->openid;
            if ($openId == 'undefined') {
                throw new BadRequestException("openId:" . $openId);
            }
            $unionid = $request->unionid;
            if ($request->mobile) {
                $user = Member::where('mobile', $request->mobile)->where('uniacid', $uniacid)->first();
            } else {
                $user = false;
            }
            Db::beginTransaction();
            if ($user) {
                $memberBind = MemberBind::where('openId', $openId)->where('type', $type)->first();
                if (empty($memberBind)) {
                    $memberBind = new MemberBind();
                    $memberBind->userId = $user->id;
                    $memberBind->type = $type;
                    $memberBind->unionid = $unionid ?? '';
                    $memberBind->openid = $openId;
                    $memberBind->nickname = $request->nickname ?? '';
                    $memberBind->mobile = $request->mobile ?? '';
                    $memberBind->avatar = $request->avatar ?? '';
                    $memberBind->data = json_encode([], 320);
                    $memberBind->save();
                }
                DB::commit();
                $token = JWTAuth::fromUser($user);
                return  ['token' => $token, 'userInfo' => new Profix($user)];
            } else {
                if ($unionid) {
                    $user = MemberBind::with('member')->where(function ($q) use ($unionid) {
                        if ($unionid) {
                            return $q->where('unionid', $unionid);
                        }
                        return $q;
                    })->first();
                    if ($user) {
                        $user = $user->member;
                    } else {
                        $user = Member::create([
                            'nickname' => $request->nickname,
                            'mobile' => $request->mobile ?: '',
                            'avatar' => $request->avatar,
                            'score' => $type,
                            'uniacid' => $uniacid,
                            'password' => Hash::make('123456'),
                            'state' => 1,
                        ]);
                        $memberSub = MemberSubscribe::where('unionid', $unionid)->first();
                        if ($memberSub) {
                            $memberBind = new MemberBind();
                            $memberBind->userId = $user->id;
                            $memberBind->type = 2;
                            $memberBind->unionid = $memberSub->unionid ?? '';
                            $memberBind->openid = $memberSub->openId ?? '';
                            $memberBind->nickname = $user->nickname;
                            $memberBind->mobile = $user->mobile ?: '';
                            $memberBind->avatar = $user->avatar;
                            $memberBind->data = json_encode([], 320);
                            $memberBind->save();
                        }
                    }
                } else {
                    $user = Member::create([
                        'nickname' => $request->nickname,
                        'mobile' => $request->mobile ?: '',
                        'avatar' => $request->avatar,
                        'score' => $type,
                        'uniacid' => $uniacid,
                        'password' => Hash::make('123456'),
                        'state' => 1,
                    ]);
                }
            }
            $memberBind = new MemberBind();
            $memberBind->userId = $user->id;
            $memberBind->type = $type;
            $memberBind->unionid = $unionid;
            $memberBind->openid = $openId;
            $memberBind->nickname = $request->nickname;
            $memberBind->mobile = $request->mobile ?: '';
            $memberBind->avatar = $request->avatar;
            $memberBind->data = json_encode([], 320);
            $memberBind->save();
            $memberBind->refresh();
            DB::commit();
            $token = JWTAuth::fromUser($memberBind->member);
            return  ['token' => $token, 'userInfo' => new Profix($memberBind->member)];
        } catch (\Exception $e) {
            DB::rollBack();
            throw new BadRequestException($e->getMessage());
        }
    }

    /**
     *
     * {
     *   "openid": "OPENID",
     *  "nickname": NICKNAME,
     *  "sex": 1,
     *    "province":"PROVINCE",
     *   "city":"CITY",
     *   "country":"COUNTRY",
     *   "headimgurl":"https://thirdwx.qlogo.cn/mmopen/g3MonUZtNHkdmzicIlibx6iaFqAc56vxLSUfpb6n5WKSYVY0ChQKkiaJSgQ1dZuTOgvLLrhJbERQQ4eMsv84eavHiaiceqxibJxCfHe/46",
     *   "privilege":[ "PRIVILEGE1" "PRIVILEGE2"     ],
     *  "unionid": "o6_bmasdasdsad6_2sgVt7hMZOPfL"
     * }
     */
    public static function wechatRegister($request, $type = 2)
    {
        try {
            $smsCode = Cache::get('register.' . $request->mobile);
            $code = $request->code;
            if ($smsCode != $code) {
                throw new BadRequestException(__("sms.code_error"));
            }
            $uniacid = $request->header('uniacid');
            $code = $request->code; //接收前端传入的code值
            $wechatUser = $request->all();
            $user = Member::where('mobile', $request->mobile)->where('uniacid', $uniacid)->first();
            if ($user) {
                $memberBind = MemberBind::where('openId', $wechatUser['openid'])->where('type', $type)->first();
                if (empty($memberBind)) {
                    $memberBind = new MemberBind();
                    $memberBind->userId = $user->id;
                    $memberBind->type = $type;
                    $memberBind->unionid = $wechatUser['unionid'] ?? '';
                    $memberBind->openid = $wechatUser['openid'];
                    $memberBind->nickname = $wechatUser['nickname'];
                    $memberBind->mobile = $request->mobile ?: '';
                    $memberBind->avatar = $wechatUser['headimgurl'];
                    $memberBind->data = json_encode([], 320);
                    $memberBind->save();
                }
                $token = JWTAuth::fromUser($user);
                Cache::set("userLoginOpenid:" . $type . ':' . $user->userId, $wechatUser['openid']);
                return  ['token' => $token, 'userInfo' => new Profix($user)];
            } else {
                $unionid = $wechatUser['unionid'];
                if ($unionid) {
                    $user = MemberBind::with('member')->where(function ($q) use ($unionid) {
                        if ($unionid) {
                            return $q->where('unionid', $unionid);
                        }
                        return $q;
                    })->first();
                    if ($user) {
                        $user = $user->member;
                    } else {
                        $user = Member::create([
                            'nickname' => $wechatUser['nickname'],
                            'mobile' => $request->mobile ?: '',
                            'avatar' => $wechatUser['headimgurl'],
                            'score' => $type,
                            'uniacid' => $uniacid,
                            'password' => Hash::make('123456'),
                            'state' => 1,
                        ]);
                    }
                } else {
                    $user = Member::create([
                        'nickname' => $wechatUser['nickname'],
                        'mobile' => $request->mobile ?: '',
                        'avatar' => $wechatUser['headimgurl'],
                        'score' => $type,
                        'uniacid' => $uniacid,
                        'password' => Hash::make('123456'),
                        'state' => 1,
                    ]);
                }
            }
            $memberBind = new MemberBind();
            $memberBind->userId = $user->id ?: 0;
            $memberBind->type = $type;
            $memberBind->unionid = $wechatUser['unionid'];
            $memberBind->openid = $wechatUser['openid'];
            $memberBind->nickname = $wechatUser['nickname'];
            $memberBind->mobile = $request->mobile ?: '';
            $memberBind->avatar = $wechatUser['headimgurl'];
            $memberBind->data = json_encode([], 320);
            if (empty($user)) {
                DB::beginTransaction();
                $user = Member::create([
                    'nickname' => $wechatUser['nickname'],
                    'mobile' => $request->mobile ?: '',
                    'avatar' => $wechatUser['headimgurl'],
                    'score' => $type,
                    'uniacid' => $uniacid,
                    'password' => Hash::make('123456'),
                    'state' => 1,
                ]);
                $memberBind->userId = $user->id;
            }
            $memberBind->save();
            $memberBind->refresh();
            DB::commit();
            $token = JWTAuth::fromUser($memberBind->member);
            return  ['token' => $token, 'userInfo' => new Profix($memberBind->member)];
        } catch (\Exception $e) {
            DB::rollBack();
            throw new BadRequestException($e->getMessage());
        }
    }


    /**
     * h5注册
     */
    public static function h5Register($request, $type = 5)
    {
        DB::beginTransaction();
        $uniacid = $request->header('uniacid');
        $code = $request->code; //接收前端传入的code值
        $smsCode = Cache::get('register.' . $request->mobile);
        if ($smsCode != $code) {
            throw new BadRequestException(__("sms.code_error"));
        }
        $user = Member::where('mobile', $request->mobile)->first();
        if ($user) {
            $memberBind = MemberBind::where('mobile', $request->mobile)->where('type', $type)->first();
            if (empty($memberBind)) {
                $memberBind = new MemberBind();
                $memberBind->userId = $user->id ?: 0;
                $memberBind->type = $type;
                $memberBind->unionid = '';
                $memberBind->openid = '';
                $memberBind->nickname = '';
                $memberBind->mobile = $request->mobile;
                $memberBind->avatar = '';
                $memberBind->data = json_encode([], 320);
                $memberBind->save();
            }
            $token = JWTAuth::fromUser($user);
            return  ['token' => $token, 'userInfo' => new Profix($user)];
        } else {
            $user = Member::create([
                'nickname' => '新用户',
                'mobile' => $request->mobile,
                'avatar' => '',
                'uniacid' => $uniacid,
                'score' => $type,
                'password' => Hash::make('123456'),
                'state' => 1,
            ]);
            $memberBind = new MemberBind();
            $memberBind->userId = $user->id ?: 0;
            $memberBind->type = $type;
            $memberBind->unionid = '';
            $memberBind->openid = '';
            $memberBind->nickname = '';
            $memberBind->mobile = $request->mobile;
            $memberBind->avatar = '';
            $memberBind->data = json_encode([], 320);
            $memberBind->save();
        }

        DB::commit();
        $token = JWTAuth::fromUser($user);
        return  ['token' => $token, 'userInfo' => new Profix($memberBind->member)];
    }

    public static function ksRegister($request, $type = 8)
    {
        try {
            $uniacid = $request->header('uniacid');
            $code = $request->code; //接收前端传入的code值
            $openId = $request->openid;
            if ($openId == 'undefined') {
                throw new BadRequestException("openId:" . $openId);
            }
            $unionid = $request->unionid;
            $user = Member::where('mobile', $request->mobile)->where('uniacid', $uniacid)->first();
            Db::beginTransaction();
            if ($user) {
                $memberBind = MemberBind::where('openId', $openId)->where('type', $type)->first();
                if (empty($memberBind)) {
                    $memberBind = new MemberBind();
                    $memberBind->userId = $user->id;
                    $memberBind->type = $type;
                    $memberBind->unionid = $unionid ?? '';
                    $memberBind->openid = $openId;
                    $memberBind->nickname = $request->nickname ?? '';
                    $memberBind->mobile = $request->mobile;
                    $memberBind->avatar = $request->avatar ?? '';
                    $memberBind->data = json_encode([], 320);
                    $memberBind->save();
                }
                DB::commit();
                $token = JWTAuth::fromUser($user);
                return  ['token' => $token, 'userInfo' => new Profix($user)];
            } else {
                $user = Member::create([
                    'nickname' => $request->nickname,
                    'mobile' => $request->mobile ?: '',
                    'avatar' => $request->avatar,
                    'score' => $type,
                    'uniacid' => $uniacid,
                    'password' => Hash::make('123456'),
                    'state' => 1,
                ]);
            }
            $memberBind = new MemberBind();
            $memberBind->userId = $user->id;
            $memberBind->type = $type;
            $memberBind->unionid = $unionid;
            $memberBind->openid = $openId;
            $memberBind->nickname = $request->nickname;
            $memberBind->mobile = $request->mobile ?: '';
            $memberBind->avatar = $request->avatar;
            $memberBind->data = json_encode([], 320);
            $memberBind->save();
            $memberBind->refresh();
            DB::commit();
            $token = JWTAuth::fromUser($memberBind->member);
            return  ['token' => $token, 'userInfo' => new Profix($memberBind->member)];
        } catch (\Exception $e) {
            DB::rollBack();
            throw new BadRequestException($e->getMessage());
        }
    }
}
