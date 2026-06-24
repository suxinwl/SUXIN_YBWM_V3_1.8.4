<?php

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller as BaseController;
use App\Http\Helpers\ApiResponse;
use App\Models\Mini\Register;
use App\Models\MiniVersion;
use App\Models\OpenWechatAuth;
use App\Models\WecahtServerGuard\Guard as WecahtServerGuardGuard;
use App\OpenWechat\Services\Admin;
use App\Services\OpenWechat\AdminOpenWechat;
use App\Services\OpenWechat\ChannelOpenWechat;
use App\Services\WechatMessageService;
use EasyWeChat\OpenPlatform\Server\Guard;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

use const WeChatPay\WechatpaySignature;

class OpenPlatformController extends ApiController
{
    public function event()
    {
        try {
            $app = AdminOpenWechat::openPlatform();
            $server = $app->server;
            $request = request();
            // if (isCli()) {
            //     $app['openPlatform']->rebind('request', new Request($request->get(), $request->post(), [], [], [], $request->server(), $request->getContent()));
            //     $this->application['openPlatform']->register(new ServiceProvider());
            // }
            Log::info($server->getMessage());
            // 处理授权成功事件，其他事件同理
            $server->push(function ($message) {
                Log::info($message);
            }, Guard::EVENT_AUTHORIZED);
            // 处理取消授权事件，其他事件同理
            $server->push(function ($message) {
                Log::info($message);
                $model = OpenWechatAuth::where('authorizer_appid', $message['AuthorizerAppid'])->first();
                if ($model) {
                    // if($model->type == 'mini'){
                    //     $app = ChannelOpenWechat::miniProgram($model->uniacid);
                    // }else{
                    //     $app = ChannelOpenWechat::officialAccount($model->uniacid);
                    // }
                    // if($model->open_appid){
                    //     $app->account->unbindFrom($model->open_appid);
                    // }
                    $model->delete();
                }
            }, Guard::EVENT_UNAUTHORIZED);
            // 处理更新授权事件，其他事件同理
            // 快速处理小程序推送事件，其他事件同理
            $server->push(function ($message) {
                Log::info($message);
                $mini = Register::where('auth_code', $message['info']['auth_code'])
                    ->where('legal_persona_wechat', $message['info']['legal_persona_wechat'])
                    ->where('legal_persona_name', $message['info']['legal_persona_name'])
                    ->where('component_phone', $message['info']['component_phone'])
                    ->where('code', $message['info']['code'])
                    ->where('name', $message['info']['name'])
                    ->where('state', 0)->first();
                if (empty($mini)) {
                    return true;
                }
                $mini->appid = $message['appid'];
                $mini->msg = $message['msg'];
                $mini->status = $message['status'];
                $mini->state = $message['status'] == 0 ? 1 : 2;
                $mini->save();
                return true;
            }, Guard::EVENT_THIRD_FAST_REGISTERED);
            return $server->serve();
        } catch (\Exception $e) {
            Log::info($e->getMessage());
            return $server->serve();
        }
    }

    public function auth($uniacid)
    {
        $doman = Request()->getHttpHost();
        $app = AdminOpenWechat::openPlatform();
        $res = $app->handleAuthorize(Request()->auth_code);
        if ($res['errcode'] != 0) {
            throw new BadRequestException($res['errmsg']);
        }
        $data = $app->getAuthorizer($res['authorization_info']['authorizer_appid']);
        $authorization_info = $res['authorization_info'];
        $authorizer_info = $data['authorizer_info'];
        if (isset($authorizer_info['MiniProgramInfo'])) {
            $backUrl = url('/admin/#/channel/wechatMangement');
        } else {
            $backUrl = url('/admin/#/channel/officialAccounts');
        }
        $modelOther = OpenWechatAuth::where('authorizer_appid', $res['authorization_info']['authorizer_appid'])->first();
        //如果有授权的小程序/公众号则加入授权后过的开放平台
        $openModle = OpenWechatAuth::where('uniacid', $uniacid)->where('authorizer_appid', '!=', $res['authorization_info']['authorizer_appid'])->first();
        if ($modelOther) {
            if ($modelOther->uniacid != $uniacid) {
                throw new BadRequestException('当前小程序已授权其他店铺');
            }
        }
        $model = new OpenWechatAuth();

        if (isset($authorizer_info['MiniProgramInfo'])) {
            $model->type = 'mini';
            $miniData = OpenWechatAuth::where('uniacid', $uniacid)->where('type', $model->type)->first();
            if ($miniData) {
                $miniData->delete();
            }
            $progrom =  $app->miniProgram($authorization_info['authorizer_appid'], $authorization_info['authorizer_refresh_token']);
            $progrom->domain->setWebviewDomain([$doman]);
            $progrom->domain->modify([
                'action' => 'add',
                'requestdomain' => ["https://" . $doman, "https://apis.map.qq.com"],
                'wsrequestdomain' => ["wss://" . $doman],
                'uploaddomain' => ["https://" . $doman],
                'downloaddomain' => ["https://" . $doman],
                'udpdomain' => ["udp://" . $doman],
                'tcpdomain' => ["tcp://" . $doman]
            ]);
        } else {
            $model->type = 'official';
            $officialData = OpenWechatAuth::where('uniacid', $uniacid)->where('type', 'official')->first();
            if ($officialData) {
                $officialData->delete();
            }
            $progrom  =  $app->officialAccount($authorization_info['authorizer_appid'], $authorization_info['authorizer_refresh_token']);
        }
        $have = $progrom->account->getBinding();
        if (empty($openModle) && $have['errcode'] == 0) {
            //$progrom->account->unbindFrom($have['open_appid']);
            $model->open_appid = $have['open_appid'];
        } else {
            if ($openModle && !empty($openModle->open_appid)) {
                if ($have['errcode'] == 0 && $openModle->open_appid != $have['open_appid']) {
                    $progrom->account->unbindFrom($have['open_appid']);
                    $res = $progrom->account->bindTo($openModle->open_appid);
                    if ($res['errcode'] != 0) {
                        throw new BadRequestException($res['errmsg']);
                    };
                } elseif ($openModle->open_appid == $have['open_appid']) {
                    $model->open_appid = $have['open_appid'];
                } else {
                    $res = $progrom->account->bindTo($openModle->open_appid);
                    if ($res['errcode'] != 0) {
                        throw new BadRequestException($res['errmsg']);
                    };
                }
                $model->open_appid = $openModle->open_appid;
            } elseif (empty($openModle)) {
                //新建开放平台
                $open_appid =  $progrom->account->create();
                if ($open_appid['errcode'] == 0) {
                    $progrom->account->bindTo($open_appid['open_appid']); //绑定开放平台
                    $model->open_appid = $open_appid['open_appid'];
                } else {
                    throw new BadRequestException($res['errmsg']);
                }
            }
        }

        $model->authorizer_appid = $authorization_info['authorizer_appid'];
        $model->authorizer_access_token = $authorization_info['authorizer_access_token'];
        $model->authorizer_refresh_token = $authorization_info['authorizer_refresh_token'];
        $model->expires_time = $authorization_info['expires_in'];
        $model->version = '';
        $model->user_name = $authorizer_info['user_name'];
        $model->uniacid = $uniacid;
        $model->func_info = $authorization_info['func_info'];
        $model->data = $authorizer_info;
        if ($model->save()) {
            return redirect($backUrl);
        };
        throw new BadRequestException("授权失败");
    }

    public function server(Request $request, $appid)
    {
        try {
            $openPlatform = AdminOpenWechat::openPlatform();
            $server = $openPlatform->server;
            Log::info($server->getMessage());
            Log::info($appid);
            $OpenWechatAuthModel = OpenWechatAuth::where('authorizer_appid', $appid)->first();
            if (!$OpenWechatAuthModel) {
                return $server->serve();
            }
            if ($OpenWechatAuthModel->type == 'mini') {
                $app = $openPlatform->miniProgram($OpenWechatAuthModel->authorizer_appid, $OpenWechatAuthModel->authorizer_refresh_token);
                $server  = $app->server;
                $server->push(function ($message) {
                    if ($message['Event'] == 'weapp_audit_success') {
                        Log::info('小程序代码审核成功');
                        $model = OpenWechatAuth::select(['authorizer_appid', 'uniacid'])->where('user_name', $message['ToUserName'])->first();
                        $miniVersion = MiniVersion::where('appid', $model->authorizer_appid)->orderBy('audit_time', 'desc')->first();
                        $miniVersion->state = 0;
                        $miniVersion->audit_ok_time = date("Y-m-d H:i:s", $message['CreateTime']);
                        if ($miniVersion->autoRelease) {
                            $res = ChannelOpenWechat::release($model->uniacid);
                            if ($res['code'] == 0) {
                                $miniVersion->state = 9;
                                $miniVersion->release_time = date("Y-m-d H:i:s", time());
                            }
                        }
                        $miniVersion->save();
                    }

                    if ($message['Event'] == 'weapp_audit_fail') {
                        Log::info('小程序代码审核失败');
                        $model = OpenWechatAuth::select(['authorizer_appid', 'uniacid'])->where('user_name', $message['ToUserName'])->first();
                        $miniVersion = MiniVersion::where('appid', $model->authorizer_appid)->orderBy('audit_time', 'desc')->first();
                        $miniVersion->state = 1;
                        // $miniVersion->audit_ok_time = date("Y-m-d H:i:s",$message['CreateTime']);
                        $miniVersion->audit_res = json_encode(['title' => $message['Reason'], 'ScreenShot' => $message['ScreenShot']], 320);
                        $miniVersion->save();
                    }

                    if ($message['Event'] == 'weapp_audit_delay') {
                        Log::info('小程序代码审核延期');
                        $model = OpenWechatAuth::select(['authorizer_appid', 'uniacid'])->where('user_name', $message['ToUserName'])->first();
                        $miniVersion = MiniVersion::where('appid', $model->authorizer_appid)->orderBy('audit_time', 'desc')->first();
                        $miniVersion->state = 4;
                        // $miniVersion->audit_ok_time = date("Y-m-d H:i:s",$message['CreateTime']);
                        $miniVersion->audit_res = json_encode(['title' => $message['Reason'], 'ScreenShot' => []], 320);
                        $miniVersion->save();
                    }
                });
                return $server->serve();
            } elseif ($OpenWechatAuthModel->type == 'official') {
                $app = $openPlatform->officialAccount($OpenWechatAuthModel->authorizer_appid, $OpenWechatAuthModel->authorizer_refresh_token);
                $server  = $app->server;
                $server->push(function ($message)  use ($app, $OpenWechatAuthModel) {
                    return WechatMessageService::event($app, $message, $OpenWechatAuthModel->uniacid);
                });
                return $server->serve();
            }
        } catch (\Exception $e) {
            Log::info($e->getMessage());
        }
    }
}
