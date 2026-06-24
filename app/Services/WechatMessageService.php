<?php

namespace App\Services;

use App\Models\MemberBind;
use App\Models\MemberSubscribe;
use App\Models\Sms;
use App\Models\SmsLog;
use App\Models\User;
use App\Models\WechatReply;
use EasyWeChat\Kernel\Messages\MiniProgramPage;
use EasyWeChat\Kernel\Messages\Raw;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Overtrue\EasySms\EasySms;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class WechatMessageService extends BaseService
{
    public static function event($app, $message, $uniacid)
    {
        if ($message['Event'] == 'subscribe') {
            $user = $app->user->get($message['FromUserName']);
            $wechatBind = MemberBind::where(function ($q) use ($user) {
                return $q->where('unionid', $user['unionid'])->where('type', 2);
            })->first();

            $miniBind = MemberBind::where(function ($q) use ($user) {
                return $q->where('unionid', $user['unionid'])->where('type', 1);
            })->first();
            if (empty($wechatBind) && !empty($miniBind)) {
                $memberBind = new MemberBind();
                $memberBind->userId = $miniBind->userId;
                $memberBind->type = 2;
                $memberBind->unionid = $user['unionid'];
                $memberBind->openid = $user['openid'];
                $memberBind->nickname = $miniBind->member->nickname;
                $memberBind->mobile = $miniBind->member->mobile ?? '';
                $memberBind->avatar = $miniBind->member->avatar ?? '';
                $memberBind->data = json_encode([], 320);
                $memberBind->save();
            }
            $memberSubscribe = MemberSubscribe::where('openid', $user['openid'])->first();
            if (empty($memberSubscribe)) {
                $memberSubscribe = new MemberSubscribe();
                $memberSubscribe->openid = $user['openid'];
                $memberSubscribe->unionid = $user['unionid'] ?? '';
                $memberSubscribe->save();
            } else {
                $memberSubscribe->subscribe = $user['subscribe'];
                $memberSubscribe->save();
            }
            $model = WechatReply::where('channel', 2)->where('state', 1)->where('uniacid', $uniacid)->first();
            $keyword = empty($model) ? false : $model->keyword;
            $replyList = WechatReply::where('channel', 1)->where('state', 1)->where('uniacid', $uniacid)->where('keyword', 'like', "%{$keyword}%")->get();
            Log::info($replyList);
            foreach ($replyList as $key => $reply) {
                $messages = $reply->getMessage($message['FromUserName']);
                Log::info($messages);
                foreach ($messages as $key => $item) {
                    $app->customer_service->message(
                        new Raw(json_encode($item))
                    )->send();
                }
            }
        } else if ($message['Event'] == 'CLICK' || $message['MsgType'] == 'text') {
            $keyword = $message['EventKey'] ?? $message['Content'];
            $replyList = WechatReply::where('channel', 1)->where('state', 1)->where('uniacid', $uniacid)->where('keyword', 'like', "%{$keyword}%")->get();
            if (empty($replyList->toarray())) {
                $model = WechatReply::where('channel', 3)->where('state', 1)->where('uniacid', $uniacid)->first();
                if ($model) {
                    $reply = WechatReply::where('uniacid', $uniacid)->where('keyword', $model->keyword)->first();
                    if (!empty($reply)) {
                        $messages = $reply->getMessage($message['FromUserName']);
                        foreach ($messages as $key => $item) {
                            $app->customer_service->message(
                                new Raw(json_encode($item))
                            )->send();
                        }
                    }
                }
            } else {
                Log::info($replyList);
                foreach ($replyList as $key => $reply) {
                    $messages = $reply->getMessage($message['FromUserName']);
                    Log::info($messages);
                    foreach ($messages as $key => $item) {
                        $app->customer_service->message(
                            new Raw(json_encode($item))
                        )->send();
                    }
                }
            }
        } elseif ($message['Event'] == 'unsubscribe') {
            $memberSubscribe = MemberSubscribe::where('openid', $message['FromUserName'])->first();
            if ($memberSubscribe) {
                $memberSubscribe->subscribe = 0;
                $memberSubscribe->save();
            }
        }
        return '';
    }
}
