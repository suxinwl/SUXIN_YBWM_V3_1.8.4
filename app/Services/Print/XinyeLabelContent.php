<?php

namespace App\Services\Print;

use App\Models\BulkOrder;
use App\Models\BulkOrderGoods;
use App\Models\BulkOrderGoodsGroup;
use App\Models\Print\PrintTemplate;
use App\Models\Print\PrintValue;
use App\Models\ShopPrint;
use App\Models\Tables\Table;
use App\Services\ConfigService;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

// define('USER', 'xxxxxxxxxxxxxxxxx');  //*必填*：飞鹅云后台注册账号
// define('UKEY', 'xxxxxxxxxxxxxxxxx');  //*必填*: 飞鹅云后台注册账号后生成的UKEY 【备注：这不是填打印机的KEY】
// define('SN', 'xxxxxxxxxxxxxxxxx');      //*必填*：打印机编号，必须要在管理后台里添加打印机或调用API接口添加之后，才能调用API

// //以下参数不需要修改
// define('IP', 'api.feieyun.cn');      //接口IP或域名
// define('PORT', 80);            //接口IP端口
// define('PATH', '/Api/Open/');    //接口路径


//*************************************方法1 批量添加打印机接口*************************************************
//***接口返回值说明***
//正确例子：{"msg":"ok","ret":0,"data":{"ok":["sn#key#remark#carnum","316500011#abcdefgh#快餐前台"],"no":["316500012#abcdefgh#快餐前台#13688889999  （错误：识别码不正确）"]},"serverExecutedTime":3}
//错误：{"msg":"参数错误 : 该帐号未注册.","ret":-2,"data":null,"serverExecutedTime":37}

//提示：
//$printerConten => 打印机编号sn(必填) # 打印机识别码key(必填) # 备注名称(选填) # 流量卡号码(选填)，多台打印机请换行（\n）添加新打印机信息，每次最多100台。
//打开注释可测试
//$printerContent = "sn1#key1#remark1#carnum1\nsn2#key2#remark2#carnum2";
//printerAddlist($printerContent);



//****************************************方法2 小票机打印订单接口****************************************************
//***接口返回值说明***
//正确例子：{"msg":"ok","ret":0,"data":"123456789_20160823165104_1853029628","serverExecutedTime":6}
//错误例子：{"msg":"错误信息.","ret":非零错误码,"data":null,"serverExecutedTime":5}

//标签说明：
//单标签:
//"<BR>"为换行,"<CUT>"为切刀指令(主动切纸,仅限切刀打印机使用才有效果)
//"<LOGO>"为打印LOGO指令(前提是预先在机器内置LOGO图片),"<PLUGIN>"为钱箱或者外置音响指令
//成对标签：
//"<CB></CB>"为居中放大一倍,"<B></B>"为放大一倍,"<C></C>"为居中,<L></L>字体变高一倍
//<W></W>字体变宽一倍,"<QR></QR>"为二维码,"<BOLD></BOLD>"为字体加粗,"<RIGHT></RIGHT>"为右对齐

//拼凑订单内容时可参考如下格式
//根据打印纸张的宽度，自行调整内容的格式，可参考下面的样例格式
$content = '<CB>测试打印</CB><BR>';
$content .= '名称　　　　　 单价  数量 金额<BR>';
$content .= '--------------------------------<BR>';
$content .= '饭　　　　　 　10.0   10  100.0<BR>';
$content .= '炒饭　　　　　 10.0   10  100.0<BR>';
$content .= '蛋炒饭　　　　 10.0   10  100.0<BR>';
$content .= '鸡蛋炒饭　　　 10.0   10  100.0<BR>';
$content .= '西红柿炒饭　　 10.0   10  100.0<BR>';
$content .= '西红柿蛋炒饭　 10.0   10  100.0<BR>';
$content .= '西红柿鸡蛋炒饭 10.0   10  100.0<BR>';
$content .= '--------------------------------<BR>';
$content .= '备注：加辣<BR>';
$content .= '合计：xx.0元<BR>';
$content .= '送货地点：广州市南沙区xx路xx号<BR>';
$content .= '联系电话：13888888888888<BR>';
$content .= '订餐时间：2014-08-08 08:08:08<BR>';
$content .= '<QR>http://www.feieyun.com</QR>'; //把二维码字符串用标签套上即可自动生成二维码


//提示：
//SN => 打印机编号
//$content => 打印内容,不能超过5000字节
//$times => 打印次数，默认为1。
//打开注释可测试
// printMsg(SN,$content,1);//该接口只能是小票机使用,如购买的是标签机请使用下面方法3，调用打印


//****************************************方法3 标签机专用打印订单接口****************************************************
//***接口返回值说明***
//正确例子：{"msg":"ok","ret":0,"data":"123456789_20160823165104_1853029628","serverExecutedTime":6}
//错误例子：{"msg":"错误信息.","ret":非零错误码,"data":null,"serverExecutedTime":5}

//标签说明：
$content = "<DIRECTION>1</DIRECTION>"; //设定打印时出纸和打印字体的方向，n 0 或 1，每次设备重启后都会初始化为 0 值设置，1：正向出纸，0：反向出纸，
$content .= "<TEXT x='9' y='10' font='12' w='1' h='2' r='0'>#001       五号桌      1/3</TEXT><TEXT x='80' y='80' font='12' w='2' h='2' r='0'>可乐鸡翅</TEXT><TEXT x='9' y='180' font='12' w='1' h='1' r='0'>张三先生       13800138000</TEXT>"; //40mm宽度标签纸打印例子，打开注释调用标签打印接口打印

//提示：
//SN => 打印机编号
//$content => 打印内容,不能超过5000字节
//$times => 打印次数，默认为1。
//打开注释可测试
// printLabelMsg(SN,$content,1);//打开注释调用标签机打印接口进行打印,该接口只能是标签机使用，其它型号打印机请勿使用该接口


//**************************************方法4 批量删除打印机**************************************************
//***接口返回值说明***
//成功：{"msg":"ok","ret":0,"data":{"ok":["123456789成功"],"no":[]},"serverExecutedTime":5}
//错误：{"msg":"ok","ret":0,"data":{"ok":[],"no":["12345678打印机不存在"]},"serverExecutedTime":2}
//错误：{"msg":"ok","ret":0,"data":{"ok":[],"no":["123456789用户UID不匹配"]},"serverExecutedTime":3}
//提示：
//$snlist => 打印机编号，多台打印机请用减号"-"连接起来。
//打开注释可测试
// $snlist = "123456789";
// printerDelList($snlist);



//************************************方法5 修改打印机信息接口************************************************
//***接口返回值说明***
//成功：{"msg":"ok","ret":0,"data":true,"serverExecutedTime":5}
//错误：{"msg":"参数错误 : 参数值不能传空字符，\"\"、\"null\"、\"undefined\".","ret":-2,"data":null,"serverExecutedTime":1}
//提示：
//SN => 打印机编号
//$name => 打印机备注名称
//$phonenum => 打印机流量卡号码
//打开注释可测试
//$name = "飞鹅云打印机";
//$phonenum = "01234567891011121314";
// printerEdit(SN,$name,$phonenum);



//************************************方法6 清空待打印订单接口************************************************
//***接口返回值说明***
//成功：{"msg":"ok","ret":0,"data":true,"serverExecutedTime":4}
//错误：{"msg":"验证失败 : 打印机编号和用户不匹配.","ret":1002,"data":null,"serverExecutedTime":3}
//错误：{"msg":"参数错误 : 参数值不能传空字符，\"\"、\"null\"、\"undefined\".","ret":-2,"data":null,"serverExecutedTime":2}
//提示：
//SN => 打印机编号
//打开注释可测试
// delPrinterSqs(SN);


//*********************************方法7 查询订单是否打印成功接口*********************************************
//***接口返回值说明***
//正确例子：
//已打印：{"msg":"ok","ret":0,"data":true,"serverExecutedTime":6}
//未打印：{"msg":"ok","ret":0,"data":false,"serverExecutedTime":6}

//提示：
//$orderid => 订单ID，由方法1接口Open_printMsg返回。
//打开注释可测试
//$orderid = "123456789_20160823165104_1853029628";//订单ID，从方法1返回值中获取
//queryOrderState($orderid);



//*****************************方法8 查询指定打印机某天的订单统计数接口*****************************************
//***接口返回值说明***
//正确例子：
//{"msg":"ok","ret":0,"data":{"print":6,"waiting":1},"serverExecutedTime":9}
//错误：{"msg":"验证失败 : 打印机编号和用户不匹配.","ret":1002,"data":null,"serverExecutedTime":3}

//提示：
//$date => 查询日期，格式YY-MM-DD，如：2016-09-20
//打开注释可测试
// $date = "2016-09-20";
// queryOrderInfoByDate(SN,$date);



//***********************************方法9 获取某台打印机状态接口***********************************************
//***接口返回值说明***
//正确例子：
//{"msg":"ok","ret":0,"data":"离线","serverExecutedTime":9}
//{"msg":"ok","ret":0,"data":"在线，工作状态正常","serverExecutedTime":9}
//{"msg":"ok","ret":0,"data":"在线，工作状态不正常","serverExecutedTime":9}

//提示：
//SN => 填打印机编号
//打开注释可测试
// queryPrinterStatus(SN);

/**
 * 订单打印
 * "qtWmBusiness": 1, 商家联
 *   "qtWmCustomer": 1, 顾客联
 *   "qtWmRefund": 1,  退款联
 *  hcWmPrintNum： 厨房联
 * hcWmPrintMet  全部  1全部  3 分类 4 商品
 * hcWmGoodsClass 商品分类
 * hcWmSelectGoods  商品
 * hcWmPrintWay 打印方式  1 整单  2 一菜一单
 * qtWmJoin 打印联数
 */

class XinyeLabelContent
{
    static function labelAllContent($order, $goods, $rule)
    {
        $printGoods = [];
        if ($rule['config']['hcWmPrintMet'] == 3) {
            $printGoods = collect($goods)->filter(function ($value, $key) use ($rule) {
                if (is_array($value->spu->catId) && is_array($rule['config']['hcWmGoodsClass'])) {
                    return !empty(array_intersect($value->spu->catId, $rule['config']['hcWmGoodsClass']));
                }
            })->all();
        } elseif ($rule['config']['hcWmPrintMet'] == 4) {
            $printGoods = collect($goods)->filter(function ($value, $key) use ($rule) {
                if ($value->spuId && $rule['config']['hcWmSelectGoods']) {
                    return in_array($value->spuId, $rule['config']['hcWmSelectGoods']);
                }
            })->all();
        } else {
            $printGoods = $goods;
        }
        $num = $rule && $rule['config']['qtWmJoin'];
        $content = [];
        $template = PrintTemplate::where('uniacid', $order->uniacid)
            ->where('print_type', 13)
            ->first();
        if (!empty($template)) {
            $printTemplate = empty($template) ? array() : $template->toArray();
            $printData = $printTemplate['data'];
            $labelData = array_column($printData, 'label');
            if (!in_array('spec', $labelData)) {
                $printData = PrintValue::labelContents();
            }
        } else {
            $printData = PrintValue::labelContents();
        }
        $goodsContent = [];
        foreach ($printGoods as $k5 => $v5) {
            if (empty($v5->setMealData)) {
                for ($i = 0; $i < $v5->num; $i++) {
                    $goodsContent[] = $v5;
                }
            }
            if ($v5->setMealData) {
                $setMealData = $v5->setMealData;
                foreach ($setMealData as $k => $v) {
                    for ($i = 0; $i < bcmul($v5->num, $v['num']); $i++) {
                        $goodsContent[] = $v;
                    }
                    // try {
                    //     $content[] = self::feiStyleLabel($v, $order, $printData, bcmul($v5->num, $v['num']));
                    // } catch (\Exception $e) {
                    //     Log::error($e->getMessage() . $e->getFile() . $e->getLine());
                    //     break;
                    // }
                }
            }
        }
        $num = count($goodsContent);
        foreach ($goodsContent as $key => $goods) {
            try {
                $content[] = self::feiStyleLabel($goods, $order, $printData, ($key + 1), $num);
            } catch (\Exception $e) {
                Log::error($e->getMessage() . $e->getFile() . $e->getLine());
                break;
            }
        }
        $content = self::array3_to_array2($content);
        return $content;
    }
    /***************
     * @function                         三维数组转二维数组
     * @Param:      $array :             传入参数
     * @Return:     $tempArr             返回结果数组
     ***************/
    static  function array3_to_array2($array)
    {
        $array = array_filter($array);
        $array = array_values($array);
        foreach ($array as $orderKey => $orderVal) {
            $count = count($orderVal);
            if ($count > 1) {
                for ($i = 0; $i < $count; $i++) {
                    $tempArr[] = $orderVal[$i];
                }
            } else {
                $tempArr[] = $orderVal[0];
            }
        }
        return $tempArr;
    }
    public static function customContent($data, $content, $y)
    {
        $str = '';
        if ($data) {
            if ($data['size']) {
                switch ($data['size']) {
                    case 1;
                        $font = 12;
                        $w = 1;
                        $h = 1;
                        $r = 0;
                        break;
                    case 2;
                        $font = 12;
                        $w = 2;
                        $h = 1;
                        $r = 0;
                        break;
                    case 3;
                        $font = 12;
                        $w = 1;
                        $h = 2;
                        $r = 0;
                        break;
                    case 4;
                        $font = 12;
                        $w = 2;
                        $h = 2;
                        $r = 0;
                        break;
                }
            }

            if ($data['align']) {
                switch ($data['align']) {
                    case 1;
                        $r = 0;
                        break;
                    case 2;
                        $r = 1;
                        break;
                    case 3;
                        $r = 2;
                        break;
                }
            }
        }
        $str = '<TEXT x="10" y=' . $y . ' font="' . $font . '"w="' . $w . '"h="' . $h . '"r="' . $r . '">' . $content . '</TEXT>';
        return $str;
    }
    static function feiStyleLabel($goods, $order, $printData, $inedx, $num)
    {
        $data = [];
        $y = 10;
        $content = "";
        foreach ($printData as $ko => $vo) {
            switch ($vo['label']) {
                case 'orderType';
                    if ($order->diningType == 4 || $order->diningType == 5) {
                        $tableInfo = Table::where('id', $order->tableId)->first();
                        $name = $tableInfo->name;
                    } else {
                        $name = '#' . $order->pickNo;
                    }
                    $content .= '<TEXT x="10" y=' . $y . ' font="12" w="1" h="1" r="0">' . self::LR($name, $vo['name'] . '：' . $inedx . '/' . $num, 22) . '</TEXT>';
                    break;
                case 'goodInformation';
                    $y += 32;
                    $length = mb_strlen($content, 'utf-8');
                    $goodsName = $goods->name ?: $goods['name'];
                    if ($goods->pack == 1) {
                        $goodsName = '(打包)' . $goodsName;
                    }
                    if ($vo['size'] == 1 || $vo['size'] == 3) {
                        if ($length > 12) {
                            $content .= self::customContent($printData[$ko], mb_substr($goodsName, 0, 12), $y);
                            $y += 24;
                            $content .= self::customContent($printData[$ko], mb_substr($goodsName, 12, 12), $y);
                        } else {
                            $content .= self::customContent($printData[$ko], $goodsName, $y);
                        }
                    }
                    if ($vo['size'] == 2 || $vo['size'] == 4) {
                        if ($length > 12) {
                            $content .= self::customContent($printData[$ko], mb_substr($goodsName, 0, 6), $y);
                            $y += 40;
                            $content .= self::customContent($printData[$ko], mb_substr($goodsName, 6, 12), $y);
                        } else {
                            $content .= self::customContent($printData[$ko], $goodsName, $y);
                        }
                    }
                    if ($vo['size'] == 2 || $vo['size'] == 4) {
                        $y += 24;
                    }
                    break;
                case 'spec';
                    $specData=$goods->attrData['spec']?:$goods['attrData']['spec'];
                    if (!empty($specData)) {
                        $y += 24;
                        $content .= self::customContent($printData[$ko], $specData, $y);
                    }
                    if ($vo['size'] == 2 || $vo['size'] == 4) {
                        $y += 24;
                    }
                    break;
                case 'matal';
                    $matalData=$goods->attrData['matal']?:$goods['attrData']['matal'];
                    if (!empty($matalData)) {
                        $y += 24;
                        $content .= self::customContent($printData[$ko], $matalData, $y);
                    }
                    if ($vo['size'] == 2 || $vo['size'] == 4) {
                        $y += 24;
                    }
                    break;
                case 'attr';
                    $attrData=$goods->attrData['attr']?:$goods['attrData']['attr'];
                    if (!empty($attrData)) {
                        $y += 24;
                        $content .= self::customContent($printData[$ko], $attrData, $y);
                    }
                    if ($vo['size'] == 2 || $vo['size'] == 4) {
                        $y += 24;
                    }
                    break;
                case 'desc';
                    if (!empty($vo['value'])) {
                        $y += 24;
                        $content .= self::customContent($printData[$ko], $vo['value'], $y);
                    }
                    if ($vo['size'] == 2 || $vo['size'] == 4) {
                        $y += 24;
                    }
                    break;
            }
        }
        if ($y <= 180 && $order->notes) {
            $content .= '<TEXT x="10" y="180" font="12" w="1" h="1" r="0">' . '备注:' . $order->notes . '</TEXT>';
        }
        if ($y <= 210) {
            $time = $order->serverTime ?: $order->payTime;
            $diningTypeFormat = $order->diningTypeFormat;
            if ($order->packaging == 1) {
                $diningTypeFormat = '打包带走';
            }
            $content .= '<TEXT x="10" y="210" font="12" w="1" h="1" r="0">' . date("m-d H:i", strtotime($time)) . " " . $diningTypeFormat . '</TEXT>';
        }
        $data[] = $content;
        return $data;
        //
        //        $content = "";
        //        $startNum = 0;
        //        $data=[];
        //        for ($i = 1; $i <= $goods->num; $i++) {
        //            $extStr = '';
        //            $startNum++;
        //            $symbol = '/';
        //            $tableName = '单号';
        //            $y=5;
        //            if($order->diningType==4||$order->diningType==5){
        //                $tableInfo=Table::where('id',$order->tableId)->first();
        //                $content.= '<TEXT x="11" y='.$y.' font="12" w="1" h="1" r="0">' . self::LR("{$tableName}：{$tableInfo->name}", " 数量：{$i}/$goods->num", 20) . '</TEXT>';
        //            }else{
        //                $sign = '#';
        //                $content.= '<TEXT x="11" y='.$y.' font="12" w="1" h="1" r="0">' . self::LR("{$tableName}：{$sign}{$order->pickNo}", " 数量：{$i}/$goods->num", 20) . '</TEXT>';
        //            }
        //            $y=bcadd($y,30);
        //            $content.= '<TEXT x="11" y='.$y.' font="12" w="1" h="2" r="0">' . $goods->name . '</TEXT>';
        //            if($goods->attrData['spec']){
        //                $y=bcadd($y,50);
        //                $content.= '<TEXT x="11" y='.$y.' font="12" w="1" h="1" r="0">' . "规格：" . $goods->attrData['spec'].'</TEXT>';
        //            }
        //            if (!empty($goods->attrData['attr'])) {
        //                $y=bcadd($y,50);
        //                $content.= '<TEXT x="11" y='.$y.' font="12" w="1" h="1" r="0">' . "属性：" . $goods->attrData['attr'] . '</TEXT>';
        //            }
        //            if (!empty($goods->attrData['matal'])) {
        //                $y=bcadd($y,30);
        //                $extStr= "加料：" . $goods->attrData['matal'];
        //                $content.='<TEXT x="11" y='.$y.' font="12" w="1" h="1" r="0">' .$extStr . '</TEXT>';
        //            }
        //            if (!empty($goods->notes)) {
        //                $y=bcadd($y,30);
        //                $notesStr= "备注：" . $goods->notes;
        //                $content.='<TEXT x="11" y='.$y.' font="12" w="1" h="1" r="0">' .$notesStr . '</TEXT>';
        //            }
        //            if ($y < 210) {
        //                $time=$order->serverTime?:$order->payTime;
        //                $content.= '<TEXT x="11" y="210" font="12" w="1" h="1" r="0">' . date("m-d H:i", strtotime($time)) . " " . $order->diningTypeFormat . '</TEXT>';
        //            }
        //            $data[]=$content;
        //        }
        //        return $data;
    }



    static function feiStyle($arr, $fixType = 1)
    {
        $str = '';
        foreach ($arr as $k5 => $v5) {
            $prices = $v5->money;
            $name = $v5->name;
            $nums = 'X' . $v5->num;
            if ($v5->isTemp) {
                $name = '(临)' . $name;
            }
            if ($v5->discountLabel) {
                $name = '(' . $v5->discountLabel . ')' . $name;
            }
            if ($v5->pack == 1) {
                $name = '(打包)' . $name;
            }
            if (!empty($v5->attrData['spec'])) {
                $name .= ' ' . $v5->attrData['spec'];
            }
            if (!empty($v5->attrData['attr'])) {
                $name .= ' ' . $v5->attrData['attr'];
            }
            if (!empty($v5->attrData['matal'])) {
                $extStr = '[加料] ';
                $extStr .= str_replace(',', ' + ', $v5->attrData['matal']);
            }
            if ($extStr) {
                $str .= "<L><BOLD>" . self::strFix($name, 32) . "</BOLD></L><BR>";
                $str .= self::strFix($extStr, 32);
                if ($fixType == 1) {
                    $str .= "<L><BOLD>" . self::strFix(' ', 20) .   self::strFix($nums, 5)  . self::strFix($prices, 7) . "</BOLD></L><BR>";
                } else {
                    $str .= "<L><BOLD>" . self::strFix(' ', 27) .   self::strFix($nums, 5) . "</BOLD></L><BR>";
                }
            } else {
                if ($fixType == 1) {
                    $str .= "<L><BOLD>" . self::strFix($name, 20) .    self::strFix($nums, 5)  . self::strFix($prices, 7) . "</BOLD></L><BR>";
                } else {
                    $str .= "<L><BOLD>" . self::strFix($name, 27) .    self::strFix($nums, 5)  . "</BOLD></L><BR>";
                }
            }
            if ($v5->notes) {
                $str .= '<BR>' . '备注：' . $v5->notes . '<BR>';
            }
        }
        return $str;
    }

    public static function strFix($name, $A, $maxLine = 26, $x, &$Y, $w = 0, $line = 10)
    {
        $lan = mb_strlen($name, 'utf-8');
        $tail = '';
        $m = 0;
        $j = 1;
        $br = 0;
        $strLan = mb_strwidth($name, 'utf-8') > $A ? $maxLine : $A;
        $blankNum = $strLan;
        for ($i = 0; $i < $lan; $i++) {
            $new = mb_substr($name, $m, $j, 'utf-8');
            $j++;
            if ($br < 2) {
                if (mb_strwidth($new, 'utf-8') < $blankNum) {
                    if ($i + 1 >= $lan) {
                        $startY =  $Y + $br * $line * $w;
                        $tail .= "<TEXT x='11' y='{$startY}' font='12' w='1' h='1' r='0'>$new</TEXT>";
                    }
                } else {
                    $Y =  $Y + $br * $line * $w;
                    $m = $i + 1;
                    $tail .= "<TEXT x='11' y='{$Y}' font='12' w='1' h='1' r='0'>$new</TEXT>";
                    $j = 1;
                    $br++;
                }
            }
        }
        return $tail;
    }

    static  function LR($str_left, $str_right, $length)
    {
        if (empty($str_left) || empty($length)) return '请输入正确的参数';
        $kw = '';
        $str_left_lenght = strlen(iconv("UTF-8", "GBK//IGNORE", $str_left));
        $str_right_lenght = strlen(iconv("UTF-8", "GBK//IGNORE", $str_right));
        $k = $length - ($str_left_lenght + $str_right_lenght);
        for ($q = 0; $q < $k; $q++) {
            $kw .= ' ';
        }
        return $str_left . $kw . $str_right;
    }
}
