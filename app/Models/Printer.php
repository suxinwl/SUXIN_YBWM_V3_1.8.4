<?php

namespace App\Models;

use App\Api\RpcService;
use App\Models\Order\OrderGoods;
use Illuminate\Support\Facades\Http;
use App\Config\YlyConfig;
use App\Oauth\YlyOauthClient;
use App\Api\PrinterService;
use App\Api\PrintService;
use Illuminate\Support\Facades\Redis;
use App\Models\Hardware;
use Illuminate\Support\Facades\Log;

use function Symfony\Component\String\s;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class Printer extends BaseModel
{
    //查询打印设备
    public static function getHardware($uniacid, $storeId, $type = '', $scene = '')
    {
        $query = Hardware::with('rule')
            ->where('uniacid', $uniacid)
            ->where('storeId', $storeId)->where('display', 1)
            ->when($scene, function ($query) use ($scene) {
                return $query->whereHas('rule', function ($query) use ($scene) {
                    return $query->where('scene', 'like', "%$scene%");
                });
            });
        if ($type) {
            $query = $query->where('type', $type);
        }
        $data = $query->get()->toArray();
        return $data;
    }
    /*飞蛾打印机*/
    public static function  feiPrint($device_config, $content = '测试打印内容', $operationType = 1, $printNum = 1)
    {
        $time = time();
        $params = $device_config['config'];
        $user = $params['feAcc'];
        $feie_key = $params['feUkey'];
        $contents = array(
            'user' => $user,
            'stime' => $time,
            'sig' => sha1($user . $feie_key . $time),
        );
        switch ($operationType) {
            case 1; //添加打印机
                $contents['apiname'] = 'Open_printerAddlist';
                break;
            case 2; //小票机打印订单
                $contents['apiname'] = 'Open_printMsg';
                $contents['sn'] = $params['feNum'];
                $contents['content'] = $content;
                $contents['times'] = $printNum ?: 1;
                break;
            case 3; //标签机打印订单
                $contents['apiname'] = 'Open_printLabelMsg';
                $contents['sn'] = $params['feNum'];
                $contents['content'] = $content;
                $contents['times'] = $printNum ?: 1;
                break;
            case 4; //删除打印机
                $contents['apiname'] = 'Open_printerDelList';
                $contents['snlist'] = $params['snlist'];
                break;
            case 5; //修改打印机
                $contents['apiname'] = 'Open_printerEdit';
                $contents['name'] = $params['feie_name'];
                $contents['sn'] = $params['feAcc'];
                break;
            case 6; //清空待打印队列
                $contents['apiname'] = 'Open_delPrinterSqs';
                $contents['sn'] = $params['feAcc'];
                break;
            case 7; //查询订单是否打印成功
                $contents['apiname'] = 'Open_queryOrderState';
                $contents['orderid'] = $params['feie_orderid'];
                break;
            case 8; //获取某台打印机状态
                $contents['apiname'] = 'Open_queryPrinterStatus';
                $contents['sn'] = $params['feAcc'];
                break;
        }
        $response = Http::asForm()->post('https://api.feieyun.cn/Api/Open/', $contents);
        return $response->body();
    }

    public static function feiContents($order)
    {
        $goods = OrderGoods::where('orderSn', $order['orderSn'])->get();
        $store = Store::where('id', $order['storeId'])->first()->toArray();
        $goods = $goods->toArray();
        switch ($order['payType']) {
            case 1:
                $payType = '微信支付';
                break;
            case 2:
                $payType = '支付宝支付';
                break;
            case 5:
                $payType = '余额支付';
                break;
            case 6:
                $payType = '现金支付';
                break;
            default;
                $payType = '余额支付';
                break;
        }
        switch ($order['diningType']) {
            case 0;
                $ddName = "立即送出";
                break;
            case 1;
                $ddName = "到店自提";
                break;
        }
        $order['serviceAt'] = $order['serverTime'];

        if ($order['appointment'] == 1) {
            $ddName = "预约单";
        }
        $content = "<CB>取单号  #" . $order['pickNo'] . "</CB><BR>";
        $content .= "<C>*" . $store['name'] . "*</C><BR>";
        if ($order['state'] == 7 or $order['state'] == 8) {
            $content .= "<B><C>--售后订单--</C></B><BR>";
            $content .= "<B><C>有订单申请售后了</C></B><BR>";
            $content .= "<B><C>请尽快处理!</C></B><BR>";
        } elseif ($order['state'] == 7) {
            $content .= "<BOLD><C>--取消订单--</C></BOLD><BR>";
            $content .= "<BOLD><C>订单已取消,请尽快处理</C></BOLD><BR>";
        } else {
            $content .= "<CB>--" . $payType . "--</CB><BR>";
            $content .= "<L><BOLD><C>【" . $ddName . "】</C></BOLD></L><BR>";
        }
        $content .= "期望送达时间：" . $order['serviceAt'] . "<BR>";
        $content .= "--------------------------------<BR>";
        $content .= "订单号：" . $order['orderSn'] . "<BR>";
        $content .= "下单时间：" . $order['payTime'] . "<BR>";
        if ($order['notes']) {
            $content .= "<L><BOLD>备注：" . $order['notes'] . "</BOLD></L><BR>";
        }
        $content .= "--------------------------------<BR>";
        $content .= "名称               数量   单价<BR>";
        $content .= "<L><BOLD>" . self::feiStyle($goods) . "</BOLD></L>";
        $content .= self::feiStyle(['0' => ['name' => '合计', 'num' => $order['goodsNum'], 'money' => $order['money']]]);
        $content .= "<C>----------其它费用----------</C>\n";
        if ($order['materialMoney']) {
            $content .= "加料：" . $order['materialMoney'] . "<BR>";
        }
        if ($order['deliveryMoney'] > 0) {
            $content .= "配送费：" . $order['deliveryMoney'] . "<BR>";
        }
        if ($order['boxMoney'] > 0) {
            $content .= "[包装费：+" . $order['boxMoney'] . "]<BR>";
        }
        $content .= "<L><BOLD>实付：" . '￥' . ' ' . $order['money'] . "</BOLD></L><BR><BR>";
        $content .= "<BOLD><B>" . $order['contacts']  . $order['address'] . "</B></BOLD><BR><BR>";
        $content .= "<C>门店电话：" . $store['storeMobile'] . "</C><BR>";
        $content .= "<BOLD><C>----#" . $order['orderSn'] . "完----</C></BOLD>\n";
        return $content;
    }

    static function labelAllContent(array $order)
    {
        $store = Store::where('id', $order['storeId'])->first()->toArray();
        $goods = OrderGoods::where('orderSn', $order['orderSn'])->get();
        $goods = $goods->toArray();
        $content = '';
        foreach ($goods as $key => $value) {
            $symbol = '/';
            $tableName = '单号';
            $sign = '#';
            $orderType = '外卖';
            $orderTex = '配送';
            $goodsNum = $order['goodsNum'];
            //$num = $key + 1;
            $content .= '<TEXT x="11" y="5" font="12" w="1" h="1" r="0">' . $tableName . '：' . $sign . $order['pickNo'] . "   " . ' 数量：' . $goodsNum . '/' . $goodsNum . '</TEXT>';
            $content .= '<TEXT x="11" y="40" font="12" w="1" h="2" r="0">' . $value["name"] . '</TEXT>';
            $y = 60;
            if ($y < 180) {
                $content .= '<TEXT x="11" y="210" font="12" w="1" h="1" r="0">' . $order['created_at'] . " " . $orderType . '</TEXT>';
            }
        }
        return $content;
    }

    static function feiStyle($arr, $A = 21, $B = 6, $C = 3, $D = 6)
    {
        $orderInfo = '';
        $nums = '';
        $prices = '';

        foreach ($arr as $k5 => $v5) {
            $name = $v5['name'];
            if ($v5['data']) {
                $name = $v5['name'] . '(' . $v5['data'] . ')';
            }
            if ($v5['attribute']) {
                $name = $name . '(' . $v5['attribute'] . ')';
            }
            if ($v5['material']) {
                $name = $name . '(' . $v5['material'] . ')';
            }
            $name = $name;
            $price = $v5['price'];
            if ($v5['vipMoney'] > 0) {
                $price = $v5['vipMoney'];
            }
            $num = $v5['num'];
            $prices = $price;
            $kw3 = '';
            $kw1 = '';
            $kw2 = '';
            $kw4 = '';
            $str = $name;
            $blankNum = $A; //名称控制为14个字节
            $lan = mb_strlen($str, 'utf-8');
            $m = 0;
            $j = 1;
            $blankNum++;
            $result = array();
            if (strlen($price) < $B) {
                $k1 = $B - strlen($price);
                for ($q = 0; $q < $k1; $q++) {
                    $kw1 .= ' ';
                }
                $price = $price . $kw1;
            }
            if (strlen($num) < $C) {
                $k2 = $C - strlen($num);
                for ($q = 0; $q < $k2; $q++) {
                    $kw2 .= ' ';
                }
                $num = $num . $kw2;
            }
            if (strlen($prices) < $D) {
                $k3 = $D - strlen($prices);
                for ($q = 0; $q < $k3; $q++) {
                    $kw4 .= ' ';
                }
                $prices = $prices . $kw4;
            }
            for ($i = 0; $i < $lan; $i++) {
                $new = mb_substr($str, $m, $j, 'utf-8');
                $j++;
                if (mb_strwidth($new, 'utf-8') < $blankNum) {
                    if ($m + $j > $lan) {
                        $m = $m + $j;
                        $tail = $new;
                        $lenght = iconv("UTF-8", "GBK//IGNORE", $new);
                        $k = $A - strlen($lenght);
                        for ($q = 0; $q < $k; $q++) {
                            $kw3 .= ' ';
                        }
                        if ($m == $j) {
                            $tail .= $kw3 . ' ' . $num . ' ' . $prices;
                        } else {
                            $tail .= $kw3 . '<BR>';
                        }
                        break;
                    } else {
                        $next_new = mb_substr($str, $m, $j, 'utf-8');
                        if (mb_strwidth($next_new, 'utf-8') < $blankNum) continue;
                        else {
                            $m = $i + 1;
                            $result[] = $new;
                            $j = 1;
                        }
                    }
                }
            }
            $head = '';
            foreach ($result as $key => $value) {
                if ($key < 1) {
                    $v_lenght = iconv("UTF-8", "GBK//IGNORE", $value);
                    $v_lenght = strlen($v_lenght);
                    if ($v_lenght == 13) $value = $value . " ";
                    $head .= $value . ' ' . $num . ' ' . $prices . '<BR>';
                } else {
                    $head .= $value . '<BR>';
                }
            }
            $orderInfo .= $head . $tail;
            $nums = floatval($nums);
            $prices = floatval($prices);
            @$nums += $prices;
        }

        //var_dump($orderInfo);die;

        return $orderInfo;
    }
    //清空待打印队列
    static function delPrinterSqs($id)
    {
        $params = Hardware::where('id', $id)->first();
        if ($params) {
            $params = empty($params) ? array() : $params->toArray();
            if ($params['type'] < 3) {
                try {
                    if ($params['vendor'] == 'feie') {
                        $time = time();
                        $user = $params['config']['feAcc'];
                        $feie_key = $params['config']['feUkey'];
                        $contents = array(
                            'user' => $user,
                            'stime' => $time,
                            'sig' => sha1($user . $feie_key . $time),
                        );
                        $contents['apiname'] = 'Open_delPrinterSqs';
                        $contents['sn'] = $params['config']['feNum'];
                        $response = Http::asForm()->post('https://api.feieyun.cn/Api/Open/', $contents);
                        $res = $response->body();
                    }
                    if ($params['vendor'] == 'esLink') {
                        $res = self::cancelall($params);
                    }
                    if ($params['vendor'] == 'spyun') {
                        $url = 'https://open.spyun.net/v1/printer/cleansqs';
                        $res = self::_request('POST', $url, self::makeRequestParams($params['config']['spyAppid'], $params['config']['spyAppsecret'], [
                            'sn' => $params['config']['spySn'],
                            'pkey' => $params['config']['pkey'],
                            'name' => $params['config']['name']
                        ]));
                    }
                    if ($params['vendor'] == 'daqu') {
                        $sn = $params['config']['daquSn'] ?: '670020011553';
                        $url = 'https://iot-device.trenditiot.com/openapi/cleanWaitingQueue';
                        $data =
                            [
                                "sn" => $sn,
                            ];
                        $res = self::http($params, $url, 'POST', $data, true);
                    }
                    if ($params['vendor'] == 'jiabo') {
                        $host = 'http://api.poscom.cn'; //接口IP或域名
                        $port = '80';                   //接口端口
                        $reqTime = self::getMillisecond();    //请求时间
                        $apiKey = $params['config']['jiaboKey'] ?: 'QQ8WWIWHVLHZD8EAQSWKVU9WJ8LAWQ69';                     //api密钥
                        $memberCode = $params['config']['jiaboCode'] ?: 'F5F67777B8786F1C08F1B0DC0A13940F';                 //商户编码
                        $deviceID = $params['config']['jiabodeviceID'] ?: '00103962830526108';                 //设备编号
                        $securityCode = md5($memberCode . $reqTime . $apiKey . $deviceID);
                        $url = $host . ':' . $port . '/apisc/cancelPrint';
                        $content['reqTime'] = $reqTime;
                        $content['memberCode'] = $memberCode;
                        $content['deviceID'] = $deviceID;
                        $content['securityCode'] = $securityCode;
                        // 打印内容
                        $content['all'] = '1';
                        $res = self::request_post($url, $content);
                    }
                    if ($params['vendor'] == 'xinye') {
                        $time = time();
                        $user = $params['config']['xinyeUser'];
                        $timestamp = self::getMillisecond();
                        $user_key = $params['config']['userKEY']; //用户开发者密钥
                        $contents = array(
                            'sn' => $params['config']['xinyeNo'],
                            'user' => $user,
                            'timestamp' => $time,
                            'sign' => sha1($user . $user_key . $timestamp),
                        );
                        $url = 'https://open.xpyun.net/api/openapi/xprinter/delPrinterQueue';
                        $res = httpRequest($url, $contents);
                    }
                    return $res;
                } catch (\Exception $e) {
                    throw new BadRequestException($e->getMessage());
                }
            }
        }
    }
    //测试打印
    public static function printTest($params, $printType = 1)
    {
        switch ($printType) {
            case 1;
                $content = '<CB>测试打印</CB><BR>';
                $content .= '名称　　　　　     数量 金额<BR>';
                $content .= '--------------------------------<BR>';
                $content .= '鸡蛋炒饭　　　      10  100.0<BR>';
                $content .= '西红柿炒饭　　      10  100.0<BR>';
                $content .= '西红柿鸡蛋炒饭      10  100.0<BR>';
                $content .= '--------------------------------<BR>';
                $content .= '备注：加辣<BR>';
                $content .= '合计：xx.0元<BR>';
                $content .= '送货地点：广州市南沙区xx路xx号<BR>';
                $content .= '联系电话：13888888888888<BR>';
                $content .= '订餐时间：2014-08-08 08:08:08<BR>';
                $content .= '<QR>http://www.feieyun.com</QR>';
                $data = self::feiPrint($params, $content, 2);
                break;
            case 2;
                $content = "<FS2><center>取单号：#001**</center></FS2>";
                $content .= "<FS><center>张周兄弟烧烤</center></FS>";
                $content .= "<FS2><center>--在线支付--</center></FS2>";
                $content .= "<FS2><center>【立即送达】</center></FS2>";
                $content .= "期望送达时间:" . date("Y-m-d H:i") . "\n";
                $content .= str_repeat('.', 32);
                $content .= "订单编号:40807050607030\n";
                $content .= "下单时间:" . date("Y-m-d H:i") . "\n";
                $content .= '名称' . str_repeat(" ", 12) . "数量       单价\n";
                $content .= "<FS><table>";
                $content .= "<tr><td>烤土豆(超级辣)</td><td>x3</td><td>5.96</td></tr>";
                $content .= "<tr><td>烤排骨(超级辣)</td><td>x3</td><td>12.44</td></tr>";
                $content .= "<tr><td>烤韭菜(超级辣)</td><td>x3</td><td>8.96</td></tr>";
                $content .= "</table></FS>";
                $content .= str_repeat('.', 32);
                $content .= "[折扣:￥４ ]\n";
                $content .= "<FS2>实付：￥78</FS2>\n";
                $content .= str_repeat('*', 32);
                $content .= "<QR>这是二维码内容</QR>";
                $content .= "<FS2><center>**#1 完**</center></FS2>";
                $data = self::ylyPrint($params, $content, 2);
                break;
            case 3;
                $content = "<TEXT x='9' y='10' font='12' w='1' h='2' r='0'>#001       五号桌      1/3</TEXT><TEXT x='80' y='80' font='12' w='2' h='2' r='0'>可乐鸡翅</TEXT><TEXT x='9' y='180' font='12' w='1' h='1' r='0'>张三先生       13800138000</TEXT>";
                $content .= "<DIRECTION>1</DIRECTION>";
                $data = self::feiPrint($params, $content, 3);
                break;
            case 4;
                $content = '<C><B>测试打印</B></C><BR>';
                $content .= '名称　　　　　     数量 金额<BR>';
                $content .= '--------------------------------<BR>';
                $content .= '鸡蛋炒饭　　　      10  100.0<BR>';
                $content .= '西红柿炒饭　　      10  100.0<BR>';
                $content .= '西红柿鸡蛋炒饭      10  100.0<BR>';
                $content .= '--------------------------------<BR>';
                $content .= '备注：加辣<BR>';
                $content .= '合计：xx.0元<BR>';
                $content .= '送货地点：广州市南沙区xx路xx号<BR>';
                $content .= '联系电话：13888888888888<BR>';
                $content .= '订餐时间：2014-08-08 08:08:08<BR>';
                $content .= '<QRCODE>http://www.feieyun.com</QRCODE>';
                $data = self::spyPrint($params, $content, 2);
                break;
            case 5;
                $content = '<LEFT><font# bolder=1 height=2 width=2>商家小票</font#></LEFT><BR>
<C><font# bolder=1 height=2 width=2>***#3美团外卖***</font#></C><BR>
<LEFT>下单时间：04-01 15:15:42</LEFT><BR>
<LEFT>期望送达时间：立即送餐</LEFT><BR><C>--------------------------------</C><BR>
<LEFT><font# bolder=1 height=2 width=2>备注：环保单，顾客不需要附带餐具</font#></LEFT><BR>
<C>********************************</C><BR>
<font# bolder=1 height=2 width=1>黄焖鸡(大份)     ×1   ￥45.0   </font#><BR>
<C>--------------优惠--------------</C><BR>
<LEFT><font# bolder=0 height=2 width=1>[满1.0元减1.0元]</font#></LEFT><BR>
<C>--------------------------------</C><BR>
<LEFT>配送费：￥0.01</LEFT><BR><LEFT>原价：￥45.01</LEFT><BR>
<LEFT>实付：￥44.01</LEFT><BR>
<C>--------------------------------</C><BR>
<LEFT><font# bolder=1 height=2 width=1>油炸小猫咪 (哈哈)@#西藏自治区林芝市墨脱县色金拉</font#></LEFT><BR>
<LEFT><font# bolder=1 height=2 width=1>Echo(女士)</font#></LEFT><BR>
<LEFT><font# bolder=1 height=2 width=1>134****102</font#></LEFT><BR>
<C>****#3完****</C><BR>';
                $bool = self::daquPrint($params, $params['name'], 1);
                //var_dump($bool);
                $data = self::daquPrint($params, $content, 2);
                // var_dump($data);die;
                break;
            case 6;
                $content = "佳博云打印，无线聚商机\n";
                $content .= "互联网+时代，佳博云打印终端能帮您轻松实现远程打印，让您随时随地接单盈利。\n";
                $content .= "<gpBarCode Type=2 Height=20 Position=2>123456789012</gpBarCode>";
                $content .= "终端编号：123456\n";
                $content .= "1: UPC-A:\n <gpBarCode Type=1 Height=40 Position=0>11111111111</gpBarCode>";
                $content .= "2: JAN13(EAN13):\n <gpBarCode Type=2 Height=45 Position=1>222222222222</gpBarCode>";
                $content .= "3: JAN8(EAN8):\n <gpBarCode Type=3 Height=50 Position=2>3333333</gpBarCode>";
                $content .= "4: CODE39:\n <gpBarCode Type=4 Height=55 Position=3>33333</gpBarCode>";
                $content .= "5: ITF:\n <gpBarCode Type=5 Height=40 Position=2>444444</gpBarCode>";
                $content .= "6: CODABAR:\n <gpBarCode Type=6 Height=45 Position=1>A52$1+2-23C</gpBarCode>";
                $content .= "abcdefghijklmnopqrstuvwxyz\n";
                $content .= "ABCDEFGHIJKLMNOPQRSTUVWXYZ\n";
                $content .= "1234567890\n";
                $content .= "!@#$%^&*()_+~{};'\,../<>?\n";
                $content .= "<gpQRCode>http://m.gainscha.com</gpQRCode>";
                $data = self::jiaboPrint($params, $content, 2);
                break;
            case 7;
                $content = '<CB>测试打印</CB><BR>';
                $content .= '名称　　　　　     数量 金额<BR>';
                $content .= '--------------------------------<BR>';
                $content .= '鸡蛋炒饭　　　      10  100.0<BR>';
                $content .= '西红柿炒饭　　      10  100.0<BR>';
                $content .= '西红柿鸡蛋炒饭      10  100.0<BR>';
                $content .= '--------------------------------<BR>';
                $content .= '备注：加辣<BR>';
                $content .= '合计：xx.0元<BR>';
                $content .= '送货地点：广州市南沙区xx路xx号<BR>';
                $content .= '联系电话：13888888888888<BR>';
                $content .= '订餐时间：2014-08-08 08:08:08<BR>';
                $data = self::xinyePrint($params, $content, 2);
                break;
            case 8;
                $content = "<TEXT x='9' y='10' font='12' w='1' h='2' r='0'>#001       五号桌      1/3</TEXT><TEXT x='80' y='80' font='12' w='2' h='2' r='0'>可乐鸡翅</TEXT><TEXT x='9' y='180' font='12' w='1' h='1' r='0'>张三先生       13800138000</TEXT>";
                $data = self::xinyePrint($params, $content, 3);
                break;
        }
        return $data;
    }
    /*-------------------------------------------------------易联云-----------------------------------------------------------*/
    /*易联云打印机*/
    public static function  ylyPrint($params, $content = '测试打印内容', $operationType = 1, $printNum = 1)
    {
        try {
            switch ($operationType) {
                case 1;
                    $data = self::cancelall($params);
                    break;
                case 2;
                    if ($printNum > 1) {
                        $content = '<MN>' . $printNum . '</MN>' . $content;
                    }
                    $outTradeNo = date("YmdHis") . rand(111111, 999999);
                    $token = self::checkYlyToken($params);
                    $device_config = $params['config'];
                    $config = new YlyConfig($device_config['ylyId'], $device_config['ylySecretKey']);
                    $print = new PrintService($token, $config);
                    $data = $print->index($device_config['ylyNum'], $content, $outTradeNo);
                    $data = json_encode(object_array($data));
                    break;
                case 3;
                    $data = self::shutdownrestart($params);
                    break;
            }
        } catch (\Exception $e) {
            $data = $e->getMessage();
        }
        return $data;
    }

    public static function ylyContents($order)
    {
        $goods = OrderGoods::where('orderSn', $order['orderSn'])->get();
        $store = Store::where('id', $order['storeId'])->first()->toArray();
        $goods = $goods->toArray();
        switch ($order['payType']) {
            case 1:
                $payType = '微信支付';
                break;
            case 2:
                $payType = '支付宝支付';
                break;
            case 5:
                $payType = '余额支付';
                break;
            case 6:
                $payType = '现金支付';
                break;
            default;
                $payType = '余额支付';
                break;
        }
        switch ($order['diningType']) {
            case 0;
                $ddName = "立即送出";
                break;
            case 1;
                $ddName = "到店自提";
                break;
        }
        $order['serviceAt'] = $order['serverTime'];

        if ($order['appointment'] == 1) {
            $ddName = "预约单";
        }
        $content = "<FS2>取单号  #" . $order['pickNo'] . "</FS2>\n\n";
        $content .= "<center>*" . $store['name'] . "*</center>\n";
        if ($order['state'] == 7 or $order['state'] == 8) {
            $content .= "<FS2><center>--售后订单--</center></FS2>\n";
            $content .= "<FS2><center>有订单申请售后了</center></FS2>\n";
            $content .= "<FS2><center>请尽快处理!</center></FS2>\n";
        } elseif ($order['state'] == 7) {
            $content .= "<FS2><center>--取消订单--</center></FS2>\n";
            $content .= "<FS2><center>订单已取消,请尽快处理</center></FS2>\n";
        } else {
            $content .= "<FS2><center>--" . $payType . "--</center></FS2>\n";
            $content .= "<FS2><center>【" . $ddName . "】</center></FS2>\n";
        }
        $content .= "期望送达时间：" . $order['serviceAt'] . "\n";
        $content .= "--------------------------------\n";
        $content .= "订单号：" . $order['orderSn'] . "\n";
        $content .= "下单时间：" . $order['payTime'] . "\n";
        if ($order['notes']) {
            $content .= "<FB>备注：" . $order['notes'] . "</FB>\n";
        }
        $content .= "--------------------------------\n";
        $content .= "名称               数量   单价\n";
        $content .= "<FS><table>";
        foreach ($goods as $key => $value) {
            $name = $value['name'];
            $content .= "<tr><td>" . $name . "</td><td>" . $value['num'] . "</td><td>  " . $value['money'] . "</td></tr>";
        }

        $content .= "</table></FS>\n";
        $content .= "<table><tr><td>合计：</td><td>" . $order['goodsNum'] . "</td><td>  " . $order['money'] . "</td></tr></table>";
        $content .= "<center>----------其它费用----------</center>\n";
        if ($order['materialMoney']) {
            $content .= "加料：" . $order['materialMoney'] . "\n";
        }
        if ($order['deliveryMoney'] > 0) {
            $content .= "配送费：" . $order['deliveryMoney'] . "\n";
        }
        if ($order['boxMoney'] > 0) {
            $content .= "[包装费：+" . $order['boxMoney'] . "]\n";
        }
        $content .= "<FB>实付：" . '￥' . ' ' . $order['money'] . "</FB>\n";
        $content .= "<FB>" . $order['contacts']  . $order['address'] . "</FB>\n";
        $content .= "<center>门店电话：" . $store['storeMobile'] . "</center>\n";
        $content .= "<FB><center>----#" . $order['orderSn'] . "完----</center></FB>\n";
        return $content;
    }
    //易联云取消所有未打印订单
    public static function cancelall($params)
    {
        $token = self::checkYlyToken($params);
        $device_config = $params['config'];
        $config = new YlyConfig($device_config['ylyId'], $device_config['ylySecretKey']);
        $print = new PrinterService($token, $config);
        $data = $print->cancelAll($device_config['ylyNum']);
        $data = json_encode(object_array($data));
        return $data;
    }
    //易联云关机重启接口  重启:restart,关闭:shutdown
    public static function shutdownrestart($params, $responseType = 'restart')
    {
        $device_config = $params['config'];
        $token = self::checkYlyToken($params);
        $config = new YlyConfig($device_config['ylyId'], $device_config['ylySecretKey']);
        $print = new PrinterService($token, $config);
        $data = $print->shutdownRestart($device_config['ylyNum'], $responseType);
        $data = json_encode(object_array($data));
        return $data;
    }

    //易联云获取token、重置token
    public static function checkYlyToken($params)
    {
        // if($params['access_token']){
        //    $access_token=$params['access_token'];
        //}else{
        $device_config = $params['config'];
        $config = new YlyConfig($device_config['ylyId'], $device_config['ylySecretKey']);
        $client = new YlyOauthClient($config);
        $tokenObj = $client->getToken();
        $access_token = $tokenObj->access_token;
        $refresh_token = $tokenObj->refresh_token;
        $expires_in = $tokenObj->expires_in;
        $data = ['access_token' => $access_token, 'refresh_token' => $refresh_token, 'expires_in' => $expires_in];
        if ($access_token) {
            Hardware::where('id', $params['id'])->update($data);
        }
        // }
        return $access_token;
    }

    //易联云刷新token
    public static function refreshToken($params)
    {
        $device_config = $params['config'];
        $config = new YlyConfig($device_config['ylyId'], $device_config['ylySecretKey']);
        $client = new YlyOauthClient($config);
        $tokenObj = $client->refreshToken($params['refresh_token']);
        $access_token = $tokenObj->access_token;
        $refresh_token = $tokenObj->refresh_token;
        $expires_in = $tokenObj->expires_in;
        $data = ['access_token' => $access_token, 'refresh_token' => $refresh_token, 'expires_in' => $expires_in];
        if ($access_token) {
            Hardware::where('id', $params['id'])->update($data);
        }
    }
    /*-------------------------------------------------------商鹏云-----------------------------------------------------------*/
    public static function spyPrint($params, $content = '测试打印内容', $operationType = 1, $printNum = 1)
    {
        $device_config = $params['config'];
        switch ($operationType) {
            case 1; //添加打印机
                $url = 'https://open.spyun.net/v1/printer/add';
                return self::_request('POST', $url, self::makeRequestParams($device_config['spyAppid'], $device_config['spyAppsecret'], [
                    'sn' => $device_config['spySn'],
                    'pkey' => $device_config['pkey'],
                    'name' => $device_config['name']
                ]));
                break;
            case 2;
                $url = 'https://open.spyun.net/v1/printer/print';
                $param = [
                    'sn' => $device_config['spySn'],
                    'content' => $content,
                    'times' => $printNum
                ];
                $params = self::makeRequestParams($device_config['spyAppid'], $device_config['spyAppsecret'], $param);
                $data = self::_request('POST', $url, $params);
                Log::error($data);
                return $data;
                break;
        }
    }
    /**
     * 创建请求参数
     * @param array $params
     * @return array
     */
    public static function makeRequestParams($spyAppid, $spyAppsecret, array $params = [])
    {
        $params['appid'] = $spyAppid;
        $params['timestamp'] = time();
        $params['sign'] = self::makeSign($spyAppsecret, $params);

        return $params;
    }
    /**
     * 创建签名
     * @param array $params
     * @return string
     */
    public static function makeSign($spyAppsecret, array $params)
    {
        ksort($params);
        $sign = "";
        foreach ($params as $k => $v) {
            if ($k != "sign" && $k != "appsecret" && $v !== "" && !is_array($v) && !is_null($v)) {
                $sign .= $k . "=" . $v . "&";
            }
        }
        $sign = rtrim($sign, '&');
        $sign .= "&appsecret=" . $spyAppsecret;

        return strtoupper(md5($sign));
    }
    /**
     * @param string $method
     * @param string $uri
     * @param array $params
     * @return array
     * @throws Exception
     */
    public static function _request($method, $url, array $params = [])
    {
        $ch = curl_init();
        if ($method == 'GET' || $method == 'DELETE') {
            $url .= '?' . http_build_query($params);
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 4);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

    /*-------------------------------------------------------大趋-----------------------------------------------------------*/
    // 请求工具方法
    public static function http($params, $url, $method = 'GET', $postfields = null, $debug = false)
    {
        $headers = self::getHeader($params, $postfields);
        $body = json_encode($postfields);
        $ci = curl_init();
        /* Curl settings */
        curl_setopt($ci, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ci, CURLOPT_TIMEOUT, 30);
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ci, CURLINFO_HEADER_OUT, true);
        curl_setopt($ci, CURLOPT_HTTPHEADER, $headers); // 设置通用传参
        switch ($method) {
            case 'POST':
                curl_setopt($ci, CURLOPT_POST, true);
                if (!empty($postfields)) {
                    curl_setopt($ci, CURLOPT_POSTFIELDS, $body);
                }
                break;
        }

        curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
        curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, false); // 从证书中检查SSL加密算法是否存在
        curl_setopt($ci, CURLOPT_URL, $url);
        $response = curl_exec($ci);
        curl_close($ci);
        return $response;
        $http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
        $request_header = curl_getinfo($ci, CURLINFO_HEADER_OUT);
        if ($debug) {
            //dd($request_header);
        }

        return array($http_code, $response);
    }

    // Header通用参数
    public static function getHeader($params, $data)
    {
        $appid = '994613708468539392';
        $appkey = '14bdd63f6a0844a683d4a49d197f4d16';
        $bh = date("YmdHis") . rand(111111, 999999); // 生成请求ID，略...
        $time = time(); // 生成请求时间戳
        $json = json_encode($data);
        $str = $bh . $appid . $time . $appkey . $json;

        $res = md5($str);
        $header = array();
        $header[] = "Content-Type:application/json";
        $header[] = "appid:" . $appid;
        $header[] = "uid:" . $bh;
        $header[] = "stime:" . $time;
        $header[] = "sign:" . $res;
        return $header;
    }

    public static function getDeviceStatus($params)
    {
        $sn = $params['config']['daquSn'] ?: '670020011553';
        $url = 'https://iot-device.trenditiot.com/openapi/getDeviceStatus';
        $data = [
            "sn" => $sn,
        ];
        $res = self::http($params, $url, 'POST', $data, true);
        return $res;
    }
    public static function daquPrint($params, $content, $printType = '', $num = 1, $name = '')
    {
        switch ($printType) {
            case 1;
                $res = self::addDaquPrint($params, $name);
                break;
            case 2;
                $sn = $params['config']['daquSn'] ?: '670020011553';
                $url = 'https://iot-device.trenditiot.com/openapi/print';
                $data =
                    [
                        "sn" => $sn,
                        "voice" => 4,
                        "content" => $content,
                        'copies' => $num
                    ];
                $res = self::http($params, $url, 'POST', $data, true);
                break;
        }

        return $res;
    }
    public static function addDaquPrint($params, $name = '')
    {

        $sn = $params['config']['daquSn'];
        $key = $params['config']['daquKey'];
        $url = "https://iot-device.trenditiot.com/openapi/addPrinter";
        $data = [
            [
                "sn" => $sn ?: '670020011553',
                "key" => $key ?: 'd25gev',
                "name" => $name ?: '大趋智能打印机'
            ]
        ];

        $res = self::http($params, $url, 'POST', $data, true);

        return $res;
    }
    /*-------------------------------------------------------佳博-----------------------------------------------------------*/

    //毫秒时间戳
    public static function getMillisecond()
    {
        list($t1, $t2) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
    }
    /**
     * 模拟post进行url请求
     * @param string $url
     * @param array $post_data
     */
    public static function request_post($url = '', $post_data = array())
    {
        if (empty($url) || empty($post_data)) {
            return false;
        }
        $postUrl = $url;
        $curlPost = $post_data;
        $ch = curl_init(); //初始化curl
        curl_setopt($ch, CURLOPT_URL, $postUrl); //抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0); //设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_POST, 1); //post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        $data = curl_exec($ch); //运行curl
        curl_close($ch);
        return $data;
    }
    //请求封装
    public static function jiaboPrint($params, $msgDetail, $mode, $time = 1)
    {
        $host = 'http://api.poscom.cn'; //接口IP或域名
        $port = '80';                   //接口端口
        $reqTime = self::getMillisecond();    //请求时间
        $securityCode = 'F5F67777B8786F1C08F1B0DC0A13940F';               //安全检验码 ★★★ 不同接口生成安全检验码的字符组装方式不同 ★★★
        $apiKey = $params['config']['jiaboKey'] ?: 'QQ8WWIWHVLHZD8EAQSWKVU9WJ8LAWQ69';                     //api密钥
        $memberCode = $params['config']['jiaboCode'] ?: 'F5F67777B8786F1C08F1B0DC0A13940F';                 //商户编码
        $deviceID = $params['config']['jiabodeviceID'] ?: '00103962830526108';                 //设备编号
        $msgNo = '';                    //订单编号
        $charset = 1;                   //编码格式 ★★★ 票据机型、GP-TD2270请选择 1：gb18030，标签机GP-CH421D请选择 4：utf-8 ★★★
        switch ($mode) {
                // 发送数据到打印机
            case 2:
                $reqTime = self::getMillisecond();
                $securityCode = md5($memberCode . $deviceID . $msgNo . $reqTime . $apiKey);
                $url = $host . ':' . $port . '/apisc/sendMsg';
                $content['charset'] = $charset;
                $content['reqTime'] = $reqTime;
                $content['memberCode'] = $memberCode;
                $content['deviceID'] = $deviceID;
                $content['securityCode'] = $securityCode;
                // 打印内容
                $content['msgDetail'] = $msgDetail;
                $content['msgNo'] = $msgNo;
                // reprint 是否验证订单编号，1：不验证订单编号，可重新打印订单
                $content['reprint'] = 1;
                // multi 是否多订单模式，默认0：为单订单模式，1：多订单模式，
                // 多订单模式下 $msgDetail 为json格式，格式为{"ordernum001":"msgDetail001","ordernum002":"msgDetail002"}
                // 多订单模式下订单编号不能重复，必须填写。建议最大订单数为50
                $content['multi'] = 0;
                // 打印类型
                $content['mode'] = $mode;
                // 打印联数
                $content['times'] = $time;
                $res = self::request_post($url, $content);
                break;
                // 查询打印异常信息
            case "listException":
                //安全校验码 md5(memberCode+reqTime+apiKey)
                $securityCode = md5($memberCode . $reqTime . $apiKey);
                $url = $host . ':' . $port . '/apisc/listException';
                $content['memberCode'] = $memberCode;
                $content['reqTime'] = $reqTime;
                $content['securityCode'] = $securityCode;
                $content['imsi'] = $deviceID;
                $content['start'] = '';
                $content['end'] = '';
                $client = new HttpClient("api.poscom.cn", "80");
                if ($client->get('/apisc/listException', $content)) {
                    echo $client->getContent();
                }
                break;

                // 添加打印机
            case "adddev":
                //安全校验码 md5(memberCode+reqTime+apiKey+deviceID)
                $securityCode = md5($memberCode . $reqTime . $apiKey . $deviceID);
                $url = $host . ':' . $port . '/apisc/adddev';
                $content['memberCode'] = $memberCode;
                $content['reqTime'] = $reqTime;
                $content['securityCode'] = $securityCode;
                $content['deviceID'] = $deviceID;
                $content['devName'] = '设备名称';
                $content['grpID'] = ''; //分组ID
                $content['mPhone'] = '';
                $content['nPhone'] = '';
                $content['cutting'] = '';
                $res = self::request_post($url, $content);
                break;


                // 设置NVLogo（票据机）
            case "setLogo":
                //安全校验码 md5(memberCode+reqTime+apiKey+deviceID)
                $securityCode = md5($memberCode . $reqTime . $apiKey . $deviceID);
                $url = $host . ':' . $port . '/apisc/setLogo';
                $content['memberCode'] = $memberCode;
                $content['reqTime'] = $reqTime;
                $content['securityCode'] = $securityCode;
                $content['deviceID'] = $deviceID;
                $content['imgUrl'] = '';
                $res = self::request_post($url, $content);
                break;

                // 删除NVLogo（票据机）
            case "deleteLogo":
                //安全校验码 md5(memberCode+reqTime+apiKey+deviceID)
                $securityCode = md5($memberCode . $reqTime . $apiKey . $deviceID);
                $url = $host . ':' . $port . '/apisc/deleteLogo';
                $content['memberCode'] = $memberCode;
                $content['reqTime'] = $reqTime;
                $content['securityCode'] = $securityCode;
                $content['deviceID'] = $deviceID;
                $res = self::request_post($url, $content);
                break;
        }
        return $res;
    }
    /*-------------------------------------------------------芯烨云-----------------------------------------------------------*/
    public static function xinyePrint($params, $content = '测试打印内容', $operationType = 1, $printNum = 1)
    {
        $device_config = $params['config'];
        switch ($operationType) {
            case 1; //添加打印机
                $url = 'https://open.xpyun.net/api/openapi/xprinter/addPrinters';
                $user = $params['config']['xinyeUser']; //芯烨云平台注册用户名(开发者 ID)
                $timestamp = time(); //当前UNIX时间戳，10位，精确到秒
                $user_key = $params['config']['userKEY']; //用户开发者密钥
                $sign = sha1($user . $user_key . $timestamp); //对参数 user + user_key + timestamp 拼接后(+号表示连接符)进行SHA1加密得到签名，值为40位小写字符串
                $sn = $params['config']['xinyeNo']; //打印机编号
                $items = [
                    ['sn' => $sn, 'name' => '芯烨小票打印机']
                ];
                $data = array("items" => $items, "user" => $user, "timestamp" => $timestamp, "sign" => $sign);
                $res = httpRequest($url, $data, [], 'post', false);
                break;
            case 2;
                $url = 'https://open.xpyun.net/api/openapi/xprinter/print';
                $user = $params['config']['xinyeUser']; //芯烨云平台注册用户名(开发者 ID)
                $timestamp = time(); //当前UNIX时间戳，10位，精确到秒
                $user_key = $params['config']['userKEY']; //用户开发者密钥
                $sign = sha1($user . $user_key . $timestamp); //对参数 user + user_key + timestamp 拼接后(+号表示连接符)进行SHA1加密得到签名，值为40位小写字符串
                $sn = $params['config']['xinyeNo']; //打印机编号
                $data = array("user" => $user, "timestamp" => $timestamp, "sign" => $sign, "sn" => $sn, "content" => $content, 'copies' => $printNum, "voice" => 2);
                $res = httpRequest($url, $data, [], 'post', false);
                break;
            case 3;
                $url = 'https://open.xpyun.net/api/openapi/xprinter/printLabel';
                $user = $params['config']['xinyeUser']; //芯烨云平台注册用户名(开发者 ID)
                $timestamp = time(); //当前UNIX时间戳，10位，精确到秒
                $user_key = $params['config']['userKEY']; //用户开发者密钥
                $sign = sha1($user . $user_key . $timestamp); //对参数 user + user_key + timestamp 拼接后(+号表示连接符)进行SHA1加密得到签名，值为40位小写字符串
                $sn = $params['config']['xinyeNo']; //打印机编号
                $data = array("user" => $user, "timestamp" => $timestamp, "sign" => $sign, "sn" => $sn, "content" => $content, 'copies' => $printNum);
                $res = httpRequest($url, $data, [], 'post', false);
                break;
        }
        return $res;
    }
}
