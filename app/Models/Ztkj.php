<?php
namespace App\Models;
class Ztkj extends BaseModel{
    //管理商基本信息接口
    public static function account(){
        date_default_timezone_set('PRC'); //设置时区
        $url='https://api-gateway.zthysms.com/partner/v1/account';
        $data = self::httpRequest($url); //暂时关闭
        return $data;
    }
    //发送短信
    public static function sendSms(){
        $sub_name='jay_zhou';
        $url     = "https://api-gateway.zthysms.com/partner/v1/sms/sub-accounts/message/.$sub_name./template";
        $records = array();

        for ($i = 0; $i < 1; $i++) {
            $record = array("mobile" => "17607186026", "tpContent" => array("valid_code" =>
                "123456"));
            array_push($records, $record);
        }

        $date     = array(
            'temId'      => '100027', //模板id
            'signature' => '【云贝科技】',
            'records'   => $records
        );
        $ret = self::httpPost($url, $date);
        var_dump($ret);die;
    }
    public  static function httpPost($url, $date) { // 模拟提交数据函数
        #管理商账号
        $username = "yunbeiglshy";
        #管理商接口密码
        $password = '0406CC7CC27ADCDb';
        $auth = 'Basic '.base64_encode("$username:$password");
        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 对认证证书来源的检查
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false); // 从证书中检查SSL加密算法是否存在
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
        curl_setopt($curl, CURLOPT_POST, true); // 发送一个常规的Post请求
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($date)); // Post提交的数据包
        //curl_setopt($curl, CURLOPT_POSTFIELDS,  http_build_query($postArr)); // Post提交的数据包
        curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
        curl_setopt($curl, CURLOPT_HEADER, false); // 显示返回的Header区域内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); // 获取的信息以文件流的形式返回
        curl_setopt($curl, CURLOPT_HEADER, false); //开启header
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charset=utf-8',
            'Authorization: '.$auth
        )); //类型为json
        $result = curl_exec($curl); // 执行操作
        if (curl_errno($curl)) {
            echo 'Error POST' . curl_error($curl);
        }
        curl_close($curl); // 关键CURL会话
        return $result; // 返回数据
    }


    public static function httpRequest($url, $data = '', $method = 'GET', $json = false){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
        if ($method == 'POST') {
            curl_setopt($curl, CURLOPT_POST, 1);
            if ($data != '') {
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                if ($json) {
                    // curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json')); //原来的
                    $headers = array("Content-Type: application/json;charset='utf-8'", "Accept: application/json");
                    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
                }
            }
        }else{
            $name = 'yunbeiglshy'; //管理商账号
            $pwd = '0406CC7CC27ADCDb'; // 管理商密码
            $headers = array('Authorization:Basic ' . base64_encode("$name:$pwd"));
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($curl);
        curl_close($curl);
        return $result;
    }
}
