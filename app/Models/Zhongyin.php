<?php

namespace App\Services;

use App\Exports\ExcelExport;
use App\Traits\ResourceTrait;
use Maatwebsite\Excel\Facades\Excel;

class ZhongYinService
{

    public static  function sha256_with_rsa_signData($data, $secretKey)
    {
        $str=$secretKey;
        $str = chunk_split($str, 64, "\n");
        $secretKey = "-----BEGIN RSA PRIVATE KEY-----\n$str-----END RSA PRIVATE KEY-----\n";
        $pkeyid  = openssl_pkey_get_private($secretKey);
        openssl_sign($data,$sign,$pkeyid,OPENSSL_ALGO_SHA256);
        openssl_free_key($pkeyid);
        return base64_encode($sign);
    }

    public static  function get_sign_data($arr)
    {
        $data = "";
        ksort($arr);
        foreach($arr as $value){
            $data = $data . $value;
        }
        return $data;
    }

    public static function send_post($url, $post_data) {
        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => "Content-type:application/json",
                'content' => $post_data
            )
        );
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        return $result;//聚合后台返回报文为json串经过编码后的纯字符串数据
    }

    public static function verify($data,$serverSign,$publicKey)
    {
        $str=$publicKey;
        $str = chunk_split($str, 64, "\n");
        $publicKey = "-----BEGIN PUBLIC KEY-----\n$str-----END PUBLIC KEY-----\n";
        $pubkeyid = openssl_pkey_get_public($publicKey);
        $r = openssl_verify($data, base64_decode($serverSign), $pubkeyid, OPENSSL_ALGO_SHA256);
        openssl_free_key($pubkeyid);
        return $r;
    }

    public static function pay(){
        //商户私钥，PKCS1
        $secretKey = "MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQDI0DDlser2HOAfu0Nyjj54siSJad/ddONhKs5oo1cPieLkSR+r+KOJpuwC7G+8hXeiyv4+Iw2xwXV20YFMaJ51NNstA9hCnzeeoFsiAqtI9xLtvQviOEGgJtp602Qhg4aOn5K1tUG0yyEur7I9bhklhbgm1OHBRp4Bq4GRogazIOeEYLKO8HpSIZZ5v+q1bJ7LtHmtssOJ72gBP51Pmi5EQaUmFHSJchezxDB/7e0kOcsufYOWWZ6c5AFfis5aRj4+nI4QTUul+TExq94o2NoaBGoU+1mY9l0wEaBWftexLMA6I5DIzazGz3JrkwqyEybFqMKYUhY5Hp2SpEXxghSjAgMBAAECggEAOUoz+QPWPZqlUkgxiNgN+I8SH2XmKR70077LnkyjRNqYsXovH/fSaC4H/RmJEyacqAPznrSPbpv7sAc7NRpPwq5urRwAsZkM3CNxfHV4eluKueqKHmLgThlnkU0Hrwv/8maSC2cHtNWSxOf5GM91OyA4FlP7iBFmeKF+WJb6BFYnqhQHibU36JMSscU4fSrV9+C0gJzGrFCGMvQNbvT4J+F+kWt1eXx4UXabtm2kRDsvv1UNcRYboVeWOaQzewnVIuoqJrv/2jMmP3G16gRGqGUmQEab5SNZeGb+y/1vMBO1wzmwZwlfyyuu0WVJHyPXkh9YdWBb+rjdWIefwYPWSQKBgQD9cY0VKWJ9GVQ9t52+NhPZMp4RXkANVn8T9HceYGFK1okc4YZEEALlOX6JzykwO9vAwTsgBNzBK3kWGF+Bn4geNDeanm/USa6MafUC007wMYh8MOi8OmwTsh0ysgoAvMSAtTTLu+1sRtsmtw5tBuzeYbK4/boQ2zx+9Zcpm9bwPQKBgQDK1ryJCkzxi6AhxfRpVuxlxXfHr0ndTdyZEKCQh1JQHjdJei/PxKxArVuqX4df2Dtfbvy9mvEBNtOSvf8ZpCWC5otiM3frBzIPclhSceRvq46jLmwhg7zz9hUUwQBKZiJ5x57Tt919DV9vockxNUZ6CczYCuJ8sRQGsCbqj+uGXwKBgCZ8d9Ae2LXmgsRcfbfEvn7dEOPSTCe6WtHM7cnPGPckXO3l0V3EkKv+bxi+PKL2dAhxT2ktU9iBoYGZceddMm5jv43bQWAbUEZCUNJ9BB+1ZeR2COGMfZ0ADy8DmkFCaRSib8IAZ61plR3r7mTgCFg8vccZwIeOw0EESlAnOhyBAoGAPXxeZkQjEs06W0KAVjYtLqRvLK6r+1OQ3S03HtiGURb3t2Q8fgSmGmzybnDKvnjzdnhUUL1Y9P9DnyXM4F7rqARul/P2E954hEorD8LKdjdQ45I84OPoMSOVPFdM2UCgjr2+HXuMvk2BcnteW3ZEyNWTrKGQCjA7W9Ol2FCMGo0CgYEA+2RccJotMdVk1NnwgknlrmH9ZsLqAI0Si9VViUDWe66CpgQ0UisRY/fvfdagc4eX8cIHR2INxIlcn38pIypbRH6yC1z/AqmtKhEkpD6DDnErDfSM79NIHMAJW3VG1A5AcYN+cuPKx59gFeSk4Kr866F0MkiCkIadaUNxGbw7iE0=";
        //平台公钥
        $publicKey = "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAjBSmvi3Xmvrne2heayLd0yPPz1J7JlMCiTg8qVU0vyFAytglP0V3SHGjwwMUI0MAXDENf0FoFlVmltvVtiXqQG4zZUNec2kgM2/uA0dfLr5yyuqTO/IR1B0BrJ3COguMYIM48GE+DIiiCfRYgjSQxCMD8zZriX7p1up2cJRJqGsOxgoiv6HekmQUGfyb4ZoSHAaksniDR1nZ/CEJrhp03S8pLN8OmM2Y5PSZgMx5e4HgTkqDrnijZzVEnPCjXcWlR6+vFuFVqahyr4qAXFuS4wj0/GSXONos5Ihm/Cuj2SaJpyPChSaWfrGQPuxUj/CamjR9KnwqEfwmC6i2qYI95wIDAQAB";
        //商户公钥
        $publicKey1 = "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAyNAw5bHq9hzgH7tDco4+eLIkiWnf3XTjYSrOaKNXD4ni5Ekfq/ijiabsAuxvvIV3osr+PiMNscF1dtGBTGiedTTbLQPYQp83nqBbIgKrSPcS7b0L4jhBoCbaetNkIYOGjp+StbVBtMshLq+yPW4ZJYW4JtThwUaeAauBkaIGsyDnhGCyjvB6UiGWeb/qtWyey7R5rbLDie9oAT+dT5ouREGlJhR0iXIXs8Qwf+3tJDnLLn2DllmenOQBX4rOWkY+PpyOEE1LpfkxMaveKNjaGgRqFPtZmPZdMBGgVn7XsSzAOiOQyM2sxs9ya5MKshMmxajCmFIWOR6dkqRF8YIUowIDAQAB";
        $json = '{
                "requestId":"test0001",
                "service":"CreateBocCashier",
                "version":"2.0",
                "ipAddress":"",
                "signType":"RSA2",
                "merchantId":"138619080029800",
                "terminalNo":"88011746",
                "cashierLanguage":"zh_TW",
                "amount":"2",
                "originalAmount":"100",
                "merchantPreferentialCnName":"测试",
                "merchantPreferentialEnName":"test",
                "subject":"测试商品",
                "productDesc":"",
                "payChannel":"ALL",
                "DefaultUI":"AASQR",
                "groupUserId":"",
                "productCode":"PCWEB",
                "mercOrderNo":"20200919160200",
                "orderDate":"20200919",
                "orderTime":"160200",
                "validNumber":"",
                "notifyUrl":"1.do",
                "pageUrl":"2.do",
                "reserved1":"",
                "reserved2":"",
                "reserved3":"",
                "supplier":"",
                "referUrl":"1.com",
                "businessType":"5",
                "hotelName":"",
                "checkInTime":"",
                "checkOutTime":"",
                "flightNumber":"",
                "departureTime":"",
                "admissionNoticeUrl":"",
                "goodsInfo":"",
                "totalQuantity":"",
                "otherBusinessType":"test"
            }';
        $arr = json_decode($json,true);
        //获取签名串
        $data = self::get_sign_data($arr);
        //echo "签名串=".$data.PHP_EOL."<br>";
        //获取merchantSign

        $merchantSign = self::sha256_with_rsa_signData($data, $secretKey);

        $arr['merchantSign'] = $merchantSign;
        $url = "https://aas.bocmacau.com/w/rsa/mercapi_ol";
        //请求报文整串url编码
        $post_data = urlencode(json_encode($arr));
        //post请求

        $return_data = urldecode(self::send_post($url, $post_data));

        dd($return_data);die;



        //echo "返回报文=".$return_data.PHP_EOL."<br>";
        $arr = json_decode($return_data,true);
        $serverSign = $arr['serverSign'];
        unset($arr['serverSign']);


        //返回报文验签，1=验签成功 ，0=验签失败，-1=内部错误
        $data = self::get_sign_data($arr);
        echo "验签字符串=".$data.PHP_EOL."<br>";
        echo "返回报文验签结果：".self::verify($data,$serverSign,$publicKey);



    }


}
