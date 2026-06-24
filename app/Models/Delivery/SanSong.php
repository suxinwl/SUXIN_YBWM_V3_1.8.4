<?php

namespace App\Models\Delivery;

use App\Models\BaseModel;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SanSong extends BaseModel
{
    public static function request_post($url = '', $post_data)  {
        if (empty($url) || empty($post_data)) {
            return false;
        }
        $postUrl = $url;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$postUrl);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;

    }




    public static function  generateSignature($data, $appSecrty) {
        //return strtoupper(md5($partnerNo.$orderNo.$senderMobile.$key));
        //1.升序排序
        ksort($data);

        //2.字符串拼接
        $args = "";
        foreach ($data as $key => $value) {
            $args.=$key.$value;
        }
        $args = $appSecrty.$args;
        //3.MD5签名,转为大写
        $sign = strtoupper(md5($args));

        return $sign;
    }

}
