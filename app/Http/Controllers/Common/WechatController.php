<?php

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller as BaseController;
use App\Models\Admin\AdminBind;
use EasyWeChat\Factory;
use App\Services\OfficelIneService;
use Illuminate\Support\Facades\Cache;
use Tymon\JWTAuth\Facades\JWTAuth;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class WechatController extends ApiController
{
    public $config;

    public function __construct()
    {
        $this->config = [
            /**
             * 账号基本信息，请从微信公众平台/开放平台获取
             */
            'app_id'  => '',         // AppID
            'secret'  => '',     // AppSecret
            'token'   => '',          // Token
            'aes_key' => '',                    // EncodingAESKey，兼容与安全模式下请一定要填写！！！

            /**
             * 指定 API 调用返回结果的类型：array(default)/collection/object/raw/自定义类名
             * 使用自定义类名时，构造函数将会接收一个 `EasyWeChat\Kernel\Http\Response` 实例
             */
            'response_type' => 'array',

            /**
             * 日志配置
             *
             * level: 日志级别, 可选为：
             *         debug/info/notice/warning/error/critical/alert/emergency
             * path：日志文件位置(绝对路径!!!)，要求可写权限
             */
            'log' => [
                'default' => 'dev', // 默认使用的 channel，生产环境可以改为下面的 prod
                'channels' => [
                    // 测试环境
                    'dev' => [
                        'driver' => 'single',
                        'path' => app_path() . DIRECTORY_SEPARATOR . 'storage/easywechat.log',
                        'level' => 'debug',
                    ],
                    // 生产环境
                    'prod' => [
                        'driver' => 'daily',
                        'path' => app_path() . DIRECTORY_SEPARATOR . 'storage/easywechat.log',
                        'level' => 'info',
                    ],
                ],
            ],

            /**
             * 接口请求相关配置，超时时间等，具体可用参数请参考：
             * http://docs.guzzlephp.org/en/stable/request-config.html
             *
             * - retries: 重试次数，默认 1，指定当 http 请求失败时重试的次数。
             * - retry_delay: 重试延迟间隔（单位：ms），默认 500
             * - log_template: 指定 HTTP 日志模板，请参考：https://github.com/guzzle/guzzle/blob/master/src/MessageFormatter.php
             */
            'http' => [
                'max_retries' => 1,
                'retry_delay' => 500,
                'timeout' => 5.0,
                // 'base_uri' => 'https://api.weixin.qq.com/', // 如果你在国外想要覆盖默认的 url 的时候才使用，根据不同的模块配置不同的 uri
            ],

            /**
             * OAuth 配置
             *
             * scopes：公众平台（snsapi_userinfo / snsapi_base），开放平台：snsapi_login
             * callback：OAuth授权完成后的回调页地址
             */
            'oauth' => [
                'scopes'   => ['snsapi_userinfo'],
                'callback' => '/examples/oauth_callback.php',
            ],
        ];
    }
    public function Index()
    {
        $app = Factory::officialAccount($this->config);
        // ... 前面部分省略
        $app->server->push(function ($message) {
            switch ($message['MsgType']) {
                case 'event':
                    if ($message['Event'] == 'subscribe') {
                        return '欢迎关注';
                    };
                    if ($message['Event'] == 'SCAN') {
                        try {
                            $EventKey =  $message['EventKey'];
                            $EventKey = explode("_", $EventKey);
                            if (isset($EventKey[1])) {
                                $EventKey = explode(".", $EventKey[1]);
                                if ($EventKey[0] == 'login') {
                                    $state = $EventKey[1];
                                    $app = Factory::officialAccount($this->config);
                                    $data = $app->user->get($message['FromUserName']);
                                    Cache::put('data_' . $state, $data);
                                    $adminBind = AdminBind::where(['openid' => $data['openid'], 'type' => 'wechat', 'channel' => 'open'])->orWhere('unionid', $data['unionid'])->first();
                                    if ($adminBind) {
                                        $token = JWTAuth::fromUser($adminBind->admin);
                                        Cache::put($state, ['type' => 1, 'token' => $token]);
                                        return "登录成功";
                                    } else {
                                        Cache::put($state, ['type' => 2, 'state' => $state]);
                                        return "您还没有绑定账号，请绑定";
                                    }
                                }
                            }
                        } catch (\Exception $e) {
                            return $e->getMessage();
                        }
                    }
                    break;
                case 'text':
                    return '收到文字消息';
                    break;
                case 'image':
                    return '收到图片消息';
                    break;
                case 'voice':
                    return '收到语音消息';
                    break;
                case 'video':
                    return '收到视频消息';
                    break;
                case 'location':
                    return '收到坐标消息';
                    break;
                case 'link':
                    return '收到链接消息';
                    break;
                case 'file':
                    return '收到文件消息';
                    // ... 其它消息
                    break;
                default:
                    //return json_encode($message, 320);
                    break;
            }
        });
        return  $app->server->serve();
    }

    public function Qrcode()
    {
        try {
            $app = new OfficelIneService();
            $qrCode  = $app->getLoingCode();
            if ($qrCode == false) {
                return $this->failed(__('base.qrcode_error'));
            }
            return $this->success($qrCode);
        } catch (\Exception $e) {
            return $this->failed(__('base.qrcode_error'));
        }
    }
}
