<?php

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WechatMerchantController extends ApiController
{
    /**
     *进件
     */
    public function add(Request $request)
    {

        $uniacid = 1;
        $url = 'https://api.mch.weixin.qq.com/v3/applyment4sub/applyment/';
        $params = $request->all();
        $data = [
            //业务申请编号
            'business_code'     => $params['business_code'],
            //超级管理员信息
            'contact_info'      => [
                'contact_name'      => $this->getEncrypt($params['contact_name']), //超级管理员姓名
                'contact_id_number' => $this->getEncrypt($params['contact_id_number']), //超级管理员身份证件号码
                'mobile_phone'      => $this->getEncrypt($params['mobile_phone']), //联系手机
                'contact_email'     => $this->getEncrypt($params['contact_email']), //联系邮箱
            ],
            //主体资料
            'subject_info'      => [
                //主体类型SUBJECT_TYPE_INDIVIDUAL（个体户）SUBJECT_TYPE_ENTERPRISE（企业）SUBJECT_TYPE_INSTITUTIONS（党政、机关及事业单位）SUBJECT_TYPE_OTHERS（其他组织）
                'subject_type'  => 'SUBJECT_TYPE_ENTERPRISE',
                //营业执照
                'business_license_info' => [
                    'license_copy' => $params['license_copy'], //营业执照照片
                    'license_number' => $params['license_number'], //注册号/统一社会信用代码
                    'merchant_name' => $params['merchant_name'], //商户名称
                    'legal_person' => $params['legal_person'], //个体户经营者/法人姓名
                ],
                //经营者/法人身份证件
                'identity_info' => [
                    'id_doc_type'  => 'IDENTIFICATION_TYPE_IDCARD', //证件类型
                    'owner'        => true, //经营者/法人是否为受益人
                    //身份证信息
                    'id_card_info' => [
                        //身份证人像面照片
                        'id_card_copy'      => $params['id_card_copy'],
                        //身份证国徽面照片
                        'id_card_national'  => $params['id_card_national'],
                        //身份证姓名
                        'id_card_name'      => $this->getEncrypt($params['id_card_name']),
                        //身份证号码
                        'id_card_number'    => $this->getEncrypt($params['id_card_number']),
                        'card_period_begin' => $params['card_period_begin'], //身份证有效期开始时间
                        'card_period_end'   => $params['card_period_end'], //身份证有效期结束时间
                    ],
                ],
            ],
            //经营资料
            'business_info'     => [
                'merchant_shortname' => $params['merchant_shortname'],
                'service_phone'      => $params['service_phone'],
                'sales_info'         => [
                    'sales_scenes_type' => ['SALES_SCENES_STORE'],
                    //                    SALES_SCENES_STORE：线下场所
                    //                    SALES_SCENES_MP：公众号
                    //                    SALES_SCENES_MINI_PROGRAM：小程序
                    //                    SALES_SCENES_WEB：互联网网站
                    //                    SALES_SCENES_APP：APP
                    //                    SALES_SCENES_WEWORK：企业微信
                    //线下门店场景
                    'biz_store_info'    => [
                        'biz_store_name'     => $params['biz_store_name'], //线下场所名称
                        'biz_address_code'   => $params['biz_address_code'], //线下场所省市编码
                        'biz_store_address'  => $params['biz_store_address'], //线下场所地址
                        'store_entrance_pic' => [$params['store_entrance_pic']], //线下场所门头照片
                        'indoor_pic'         => [$params['indoor_pic']], //线下场所内部照片
                    ],
                ],
            ],
            //结算规则
            'settlement_info'   => [
                'settlement_id'      => $params['settlement_id'], //入驻结算规则ID
                'qualification_type' => $params['qualification_type'], //所属行业
                'activities_id'      => $params['activities_id'], //优惠费率活动ID
                'activities_rate'    => $params['activities_rate'], //优惠费率活动值
            ],
            //结算银行账户
            'bank_account_info' => [
                'bank_account_type' => 'BANK_ACCOUNT_TYPE_CORPORATE',
                'account_name'      => $this->getEncrypt($params['account_name']),
                'account_bank'      => $params['account_bank'],
                'bank_address_code' => $params['bank_address_code'],
                'bank_name'         => $params['bank_name'],
                'account_number' => $this->getEncrypt($params['account_number']),
            ],
            //补充材料
            //            'addition_info'=>[
            //                'legal_person_commitment'=>$params['legal_person_commitment'],
            //                'business_addition_pics'=>[
            //                    "47ZC6GC-NIO4kqg05InE4d2I6_H7I4"
            //                ],
            //            ],
        ];
        $merchant_id = '1384780002'; //服务商商户号
        $serial_no = '73CFCB57CB34D34E6ABAF3C05EB8BD99E8CED270'; //API证书序列号
        $mch_private_key = $this->getPrivateKey(public_path() . "/payment/" . 'apiclient_key_' . $uniacid . '.pem'); //读取商户api证书公钥 getPublicKey()获取方法
        $timestamp      = time(); //时间戳
        $nonce          = $this->nonce_str(); //获取随机字符串
        $sign           = $this->sign($url, 'POST', $timestamp, $nonce, json_encode($data), $mch_private_key, $merchant_id, $serial_no); //进行签名操作
        $header         = [ //设置发送的头信息
            'Authorization:WECHATPAY2-SHA256-RSA2048 ' . $sign, //签名
            'Accept:application/json',
            'User-Agent:' . $merchant_id, //服务商的商户号
            'Content-Type:application/json',
            'Wechatpay-Serial:' . $this->getzhengshu() //获取平台证书的序列号，注意：是平台证书，不是上文的商户证书序列号
        ];

        $result = $this->curl($url, json_encode($data), $header); //curl方法见下文
        $result = json_decode($result, true);
        dd($result);
        die;
        if ($result['applyment_id']) {
            //2000002273978023
        } else {
            $result['message'];
        }
    }

    /*
    *查询
    */
    public function query(Request $request)
    {
        /*判断请求类型（GET、POST、PUT、DELETE）*/

        $uniacid = 1;
        /*接收请求参数*/
        $no =  $request->applyment_id ?: '2000002273978023';
        /*判断必要参数是否存在*/
        if ($no) {
            $url  = 'https://api.mch.weixin.qq.com/v3/applyment4sub/applyment/applyment_id/' . $no;
            $merchant_id = '1384780002'; //服务商商户号
            $serial_no = '73CFCB57CB34D34E6ABAF3C05EB8BD99E8CED270'; //API证书序列号
            $mch_private_key = $this->getPrivateKey(public_path() . "/payment/" . 'apiclient_key_' . $uniacid . '.pem'); //读取商户api证书公钥 getPublicKey()获取方法
            $timestamp = time();
            $nonce  = $this->nonce_str();
            $body = "";
            $sign = $this->sign($url, 'GET', $timestamp, $nonce, $body, $mch_private_key, $merchant_id, $serial_no); //签名
            $header = [
                'Authorization: WECHATPAY2-SHA256-RSA2048 ' . $sign,
                'Accept: application/json',
                'User-Agent:' . $merchant_id,
                'Content-Type :application/json',
                'Wechatpay-Serial:' . $this->getzhengshu()
            ];
            $result = $this->curl($url, '', $header, 'GET');
            $result = json_decode($result, true);
            var_dump($result);
            die;
            $this->ret_data = $result;
        } else {
        }
    }

    /**
     *图片上传     文件大小不能超过2M   必须以JPG、BMP、PNG为后缀
     **/
    public function uploadMedia(Request $request)
    {
        $uniacid = 1;
        $url = 'https://api.mch.weixin.qq.com/v3/merchant/media/upload';
        header("Content-type:text/html;charset=utf-8");
        //$filename = public_path().'/yqjz/0/2022/03/09/8920a9ce97dac31bef6f0f56e1b7d688.png';
        $file = $request->file('file');
        if (1024 * 1024 * 2 < $request->file('file')->getSize()) {
            return false;
        }
        $merchant_id = '1384780002'; //服务商商户号
        $serial_no = '73CFCB57CB34D34E6ABAF3C05EB8BD99E8CED270'; //API证书序列号
        //获取私钥
        $mch_private_key = $this->getPrivateKey(public_path() . "/payment/" . 'apiclient_key_' . $uniacid . '.pem'); //读取商户api证书公钥 getPublicKey()获取方法

        $mime_type = $request->file('file')->extension();
        $mess          =     $this->binaryEncodeImage($file);
        $fileName = date('Y') . "/" . date('m') . "/" . date('d') . "/" . date("YmdHis") . rand(1111, 9999) . '.' . $file->getClientOriginalExtension();
        $data['filename'] = $fileName;
        $meta['filename'] = $fileName;
        $meta['sha256'] = hash_file('sha256', $file);
        $boundary = uniqid(); //分割符号
        $date = time();
        $nonce = $this->nonce_str(); //随机字符串
        $sign = $this->sign($url, 'POST', $date, $nonce, json_encode($meta), $mch_private_key, $merchant_id, $serial_no); //$http_method要大写
        $header[] = 'User-Agent:Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.108 Safari/537.36';
        $header[] = 'Accept:application/json';
        $header[] = 'Authorization:WECHATPAY2-SHA256-RSA2048 ' . $sign;
        $header[] = 'Content-Type:multipart/form-data;boundary=' . $boundary;
        $boundaryStr = "--{$boundary}\r\n";
        $out = $boundaryStr;
        $out .= 'Content-Disposition: form-data; name="meta"' . "\r\n";
        $out .= 'Content-Type: application/json' . "\r\n";
        $out .= "\r\n";
        $out .= json_encode($meta) . "\r\n";
        $out .=  $boundaryStr;
        $out .= 'Content-Disposition: form-data; name="file"; filename="' . $data['filename'] . '"' . "\r\n";
        $out .= 'Content-Type: ' . $mime_type . ';' . "\r\n";
        $out .= "\r\n";
        $out .= $mess . "\r\n";
        $out .= "--{$boundary}--\r\n";
        $r = $this->curl($url, $out, $header);
        $result = json_decode($r, true);
        if ($result['media_id']) {
            return $result['media_id'];
        } else {
            echo json_encode(['code' => '501', 'msg' => $result['err_msg']]);
            die;
        }
    }


    /**
     *加密字符串
     */
    private function getEncrypt($str)
    {
        //$str是要加密的字符串
        $public_key_path = public_path() . "/payment/cert.pem"; //微信支付平台证书中的公钥
        $public_key = file_get_contents($public_key_path);
        $encrypted  = '';
        if (openssl_public_encrypt($str, $encrypted, $public_key, OPENSSL_PKCS1_OAEP_PADDING)) {
            //base64
            $sign = base64_encode($encrypted);
        } else {
            throw new \Exception('encrypt failed');
        }
        return $sign;
    }

    /**
     *获取证书
     **/
    public function getzhengshu()
    {
        $url = "https://api.mch.weixin.qq.com/v3/certificates"; //获取地址
        $timestamp = time(); //时间戳
        $nonce = $this->nonce_str(); //获取一个随机字符串
        $body = "";
        $uniacid = 1;
        $mch_private_key = $this->getPrivateKey(public_path() . "/payment/" . 'apiclient_key_' . $uniacid . '.pem'); //调用获取商户私钥方法传证书文件路径进去
        $merchant_id = '1384780002'; //服务商商户号
        $serial_no = '73CFCB57CB34D34E6ABAF3C05EB8BD99E8CED270'; //API证书序列号
        $sign = $this->sign($url, 'GET', $timestamp, $nonce, $body, $mch_private_key, $merchant_id, $serial_no);

        $header = [
            'Authorization:WECHATPAY2-SHA256-RSA2048 ' . $sign,
            'Accept:application/json',
            'User-Agent:' . $merchant_id
        ];


        $result = $this->curl($url, '', $header, 'GET');
        $result = json_decode($result, true);

        $serial_no = $result['data'][0]['serial_no']; //获取的平台证书***

        $encrypt_certificate = $result['data'][0]['encrypt_certificate'];

        $sign_key = "81e7d7a68899ef1e7d7995adc4bcb77c";  //APIv3**，商户平台API安全中获取
        $result = $this->decryptToString($encrypt_certificate['associated_data'], $encrypt_certificate['nonce'], $encrypt_certificate['ciphertext'], $sign_key);

        file_put_contents(public_path() . "/payment/cert.pem", $result); //获取的文件临时保存到服务器

        return $serial_no; //返回平台证书***
    }

    /**
     *解密方法
     */
    public function decryptToString($associatedData, $nonceStr, $ciphertext, $aesKey)
    {
        $ciphertext = \base64_decode($ciphertext);
        if (function_exists('\sodium_crypto_aead_aes256gcm_is_available') && \sodium_crypto_aead_aes256gcm_is_available()) {
            return \sodium_crypto_aead_aes256gcm_decrypt($ciphertext, $associatedData, $nonceStr, $aesKey);
        }
        if (PHP_VERSION_ID >= 70100 && in_array('aes-256-gcm', \openssl_get_cipher_methods())) {
            $ctext = substr($ciphertext, 0, -16);
            $authTag = substr($ciphertext, -16);
            return \openssl_decrypt(
                $ctext,
                'aes-256-gcm',
                $aesKey,
                \OPENSSL_RAW_DATA,
                $nonceStr,
                $authTag,
                $associatedData
            );
        }
        throw new \RuntimeException('php7.1');
    }

    /**
     *curl请求方法
     **/
    public function curl($url, $data = [], $header, $method = 'POST')
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        if ($method == "POST") {
            curl_setopt($curl, CURLOPT_POST, TRUE);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        $result = curl_exec($curl);
        curl_close($curl);
        return $result;
    }

    /**
     *生成一个随机字符串
     */
    private function nonce_str($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /**
     *获取商户私钥
     */
    public static function getPrivateKey($path)
    {
        return openssl_get_privatekey(file_get_contents($path));
    }
    /**
     * 获取商户私钥
     * @return false|resource
     */
    public function get_Privatekey()
    {
        $private_key_file = public_path() . '/payment/apiclient_cert_1.pem'; //微信支付平台证书中的公钥
        //$mch_private_key  = openssl_pkey_get_private(file_get_contents($private_key_file));//获取私钥
        $mch_private_key  = file_get_contents($private_key_file); //获取私钥
        //var_dump(file_get_contents($private_key_file));die;
        return $mch_private_key;
    }
    public function sign($url, $http_method, $timestamp, $nonce, $body, $mch_private_key, $merchant_id, $serial_no)
    {
        $url_parts = parse_url($url);
        $canonical_url = ($url_parts['path'] . (!empty($url_parts['query']) ? "?${url_parts['query']}" : ""));
        $message = $http_method . "\n" .
            $canonical_url . "\n" .
            $timestamp . "\n" .
            $nonce . "\n" .
            $body . "\n";

        openssl_sign($message, $raw_sign, $mch_private_key, 'sha256WithRSAEncryption');
        $sign = base64_encode($raw_sign);

        $schema = 'WECHATPAY2-SHA256-RSA2048';
        $token = sprintf(
            'mchid="%s",nonce_str="%s",timestamp="%d",serial_no="%s",signature="%s"',
            $merchant_id,
            $nonce,
            $timestamp,
            $serial_no,
            $sign
        );
        //var_dump($token);die;
        return $token;
    }

    /**
     * 图片转化为二进制数据流
     * @desc  图片转化为二进制数据流
     * return string
     */

    public function binaryEncodeImage($img_file)
    {
        header("Content-type:text/html;charset=utf-8");
        $p_size = filesize($img_file);
        $img_binary = fread(fopen($img_file, "rb"), $p_size);
        return $img_binary;
    }
}
