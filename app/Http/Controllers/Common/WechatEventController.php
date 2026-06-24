<?php
namespace App\Http\Controllers\Common;
use App\Http\Controllers\Controller ;
use App\Models\Config;
use App\Services\OpenWechat\AdminOpenWechat;
use EasyWeChat\Factory;
use Illuminate\Http\Request;
use EasyWeChat\OpenPlatform\Server\Guard;
use Illuminate\Support\Facades\Log;
class WechatEventController extends Controller{
    public function authorizationReceive(){
        $postStr = file_get_contents('php://input');
        //file_put_contents('weixin.log',$postStr);
        $config=Config::getSystemSet('wechatOpenConfig',0);
        $config=object_array($config);
        $encodingAesKey =$config['encodingAesKey']?: "eC5p1GygrrRrIHG9www11wb1rhRnwWbR8awRrz11e1H";
        $encode_ticket = asimplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);

        if (empty($postStr) || empty($encode_ticket)) {
            exit('fail');
        }

        $decode_ticket = aaes_decode($encode_ticket->Encrypt, $encodingAesKey);
        $ticket_xml = asimplexml_load_string($decode_ticket, 'SimpleXMLElement', LIBXML_NOCDATA);
        if (empty($ticket_xml)) {
            exit('fail');
        }
        if (!empty($ticket_xml->ComponentVerifyTicket) &&'component_verify_ticket' == $ticket_xml->InfoType) {
            $ticket = strval($ticket_xml->ComponentVerifyTicket);
            $config['component_verify_ticket']=$ticket;
            //file_put_contents('ticket.log',$ticket);
            $options = [
                'app_id'   => 'wxad8db08b2d19a021',
                'secret'   => '190e4e1c84107265e827c84f4e060ca3',
                'token'    => 'P44m4L5554oAllAOGtT765562Q776g55',
                'aes_key'  => 'eC5p1GygrrRrIHG9www11wb1rhRnwWbR8awRrz11e1H'
            ];
            $app = Factory::make('openPlatform',$options);
            $app->verify_ticket->setTicket($ticket);
            $config['component_verify_ticket_time']=time();
            Config::saveSystemSet($config,'wechatOpenConfig',0,'微信开放平台配置');
        }else{
            file_put_contents('wechatError.log',$postStr);
        }
        echo 'success';die;
    }

    public function newsReceive(Request $request){
        $openPlatform = AdminOpenWechat::openPlatform();
        $server = $openPlatform->server;
        // 处理授权成功事件，其他事件同理
        $server->push(function ($message) {
            Log::info($message);
            // $message 为微信推送的通知内容，不同事件不同内容，详看微信官方文档
            // 获取授权公众号 AppId： $message['AuthorizerAppid']
            // 获取 AuthCode：$message['AuthorizationCode']
            // 然后进行业务处理，如存数据库等...
        }, Guard::EVENT_AUTHORIZED);
        return $server->serve();
        return 'success';
    }
}
