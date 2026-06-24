<?php
namespace App\Services\Print;
use App\Models\BulkOrder;
use App\Models\BulkOrderGoods;
use App\Models\BulkOrderGoodsGroup;
use App\Models\Print\PrintTemplate;
use App\Models\Print\PrintValue;
use App\Models\QueuingUp;
use App\Models\ShopPrint;
use App\Services\ConfigService;
use Predis\Command\Redis\DECR;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use App\Models\Tables\Table;
use App\Models\MemberAccount;
//http://help.feieyun.com/document.php
class XinyeContent
{
    //充值订单
    public static function rechargeContents($order, $balance,$str='')
    {
        $name = $order->user->realname ?: $order->user->nickname;
        $content = '<B><C>充值订单</C></B><BR><BR>';
        $content .= self::LR('订单号：', $order->orderSn, 32) . '<BR>';
        $content .= self::LR('下单时间：', date('m-d H:i', strtotime($order->created_at)), 32) . '<BR>';
        $content .= '********************************<BR>';
        $content .= self::LR('充值门店：', $order->store->name, 32) . '<BR>';
        $content .= self::LR('手机号码：', $order->user->mobile, 32) . '<BR>';
        $content .= self::LR('会员姓名：', $name, 32) . '<BR>';
        $content .= self::LR('充值金额：', $order->subOrder->money?:$order->money, 32) . '<BR>';
        $content .= self::LR('支付方式：', '微信支付', 32) . '<BR>';
        if($str){
            $content .= '充值赠送：' . $str . '<BR>';
        }
        $content .= self::LR('充后余额：', $balance, 32) . '<BR>';
        return $content;
    }
    //当面付订单
    public static function facepayContents($order)
    {
        $account = MemberAccount::where('userId', $order->userId)->first();
        $name = $order->user->realname ?: $order->user->nickname;
        $content = '<B><C>当面付订单</C></B><BR><BR>';
        $content .= self::LR('订单号：', $order->orderSn, 32) . '<BR>';
        if($order->pickNo) {
            $content .= self::LR('流水号：', $order->pickNo, 32) . '<BR>';
        }
        $content .= self::LR('支付时间：', date('m-d H:i', strtotime($order->created_at)), 32) . '<BR>';
        $content .= '********************************<BR>';
        if($order->user->mobile){
            $content .= self::LR('手机号码：', $order->user->mobile, 32) . '<BR>';
        }
        if($name){
            $content .= self::LR('会员姓名：', $name, 32) . '<BR>';
        }
        $content .= self::LR('消费门店：', $order->store->name, 32) . '<BR>';
        $content .= self::LR('付款金额：', $order->money ?? $order->subOrder->money, 32) . '<BR>';
        $content .= self::LR('用户余额：', $account->balance, 32) . '<BR>';
        $content .= self::LR('支付方式：', $order->PayTypeFormat, 32) . '<BR>';
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
                if($length<=32){
                    if($isB==true){
                        $str .= '<HB><BOLD>' . self::LR($name, $rightContent, 32) . '</BOLD></HB><BR>';
                    }else{
                        $str .=self::LR($name, $rightContent, 32) . '<BR>';
                    }
                }else{
                    if($isB==true) {
                        $str .= '<HB><BOLD>' . mb_substr($name, 0, 32, 'utf-8') . '</BOLD></HB><BR>';
                        $str .= '<HB><BOLD>' . self::LR(mb_substr($name, 32, $length, 'utf-8'), $rightContent, 32) . '</BOLD></HB><BR>';
                    }else{
                        $str .= mb_substr($name, 0, 32, 'utf-8') . '<BR>';
                        $str .= self::LR(mb_substr($name, 32, $length, 'utf-8'), $rightContent, 32) . '<BR>';
                    }
                }
            }else{
                $rightContent=$nums;
                $length=mb_strlen($name.$rightContent);
                if($length<=32){
                    if($isB==true) {
                        $str .= '<HB><BOLD>' . self::LR($name, $rightContent, 32) . '</BOLD></HB><BR>';
                    }else{
                        $str .= self::LR($name, $rightContent, 32) . '<BR>';
                    }
                }else{
                    if($isB==true) {
                        $str .= '<HB><BOLD>' . mb_substr($name, 0, 32, 'utf-8') . '</BOLD></HB><BR>';
                        $str .= '<HB><BOLD>' . self::LR(mb_substr($name, 32, $length, 'utf-8'), $rightContent, 32) . '</BOLD></HB><BR>';
                    }else{
                        $str .= mb_substr($name, 0, 32, 'utf-8') . '<BR>';
                        $str .= self::LR(mb_substr($name, 32, $length, 'utf-8'), $rightContent, 32) . '<BR>';
                    }
                }
            }
        }
        return $str;
    }

    public static function strFix($name, $A, $br = '<BR>', $maxLine = 32)
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
                    $lenght = iconv('UTF-8', 'GBK//IGNORE', $new);
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
        $str_left_lenght = strlen(iconv('UTF-8', 'GBK//IGNORE', $str_left));
        $str_right_lenght = strlen(iconv('UTF-8', 'GBK//IGNORE', $str_right));
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
        $str_left_lenght = strlen(iconv('UTF-8', 'GBK//IGNORE', $str_left));
        $center = strlen(iconv('UTF-8', 'GBK//IGNORE', $center));
        $str_right_lenght = strlen(iconv('UTF-8', 'GBK//IGNORE', $str_right));
        $k = $length - (9);
        for ($q = 0; $q < $k; $q++) {
            $kw .= ' ';
        }
        return $str_left . $kw . $center.' '.$str_right;
    }

    public static function instoreOneContents($order, $orderGoods = [], $otherMode = '')
    {
        $makeName = $orderGoods ? $otherMode . '制作分单' : $otherMode . '制作整单';
        $content = '<CB>' . $makeName . '</CB><BR>';
        if ($order->tableId) {
            if($order->table->area->name){
                $content .= '<B>桌号:' . $order->table->area->name . '-' . $order->table->name . '</B><BR><BR>';
            }else{
                $content .= '<B>桌号:' . $order->table->name . '</B><BR><BR>';
            }

            $content .= '人数：' . $order->people . '<BR><BR>';
        } else {
            $content .= '<B>牌号:' . $order->pickNo . '</B><BR>';
        }
        $content .= '类型：' . $order->packagingFormat . '<BR>';
        $content .= '菜品                        数量<BR>';
        $content .= '--------------------------------<BR>';
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
            $content .= '<B>' . self::LR($name, $orderGoods->num, 32) . '</B><BR>';
        } else {
            $content .= '<B>' . self::feiStyle($order->goods,2) . '</B><BR>';
        }
        $content .= '--------------------------------<BR>';
        if ($order->notes) {
            $content .= '<HB><BOLD>整单备注：' . $order->notes . '</HB></BOLD><BR>';
            $content .= '--------------------------------<BR>';
        }
        $content .= self::LR('单号：', $order->orderSn, 32) . '<BR>';
        $content .= self::LR('操作人：', $order->user->nickname, 32) . '<BR>';
        $content .= self::LR('时间：', date('Y-m-d H:i', strtotime($order->created_at)), 32) . '<BR>';
        return $content;
    }

    public static function customContent($data,$content){
        if($data){
            if($data['line']==1){
                $content.='<BR>';
            }
            if($data['bold']==2){
                $content='<BOLD>'.$content.'</BOLD>';
            }
            if($data['size']){
                switch ($data['size']){
                    case 2;
                        $content='<WB>'.$content.'</WB>';
                        break;
                    case 3;
                        $content='<HB>'.$content.'</HB>';
                        break;
                    case 4;
                        $content='<B>'.$content.'</B>';
                        break;
                }
            }

            if($data['align']){
                switch ($data['align']){
                    case 1;
                        $content=$content;
                        break;
                    case 2;
                        $content='<C>'.$content.'</C>';
                        break;
                    case 3;
                        $content='<R>'.$content.'</R>';
                        break;
                }

            }
            if($data['boder']==2){
                $content.='<BR>--------------------------------';
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
        $content='';$goodsTitleNum=0;
        foreach ($data as $k=>$v){
            switch ($v['label']){
                case 'orderType';
                    if(in_array($print_type,[4,5,11,14,15])){
                        $content.=self::customContent($v,$data[$k]['name'].'：'.'#' .$order->pickNo).'<BR>';
                    }else{
                        $content.=self::customContent($v,$data[$k]['name']).'<BR>';
                    }
                    break;
                case 'storeName';
                    $content.=self::customContent($v,'*'.$order->store->name.'*').'<BR>';
                    break;
                case 'tableNo';
                        if($order->diningType==4||$order->diningType==5) {
                            $content .= self::customContent($v, $data[$k]['name'] . '：' . $order->table->name) . '<BR>';
                        }
                    break;
                case 'people';
                    if($order->tableId) {
                        $content .= self::customContent($v, $data[$k]['name'] . '：' . $order->people) . '<BR>';
                    }
                    break;
                case 'pickNo';
                    if($order->diningType==6 || $order->diningType==5){
                        $content.=self::customContent($v,$data[$k]['name'].'：'.$order->pickNo).'<BR>';
                    }
                    break;

                case 'payTime';
                    $content.=self::customContent($v,$data[$k]['name'].'：'.date('Y-m-d H:i', strtotime($order->created_at))).'<BR>';
                    break;
                case 'orderSn';
                    $content.=self::customContent($v,$data[$k]['name'].'：'.$order->orderSn).'<BR>';
                    break;
                case 'goodsTitle';
                    $goodsTitle=[];
                    $goodsTitle=explode('，',$data[$k]['name']);

                    $goodsTitleNum=count($goodsTitle);
                    if($goodsTitleNum==2){
                        $content .= self::LR($goodsTitle[0], $goodsTitle[1], 32) . '<BR>';
                    }else{
                        switch ($v['size']){
                            case 1;//18个空格
                                if($print_type==11){
                                    $content .= self::LR($goodsTitle[0],$goodsTitle[1].'       '.$goodsTitle[2],32);
                                }else{
                                    $content .= self::LR($goodsTitle[0],$goodsTitle[1].' '.$goodsTitle[2],32);
                                }
                                break;
                            case 2;
                                $content .= '<WB>'.self::LR($goodsTitle[0],$goodsTitle[1].' '.$goodsTitle[2],16).'</WB>';
                                break;
                            case 3;
                                $content .= '<HB>'.self::LR($goodsTitle[0],$goodsTitle[1].' '.$goodsTitle[2],32).'</HB>';
                                break;
                            case 4;//2个空格
                                $content .= '<B>'.self::LR($goodsTitle[0],$goodsTitle[1].' '.$goodsTitle[2],16).'</B>';
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
                    $content .= '--------------------------------<BR>';
                    break;
                case 'notes';
                    if($order->notes){
                        $content.=self::customContent($v,$data[$k]['name'].'：'.$order->notes).'<BR>';
                    }
                    break;
                case 'arriveTime';
                    if ($order->scene == 1) {
                        if ($order->appointment == 1) {
                            if (strtotime($order->serverTime) > strtotime(date('Y-m-d 23:59:59'))) {
                                $content.=self::customContent($v, '预订单 ' . date('m-d H:i', strtotime($order->serverTime)) . '送达').'<BR>';
                            } else {
                                $content.=self::customContent($v, date('m-d H:i', strtotime($order->serverTime)) . '送达').'<BR>';
                            }
                        } else {
                            $content.=self::customContent($v, '预计' . date('m-d H:i', strtotime($order->serverTime)) . '送达').'<BR>';
                        }
                    } else {
                        if ($order->appointment == 1) {
                            if (strtotime($order->serverTime) > strtotime(date('Y-m-d 23:59:59'))) {
                                $content.=self::customContent($v, '预订单 ' .date('m-d H:i', strtotime($order->serverTime))).'<BR>';
                                $content.=self::customContent($v, '取餐码：' . $order->pickNo).'<BR>';
                            } else {
                                $content.=self::customContent($v, date('m-d H:i', strtotime($order->serverTime)) . $order->diningTypeFormat).'<BR>';
                                $content.=self::customContent($v, '取餐码：' . $order->pickNo).'<BR>';
                            }
                        } else {
                            $content.=self::customContent($v, date('m-d H:i', strtotime($order->serverTime)) . $order->diningTypeFormat).'<BR>';
                        }
                    }
                    break;
                case 'checkoutType';
                    $content.=self::customContent($v,$data[$k]['name'].'：'.$order->sourceFormat).'<BR>';;
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
                                $diningType=$order->diningTypeFormat.'预订单';
                            } else {
                                $diningType=$order->diningTypeFormat.'即时单';
                            }
                        }
                    }
                    $diningType=$diningType?:$order->diningTypeFormat;
                    if($order->packaging==1){
                        $diningType=$diningType.'(打包带走)';
                    }
                    $content.=self::customContent($v,$data[$k]['name'].'：'.$diningType).'<BR>';;
                    break;
                case 'packTime';
                    if ($order->scene == 1) {
                        if ($order->appointment == 1) {
                            if (strtotime($order->serverTime) > strtotime(date('Y-m-d 23:59:59'))) {
                                $content.=self::customContent($v, $data[$k]['name'].'：'.'预订单 ' . date('m-d H:i', strtotime($order->serverTime)) . '送达').'<BR>';
                            } else {
                                $content.=self::customContent($v, $data[$k]['name'].'：'.date('m-d H:i', strtotime($order->serverTime)) . '送达').'<BR>';
                            }
                        } else {
                            $content.=self::customContent($v, $data[$k]['name'].'：'.'预计' . date('m-d H:i', strtotime($order->serverTime)) . '送达').'<BR>';
                        }
                    } else {
                        if ($order->appointment == 1) {
                            if (strtotime($order->serverTime) > strtotime(date('Y-m-d 23:59:59'))) {
                                $content.=self::customContent($v,$data[$k]['name'].'：'. date('m-d H:i', strtotime($order->serverTime))).'<BR>';
                                $content.=self::customContent($v, '取餐码：' . $order->pickNo).'<BR>';
                            } else {
                                $content.=self::customContent($v, $data[$k]['name'].'：'.date('m-d H:i', strtotime($order->serverTime)) . $order->diningTypeFormat).'<BR>';
                                $content.=self::customContent($v, '取餐码：' . $order->pickNo).'<BR>';
                            }
                        } else {
                            $content.=self::customContent($v, $data[$k]['name'].'：'.date('m-d H:i', strtotime($order->serverTime)) . $order->diningTypeFormat).'<BR>';
                        }
                    }
                    break;
                case 'money';
                    $content.=self::customContent($v,$data[$k]['name'].'：'.$order->sellMoney).'<BR>';
                    if($order->tableMoney>0){
                        $content.=$order->tableFormat.'*'.$order->tableNum.'：'.$order->tableMoney.'<BR>';
                    }
                    break;
                case 'payMoney';
                    if($order->service_money) {
                        $content .= '订单服务费：' . $order->service_money . '<BR>';
                    }
                    $payMoney=$order->money;
                    $content.=self::customContent($v,$data[$k]['name'].'：'.$payMoney).'<BR>';
                    break;
                case 'payable';
                    $content.=self::customContent($v,$data[$k]['name'].'：'.$order->sellMoney).'<BR>';
                    if($order->discountMoney){
                        $content.='订单优惠：'.$order->discountMoney.'<BR>';
                    }
                    if($order->service_money) {
                        $content .= '订单服务费：' . $order->service_money . '<BR>';
                    }
                    break;
                case 'disbursement';
                    if ($order->payTime) {
                        $disbursementMoney=$order->money;
                        $payTypeFormat=$order->costomPayFormat?$order->costomPayFormat:$order->PayTypeFormat;
                        $content.=self::customContent($v,$payTypeFormat.'：'.$disbursementMoney).'<BR>';
                    }
                    break;
                case 'payType';//应付金额
                    $actualMoney=$order->money;
                    $content.=self::customContent($v,$data[$k]['name'].'：'.$actualMoney).'<BR>';
                    break;

                case 'payAll';
                    $payAllMoney=$order->money;
                    $content.=self::customContent($v,$data[$k]['name'].'：'.$payAllMoney);
                    break;
                case 'username';
                    if($print_type<>10&&$order->mobile){
                        $mobile=$order->address['mobile']?:$order->mobile;
                        if($order->address['contact']){
                            $content.=self::customContent($v,$data[$k]['name'].'：'. '(' . $order->address['contact'] . ')'.$mobile).'<BR>';
                        }else{
                            $content.=self::customContent($v,$data[$k]['name'].'：'.$mobile).'<BR>';
                        }
                    }
                    break;
                case 'created_at';
                    $content.=self::customContent($v,$data[$k]['name'].'：'.date('Y-m-d H:i', strtotime($order->created_at))).'<BR>';
                    break;
                case 'print_at';
                    $content.=self::customContent($v,$data[$k]['name'].'：'.date('Y-m-d H:i', time())).'<BR>';
                    break;
                case 'desc';
                    if($data[$k]['value']){
                        if($v['line']==1){
                            $data[$k]['value']='<BR>'.$data[$k]['value'];
                        }
                        $content.=self::customContent($v,$data[$k]['value']).'<BR>';
                    }
                    break;
                case 'waiter';
                    $mobile=$order->address['mobile']?:$order->mobile;
                    if($mobile){
                        if($order->address['contact']){
                            $content.=self::customContent($v,$data[$k]['name'].'：'. '(' . $order->address['contact'] . ')'.$mobile).'<BR>';
                        }else{
                            $content.=self::customContent($v,$data[$k]['name'].'：'. $mobile).'<BR>';
                        }
                    }
                    break;
                case 'site';
                    if($order->address){
                        $content.=self::customContent($v,$data[$k]['name'].'：'.$order->address['address'].$order->address['description']).'<BR>';
                    }
                    break;
                case 'reason';
                    if($refundReason){
                        $content.=self::customContent($v,$data[$k]['name'].'：'.$refundReason).'<BR>';
                    }
                    break;
                case 'address';
                    if($order->address){
                        $content.=self::customContent($v,$data[$k]['name'].'：'.$order->address['address'].$order->address['description']).'<BR>';
                    }
                    break;
                case 'delivery';
                    $content.=self::customContent($v,$data[$k]['name'].'：'.$order->deliveryMoney).'<BR>';
                    break;
                case 'code';
                    if($data[$k]['value']==1){
                        $content.='<QRCODE s=6 e=L l=center>'.$order->qrcode.'</QRCODE>';
                    }
                    if($data[$k]['value']==2){
                        $content.='<QRCODE s=6 e=L l=center>'.$data[$k]['name'].'</QRCODE>';
                    }
                    break;
                case 'mobile';
                    $content.=self::customContent($v,$data[$k]['name'].'：'.$order->store->mobile).'<BR>';
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
                $maxNumber=16;
                $num=str_pad($num,2,' ',STR_PAD_RIGHT);
                $prices=str_pad($prices,3,' ',STR_PAD_LEFT);
                $right_length=strlen($num)+strlen($prices)+4;
                $number=floor(bcdiv((32-$right_length),2));
                $name_length=mb_strlen($name,'utf-8');
                if($number>$name_length){
                    $orderInfo.='<BOLD>'.self::LR($name,' ×'.$num.' '.$prices,32).'</BOLD>';
                }else{
                    $orderInfo.='<BOLD>'.self::LR(mb_substr($name,0,$number),' ×'.$num.' '.$prices,32).'</BOLD>';
                }
                $remainder= ceil((mb_strlen($name,'utf-8')-$number) /$maxNumber);
                if($remainder>0){
                    for ($i=0;$i<$remainder;$i++){
                        $orderInfo.='<BOLD>'.self::LR(mb_substr($name,$number+$i*$maxNumber,$maxNumber),'',32).'</BOLD>';
                    }
                }
            }else{
                $maxNumber=16;
                $num=str_pad($num,2,' ',STR_PAD_RIGHT);
                $name_length=mb_strlen($name,'utf-8');
                $right_length=strlen($num)+3;
                $number=floor(bcdiv((32-$right_length),2));
                if($number>$name_length){
                    $orderInfo.='<BOLD>'.self::LR($name,' ×'.$num,32).'</BOLD>';
                }else{
                    $orderInfo.='<BOLD>'.self::LR(mb_substr($name,0,$number),' ×'.$num,32).'</BOLD>';
                }
                $remainder= ceil((mb_strlen($name,'utf-8')-$number) /$maxNumber);
                if($remainder>0){
                    for ($i=0;$i<$remainder;$i++){
                        $orderInfo.='<BOLD>'.self::LR(mb_substr($name,$number+$i*$maxNumber,$maxNumber),'',32).'</BOLD>';
                    }
                }
            }
            $orderInfo.='<BR>';
            if ($notes&&$v5->notes) {
                $orderInfo.='备注：'.$v5->notes.'<BR>';
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
                    $prices =$vs['money'];
                    $name=' -'.$name;
                    if($printType==1){
                        $maxNumber=16;
                        $num=str_pad($num,2,' ',STR_PAD_RIGHT);
                        $prices=str_pad($prices,3,' ',STR_PAD_LEFT);
                        $right_length=strlen($num)+strlen($prices)+4;
                        $number=floor(bcdiv((32-$right_length),2));
                        $name_length=mb_strlen($name,'utf-8');
                        if($number>$name_length){
                            $orderInfo.='<BOLD>'.self::LR($name,' ×'.$num.' '.$prices,32).'</BOLD>';
                        }else{
                            $orderInfo.='<BOLD>'.self::LR(mb_substr($name,0,$number),' ×'.$num.' '.$prices,32).'</BOLD>';
                        }
                        $remainder= ceil((mb_strlen($name,'utf-8')-$number) /$maxNumber);
                        if($remainder>0){
                            for ($i=0;$i<$remainder;$i++){
                                $orderInfo.='<BOLD>'.self::LR(mb_substr($name,$number+$i*$maxNumber,$maxNumber),'',32).'</BOLD>';
                            }
                        }
                    }else{
                        $maxNumber=16;
                        $num=str_pad($num,2,' ',STR_PAD_RIGHT);
                        $name_length=mb_strlen($name,'utf-8');
                        $right_length=strlen($num)+3;
                        $number=floor(bcdiv((32-$right_length),2));
                        if($number>$name_length){
                            $orderInfo.='<BOLD>'.self::LR($name,' ×'.$num,32).'</BOLD>';
                        }else{
                            $orderInfo.='<BOLD>'.self::LR(mb_substr($name,0,$number),' ×'.$num,32).'</BOLD>';
                        }
                        $remainder= ceil((mb_strlen($name,'utf-8')-$number) /$maxNumber);
                        if($remainder>0){
                            for ($i=0;$i<$remainder;$i++){
                                $orderInfo.='<BOLD>'.self::LR(mb_substr($name,$number+$i*$maxNumber,$maxNumber),'',32).'</BOLD>';
                            }
                        }
                    }
                    $orderInfo.='<BR>';
                    if ($notes&&$vs['notes']) {
                        $orderInfo.='备注：'.$vs['notes'].'<BR>';
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
                $num=str_pad($num,2,' ',STR_PAD_RIGHT);
                $prices=str_pad($prices,4,' ',STR_PAD_LEFT);
                $right_length=bcmul(strlen($num)+strlen($prices)+4,2);
                $number=floor(bcdiv((32-$right_length),4));
                $name_length=mb_strlen($name,'utf-8');
                if($number>$name_length){
                    $orderInfo.='<WB><BOLD>'.self::LR($name,' ×'.$num.' '.$prices,16).'</BOLD></WB>';
                }else{
                    $orderInfo.='<WB><BOLD>'.self::LR(mb_substr($name,0,$number),' ×'.$num.' '.$prices,16).'</BOLD></WB>';
                }

                $remainder= ceil((mb_strlen($name,'utf-8')-$number) /$maxNumber);
                if($remainder>0){
                    for ($i=0;$i<$remainder;$i++){
                        $orderInfo.='<WB><BOLD>'.self::LR(mb_substr($name,$number+$i*$maxNumber,$maxNumber),'',16).'</BOLD></WB>';
                    }
                }
            }else{
                $number=6;$maxNumber=8;
                //$num=str_pad($num,2,' ',STR_PAD_RIGHT);
                $name_length=mb_strlen($name,'utf-8');
                if($number>$name_length){
                    $orderInfo.='<WB><BOLD>'.self::LR($name,' ×'.$num,16).'</BOLD></WB>';
                }else{
                    $orderInfo.='<WB><BOLD>'.self::LR(mb_substr($name,0,$number),' ×'.$num,16).'</BOLD></WB>';
                }
                $remainder= ceil((mb_strlen($name,'utf-8')-$number) /$maxNumber);
                if($remainder>0){
                    for ($i=0;$i<$remainder;$i++){
                        $orderInfo.='<WB><BOLD>'.self::LR(mb_substr($name,$number+$i*$maxNumber,$maxNumber),'',16).'</BOLD></WB>';
                    }
                }
            }
            $orderInfo.='<BR>';
            if ($notes&&$v5->notes) {
                $orderInfo.='备注：'.$v5->notes.'<BR>';
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
                    $prices =$vs['money'];
                    $name=' -'.$name;
                    if($printType==1){
                        $maxNumber=8;
                        $num=str_pad($num,2,' ',STR_PAD_RIGHT);
                        $prices=str_pad($prices,4,' ',STR_PAD_LEFT);
                        $right_length=bcmul(strlen($num)+strlen($prices)+4,2);
                        $number=floor(bcdiv((32-$right_length),4));
                        $name_length=mb_strlen($name,'utf-8');
                        if($number>$name_length){
                            $orderInfo.='<WB><BOLD>'.self::LR($name,' ×'.$num.' '.$prices,16).'</BOLD></WB>';
                        }else{
                            $orderInfo.='<WB><BOLD>'.self::LR(mb_substr($name,0,$number),' ×'.$num.' '.$prices,16).'</BOLD></WB>';
                        }

                        $remainder= ceil((mb_strlen($name,'utf-8')-$number) /$maxNumber);
                        if($remainder>0){
                            for ($i=0;$i<$remainder;$i++){
                                $orderInfo.='<WB><BOLD>'.self::LR(mb_substr($name,$number+$i*$maxNumber,$maxNumber),'',16).'</BOLD></WB>';
                            }
                        }
                    }else{
                        $number=6;$maxNumber=8;
                        //$num=str_pad($num,2,' ',STR_PAD_RIGHT);
                        $name_length=mb_strlen($name,'utf-8');
                        if($number>$name_length){
                            $orderInfo.='<WB><BOLD>'.self::LR($name,' ×'.$num,16).'</BOLD></WB>';
                        }else{
                            $orderInfo.='<WB><BOLD>'.self::LR(mb_substr($name,0,$number),' ×'.$num,16).'</BOLD></WB>';
                        }
                        $remainder= ceil((mb_strlen($name,'utf-8')-$number) /$maxNumber);
                        if($remainder>0){
                            for ($i=0;$i<$remainder;$i++){
                                $orderInfo.='<WB><BOLD>'.self::LR(mb_substr($name,$number+$i*$maxNumber,$maxNumber),'',16).'</BOLD></WB>';
                            }
                        }
                    }
                    $orderInfo.='<BR>';
                    if ($notes&&$vs['notes']) {
                        $orderInfo.='备注：'.$vs['notes'].'<BR>';
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
                $maxNumber=16;
                $num=str_pad($num,2,' ',STR_PAD_RIGHT);
                $prices=str_pad($prices,3,' ',STR_PAD_LEFT);
                $right_length=strlen($num)+strlen($prices)+4;
                $number=floor(bcdiv((32-$right_length),2));
                $name_length=mb_strlen($name,'utf-8');
                if($number>$name_length){
                    $orderInfo.='<HB><BOLD>'.self::LR($name,' ×'.$num.' '.$prices,32).'</BOLD></HB>';
                }else{
                    $orderInfo.='<HB><BOLD>'.self::LR(mb_substr($name,0,$number),' ×'.$num.' '.$prices,32).'</BOLD></HB>';
                }
                $remainder= ceil((mb_strlen($name,'utf-8')-$number) /$maxNumber);
                if($remainder>0){
                    for ($i=0;$i<$remainder;$i++){
                        $orderInfo.='<HB><BOLD>'.self::LR(mb_substr($name,$number+$i*$maxNumber,$maxNumber),'',32).'</BOLD></HB>';
                    }
                }
            }else{
                $maxNumber=16;
                $num=str_pad($num,2,' ',STR_PAD_RIGHT);
                $name_length=mb_strlen($name,'utf-8');
                $right_length=strlen($num)+3;
                $number=floor(bcdiv((32-$right_length),2));
                if($number>$name_length){
                    $orderInfo.='<HB><BOLD>'.self::LR($name,' ×'.$num,32).'</BOLD></HB>';
                }else{
                    $orderInfo.='<HB><BOLD>'.self::LR(mb_substr($name,0,$number),' ×'.$num,32).'</BOLD></HB>';
                }
                $remainder= ceil((mb_strlen($name,'utf-8')-$number) /$maxNumber);
                if($remainder>0){
                    for ($i=0;$i<$remainder;$i++){
                        $orderInfo.='<HB><BOLD>'.self::LR(mb_substr($name,$number+$i*$maxNumber,$maxNumber),'',32).'</BOLD></HB>';
                    }
                }
            }
            $orderInfo.='<BR>';
            if ($notes&&$v5->notes) {
                $orderInfo.='备注：'.$v5->notes.'<BR>';
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
                        $maxNumber=16;
                        $num=str_pad($num,2,' ',STR_PAD_RIGHT);
                        $prices=str_pad($prices,3,' ',STR_PAD_LEFT);
                        $right_length=strlen($num)+strlen($prices)+4;
                        $number=floor(bcdiv((32-$right_length),2));
                        $name_length=mb_strlen($name,'utf-8');
                        if($number>$name_length){
                            $orderInfo.='<HB><BOLD>'.self::LR($name,' ×'.$num.' '.$prices,32).'</BOLD></HB>';
                        }else{
                            $orderInfo.='<HB><BOLD>'.self::LR(mb_substr($name,0,$number),' ×'.$num.' '.$prices,32).'</BOLD></HB>';
                        }
                        $remainder= ceil((mb_strlen($name,'utf-8')-$number) /$maxNumber);
                        if($remainder>0){
                            for ($i=0;$i<$remainder;$i++){
                                $orderInfo.='<HB><BOLD>'.self::LR(mb_substr($name,$number+$i*$maxNumber,$maxNumber),'',32).'</BOLD></HB>';
                            }
                        }
                    }else{
                        $maxNumber=16;
                        $num=str_pad($num,2,' ',STR_PAD_RIGHT);
                        $name_length=mb_strlen($name,'utf-8');
                        $right_length=strlen($num)+3;
                        $number=floor(bcdiv((32-$right_length),2));
                        if($number>$name_length){
                            $orderInfo.='<HB><BOLD>'.self::LR($name,' ×'.$num,32).'</BOLD></HB>';
                        }else{
                            $orderInfo.='<HB><BOLD>'.self::LR(mb_substr($name,0,$number),' ×'.$num,32).'</BOLD></HB>';
                        }
                        $remainder= ceil((mb_strlen($name,'utf-8')-$number) /$maxNumber);
                        if($remainder>0){
                            for ($i=0;$i<$remainder;$i++){
                                $orderInfo.='<HB><BOLD>'.self::LR(mb_substr($name,$number+$i*$maxNumber,$maxNumber),'',32).'</BOLD></HB>';
                            }
                        }
                    }
                    $orderInfo.='<BR>';
                    if ($notes&&$vs['notes']) {
                        $orderInfo.='备注：'.$vs['notes'].'<BR>';
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
                $num=str_pad($num,2,' ',STR_PAD_RIGHT);
                $prices=str_pad($prices,4,' ',STR_PAD_LEFT);
                $right_length=bcmul(strlen($num)+strlen($prices)+4,2);
                $number=floor(bcdiv((32-$right_length),4));
                $name_length=mb_strlen($name,'utf-8');
                if($number>$name_length){
                    $orderInfo.='<B><BOLD>'.self::LR($name,' ×'.$num.' '.$prices,16).'</BOLD></B>';
                }else{
                    $orderInfo.='<B><BOLD>'.self::LR(mb_substr($name,0,$number),' ×'.$num.' '.$prices,16).'</BOLD></B>';
                }

                $remainder= ceil((mb_strlen($name,'utf-8')-$number) /$maxNumber);
                if($remainder>0){
                    for ($i=0;$i<$remainder;$i++){
                        $orderInfo.='<B><BOLD>'.self::LR(mb_substr($name,$number+$i*$maxNumber,$maxNumber),'',16).'</BOLD></B>';
                    }
                }
            }else{
                $number=6;$maxNumber=8;
                //$num=str_pad($num,2,' ',STR_PAD_RIGHT);
                $name_length=mb_strlen($name,'utf-8');
                if($number>$name_length){
                    $orderInfo.='<B><BOLD>'.self::LR($name,' ×'.$num,16).'</BOLD></B>';
                }else{
                    $orderInfo.='<B><BOLD>'.self::LR(mb_substr($name,0,$number),' ×'.$num,16).'</BOLD></B>';
                }
                $remainder= ceil((mb_strlen($name,'utf-8')-$number) /$maxNumber);
                if($remainder>0){
                    for ($i=0;$i<$remainder;$i++){
                        $orderInfo.='<B><BOLD>'.self::LR(mb_substr($name,$number+$i*$maxNumber,$maxNumber),'',16).'</BOLD></B>';
                    }
                }
            }
            $orderInfo.='<BR>';
            if ($notes&&$v5->notes) {
                $orderInfo.='备注：'.$v5->notes.'<BR>';
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
                        $num=str_pad($num,2,' ',STR_PAD_RIGHT);
                        $prices=str_pad($prices,4,' ',STR_PAD_LEFT);
                        $right_length=bcmul(strlen($num)+strlen($prices)+4,2);
                        $number=floor(bcdiv((32-$right_length),4));
                        $name_length=mb_strlen($name,'utf-8');
                        if($number>$name_length){
                            $orderInfo.='<B><BOLD>'.self::LR($name,' ×'.$num.' '.$prices,16).'</BOLD></B>';
                        }else{
                            $orderInfo.='<B><BOLD>'.self::LR(mb_substr($name,0,$number),' ×'.$num.' '.$prices,16).'</BOLD></B>';
                        }

                        $remainder= ceil((mb_strlen($name,'utf-8')-$number) /$maxNumber);
                        if($remainder>0){
                            for ($i=0;$i<$remainder;$i++){
                                $orderInfo.='<B><BOLD>'.self::LR(mb_substr($name,$number+$i*$maxNumber,$maxNumber),'',16).'</BOLD></B>';
                            }
                        }
                    }else{
                        $number=6;$maxNumber=8;
                        //$num=str_pad($num,2,' ',STR_PAD_RIGHT);
                        $name_length=mb_strlen($name,'utf-8');
                        if($number>$name_length){
                            $orderInfo.='<B><BOLD>'.self::LR($name,' ×'.$num,16).'</BOLD></B>';
                        }else{
                            $orderInfo.='<B><BOLD>'.self::LR(mb_substr($name,0,$number),' ×'.$num,16).'</BOLD></B>';
                        }
                        $remainder= ceil((mb_strlen($name,'utf-8')-$number) /$maxNumber);
                        if($remainder>0){
                            for ($i=0;$i<$remainder;$i++){
                                $orderInfo.='<B><BOLD>'.self::LR(mb_substr($name,$number+$i*$maxNumber,$maxNumber),'',16).'</BOLD></B>';
                            }
                        }
                    }
                    $orderInfo.='<BR>';
                    if ($notes&&$vs['notes']) {
                        $orderInfo.='备注：'.$vs['notes'].'<BR>';
                    }
                }
            }
        }
        return $orderInfo;

    }


    //退菜
    public static function refundContent($order, $orderGoods = [], $operator = '')
    {
        $content = '<CB>退菜单</CB><BR>';
        if ($order->tableId) {
            if($order->table->area->name){
                $content .= '<B>桌号:' . $order->table->area->name . '-' . $order->table->name . '</B><BR>';
            }else{
                $content .= '<B>桌号:' .  $order->table->name . '</B><BR>';
            }
            $content .= '人数：' . $order->people . '<BR>';
        } else {
            $content .= '<B>牌号:' . $order->pickNo . '</B><BR>';
        }
        $content .= '类型：' . $order->packagingFormat . '<BR>';
        $content .= '菜品                        数量<BR>';
        $content .= '--------------------------------<BR>';
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
            $content .= '<B>' . self::strFix($name, 31) .    self::strFix($orderGoods['num'], 1)  . '</B>';
        } else {
            if ($order->diningType == 4 && empty($order->prentOrderSn)) {
                $goods = $order->subGoods;
            } else {
                $goods = $order->goods;
            }
            $content .= '<B>' . self::feiStyle($goods, 2, '(退)') . '</B>';
        }
        $content .= '--------------------------------<BR>';
        if ($order->notes) {
            $content .= '<HB><BOLD>整单备注：' . $order->notes . '</HB></BOLD><BR>';
            $content .= '--------------------------------<BR>';
        }
        if ($order->refundCause) {
            $content .= '退菜原因：' . $order->refundCause . '<BR>';
            $content .= '--------------------------------<BR>';
        }
        $content .= self::LR('单号：', $order->orderSn, 32) . '<BR>';
        if ($operator) {
            $content .= self::LR('操作人：', $operator, 32) . '<BR>';
        }
        $content .= self::LR('时间：', date('Y-m-d H:i', strtotime($order->created_at)), 32) . '<BR>';
        return $content;
    }

    //转台
    public static function turntable($fromTableId,$toTableId,$order,$operator=''){
        $content = '<B><C>转台单</C></B>';
        $from=Table::with('type','area')->where('id',$fromTableId)->first();
        $to=Table::with('type','area')->where('id',$toTableId)->first();
        $content .='<HB><BOLD>'.self::LR('原桌位：', $from->area->name.'-'.$from->type->name.'-'.$from->name, 32) . '</BOLD></HB><BR>';
        $content .= '<HB><BOLD>'.self::LR('转桌位：', $to->area->name.'-'.$to->type->name.'-'.$to->name, 32) . '</BOLD></HB><BR>';
        $content .= self::LR('人数：', $order->people, 32) . '<BR>';
        $content .= self::LR('单号：', $order->orderSn, 32) . '<BR>';
        if($operator){
            $content .= self::LR('操作人：', $operator, 32) . '<BR>';
        }
        $content .= self::LR('时间：', date('Y-m-d H:i:s',time()), 32) . '<BR>';
        return $content;
    }

    public static function handoverNote($handoverOrder){
        $content = "<B><C>".$handoverOrder->store->name."</C></B>";
        $content.= "<B><C>【交班单】</C></B>";
        $content .= "交班班次：午班<BR>";
        $content .= "交班单号：".$handoverOrder->id."<BR>";
        $content .= "开班时间：".$handoverOrder->startTime."<BR>";
        $content .= "交班时间：".$handoverOrder->endTime."<BR>";
        $content .= "交班人：".$handoverOrder->admin->username."<BR>";
        $content .= "--------------------------------<BR>";
//        $begin_date=time();
//        $end_date=strtotime($handoverOrder->endTime);
//        $days=round(($end_date-$begin_date)/3600/24);
//        if($days>1){
//            $content .= "<C>超".$days."天未交班</C>";
//            $content .= "已超过7天未交班，为保证交班准确性，本次只交近7个自然日的数（10/17 15:41-10/24 15:41）<BR>";
//        }
        $dataList=$handoverOrder->contents->dataList[0];
        $content .= "<C>交班汇总</C>";
        $content .= self::LR("本班次收款：",$handoverOrder->contents->money?:0.00,32)."<BR>";
        $content .= self::LR("微信收款：",$dataList->weixinMoney?:0.00,32)."<BR>";
        $content .= self::LR("支付宝收款：",$dataList->alipayMoney?:0.00,32)."<BR>";
        $content .= self::LR("现金收款：",$dataList->cashMoney?:0.00,32)."<BR>";
        $content .= self::LR("余额收款：",$dataList->balanceMoney?:0.00,32)."<BR>";
        $content .= "--------------------------------<BR>";
        $content .= "<C>收款统计</C>";
        $content .= "收款业务    收款笔数  收款金额<BR>";
        $content .= '营业收入        '.$handoverOrder->contents->orderCount.'笔      '.$handoverOrder->contents->money.'<BR>';
        if($handoverOrder->contents->sellOut){
            foreach ($handoverOrder->contents->sellOut as $v){
                $content .= $v->name.'        '.$v->orderCount.'笔       '.$v->money.'<BR>';
            }
        }else{
            $content .= '营业外收入      0笔       0.00<BR>';
        }
        $content .= "--------------------------------<BR>";
        $content .= "<C>收款构成</C>";
        if($dataList->weixinOrder>0){
            $content .= '微信收款        '.$dataList->weixinOrder.'笔       '.$dataList->weixinMoney.'<BR>';
        }
        if($dataList->alipayOrder>0){
            $content .= '支付宝收款      '.$dataList->alipayOrder.'笔       '.$dataList->alipayMoney.'<BR>';
        }
        if($dataList->cashOrder>0){
            $content .= '现金收款        '.$dataList->cashOrder.'笔       '.$dataList->cashMoney.'<BR>';
        }
        if($dataList->balanceOrder>0){
            $content .= '余额收款        '.$dataList->balanceOrder.'笔       '.$dataList->balanceMoney.'<BR>';
        }
        $content .= "--------------------------------<BR>";
        $content .= "<C>订单统计</C>";
        $content .= '已结账订单数                '.$handoverOrder->contents->orderCount.'单<BR>';
        $content .= '堂食收款        '.$dataList->instoreOrder.'笔       '.$dataList->instoreMoney.'<BR>';
        $content .= '快餐收款        '.$dataList->fastOrder.'笔       '.$dataList->fastMoney.'<BR>';
        $content .= "--------------------------------<BR>";
        $discountTrend=$handoverOrder->contents->discountTrend;

        if(!empty($handoverOrder->contents->warn)){
            $content .= "<C>敏感操作统计</C>";
            foreach ($handoverOrder->contents->warn as $v){
                $content .=self::LR($v->name,$v->count,32)."<BR>";
            }
            $content .= "--------------------------------<BR>";
        }

        $content .= "<C>优惠统计</C>";
        if($discountTrend){
            foreach ($discountTrend as $v){
                $content .= $v->name.'        '.$v->count.'笔       '.$v->money.'<BR>';
            }
            $content .= "--------------------------------<BR>";
        }
        $content .= "<C>门店未完结订单统计</C>";
        $content .= "项目            单数       金额";
        $unBill=$handoverOrder->contents->unBill;
        $content .= '未结账堂食订单   '.$unBill[0]->count.'单      '.$unBill[0]->money.'<BR>';
//        $content .= '堂食            '.$handoverOrder->contents->unclosedInstore->count.'单       '.$handoverOrder->contents->unclosedInstore->money.'<BR>';
//        $content .= '快餐            '.$handoverOrder->contents->unclosedFast->count.'单       '.$handoverOrder->contents->unclosedFast->money.'<BR>';
        $content .= "--------------------------------<BR><BR>";
        $content .= "交班人签字_________<BR><BR>";
        $content .= "监督人签字_________<BR>";
        $content .= "提示：1、交班单中不包含会员公众号充值、在管家后台完成的会员、挂账、订单、礼品卡业务端收、退款等业务数据<BR>";
        $content .= "      2、交班单中仅统计当前班次有效的自营外卖、平台外卖订单、如交班后存在取消订单的情况，核对时请以营业概览报表的数据为准<BR>";
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
        $content = '<B><C>'.$queueOrder->store->name.'</C></B><BR>';
        $content .= "--------------------------------<BR>";
        $content .= '<B><C>'.$tableType.'</C></B><BR>';
        $content .= '<B><C>'.$queueOrder->serialNum.'('.$queueOrder->people.'人)</C></B><BR>';
        $content .= '<C>前面还有'.$queueOrder->waitingTable.'桌</C><BR>';
        $content .= '<C>大约还需等待'.$queueOrder->waitingTime.'分钟</C><BR>';
        $content.='<QRCODE s=6 e=L l=center>'.$queueOrder->qrcode.'</QRCODE><BOLD>还等几桌？扫码查进度</BOLD><BR>';
        $content .= '微信扫一扫，查看排队进度<BR>';
        $content .= "--------------------------------<BR>";
        $content .= '欢迎光临<BR>';
        if($wifi){
            $content .= '免费WI-FI：'.trim($wifi->data['name']).'<BR>';
            $content .= 'WI-FI密码：'.trim($wifi->data['password']).'<BR>';
        }
        $content .= "--------------------------------<BR>";
        $content .= '取号时间：'. date('Y-m-d H:i:s',bcadd($time,$addTime)) . '<BR>';
        $content .= $queueOrder->note.'<BR>';
        return $content;
    }
    public static function drinkLog($drinkLog){
        $username=$drinkLog->admin->username?:$drinkLog->admin->nickname;
        if($drinkLog->typeFormat) {
            $content = '<C><B><BOLD>' . $drinkLog->typeFormat . '</BOLD></B></C><BR>';
            $content .= "--------------------------------<BR>";
        }
        $content .= $drinkLog->typeFormat.'商品：'.$drinkLog->drink->name.'<BR>';
        $content .= $drinkLog->typeFormat.'数量：'.$drinkLog->num.'<BR>';
        $content .= '剩余数量：'.$drinkLog->residue.'<BR>';
        $content .= $drinkLog->typeFormat.'时间：'. $drinkLog->created_at . '<BR>';
        $content .= '用户信息：'.$drinkLog->user->nickname.'<BR>';
        if($drinkLog->user->mobile){
            $content .= '          '.$drinkLog->user->mobile.'<BR>';
        }
        $content .= '所属门店：'.$drinkLog->store->name.'<BR>';
        $content .= '订单号：'.$drinkLog->orderSn.'<BR>';
        $content .= '操作员：'.$username.'<BR>';
        $content .= '操作时间：'.date('Y-m-d H:i:s',time()).'<BR>';
        if($drinkLog->note){
            $content .= $drinkLog->note.'<BR>';
        }
        return $content;
    }
}
