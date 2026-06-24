<?php
namespace App\Services\Print;
use App\Models\BulkOrder;
use App\Models\BulkOrderGoods;
use App\Models\BulkOrderGoodsGroup;
use App\Models\Print\PrintTemplate;
use App\Models\Print\PrintValue;
use App\Models\QueuingUp;
use App\Models\ShopPrint;
use App\Models\Tables\Table;
use App\Services\ConfigService;
use App\Models\MemberAccount;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
class YlyMaxContent
{

    //充值订单
    public static function rechargeContents($order, $balance,$str='')
    {
        $name = $order->user->realname ?: $order->user->nickname;
        $content = "<FS2><center>>充值订单</center></FS2>\n\n";
        $content .= self::LR('订单号：', $order->orderSn, 48) . "\n";
        $content .= self::LR('下单时间：', date("m-d H:i", strtotime($order->created_at)), 48) . "\n";
        $content .= "********************************\n";
        $content .= self::LR('充值门店：', $order->store->name, 48) . "\n";
        $content .= self::LR('手机号码：', $order->user->mobile, 48) . "\n";
        $content .= self::LR('会员姓名：', $name, 48) . "\n";
        $content .= self::LR('充值金额：', $order->subOrder->money?:$order->money, 48) . "\n";
        $content .= self::LR('支付方式：', '微信支付', 48) . "\n";
        if($str){
            $content .= '充值赠送：' . $str . "\n";
        }
        $content .= self::LR('充后余额：', $balance, 48) . "\n";
        return $content;
    }
    //当面付订单
    public static function facepayContents($order)
    {
        $account = MemberAccount::where('userId', $order->userId)->first();
        $name = $order->user->realname ?: $order->user->nickname;
        $content = "<FS2><center>当面付订单</center></FS2>\n\n";
        $content .= self::LR('订单号：', $order->orderSn, 48) . "\n";
        if($order->pickNo) {
            $content .= self::LR('流水号：', $order->pickNo, 48) . "\n";
        }
        $content .= self::LR('支付时间：', date("m-d H:i", strtotime($order->created_at)), 48) . "\n";
        $content .= "********************************\n";
        if($order->user->mobile){
            $content .= self::LR('手机号码：', $order->user->mobile, 48) . "\n";
        }
        if($name){
            $content .= self::LR('会员姓名：', $name, 48) . "\n";
        }
        $content .= self::LR('消费门店：', $order->store->name, 48) . "\n";
        $content .= self::LR('付款金额：', $order->money ?? $order->subOrder->money, 48) . "\n";
        $content .= self::LR('用户余额：', $account->balance, 48) . "\n";
        $content .= self::LR('支付方式：', $order->PayTypeFormat, 48) . "\n";
        return $content;
    }



    static function feiStyle($arr, $fixType = 1, $note = '',$isB=true)
    {
        $str = '';
        foreach ($arr as $k5 => $v5) {
            $extStr = '';
            $prices = $v5->money;
            $name = $note ? $note . $v5->name : $v5->name;
            $nums = 'X' . $v5->num;
            if (!empty($v5->attrData['spec'])) {
                $name .= ' ' . $v5->attrData['spec'];
            }
            if (!empty($v5->attrData['attr'])) {
                $name .= ' ' . $v5->attrData['attr'];
            }
            if (!empty($v5->attrData['matal'])) {
                $extStr = '[加料] ';
                $extStr .= str_replace(',', '+', $v5->attrData['matal']);
                $name .=$extStr;
            }
            if ($fixType == 1) {
                $rightContent=$nums.'  '.$prices;
                $length=mb_strlen($name.$rightContent);
                if($length<=48){
                    if($isB==true){
                        $str .= "<FH><FB>" . self::LR($name, $rightContent, 48) . "</FB></FH>\n";
                    }else{
                        $str .=self::LR($name, $rightContent, 48) . "\n";
                    }
                }else{
                    if($isB==true) {
                        $str .= "<FH><FB>" . mb_substr($name, 0, 48, "utf-8") . "</FB></FH>\n";
                        $str .= "<FH><FB>" . self::LR(mb_substr($name, 48, $length, "utf-8"), $rightContent, 48) . "</FB></FH>\n";
                    }else{
                        $str .= mb_substr($name, 0, 48, "utf-8") . "\n";
                        $str .= self::LR(mb_substr($name, 48, $length, "utf-8"), $rightContent, 48) . "\n";
                    }
                }
            }else{
                $rightContent=$nums;
                $length=mb_strlen($name.$rightContent);
                if($length<=48){
                    if($isB==true) {
                        $str .= "<FH><FB>" . self::LR($name, $rightContent, 48) . "</FB></FH>\n";
                    }else{
                        $str .= self::LR($name, $rightContent, 48) . "\n";
                    }
                }else{
                    if($isB==true) {
                        $str .= "<FH><FB>" . mb_substr($name, 0, 48, "utf-8") . "</FB></FH>\n";
                        $str .= "<FH><FB>" . self::LR(mb_substr($name, 48, $length, "utf-8"), $rightContent, 48) . "</FB></FH>\n";
                    }else{
                        $str .= mb_substr($name, 0, 48, "utf-8") . "\n";
                        $str .= self::LR(mb_substr($name, 48, $length, "utf-8"), $rightContent, 48) . "\n";
                    }
                }
            }
        }
        return $str;
    }

    public static function strFix($name, $A, $br = '\n', $maxLine = 48)
    {
        $lan = mb_strlen($name, 'utf-8');
        $tail = '';
        $m = 0;
        $j = 1;
        $strLan = mb_strwidth($name, 'utf-8') > $A ? $maxLine : $A;
        $blankNum = $strLan;
        for ($i = 0; $i < $lan; $i++) {
            $new = mb_substr($name, $m, $j, 'utf-8');
            $j++;
            if (mb_strwidth($new, 'utf-8') < $blankNum) {
                if ($i + 1 >= $lan) {
                    $lenght = iconv("UTF-8", "GBK//IGNORE", $new);
                    if (mb_strwidth($name, 'utf-8') > $A) {
                        $kw3 = '';
                        $ext = '';
                        $k = $maxLine - strlen($lenght);
                        for ($q = 0; $q < $k; $q++) {
                            $ext .= ' ';
                        }
                        for ($q = 0; $q < $A; $q++) {
                            $kw3 .= ' ';
                        }
                        $tail .= $new . $ext . $kw3;
                    } else {
                        $kw3 = '';
                        $k = $A - strlen($lenght);
                        for ($q = 0; $q < $k; $q++) {
                            $kw3 .= ' ';
                        }
                        $tail .= $new . $kw3;
                    }
                }
            } else {
                $m = $i + 1;
                $tail .=  $new . $br;
                $j = 1;
            }
        }
        return $tail;
    }

    static  function LR($str_left, $str_right='', $length)
    {
        //if (empty($str_left) || empty($length)) return '请输入正确的参数';
        $kw = '';
        $str_left_lenght = strlen(iconv("UTF-8", "GBK//IGNORE", $str_left));
        $str_right_lenght = strlen(iconv("UTF-8", "GBK//IGNORE", $str_right));
        $k = $length - ($str_left_lenght + $str_right_lenght);
        for ($q = 0; $q < $k; $q++) {
            $kw .= ' ';
        }
        return $str_left . $kw . $str_right;
    }
    static  function combination($str_left, $center,$str_right, $length)
    {
        //if (empty($str_left) || empty($length)) return '请输入正确的参数';
        $kw = '';
        $str_left_lenght = strlen(iconv("UTF-8", "GBK//IGNORE", $str_left));
        $center = strlen(iconv("UTF-8", "GBK//IGNORE", $center));
        $str_right_lenght = strlen(iconv("UTF-8", "GBK//IGNORE", $str_right));
        $k = $length - (9);
        for ($q = 0; $q < $k; $q++) {
            $kw .= ' ';
        }
        return $str_left . $kw . $center.' '.$str_right;
    }

    public static function instoreOneContents($order, $orderGoods = [], $otherMode = '')
    {
        $makeName = $orderGoods ? $otherMode . '制作分单' : $otherMode . '制作整单';
        $content = "<FS2><center>" . $makeName . "</center></FS2>\n";
        if ($order->tableId) {
            $content .= "<FS2>桌号:" . $order->table->area->name . '-' . $order->table->name . "</FS2>\n\n";
            $content .= "人数：" . $order->people . "\n\n";
        } else {
            $content .= "<FS2>牌号:" . $order->pickNo . "</FS2>";
            $content.="\n";
        }
        $content .= "类型：" . $order->packagingFormat . "\n";
        $content .= "菜品                        数量\n";
        $content .= "------------------------------------------------\n";
        if ($orderGoods) {
            $name = $orderGoods->name;
            $attrData = $orderGoods->attrData;
            if (!empty($attrData)) {
                if (!empty($attrData['spec'])) {
                    $name .= ' ' . $attrData['spec'];
                }
                if (!empty($attrData['attr'])) {
                    $name .= ' ' . $attrData['attr'];
                }
                if (!empty($attrData['matal'])) {
                    $extStr = '[加料] ';
                    $extStr .= str_replace(',', '+', $attrData['matal']);
                    $name .= $extStr;
                }
            }
            $content .= "<FS2>" . self::LR($name, $orderGoods->num, 48) . "</FS2>";
            $content.="\n";
        } else {
            $content .= "<FS2>" . self::feiStyle($order->goods,2) . "</FS2>";
            $content.="\n";
        }
        $content .= "------------------------------------------------\n";
        if ($order->notes) {
            $content .= "<FH><FB>整单备注：" . $order->notes . "</FH></BOLD>";
            $content.="\n";
            $content .= "------------------------------------------------\n";
        }
        $content .= self::LR('单号：', $order->orderSn, 48) . "\n";
        $content .= self::LR('操作人：', $order->user->nickname, 48) . "\n";
        $content .= self::LR('时间：', date("Y-m-d H:i", strtotime($order->created_at)), 48) . "\n";
        return $content;
    }

    public static function customContent($data,$content){
        if($data){
            if($data['line']==1){
                $content.="\n";
            }
            if($data['align']){
                switch ($data['align']){
                    case 1;
                        $content=$content;
                        break;
                    case 2;
                        $content="<center>".$content."</center>";
                        break;
                    case 3;
                        $content="<right>".$content."</right>";
                        break;
                }

            }



            if($data['size']){
                switch ($data['size']){
                    case 2;
                        $content="<FW>".$content."</FW>";
                        break;
                    case 3;
                        $content="<FH>".$content."</FH>";
                        break;
                    case 4;
                        $content="<FS2>".$content."</FS2>";
                        break;
                }
            }

            if($data['bold']==2){
                $content="<FB>".$content."</FB>";
                $content.="\n";
            }
            if($data['boder']==2){
                $content.="\n------------------------------------------------";
            }

        }
        return $content;
    }
    public static function instoreCustomerContents($order,$print_type=1,$goods=[],$refundReason=''){
        if(empty($goods)){
            return false;
        }
        $template=PrintTemplate::where('uniacid',$order->uniacid)
            ->where('print_type',$print_type)
            ->first();
        $notes=true;
        switch ($print_type){
            case 1;//客单
                if(!empty($template)){
                    $printTemplate = empty($template) ? array() : $template->toArray();
                    $data=$printTemplate['data'];
                }else{
                    $data=PrintValue::instoreCustomerContents();
                }
                break;
            case 2;//预结单
                if(!empty($template)){
                    $printTemplate = empty($template) ? array() : $template->toArray();
                    $data=$printTemplate['data'];
                }else{
                    $data=PrintValue::instorePreorderContents();
                }
                break;
            case 3;//结账单
                $notes=false;
                if(!empty($template)){
                    $printTemplate = empty($template) ? array() : $template->toArray();
                    $data=$printTemplate['data'];
                }else{
                    $data=PrintValue::instoreCheckoutContents();
                }
                break;
            case 4;//外卖商家联
                if(!empty($template)){
                    $printTemplate = empty($template) ? array() : $template->toArray();
                    $data=$printTemplate['data'];
                }else{
                    $data=PrintValue::takeoutBusinessContents();
                }
                break;
            case 5;//外卖顾客联
                if(!empty($template)){
                    $printTemplate = empty($template) ? array() : $template->toArray();
                    $data=$printTemplate['data'];
                }else{
                    $data=PrintValue::takeoutCustomerContents();
                }
                break;
            case 6;//制作总单
                if(!empty($template)){
                    $printTemplate = empty($template) ? array() : $template->toArray();
                    $data=$printTemplate['data'];
                }else{
                    $data=PrintValue::instoreMakingOrderContents();
                }
                break;
            case 7;//制作分单
                if(!empty($template)){
                    $printTemplate = empty($template) ? array() : $template->toArray();
                    $data=$printTemplate['data'];
                }else{
                    $data=PrintValue::instorePartOrderContents();
                }
                break;
            case 8;//加菜(客单)
                if(!empty($template)){
                    $printTemplate = empty($template) ? array() : $template->toArray();
                    $data=$printTemplate['data'];
                }else{
                    $data=PrintValue::instoreAddCustomerContents();
                }
                break;
            case 9;//加菜制作分单
                if(!empty($template)){
                    $printTemplate = empty($template) ? array() : $template->toArray();
                    $data=$printTemplate['data'];
                }else{
                    $data=PrintValue::instoreAddPartOrderContents();
                }
                break;
            case 10;//退菜单
                if(!empty($template)){
                    $printTemplate = empty($template) ? array() : $template->toArray();
                    $data=$printTemplate['data'];
                }else{
                    $data=PrintValue::instoreRefundCustomerContents();
                }
                break;
            case 11;//外卖后厨联
                if(!empty($template)){
                    $printTemplate = empty($template) ? array() : $template->toArray();
                    $data=$printTemplate['data'];
                }else{
                    $data=PrintValue::takeoutKitchenContents();
                }
                break;
            case 13;//标签小票联
                if(!empty($template)){
                    $printTemplate = empty($template) ? array() : $template->toArray();
                    $data=$printTemplate['data'];
                }else{
                    $data=PrintValue::labelContents();
                }
                break;
            case 14;//自提商家联
                if(!empty($template)){
                    $printTemplate = empty($template) ? array() : $template->toArray();
                    $data=$printTemplate['data'];
                }else{
                    $data=PrintValue::ztBusinessContents();
                }
                break;
            case 15;//自提顾客联
                if(!empty($template)){
                    $printTemplate = empty($template) ? array() : $template->toArray();
                    $data=$printTemplate['data'];
                }else{
                    $data=PrintValue::ztCustomerContents();
                }
                break;
        }
//        if(empty($goods)){
//            $goods=!$order->goods->isEmpty()?$order->goods:$order->subGoods;
//        }
        $content=''; $goodsTitleNum=0;
        foreach ($data as $k=>$v){
            switch ($v['label']){
                case 'orderType';
                    if(in_array($print_type,[4,5,11,14,15])){
                        $content.=self::customContent($v,$data[$k]['name'].'：'."#" .$order->pickNo)."\n";
                    }else{
                        $content.=self::customContent($v,$data[$k]['name'])."\n";
                    }
                    break;
                case 'storeName';
                    $content.=self::customContent($v,'*'.$order->store->name.'*');
                    break;
                case 'tableNo';
                        if($order->diningType==4||$order->diningType==5) {
                            $content .= self::customContent($v, $data[$k]['name'] . '：' . $order->table->name) . "\n";
                        }
                    break;
                case 'people';
                    if($order->tableId) {
                        $content .= self::customContent($v, $data[$k]['name'] . '：' . $order->people) . "\n";
                    }
                    break;
                case 'pickNo';
                    if($order->diningType==6 || $order->diningType==5){
                        $content.=self::customContent($v,$data[$k]['name'].'：'.$order->pickNo)."\n";
                    }
                    break;

                case 'payTime';
                    $content.=self::customContent($v,$data[$k]['name'].'：'.date("Y-m-d H:i", strtotime($order->created_at)))."\n";
                    break;
                case 'orderSn';
                    $content.=self::customContent($v,$data[$k]['name'].'：'.$order->orderSn)."\n";
                    break;
                case 'goodsTitle';
                    $goodsTitle=[];
                    $goodsTitle=explode('，',$data[$k]['name']);

                    $goodsTitleNum=count($goodsTitle);
                    if($goodsTitleNum==2){
                        $content .= self::LR($goodsTitle[0], $goodsTitle[1], 48) . "\n";
                    }else{
                        switch ($v['size']){
                            case 1;//18个空格
                                if($print_type==11){
                                    $content .= self::LR($goodsTitle[0],$goodsTitle[1].'       '.$goodsTitle[2],48);
                                }else{
                                    $content .= self::LR($goodsTitle[0],$goodsTitle[1].' '.$goodsTitle[2],48);
                                }
                                break;
                            case 2;
                                $content .= '<FW>'.self::LR($goodsTitle[0],$goodsTitle[1].' '.$goodsTitle[2],24).'</FW>';
                                break;
                            case 3;
                                $content .= '<FH>'.self::LR($goodsTitle[0],$goodsTitle[1].' '.$goodsTitle[2],48).'</FH>';
                                break;
                            case 4;//2个空格
                                $content .= '<FS2>'.self::LR($goodsTitle[0],$goodsTitle[1].' '.$goodsTitle[2],24).'</FS2>';
                                break;
                        }
                    }
                    break;
                case 'goodInformation';
                    $note='';
                    if($print_type==8||$print_type==9){
                        $note='(加菜)';
                    }
                    $printTypes=$goodsTitleNum==2?2:1;
                    switch ($v['size']){
                        case 1;
                            $content.=self::original($goods,$note,$printTypes,$notes);
                            break;
                        case 2;
                            $content.=self::widen($goods,$note,$printTypes,$notes);
                            break;
                        case 3;
                            $content.=self::heighten($goods,$note,$printTypes,$notes);
                            break;
                        case 4;
                            $content.=self::amplify($goods,$note,$printTypes,$notes);
                            break;
                    }
                    $content .= "------------------------------------------------\n";
                    break;
                case 'notes';
                    if($order->notes){
                        $content.=self::customContent($v,$data[$k]['name'].'：'.$order->notes)."\n";
                    }
                    break;
                case 'arriveTime';
                    if ($order->scene == 1) {
                        if ($order->appointment == 1) {
                            if (strtotime($order->serverTime) > strtotime(date("Y-m-d 23:59:59"))) {
                                $content.=self::customContent($v, "预订单 " . date("m-d H:i", strtotime($order->serverTime)) . "送达")."\n";
                            } else {
                                $content.=self::customContent($v, date("m-d H:i", strtotime($order->serverTime)) . "送达")."\n";
                            }
                        } else {
                            $content.=self::customContent($v, "预计" . date("m-d H:i", strtotime($order->serverTime)) . "送达")."\n";
                        }
                    } else {
                        if ($order->appointment == 1) {
                            if (strtotime($order->serverTime) > strtotime(date("Y-m-d 23:59:59"))) {
                                $content.=self::customContent($v, "预订单 ".date("m-d H:i", strtotime($order->serverTime)))."\n";
                                $content.=self::customContent($v, "取餐码：" . $order->pickNo)."\n";
                            } else {
                                $content.=self::customContent($v, date("m-d H:i", strtotime($order->serverTime)) . $order->diningTypeFormat)."\n";
                                $content.=self::customContent($v, "取餐码：" . $order->pickNo)."\n";
                            }
                        } else {
                            $content.=self::customContent($v, date("m-d H:i", strtotime($order->serverTime)) . $order->diningTypeFormat)."\n";
                        }
                    }
                    break;
                case 'checkoutType';
                    $content.=self::customContent($v,$data[$k]['name'].'：'.$order->sourceFormat)."\n";
                    break;
                case 'diningType';
                    $diningType='';
                    if(in_array($print_type,[4,14])){
                        if ($order->scene == 1) {
                            if ($order->appointment == 1) {
                                $diningType='外卖预订单';
                            } else {
                                $diningType='外卖即时单';
                            }
                        } else {
                            if ($order->appointment == 1) {
                                $diningType=$order->diningTypeFormat."预订单";
                            } else {
                                $diningType=$order->diningTypeFormat."即时单";
                            }
                        }
                    }
                    $diningType=$diningType?:$order->diningTypeFormat;
                    $content.=self::customContent($v,$data[$k]['name'].'：'.$diningType)."\n";;
                    break;
                case 'packTime';
                    if ($order->scene == 1) {
                        if ($order->appointment == 1) {
                            if (strtotime($order->serverTime) > strtotime(date("Y-m-d 23:59:59"))) {
                                $content.=self::customContent($v, $data[$k]['name'].'：'."预订单 " . date("m-d H:i", strtotime($order->serverTime)) . "送达")."\n";
                            } else {
                                $content.=self::customContent($v, $data[$k]['name'].'：'.date("m-d H:i", strtotime($order->serverTime)) . "送达")."\n";
                            }
                        } else {
                            $content.=self::customContent($v, $data[$k]['name'].'：'."预计" . date("m-d H:i", strtotime($order->serverTime)) . "送达")."\n";
                        }
                    } else {
                        if ($order->appointment == 1) {
                            if (strtotime($order->serverTime) > strtotime(date("Y-m-d 23:59:59"))) {
                                $content.=self::customContent($v, $data[$k]['name'].'：'.date("m-d H:i", strtotime($order->serverTime)))."\n";
                                $content.=self::customContent($v, "取餐码：" . $order->pickNo)."\n";
                            } else {
                                $content.=self::customContent($v, $data[$k]['name'].'：'.date("m-d H:i", strtotime($order->serverTime)) . $order->diningTypeFormat)."\n";
                                $content.=self::customContent($v, "取餐码：" . $order->pickNo)."\n";
                            }
                        } else {
                            $content.=self::customContent($v, $data[$k]['name'].'：'.date("m-d H:i", strtotime($order->serverTime)) . $order->diningTypeFormat)."\n";
                        }
                    }
                    break;
                case 'money';
                    $content.=self::customContent($v,$data[$k]['name'].'：'.$order->sellMoney)."\n";
                    if($order->tableMoney>0){
                        $content.=$order->tableFormat.'*'.$order->tableNum.'：'.$order->tableMoney."\n";
                    }
                    break;
                case 'payMoney';
                    if($order->service_money) {
                        $content .= '订单服务费：' . $order->service_money ."\n";
                    }
                    $payMoney=$order->money;
                    $content.=self::customContent($v,$data[$k]['name'].'：'.$payMoney)."\n";
                    break;
                case 'payable';
                    $content.=self::customContent($v,$data[$k]['name'].'：'.$order->sellMoney)."\n";
                    if($order->discountMoney){
                        $content.='订单优惠：'.$order->discountMoney."\n";
                    }
                    if($order->service_money) {
                        $content .= '订单服务费：' . $order->service_money ."\n";
                    }
                    break;
                case 'disbursement';
                    if ($order->payTime) {
                        $disbursementMoney=$order->money;
                        $payTypeFormat=$order->costomPayFormat?$order->costomPayFormat:$order->PayTypeFormat;
                        $content.=self::customContent($v,$payTypeFormat.'：'.$disbursementMoney)."\n";
                    }
                    break;
                case 'payType';//应付金额
                    $actualMoney=$order->money;
                    $content.=self::customContent($v,$data[$k]['name'].'：'.$actualMoney)."\n";
                    break;

                case 'payAll';
                    $payAllMoney=$order->money;
                    $content.=self::customContent($v,$data[$k]['name'].'：'.$payAllMoney);
                    break;
                case 'username';
                    if($print_type<>10&&$order->mobile){
                        $mobile=$order->address['mobile']?:$order->mobile;
                        if($order->address['contact']){
                            $content.=self::customContent($v,$data[$k]['name'].'：'. "(" . $order->address['contact'] . ")".$mobile)."\n";
                        }else{
                            $content.=self::customContent($v,$data[$k]['name'].'：'.$mobile)."\n";
                        }
                    }
                    break;
                case 'created_at';
                    $content.=self::customContent($v,$data[$k]['name'].'：'.date("Y-m-d H:i", strtotime($order->created_at)))."\n";
                    break;
                case 'print_at';
                    $content.=self::customContent($v,$data[$k]['name'].'：'.date("Y-m-d H:i", time()))."\n";
                    break;
                case 'desc';
                    if($data[$k]['value']){
                        if($v['line']==1){
                            $data[$k]['value']="\n".$data[$k]['value'];
                        }
                        $content.=self::customContent($v,$data[$k]['value'])."\n";
                    }
                    break;
                case 'waiter';
                    $mobile=$order->address['mobile']?:$order->mobile;
                    if($mobile){
                        if($order->address['contact']){
                            $content.=self::customContent($v,$data[$k]['name'].'：'. "(" . $order->address['contact'] . ")".$mobile)."\n";
                        }else{
                            $content.=self::customContent($v,$data[$k]['name'].'：'. $mobile)."\n";
                        }
                    }
                    break;
                case 'site';
                    if($order->address){
                        $content.=self::customContent($v,$data[$k]['name'].'：'.$order->address['address'].$order->address['description'])."\n";
                    }
                    break;
                case 'reason';
                    if($refundReason){
                        $content.=self::customContent($v,$data[$k]['name'].'：'.$refundReason)."\n";
                    }
                    break;
                case 'address';
                    if($order->address){
                        $content.=self::customContent($v,$data[$k]['name'].'：'.$order->address['address'].$order->address['description'])."\n";
                    }
                    break;
                case 'delivery';
                    $content.=self::customContent($v,$data[$k]['name'].'：'.$order->deliveryMoney)."\n";
                    break;
                case 'code';
                    if($data[$k]['value']==1){
                        $content.="<QR>".$order->qrcode."</QR>";
                    }
                    if($data[$k]['value']==2){
                        $content.="<QR>".$data[$k]['name']."</QR>";
                    }
                    break;
                case 'mobile';
                    $content.=self::customContent($v,$data[$k]['name'].'：'.$order->store->mobile)."\n";
                    break;
            }
        }
        return $content;
    }


    //商品信息原始尺寸
    public static function original($arr,$note='',$printType=1,$notes=''){
        $orderInfo="";
        foreach ($arr as $k5 => $v5) {
            $name =$v5->name;
            $num = $v5->num;
            $prices = $v5->money;
            if($note){
                $name =$note.$name;
            }
            if($v5->isTemp){
                $name ='(临)'.$name;
            }
            if($v5->discountLabel){
                $name ='('.$v5->discountLabel.')'.$name;
            }
            if($v5->pack==1){
                $name ='(打包)'.$name;
            }
            if (!empty($v5->attrData['spec'])) {
                $name.= ' ' . $v5->attrData['spec'];
            }
            if (!empty($v5->attrData['matal'])) {
                $extStr = ' ';
                $extStr .= str_replace(',', '+', $v5->attrData['matal']);
                $name.=$extStr;
            }
            if (!empty($v5->attrData['attr'])) {
                $name.= ' ' . $v5->attrData['attr'].' ';
            }
            if ($notes&&$v5->notes) {
                $name.= ' ' . '备注：'.$v5->notes;
            }

            if($printType==1){
                $maxNumber=24;
                $num=str_pad($num,2," ",STR_PAD_RIGHT);
                $prices=str_pad($prices,3," ",STR_PAD_LEFT);
                $right_length=strlen($num)+strlen($prices)+4;
                $number=floor(bcdiv((48-$right_length),2));
                $name_length=mb_strlen($name,'utf-8');
                if($number>$name_length){
                    $orderInfo.="<FB>".self::LR($name,' ×'.$num.' '.$prices,48)."</FB>";
                }else{
                    $orderInfo.="<FB>".self::LR(mb_substr($name,0,$number),' ×'.$num.' '.$prices,48)."</FB>";
                }
                $remainder= ceil((mb_strlen($name,'utf-8')-$number) /$maxNumber);
                if($remainder>0){
                    for ($i=0;$i<$remainder;$i++){
                        $orderInfo.="<FB>".self::LR(mb_substr($name,$number+$i*$maxNumber,$maxNumber),'',48)."</FB>";
                    }
                }
            }else{
                $maxNumber=24;
                $num=str_pad($num,2," ",STR_PAD_RIGHT);
                $name_length=mb_strlen($name,'utf-8');
                $right_length=strlen($num)+3;
                $number=floor(bcdiv((48-$right_length),2));
                if($number>$name_length){
                    $orderInfo.="<FB>".self::LR($name,' ×'.$num,48)."</FB>";
                }else{
                    $orderInfo.="<FB>".self::LR(mb_substr($name,0,$number),' ×'.$num,48)."</FB>";
                }
                $remainder= ceil((mb_strlen($name,'utf-8')-$number) /$maxNumber);
                if($remainder>0){
                    for ($i=0;$i<$remainder;$i++){
                        $orderInfo.="<FB>".self::LR(mb_substr($name,$number+$i*$maxNumber,$maxNumber),'',48)."</FB>";
                    }
                }
            }
            $orderInfo.="\n";
            if ($notes&&$v5->notes) {
                $orderInfo.='备注：'.$v5->notes."\n";
            }
            if(!empty($v5->setMealData)){
                foreach ($v5->setMealData as $vs){
                    $name =$vs['name'];
                    if (!empty($vs['attrData']['spec'])) {
                        $name.= ' ' . $vs['attrData']['spec'];
                    }
                    if (!empty($vs['attrData']['matal'])) {
                        $extStr = ' ';
                        $extStr .= str_replace(',', '+', $vs['attrData']['matal']);
                        $name.=$extStr;
                    }
                    if (!empty($vs['attrData']['attr'])) {
                        $name.= ' ' . $vs['attrData']['attr'].' ';
                    }
                    $num = bcmul($v5->num,$vs['num']);
                    $prices = $vs['money'];
                    $name=' -'.$name;
                    if($printType==1){
                        $maxNumber=24;
                        $num=str_pad($num,2," ",STR_PAD_RIGHT);
                        $prices=str_pad($prices,3," ",STR_PAD_LEFT);
                        $right_length=strlen($num)+strlen($prices)+4;
                        $number=floor(bcdiv((48-$right_length),2));
                        $name_length=mb_strlen($name,'utf-8');
                        if($number>$name_length){
                            $orderInfo.="<FB>".self::LR($name,' ×'.$num.' '.$prices,48)."</FB>";
                        }else{
                            $orderInfo.="<FB>".self::LR(mb_substr($name,0,$number),' ×'.$num.' '.$prices,48)."</FB>";
                        }
                        $remainder= ceil((mb_strlen($name,'utf-8')-$number) /$maxNumber);
                        if($remainder>0){
                            for ($i=0;$i<$remainder;$i++){
                                $orderInfo.="<FB>".self::LR(mb_substr($name,$number+$i*$maxNumber,$maxNumber),'',48)."</FB>";
                            }
                        }
                    }else{
                        $maxNumber=24;
                        $num=str_pad($num,2," ",STR_PAD_RIGHT);
                        $name_length=mb_strlen($name,'utf-8');
                        $right_length=strlen($num)+3;
                        $number=floor(bcdiv((48-$right_length),2));
                        if($number>$name_length){
                            $orderInfo.="<FB>".self::LR($name,' ×'.$num,48)."</FB>";
                        }else{
                            $orderInfo.="<FB>".self::LR(mb_substr($name,0,$number),' ×'.$num,48)."</FB>";
                        }
                        $remainder= ceil((mb_strlen($name,'utf-8')-$number) /$maxNumber);
                        if($remainder>0){
                            for ($i=0;$i<$remainder;$i++){
                                $orderInfo.="<FB>".self::LR(mb_substr($name,$number+$i*$maxNumber,$maxNumber),'',48)."</FB>";
                            }
                        }
                    }
                    $orderInfo.="\n";
                    if ($notes&&$vs['notes']) {
                        $orderInfo.='备注：'.$vs['notes']."\n";
                    }
                }
            }
        }
        return $orderInfo;
    }
    //商品信息加宽尺寸
    public static function widen($arr,$note='',$printType=1,$notes=''){
        $orderInfo="";
        foreach ($arr as $k5 => $v5) {
            $name =$v5->name;
            $num = $v5->num;
            $prices = $v5->money;
            if($note){
                $name =$note.$name;
            }
            if($v5->isTemp){
                $name ='(临)'.$name;
            }
            if($v5->discountLabel){
                $name ='('.$v5->discountLabel.')'.$name;
            }
            if($v5->pack==1){
                $name ='(打包)'.$name;
            }
            if (!empty($v5->attrData['spec'])) {
                $name.= ' ' . $v5->attrData['spec'];
            }
            if (!empty($v5->attrData['matal'])) {
                $extStr = ' ';
                $extStr .= str_replace(',', '+', $v5->attrData['matal']);
                $name.=$extStr;
            }
            if (!empty($v5->attrData['attr'])) {
                $name.= ' ' . $v5->attrData['attr'].' ';
            }
            if ($notes&&$v5->notes) {
                $name.= ' ' . '备注：'.$v5->notes;
            }
            if($printType==1){
                $maxNumber=8;
                $num=str_pad($num,2," ",STR_PAD_RIGHT);
                $prices=str_pad($prices,4," ",STR_PAD_LEFT);
                $right_length=bcmul(strlen($num)+strlen($prices)+4,2);
                $number=floor(bcdiv((48-$right_length),4));
                $name_length=mb_strlen($name,'utf-8');
                if($number>$name_length){
                    $orderInfo.="<FW><FB>".self::LR($name,' ×'.$num.' '.$prices,24)."</FB></FW>";
                }else{
                    $orderInfo.="<FW><FB>".self::LR(mb_substr($name,0,$number),' ×'.$num.' '.$prices,24)."</FB></FW>";
                }

                $remainder= ceil((mb_strlen($name,'utf-8')-$number) /$maxNumber);
                if($remainder>0){
                    for ($i=0;$i<$remainder;$i++){
                        $orderInfo.="<FW><FB>".self::LR(mb_substr($name,$number+$i*$maxNumber,$maxNumber),'',24)."</FB></FW>";
                    }
                }
            }else{
                $number=6;$maxNumber=8;
                //$num=str_pad($num,2," ",STR_PAD_RIGHT);
                $name_length=mb_strlen($name,'utf-8');
                if($number>$name_length){
                    $orderInfo.="<FW><FB>".self::LR($name,' ×'.$num,24)."</FB></FW>";
                }else{
                    $orderInfo.="<FW><FB>".self::LR(mb_substr($name,0,$number),' ×'.$num,24)."</FB></FW>";
                }
                $remainder= ceil((mb_strlen($name,'utf-8')-$number) /$maxNumber);
                if($remainder>0){
                    for ($i=0;$i<$remainder;$i++){
                        $orderInfo.="<FW><FB>".self::LR(mb_substr($name,$number+$i*$maxNumber,$maxNumber),'',24)."</FB></FW>";
                    }
                }
            }
            $orderInfo.="\n";
            if ($notes&&$v5->notes) {
                $orderInfo.='备注：'.$v5->notes."\n";
            }
            if(!empty($v5->setMealData)){
                foreach ($v5->setMealData as $vs){
                    $name =$vs['name'];
                    if (!empty($vs['attrData']['spec'])) {
                        $name.= ' ' . $vs['attrData']['spec'];
                    }
                    if (!empty($vs['attrData']['matal'])) {
                        $extStr = ' ';
                        $extStr .= str_replace(',', '+', $vs['attrData']['matal']);
                        $name.=$extStr;
                    }
                    if (!empty($vs['attrData']['attr'])) {
                        $name.= ' ' . $vs['attrData']['attr'].' ';
                    }
                    $num = bcmul($v5->num,$vs['num']);
                    $prices = $vs['money'];
                    $name=' -'.$name;
                    if($printType==1){
                        $maxNumber=8;
                        $num=str_pad($num,2," ",STR_PAD_RIGHT);
                        $prices=str_pad($prices,4," ",STR_PAD_LEFT);
                        $right_length=bcmul(strlen($num)+strlen($prices)+4,2);
                        $number=floor(bcdiv((48-$right_length),4));
                        $name_length=mb_strlen($name,'utf-8');
                        if($number>$name_length){
                            $orderInfo.="<FW><FB>".self::LR($name,' ×'.$num.' '.$prices,24)."</FB></FW>";
                        }else{
                            $orderInfo.="<FW><FB>".self::LR(mb_substr($name,0,$number),' ×'.$num.' '.$prices,24)."</FB></FW>";
                        }

                        $remainder= ceil((mb_strlen($name,'utf-8')-$number) /$maxNumber);
                        if($remainder>0){
                            for ($i=0;$i<$remainder;$i++){
                                $orderInfo.="<FW><FB>".self::LR(mb_substr($name,$number+$i*$maxNumber,$maxNumber),'',24)."</FB></FW>";
                            }
                        }
                    }else{
                        $number=6;$maxNumber=8;
                        //$num=str_pad($num,2," ",STR_PAD_RIGHT);
                        $name_length=mb_strlen($name,'utf-8');
                        if($number>$name_length){
                            $orderInfo.="<FW><FB>".self::LR($name,' ×'.$num,24)."</FB></FW>";
                        }else{
                            $orderInfo.="<FW><FB>".self::LR(mb_substr($name,0,$number),' ×'.$num,24)."</FB></FW>";
                        }
                        $remainder= ceil((mb_strlen($name,'utf-8')-$number) /$maxNumber);
                        if($remainder>0){
                            for ($i=0;$i<$remainder;$i++){
                                $orderInfo.="<FW><FB>".self::LR(mb_substr($name,$number+$i*$maxNumber,$maxNumber),'',24)."</FB></FW>";
                            }
                        }
                    }
                    $orderInfo.="\n";
                    if ($notes&&$vs['notes']) {
                        $orderInfo.='备注：'.$vs['notes']."\n";
                    }
                }
            }
        }
        return $orderInfo;
    }
    //商品信息加高尺寸
    public static function heighten($arr,$note='',$printType=1,$notes=''){
        $orderInfo="";
        foreach ($arr as $k5 => $v5) {
            $name =$v5->name;
            $num = $v5->num;
            $prices = $v5->money;
            if($note){
                $name =$note.$name;
            }
            if($v5->isTemp){
                $name ='(临)'.$name;
            }
            if($v5->discountLabel){
                $name ='('.$v5->discountLabel.')'.$name;
            }
            if($v5->pack==1){
                $name ='(打包)'.$name;
            }
            if (!empty($v5->attrData['spec'])) {
                $name.= ' ' . $v5->attrData['spec'];
            }
            if (!empty($v5->attrData['matal'])) {
                $extStr = ' ';
                $extStr .= str_replace(',', '+', $v5->attrData['matal']);
                $name.=$extStr;
            }
            if (!empty($v5->attrData['attr'])) {
                $name.= ' ' . $v5->attrData['attr'].' ';
            }
            if ($notes&&$v5->notes) {
                $name.= ' ' . '备注：'.$v5->notes;
            }
            if($printType==1){
                $maxNumber=24;
                $num=str_pad($num,2," ",STR_PAD_RIGHT);
                $prices=str_pad($prices,3," ",STR_PAD_LEFT);
                $right_length=strlen($num)+strlen($prices)+4;
                $number=floor(bcdiv((48-$right_length),2));
                $name_length=mb_strlen($name,'utf-8');
                if($number>$name_length){
                    $orderInfo.="<FH><FB>".self::LR($name,' ×'.$num.' '.$prices,48)."</FB></FH>";
                }else{
                    $orderInfo.="<FH><FB>".self::LR(mb_substr($name,0,$number),' ×'.$num.' '.$prices,48)."</FB></FH>";
                }
                $remainder= ceil((mb_strlen($name,'utf-8')-$number) /$maxNumber);
                if($remainder>0){
                    for ($i=0;$i<$remainder;$i++){
                        $orderInfo.="<FH><FB>".self::LR(mb_substr($name,$number+$i*$maxNumber,$maxNumber),'',48)."</FB></FH>";
                    }
                }
            }else{
                $maxNumber=24;
                $num=str_pad($num,2," ",STR_PAD_RIGHT);
                $name_length=mb_strlen($name,'utf-8');
                $right_length=strlen($num)+3;
                $number=floor(bcdiv((48-$right_length),2));
                if($number>$name_length){
                    $orderInfo.="<FH><FB>".self::LR($name,' ×'.$num,48)."</FB></FH>";
                }else{
                    $orderInfo.="<FH><FB>".self::LR(mb_substr($name,0,$number),' ×'.$num,48)."</FB></FH>";
                }
                $remainder= ceil((mb_strlen($name,'utf-8')-$number) /$maxNumber);
                if($remainder>0){
                    for ($i=0;$i<$remainder;$i++){
                        $orderInfo.="<FH><FB>".self::LR(mb_substr($name,$number+$i*$maxNumber,$maxNumber),'',48)."</FB></FH>";
                    }
                }
            }
            $orderInfo.="\n";
            if ($notes&&$v5->notes) {
                $orderInfo.='备注：'.$v5->notes."\n";
            }
            if(!empty($v5->setMealData)){
                foreach ($v5->setMealData as $vs){
                    $name =$vs['name'];
                    if (!empty($vs['attrData']['spec'])) {
                        $name.= ' ' . $vs['attrData']['spec'];
                    }
                    if (!empty($vs['attrData']['matal'])) {
                        $extStr = ' ';
                        $extStr .= str_replace(',', '+', $vs['attrData']['matal']);
                        $name.=$extStr;
                    }
                    if (!empty($vs['attrData']['attr'])) {
                        $name.= ' ' . $vs['attrData']['attr'].' ';
                    }
                    $num = bcmul($v5->num,$vs['num']);
                    $prices = $vs['money'];
                    $name=' -'.$name;
                    if($printType==1){
                        $maxNumber=24;
                        $num=str_pad($num,2," ",STR_PAD_RIGHT);
                        $prices=str_pad($prices,3," ",STR_PAD_LEFT);
                        $right_length=strlen($num)+strlen($prices)+4;
                        $number=floor(bcdiv((48-$right_length),2));
                        $name_length=mb_strlen($name,'utf-8');
                        if($number>$name_length){
                            $orderInfo.="<FH><FB>".self::LR($name,' ×'.$num.' '.$prices,48)."</FB></FH>";
                        }else{
                            $orderInfo.="<FH><FB>".self::LR(mb_substr($name,0,$number),' ×'.$num.' '.$prices,48)."</FB></FH>";
                        }
                        $remainder= ceil((mb_strlen($name,'utf-8')-$number) /$maxNumber);
                        if($remainder>0){
                            for ($i=0;$i<$remainder;$i++){
                                $orderInfo.="<FH><FB>".self::LR(mb_substr($name,$number+$i*$maxNumber,$maxNumber),'',48)."</FB></FH>";
                            }
                        }
                    }else{
                        $maxNumber=24;
                        $num=str_pad($num,2," ",STR_PAD_RIGHT);
                        $name_length=mb_strlen($name,'utf-8');
                        $right_length=strlen($num)+3;
                        $number=floor(bcdiv((48-$right_length),2));
                        if($number>$name_length){
                            $orderInfo.="<FH><FB>".self::LR($name,' ×'.$num,48)."</FB></FH>";
                        }else{
                            $orderInfo.="<FH><FB>".self::LR(mb_substr($name,0,$number),' ×'.$num,48)."</FB></FH>";
                        }
                        $remainder= ceil((mb_strlen($name,'utf-8')-$number) /$maxNumber);
                        if($remainder>0){
                            for ($i=0;$i<$remainder;$i++){
                                $orderInfo.="<FH><FB>".self::LR(mb_substr($name,$number+$i*$maxNumber,$maxNumber),'',48)."</FB></FH>";
                            }
                        }
                    }
                    $orderInfo.="\n";
                    if ($notes&&$vs['notes']) {
                        $orderInfo.='备注：'.$vs['notes']."\n";
                    }
                }
            }
        }
        return $orderInfo;
    }
    //商品信息放大一倍尺寸
    public static function amplify($arr,$note='',$printType=1,$notes=''){
        $orderInfo="";
        foreach ($arr as $k5 => $v5) {
            $name =$v5->name;
            $num = $v5->num;
            $prices = $v5->money?:$v5['money'];
            if($note){
                $name =$note.$name;
            }
            if($v5->isTemp){
                $name ='(临)'.$name;
            }
            if($v5->discountLabel){
                $name ='('.$v5->discountLabel.')'.$name;
            }
            if($v5->pack==1){
                $name ='(打包)'.$name;
            }
            if (!empty($v5->attrData['spec'])) {
                $name.= ' ' . $v5->attrData['spec'];
            }
            if (!empty($v5->attrData['matal'])) {
                $extStr = ' ';
                $extStr .= str_replace(',', '+', $v5->attrData['matal']);
                $name.=$extStr;
            }
            if (!empty($v5->attrData['attr'])) {
                $name.= ' ' . $v5->attrData['attr'].' ';
            }
            if ($notes&&$v5->notes) {
                $name.= ' ' . '备注：'.$v5->notes;
            }
            if($printType==1){
                $maxNumber=12;
                $num=str_pad($num,2," ",STR_PAD_RIGHT);
                $prices=str_pad($prices,4," ",STR_PAD_LEFT);
                $right_length=bcmul(strlen($num)+strlen($prices)+4,2);
                $number=floor(bcdiv((48-$right_length),4));
                $name_length=mb_strlen($name,'utf-8');
                if($number>$name_length){
                    $orderInfo.="<FS2><FB>".self::LR($name,' ×'.$num.' '.$prices,24)."</FB></FS2>";
                }else{
                    $orderInfo.="<FS2><FB>".self::LR(mb_substr($name,0,$number),' ×'.$num.' '.$prices,24)."</FB></FS2>";
                }

                $remainder= ceil((mb_strlen($name,'utf-8')-$number) /$maxNumber);
                if($remainder>0){
                    for ($i=0;$i<$remainder;$i++){
                        $orderInfo.="<FS2><FB>".self::LR(mb_substr($name,$number+$i*$maxNumber,$maxNumber),'',24)."</FB></FS2>";
                    }
                }
            }else{
                $number=9;$maxNumber=12;
                //$num=str_pad($num,2," ",STR_PAD_RIGHT);
                $name_length=mb_strlen($name,'utf-8');
                if($number>$name_length){
                    $orderInfo.="<FS2><FB>".self::LR($name,' ×'.$num,24)."</FB></FS2>";
                }else{
                    $orderInfo.="<FS2><FB>".self::LR(mb_substr($name,0,$number),' ×'.$num,24)."</FB></FS2>";
                }
                $remainder= ceil((mb_strlen($name,'utf-8')-$number) /$maxNumber);
                if($remainder>0){
                    for ($i=0;$i<$remainder;$i++){
                        $orderInfo.="<FS2><FB>".self::LR(mb_substr($name,$number+$i*$maxNumber,$maxNumber),'',24)."</FB></FS2>";
                    }
                }
            }
            $orderInfo.="\n";
            if ($notes&&$v5->notes) {
                $orderInfo.='备注：'.$v5->notes."\n";
            }
            if(!empty($v5->setMealData)){
                foreach ($v5->setMealData as $vs){
                    $name =$vs['name'];
                    if (!empty($vs['attrData']['spec'])) {
                        $name.= ' ' . $vs['attrData']['spec'];
                    }
                    if (!empty($vs['attrData']['matal'])) {
                        $extStr = ' ';
                        $extStr .= str_replace(',', '+', $vs['attrData']['matal']);
                        $name.=$extStr;
                    }
                    if (!empty($vs['attrData']['attr'])) {
                        $name.= ' ' . $vs['attrData']['attr'].' ';
                    }
                    $num = bcmul($v5->num,$vs['num']);
                    $prices = $vs['money'];
                    $name=' -'.$name;
                    if($printType==1){
                        $maxNumber=8;
                        $num=str_pad($num,2," ",STR_PAD_RIGHT);
                        $prices=str_pad($prices,4," ",STR_PAD_LEFT);
                        $right_length=bcmul(strlen($num)+strlen($prices)+4,2);
                        $number=floor(bcdiv((48-$right_length),4));
                        $name_length=mb_strlen($name,'utf-8');
                        if($number>$name_length){
                            $orderInfo.="<FS2><FB>".self::LR($name,' ×'.$num.' '.$prices,24)."</FB></FS2>";
                        }else{
                            $orderInfo.="<FS2><FB>".self::LR(mb_substr($name,0,$number),' ×'.$num.' '.$prices,24)."</FB></FS2>";
                        }

                        $remainder= ceil((mb_strlen($name,'utf-8')-$number) /$maxNumber);
                        if($remainder>0){
                            for ($i=0;$i<$remainder;$i++){
                                $orderInfo.="<FS2><FB>".self::LR(mb_substr($name,$number+$i*$maxNumber,$maxNumber),'',24)."</FB></FS2>";
                            }
                        }
                    }else{
                        $number=6;$maxNumber=8;
                        //$num=str_pad($num,2," ",STR_PAD_RIGHT);
                        $name_length=mb_strlen($name,'utf-8');
                        if($number>$name_length){
                            $orderInfo.="<FS2><FB>".self::LR($name,' ×'.$num,24)."</FB></FS2>";
                        }else{
                            $orderInfo.="<FS2><FB>".self::LR(mb_substr($name,0,$number),' ×'.$num,24)."</FB></FS2>";
                        }
                        $remainder= ceil((mb_strlen($name,'utf-8')-$number) /$maxNumber);
                        if($remainder>0){
                            for ($i=0;$i<$remainder;$i++){
                                $orderInfo.="<FS2><FB>".self::LR(mb_substr($name,$number+$i*$maxNumber,$maxNumber),'',24)."</FB></FS2>";
                            }
                        }
                    }
                    $orderInfo.="\n";
                    if ($notes&&$vs['notes']) {
                        $orderInfo.='备注：'.$vs['notes']."\n";
                    }
                }
            }
        }
        return $orderInfo;

    }


    //退菜
    public static function refundContent($order, $orderGoods = [], $operator = '')
    {
        $content = "<FS2><center>退菜单</center></FS2>\n";
        if ($order->tableId) {
            $content .= "<FS2>桌号:" . $order->table->area->name . '-' . $order->table->name . "</FS2>\n";
            $content .= "人数：" . $order->people . "\n";
        } else {
            $content .= "<FS2>牌号:" . $order->pickNo . "</FS2>\n";
        }
        $content .= "类型：" . $order->packagingFormat . "\n";
        $content .= "菜品                        数量\n";
        $content .= "------------------------------------------------\n";
        if ($orderGoods) {
            $orderGoods = collect($orderGoods)->toArray();
            $name = '(退)' . $orderGoods['name'];
            $attrData = $orderGoods['attrData'];
            if (!empty($attrData)) {
                if (!empty($attrData['spec'])) {
                    $name .= ' ' . $attrData['spec'];
                }
                if (!empty($attrData['attr'])) {
                    $name .= ' ' . $attrData['attr'];
                }
                if (!empty($attrData['matal'])) {
                    $extStr = '[加料] ';
                    $extStr .= str_replace(',', '+', $attrData['matal']);
                    $name .= $extStr;
                }
            }
            $content .= "<FS2>" . self::strFix($name, 31) .    self::strFix($orderGoods['num'], 1)  . "</FS2>";
        } else {
            if ($order->diningType == 4 && empty($order->prentOrderSn)) {
                $goods = $order->subGoods;
            } else {
                $goods = $order->goods;
            }
            $content .= "<FS2>" . self::feiStyle($goods, 2, '(退)') . "</FS2>";
        }
        $content .= "------------------------------------------------\n";
        if ($order->notes) {
            $content .= "<FB><FH>整单备注：" . $order->notes . "</FH></FB>\n";
            $content .= "------------------------------------------------\n";
        }
        if ($order->refundCause) {
            $content .= "退菜原因：" . $order->refundCause . "\n";
            $content .= "------------------------------------------------\n";
        }
        $content .= self::LR('单号：', $order->orderSn, 48) . "\n";
        if ($operator) {
            $content .= self::LR('操作人：', $operator, 48) . "\n";
        }
        $content .= self::LR('时间：', date("Y-m-d H:i", strtotime($order->created_at)), 48) . "\n";
        return $content;
    }


    //转台
    public static function turntable($fromTableId,$toTableId,$order,$operator=''){
        $content = "<center><FS2>转台单</FS2></center>";
        $content .= "------------------------------------------------\n";
        $from=Table::with('type','area')->where('id',$fromTableId)->first();
        $to=Table::with('type','area')->where('id',$toTableId)->first();
        $content .='<FB><FH>'.self::LR('原桌位：', $from->area->name.'-'.$from->type->name.'-'.$from->name, 48) . "</FH></FB>\n";
        $content .= '<FB><FH>'.self::LR('转桌位：', $to->area->name.'-'.$to->type->name.'-'.$to->name, 48) . "</FH></FB>\n";
        $content .= self::LR('人数：', $order->people, 48) . "\n";
        $content .= self::LR('单号：', $order->orderSn, 48) . "\n";
        if($operator){
            $content .= self::LR('操作人：', $operator, 48) . "\n";
        }
        $content .= self::LR('时间：', date('Y-m-d H:i:s',time()), 48) . "\n";
        return $content;
    }

    public static function handoverNote($handoverOrder){
        $content = "<center><FS2>".$handoverOrder->store->name."</center>";
        $content.= "<center><FS2>【交班单】</center>";
        $content .= "交班班次：午班\n";
        $content .= "交班单号：".$handoverOrder->id. "\n";
        $content .= "开班时间：".$handoverOrder->startTime. "\n";
        $content .= "交班时间：".$handoverOrder->endTime. "\n";
        $content .= "交班人：".$handoverOrder->admin->username. "\n";
        $content .= "------------------------------------------------\n";
//        $begin_date=time();
//        $end_date=strtotime($handoverOrder->endTime);
//        $days=round(($end_date-$begin_date)/3600/24);
//        if($days>1){
//            $content .= "<C>超".$days."天未交班</C>";
//            $content .= "已超过7天未交班，为保证交班准确性，本次只交近7个自然日的数（10/17 15:41-10/24 15:41）<BR>";
//        }
        $dataList=$handoverOrder->contents->dataList[0];
        $content .= "<C>交班汇总</C>";
        $content .= self::LR("本班次收款：",$handoverOrder->contents->money?:0.00,48)."\n";
        $content .= self::LR("微信收款：",$dataList->weixinMoney?:0.00,48)."\n";
        $content .= self::LR("支付宝收款：",$dataList->alipayMoney?:0.00,48)."\n";
        $content .= self::LR("现金收款：",$dataList->cashMoney?:0.00,48)."\n";
        $content .= self::LR("余额收款：",$dataList->balanceMoney?:0.00,48)."\n";
        $content .= "------------------------------------------------\n";
        $content .= "<C>收款统计</C>";
        $content .= "收款业务    收款笔数  收款金额\n";
        $content .= '营业收入        '.$handoverOrder->contents->orderCount.'笔      '.$handoverOrder->contents->money."\n";
        if($handoverOrder->contents->sellOut){
            foreach ($handoverOrder->contents->sellOut as $v){
                $content .= $v->name.'        '.$v->orderCount.'笔       '.$v->money."\n";
            }
        }else{
            $content .= '营业外收入      0笔       0.00'."\n";
        }
        $content .= "------------------------------------------------\n";
        $content .= "<C>收款构成</C>";
        if($dataList->weixinOrder>0){
            $content .= '微信收款        '.$dataList->weixinOrder.'笔       '.$dataList->weixinMoney."\n";
        }
        if($dataList->alipayOrder>0){
            $content .= '支付宝收款      '.$dataList->alipayOrder.'笔       '.$dataList->alipayMoney."\n";
        }
        if($dataList->cashOrder>0){
            $content .= '现金收款        '.$dataList->cashOrder.'笔       '.$dataList->cashMoney."\n";
        }
        if($dataList->balanceOrder>0){
            $content .= '余额收款        '.$dataList->balanceOrder.'笔       '.$dataList->balanceMoney."\n";
        }
        $content .= "------------------------------------------------\n";
        $content .= "<C>订单统计</C>";
        $content .= '已结账订单数                '.$handoverOrder->contents->orderCount.'单'."\n";
        $content .= '堂食收款        '.$dataList->instoreOrder.'笔       '.$dataList->instoreMoney."\n";
        $content .= '快餐收款        '.$dataList->fastOrder.'笔       '.$dataList->fastMoney."\n";
        $content .= "------------------------------------------------\n";
        $discountTrend=$handoverOrder->contents->discountTrend;

        if(!empty($handoverOrder->contents->warn)){
            $content .= "<C>敏感操作统计</C>";
            foreach ($handoverOrder->contents->warn as $v){
                $content .=self::LR($v->name,$v->count,48)."\n";
            }
            $content .= "------------------------------------------------\n";
        }

        $content .= "<C>优惠统计</C>";
        if($discountTrend){
            foreach ($discountTrend as $v){
                $content .= $v->name.'        '.$v->count.'笔       '.$v->money."\n";
            }
            $content .= "------------------------------------------------\n";
        }
        $content .= "<C>门店未完结订单统计</C>";
        $content .= "项目            单数       金额";
        $unBill=$handoverOrder->contents->unBill;
        $content .= '未结账堂食订单   '.$unBill[0]->count.'单      '.$unBill[0]->money."\n";
//        $content .= '堂食            '.$handoverOrder->contents->unclosedInstore->count.'单       '.$handoverOrder->contents->unclosedInstore->money.'<BR>';
//        $content .= '快餐            '.$handoverOrder->contents->unclosedFast->count.'单       '.$handoverOrder->contents->unclosedFast->money.'<BR>';
        $content .= "------------------------------------------------\n\n";
        $content .= "交班人签字_________\n\n";
        $content .= "监督人签字_________\n";
        $content .= "提示：1、交班单中不包含会员公众号充值、在管家后台完成的会员、挂账、订单、礼品卡业务端收、退款等业务数据\n\n";
        $content .= "      2、交班单中仅统计当前班次有效的自营外卖、平台外卖订单、如交班后存在取消订单的情况，核对时请以营业概览报表的数据为准\n\n";
        return $content;
    }

    //排队取号
    public static function queuingNumber ($queueOrder){
        $wifi= StoreConfig::where('ident', 'storeWifi')->where('storeId', $queueOrder->storeId)->first();
        //桌位类型
        $tableType = $queueOrder->type->name;
        $tableNum=QueuingUp::where('type_id',$queueOrder->type_id)->where('state',1)->count();
        $time=strtotime($queueOrder->created_at);
        $addTime=bcmul($queueOrder->type->waitTime,bcsub($tableNum,1));
        $content = "<C><FS2><FB>".$queueOrder->store->name."</FB></FS2></C>\n";
        $content .= "------------------------------------------------\n";
        $content .= '<C><FS2>'.$tableType.'</FS2></C>\n';
        $content .= '<C><FS2>'.$queueOrder->serialNum.'('.$queueOrder->people.'人)</FS2></C>\n';
        $content .= '<C>前面还有'.$queueOrder->waitingTable.'桌</C>\n';
        $content .= "<C>大约还需等待".$queueOrder->waitingTime."分钟</C>\n";
        $content .= "<QR>".$queueOrder->qrcode."</QR><BOLD>还等几桌？扫码查进度</BOLD>\n";
        $content .= '微信扫一扫，查看排队进度\n';
        $content .= "------------------------------------------------\n";
        $content .= "欢迎光临\n";
        if($wifi){
            $content .= '免费WI-FI：'.trim($wifi->data['name'])."\n";
            $content .= 'WI-FI密码：'.trim($wifi->data['password'])."\n";
        }
        $content .= "------------------------------------------------\n";
        $content .= '取号时间：'. date('Y-m-d H:i:s',bcadd($time,$addTime)) . "\n";
        $content .= $queueOrder->note."\n";
        return $content;
    }

    public static function drinkLog($drinkLog){
        $username=$drinkLog->admin->username?:$drinkLog->admin->nickname;
        if($drinkLog->typeFormat) {
            $content = '<C><FS2><FB>' . $drinkLog->typeFormat . '</FB></FS2></C>\n';
            $content .= "------------------------------------------------\n";
        }
        $content .= $drinkLog->typeFormat.'商品：'.$drinkLog->drink->name."\n";
        $content .= $drinkLog->typeFormat.'数量：'.$drinkLog->num."\n";
        $content .= '剩余数量：'.$drinkLog->residue."\n";
        $content .= $drinkLog->typeFormat.'时间：'. $drinkLog->created_at."\n";
        $content .= '用户信息：'.$drinkLog->user->nickname."\n";
        if($drinkLog->user->mobile){
            $content .= '          '.$drinkLog->user->mobile."\n";
        }
        $content .= '所属门店：'.$drinkLog->store->name."\n";
        $content .= '订单号：'.$drinkLog->orderSn."\n";
        $content .= '操作员：'.$username."\n";
        $content .= '操作时间：'.date('Y-m-d H:i:s',time())."\n";
        if($drinkLog->note){
            $content .= $drinkLog->note."\n";
        }
        return $content;
    }
}
