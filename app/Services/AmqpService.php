<?php
namespace App\Services;
use AlibabaCloud\SDK\Iot\V20180120\Iot;
use AlibabaCloud\Tea\Model;
use AlibabaCloud\Tea\Tea;
use AlibabaCloud\Tea\Utils\Utils;
use AlibabaCloud\Tea\Console\Console;
use AlibabaCloud\Tea\Exception\TeaError;
use Darabonba\OpenApi\Models\Config;
use AlibabaCloud\SDK\Iot\V20180120\Models\PubRequest;
class AmqpService
{
    public static function sendTopic()
    {
        //参数说明，请参见AMQP客户端接入说明文档。
        $accessKey = env('ALIYUN_IOT_ACCESS_KEY', '');
        $accessSecret = env('ALIYUN_IOT_ACCESS_SECRET', '');
        $consumerGroupId = "rHR0tPgFd0aDkEeaj5OH000100"; //	消费组ID
        $clientId = "viva";
        //iotInstanceId：实例ID。-
        $iotInstanceId = "iot-06z00gyj0xv7sgf";
        $timeStamp = round(microtime(true) * 1000);
        //签名方法：支持hmacmd5，hmacsha1和hmacsha256。
        $signMethod = "hmacsha1";
        //userName组装方法，请参见AMQP客户端接入说明文档。
        //若使用二进制传输，则userName需要添加encode=base64参数，服务端会将消息体base64编码后再推送。具体添加方法请参见下一章节“二进制消息体说明”。
        $userName = $clientId . "|authMode=aksign"
            . ",signMethod=" . $signMethod
            . ",timestamp=" . $timeStamp
            . ",authId=" . $accessKey
            . ",iotInstanceId=" . $iotInstanceId
            . ",consumerGroupId=" . $consumerGroupId
            . "|";
        $signContent = "authId=" . $accessKey . "&timestamp=" . $timeStamp;
        //计算签名，password组装方法，请参见AMQP客户端接入说明文档。
        $password = base64_encode(hash_hmac("sha1", $signContent, $accessSecret, $raw_output = TRUE));
        //接入域名，请参见AMQP客户端接入说明文档。
        $client = new Client('ssl://iot-06z00gyj0xv7sgf.amqp.iothub.aliyuncs.com:61614');
        $sslContext = ['ssl' => ['verify_peer' => true, 'verify_peer_name' => false],];
        $client->getConnection()->setContext($sslContext);

        //服务端心跳监听。
        $observer = new ServerAliveObserver();
        $client->getConnection()->getObservers()->addObserver($observer);
        //心跳设置，需要云端每30s发送一次心跳包。
        $client->setHeartbeat(0, 30000);
        $client->setLogin($userName, $password);
        try {
            $client->connect();
        } catch (StompException $e) {
            throw new BadRequestException($e->getMessage());
        }
        //无异常时继续执行。
        $stomp = new StatefulStomp($client);
        $bool=$stomp->subscribe('/topic/#');
        //echo "connect success";

        //while (true) {
            try {

                // 检查连接状态
                if (!$client->isConnected()) {
                    // echo "connection not exists, will reconnect after 10s.", PHP_EOL;
                    sleep(10);
                    $client->connect();
                    $stomp->subscribe('/topic/#');
                    // echo "connect success", PHP_EOL;
                }

                //处理消息业务逻辑。
                $body = $stomp->read()->body;
                if ($body) {
                    var_dump($body);die;
                }
                var_dump($stomp->read()->body); ;
            } catch (HeartbeatException $e) {
                //echo 'The server failed to send us heartbeats within the defined interval.', PHP_EOL;
                $stomp->getClient()->disconnect();
                return false;
            } catch (\Exception $e) {
                $stomp->getClient()->disconnect();
                //throw new BadRequestException($e->getMessage());
                return false;
            }
        //}
    }

    //三木森云音箱
    public static function sendVoice(){
        $config = new Config([]);
// 您的AccessKey ID。
        $config->accessKeyId = env('ALIYUN_IOT_ACCESS_KEY', '');
// 您的AccessKey Secret。
        $config->accessKeySecret = env('ALIYUN_IOT_ACCESS_SECRET', '');
// 您的可用区ID。
        $config->regionId = "cn-shanghai";
        $client = new Iot($config);
        //dd($client);die;
        //$binary='{"cmd":"voice","msg":"支付宝收款69.20元"}';
        //$str="Hello";
        $binary='{"cmd":"voice","msg":"微信收款256.99元"}';
        //$binary=StrToBin($str);
        //var_dump($binary);
        $binary=base64_encode($binary);
        // var_dump($binary);die;
//        {"cmd":"voice","msg":"微信收款119.02元"}
        try {
            $request = new PubRequest([
                // 物联网平台实例ID。
                "iotInstanceId" => "iot-06z00gyj0xv7sgf",
                // 产品ProductKey。
                "productKey" => "hg1pDJQwMxk",//hg1pDJQwMxk
                // 要发送的消息主体，hello world Base64 String。
                "messageContent" => $binary,
                // 要接收消息的设备的自定义Topic。
                "topicFullName" => "/hg1pDJQwMxk/xMKdDOb3Ej4CuYKJHtg7/user/get",///hg1pDJQwMxk/xMKdDOb3Ej4CuYKJHtg7/user/get
                // 指定消息的发送方式，支持QoS0和QoS1。
                "qos" => 0
            ]);
            //调用pub接口。
            $response = $client->pub($request);
            dd($response);
        }catch (Exception $e) {
            dd($e->getMessage());
        }
    }
}
