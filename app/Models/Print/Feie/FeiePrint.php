<?php

namespace App\Models\Print\Feie;

use App\Models\BaseModel;
use App\Models\PrintAuth;
use App\Models\ShopPrint;
use App\Services\Print\FeieContent;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class  FeiePrint
{

    public static $config = [
        'user' => '',
        'ukey' => '',
        'sn' => '',
    ];

    public static function setUser($uniacid)
    {
        $user = PrintAuth::where('uniacid', $uniacid)->where('type', 1)->first();
        if (empty($user)) {
            throw new BadRequestException('未配置飞蛾开放设置');
        }
        self::$config = [
            'user' => $user->appid,
            'ukey' => $user->secret
        ];
        return slef::class;
    }

    public static function setPrint($printId)
    {
        $print = ShopPrint::find($printId);
        if (empty($print)) {
            throw new BadRequestException('找不到该打印机');
        }
        self::$config = [
            'user' => $print->appid,
            'ukey' => $print->secret,
            'sn' => $print->sn,
            'key' => $print->keys,
        ];
        return slef::class;
    }


    public static function bindPrint($config = [])
    {
        self::$config = [
            'user' => $config['user'],
            'ukey' => $config['ukey'],
        ];
        $res =  self::request('Open_printerAddlist', ['printerContent' => "{$config['sn']}#{$config['keys']}"]);
        if ($res['ret'] != 0) {
            throw new BadRequestException($res['msg']);
        }
        if (!empty($res['data']['no'])) {
            throw new BadRequestException($res['data']['no'][0]);
        }
        return true;
    }
    public static function destroy($config = [])
    {
        self::$config = [
            'user' => $config['user'],
            'ukey' => $config['ukey'],
        ];
        $res =  self::request('Open_printerDelList', ['snlist' => "{$config['sn']}"]);
        if ($res['ret'] != 0) {
            throw new BadRequestException($res['msg']);
        }
        if (!empty($res['data']['no'])) {
            throw new BadRequestException($res['data']['no'][0]);
        }
        return true;
    }

    public static function print($orderId, $config = [])
    {
        $contents = FeieContent::bulk($orderId);
        self::$config = [
            'user' => $config['user'],
            'ukey' => $config['ukey'],
        ];
        $res =  self::request('Open_printMsg', ['sn' => $config['sn'], 'content' => $contents, 'times' => $config['num']]);
        if ($res['ret'] != 0) {
            throw new BadRequestException($res['msg']);
        }
        return true;
    }

    public static  function request($apiname = '', $params = [])
    {
        $contents = array(
            'user' => self::$config['user'],
            'stime' => time(),
            'sig' => sha1(self::$config['user'] . self::$config['ukey'] . time()),
            'apiname' => $apiname,
        );
        $contents = array_merge($contents, $params);
        $response = Http::asForm()->post("http://api.feieyun.cn/Api/Open/", $contents);
        $response->throw();
        return $response->json();
    }

    //名称14 单价6 数量3 金额6
    public function goodsTest($arr, $A, $B, $C, $D)
    {
        $orderInfo = '<CB>飞鹅云测试</CB><BR>';
        $orderInfo .= '名称           单价  数量 金额<BR>';
        $orderInfo .= '--------------------------------<BR>';
        foreach ($arr as $k5 => $v5) {
            $name = $v5['title'];
            $price = $v5['price'];
            $num = $v5['num'];
            $prices = $v5['price'] * $v5['num'];
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
                            $tail .= $kw3 . ' ' . $price . ' ' . $num . ' ' . $prices;
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
                    $head .= $value . ' ' . $price . ' ' . $num . ' ' . $prices;
                } else {
                    $head .= $value . '<BR>';
                }
            }
            $orderInfo .= $head . $tail;
            @$nums += $prices;
        }
        $time = date('Y-m-d H:i:s', time());
        $orderInfo .= '--------------------------------<BR>';
        $orderInfo .= '合计：' . number_format($nums, 1) . '元<BR>';
        $orderInfo .= '送货地点：广州市南沙区xx路xx号<BR>';
        $orderInfo .= '联系电话：020-39004606<BR>';
        $orderInfo .= '订餐时间：' . $time . '<BR>';
        $orderInfo .= '备注：加辣<BR><BR>';
        $orderInfo .= '<QR>http://www.feieyun.com</QR>'; //把解析后的二维码生成的字符串用标签套上即可自动生成二维码
        return $orderInfo;
    }
}
