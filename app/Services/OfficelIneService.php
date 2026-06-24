<?php

namespace App\Services;

use App\Models\Sms;
use App\Models\SmsLog;
use App\Models\User;
use App\Models\Admin\AdminBind;
use EasyWeChat\Factory;
use Illuminate\Support\Facades\Cache;
use Tymon\JWTAuth\Facades\JWTAuth;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class OfficelIneService extends BaseService
{
    public $config;
    public $app;
    public function __construct(){
        $this->config = [
            /**
             * 账号基本信息，请从微信公众平台/开放平台获取
             */
            'app_id'  => 'wx30c4c979ca1f1fbe',         // AppID
            'secret'  => '859e4967e987fa9ab01e19a0ff0bab17',     // AppSecret
            'token'   => 'token',          // Token
            'aes_key' => 'PgRqJoJmldFpp5mk841PHoXk4Uy7vpA9FAc7cMq2YcD',                    // EncodingAESKey，兼容与安全模式下请一定要填写！！！

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
        $this->app =  Factory::officialAccount($this->config);
    }

    public function getLoingCode(){
        $requestId = md5(time());
        $result = $this->app->qrcode->temporary('qrscene_login.' . $requestId,300);
        if($result['url']){
            $img='data:image/png;base64,' . base64_encode(QrCode::format('png')->size(100)->generate($result['url']));
            return ['state'=>$requestId,'img'=>$img,'expirat'=>300];
        }
        return false;
    }

    public static function message($message){
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
                                $model = new self();
                                $app = Factory::officialAccount($model->config);
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
                return json_encode($message, 320);
                break;
        }
    }
}
