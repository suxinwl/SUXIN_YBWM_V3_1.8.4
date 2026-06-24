<?php

namespace App\Models;

use App\Services\ConfigService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;

class Express extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
    public static function addOrder($kuaidicom,$order)
    {
        /*
         * 京东	jd
        京东快运	jingdongkuaiyun
        德邦	debangkuaidi
        顺丰	shunfeng
        极兔	jtexpress
        圆通	yuantong
        申通	shentong
        中通	zhongtong
        韵达	yunda
        菜鸟直送(丹鸟)	cainiaozhisong
        EMS	ems
        跨越	kuayue
        */
        $storeInfo = Store::where('id', $order->storeId)->first();
        $config = ConfigService::getStoreConfig('kuaidi', $order->storeId);
        // 参数设置
        $key=$config['key'];
        $secret=$config['secret'];
        list($msec, $sec) = explode(' ', microtime());
        $t = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);    // 当前时间戳
        $domain = 'https://' . Request()->server('HTTP_HOST');
        $param = array(
            'kuaidicom' =>$kuaidicom,              // 快递公司的编码
            'recManName' =>$order->address['contact'],             // 收件人姓名
            'recManMobile' =>$order->address['mobile'],           // 收件人手机号
            'recManPrintAddr' => $order->address['address'].$order->address['description'],        // 收件人所在完整地址
            'sendManName' => $storeInfo->name,            // 寄件人姓名
            'sendManMobile' =>$storeInfo->mobile,          // 寄件人的手机号
            'sendManPrintAddr' => $storeInfo->region[0].$storeInfo->region[1].$storeInfo->region[2].$storeInfo->address,       // 寄件人所在的完整地址
            'callBackUrl' => $domain.'/channel/notify/kuaidi',            // callBackUrl订单信息回调，默认仅支持http
            'cargo' => '普货',                  // 物品名称
            'payment' => 'SHIPPER',         // 支付方式，SHIPPER: 寄付（默认）。不支持到付
            'weight' => '0.1',              // 物品总重量KG，不需带单位
            'remark' => $order->notes,             // 备注
            'salt' => '',                   // 签名用随机字符串
            'dayType' => '今天',             // 预约日期，例如：今天/明天/后天
            'pickupStartTime' => '',        // 预约起始时间（HH:mm），例如：09:00
            'pickupEndTime' => '',          // 预约截止时间（HH:mm），例如：10:00
            'valinsPay' => null             // 保价额度，单位：元
        );

        // 请求参数
        $post_data = array();
        $post_data['param'] = json_encode($param, JSON_UNESCAPED_UNICODE);
        $post_data['key'] = $key;
        $post_data['t'] = $t;
        $sign = md5($post_data['param'] . $t . $key . $secret);
        $post_data['sign'] = strtoupper($sign);
        $url = 'https://poll.kuaidi100.com/order/borderapi.do?method=bOrder';    // 商家寄件下单接口地址
        //发送post请求
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $result = curl_exec($ch);
        $data = json_decode($result, true);
        return $data;

    }

    public function price()
    {

        // 参数设置
        $key='YRbqhJpx9558';
        $secret='de0419a949cb43ef915114b8e9baca2b';
        list($msec, $sec) = explode(' ', microtime());
        $t = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);    // 当前时间戳
        $param = array(
            'kuaidiCom' => '',              // 快递公司的编码
            'sendManPrintAddr' => '',       // 出发地地址，最小颗粒到市级
            'recManPrintAddr' => '',        // 目的地地址，最小颗粒到市级
            'weight' => '0.5',              // 物品总重量KG，不需带单位
            'serviceType' => null           // 业务类型
        );
        // 请求参数
        $post_data = array();
        $post_data['param'] = json_encode($param, JSON_UNESCAPED_UNICODE);
        $post_data['key'] = $key;
        $post_data['t'] = $t;
        $sign = md5($post_data['param'] . $t . $key . $secret);
        $post_data['sign'] = strtoupper($sign);
        $url = 'https://poll.kuaidi100.com/order/borderapi.do?method=price';    // 商家寄件下单价格接口地址
        //发送post请求
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $result = curl_exec($ch);
        $data = json_decode($result, true);
        return $data;
    }
}
