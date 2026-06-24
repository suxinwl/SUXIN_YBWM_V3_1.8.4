<?php

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller as BaseController;
use App\Http\Resources\ChannelApi\User\Profix;
use App\Models\Admin\AdminBind;
use App\Models\Member;
use App\Models\MemberBind;
use EasyWeChat\Factory;
use App\Services\OfficelIneService;
use App\Services\OpenWechat\ChannelOpenWechat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use View;

class WechatLoginController extends ApiController
{
    public function show(Request $request, $uniacid, $storeId = 0)
    {
        $app = ChannelOpenWechat::officialAccount($uniacid);
        if ($request->code) {
            $url =  $request->refererUrl;
            $user = $app->oauth->userFromCode($request->code)->toArray();
            if (empty($url)) {
                $url = "/alipay/index.html?uniacid=$uniacid" . $storeId == 0 ? '' : '&storeId=' . $storeId;
            } else {
                $url = base64_decode($url);
            }
            View::share('url', $url);
            $wechatUser = $user['raw'];
            // return view('wechatLogin', ['wechatUser' => $wechatUser,'url'=>$url]);
            $user = MemberBind::with(['member' => function ($q) use ($uniacid) {
                return $q->where('uniacid', $uniacid);
            }])->where('openid', $wechatUser['openid'])
                ->whereHas('member', function ($q) use ($uniacid, $storeId) {
                    return $q->where('uniacid', $uniacid)->where('storeId', $storeId);
                })
                ->where('type', $this->appType())
                ->first();
            if ($user) {
                if ($user->member->state != 1) {
                    return $this->failed('该用户已被拉黑', 402);
                }
                if (empty($user->unionid) && !empty($wechatUser['unionid'])) {
                    $user->unionid = $wechatUser['unionid'];
                    $user->save();
                }
                $token = JWTAuth::fromUser($user->member);
                Cache::set("userLoginOpenid:" . $this->appType() . ':' . $user->userId, $wechatUser['openid']);
                return view('wechatLogin', ['token' => $token, 'userInfo' => new Profix($user->member)]);
                //return $this->success(['token' => $token, 'userInfo' => new Profix($user->member)]);
            } elseif ($wechatUser['unionid']) {
                $user = MemberBind::with('member')
                    ->whereHas('member', function ($q) use ($uniacid, $storeId) {
                        return $q->where('uniacid', $uniacid)->where('storeId', $storeId);
                    })
                    ->where('unionid', $wechatUser['unionid'])
                    ->first();
                if ($user) {
                    if ($user->member->state != 1) {
                        return $this->failed('该用户已被拉黑', 402);
                    }
                    $memberBind = new MemberBind();
                    $memberBind->userId = $user->userId ?: 0;
                    $memberBind->type = $this->appType();
                    $memberBind->unionid = $wechatUser['unionid'];
                    $memberBind->openid = $wechatUser['openid'];
                    $memberBind->nickname = $wechatUser['nickname'];
                    $memberBind->mobile = $user->mobile ?: '';
                    $memberBind->avatar = $wechatUser['headimgurl'];
                    $memberBind->data = json_encode([], 320);
                    $memberBind->uniacid = $uniacid;
                    $memberBind->storeId = $storeId;
                    $memberBind->save();
                    $token = JWTAuth::fromUser($user->member);
                    Cache::set("userLoginOpenid:" . $this->appType() . ':' . $user->userId, $wechatUser['openid']);
                    return view('wechatLogin', ['token' => $token, 'userInfo' => new Profix($user->member)]);
                }
            }
            $user = Member::create([
                'nickname' => $res['nick_name'] ?? '用户_' . rand(1000000, 9999999),
                'mobile' => $request->mobile ?? '',
                'avatar' => $res['avatar'] ?? '',
                'uniacid' => $uniacid,
                'score' => $this->appType(),
                'password' => Hash::make('123456'),
                'state' => 1,
                'uniacid' => $uniacid,
                'storeId' => $storeId,
            ]);
            $memberBind = new MemberBind();
            $memberBind->userId = $user->id ?: 0;
            $memberBind->type =  $this->appType();
            $memberBind->unionid = $wechatUser['unionid'];
            $memberBind->openid = $wechatUser['openid'];
            $memberBind->nickname = '';
            $memberBind->mobile = $request->mobile ?? '';
            $memberBind->avatar = '';
            $memberBind->uniacid = $uniacid;
            $memberBind->storeId = $storeId;
            $memberBind->data = json_encode([], 320);
            $memberBind->save();
            Cache::set("userLoginOpenid:" . $this->appType() . ':' . $user->id, $wechatUser['openid']);
            $token = JWTAuth::fromUser($user);
            return view('wechatLogin', ['token' => $token, 'userInfo' => new Profix($user->member)]);
        }
        return  redirect($app->oauth->redirect($request->fullUrl()));
    }

    public function appType()
    {
        return 2;
    }
}
