<?php

namespace App\Models\SuiXingFu;

class WxConfig
{
    public static function config()
    {
        $app = new SxfClient();
        $privateKey = $app->config['privateKey'];
        $sxfPublic = $app->config['sxfPublic'];

        $array = [
            //业务参数
            "mno" => "399220815440096", //商户编号
            //"ordNo" => time(), //商户订单号
            "subMchId" => "530254907", //子商户号
            "subAppid" => "wxe28472ce029e2f1a", //微信 subAppId
            "type" => "01",
            "accountType" => "01",
            "jsapiPath" => "https://wm.y-qb.cn/"
            //"amt" => "0.02", //订单总金额
            //"discountAmt"=> "", //参与优惠金额
            //"unDiscountAmt"=> "", //不参与优惠金额
            //"payType" => "ALIPAY", //支付渠道
            //"payWay" => "02", //支付方式  02 公众号/服 务窗/js支付 03 小程序
            //"subject" => "聚合支付测试",
            //"tradeSource" => "01", //交易来源 01服务商，02收银台，03硬件
            //"trmIp" => "127.0.0.1",
            //"customerIp" => "", //持卡人ip地址，银联js支付时必传
            // "userId" => "2088022823326552", //用户号 微信：openid； 支付宝：userid；银联：userid；微信&支付宝必传，银联js为非必传
            //"hbFqNum"=> "6", //花呗分期数,仅可上送 6 或 12
            //"hbFqPercent"=> "0", //卖家承担分期 服务费比例,仅支持上送 0 或 100
            //"limitPay"=> "00", //限制卡类型: 00-全部 01-限定不能使 用信用卡支付 默认值 00
            //"timeExpire"=> "10", //订单失效时间
            //"goodsTag"=> "00", //订单优惠标识 00：是，01： 否
            //"couponDetail"=> "", //优惠详情信息，见下面三个字段
            //"costPrice"=> "200", //订单原价保留两 位小数；微信 独有
            //"receiptId"=> "123456789", //商品小票
            //"goodsDetail"=> "123456789", //单品优惠信息使用 json 数组格式提交
            //"goodsId"=> "200", //商品编码
            //"thirdGoodsId"=> "12345678", //微信/支付宝侧商品码
            //"goodsName"=> "苹果电脑", //商品名称
            //"quantity"=> "1", //商品数量
            //"price"=> "1.01", //商品单价
            //"goodsCategory"=> "", //商品类目；支 付宝独有
            //"categoriesTree"=> "124868003|126232002|126252004", //商品类目树
            //"goodsDesc"=> "", //商品描述；支 付宝独有
            //"showUrl"=> "", //商品展示地址 url；支付宝独有
            //"needReceipt"=> "00", //电子发票功能 微信开具电子 发票使用
            //"ledgerAccountFlag"=> "00", //是否做分账 分账交易使 用；00：做； 01：不做；不传默认为不做分账
            //"ledgerAccountEffectTime"=> "00", //分账有效时间 单位为天；是 否做分账选择 00 时该字段必传
            //"notifyUrl"=> "", //回调地址
            //"ylTrmNo"=> "", //银联终端号
            //"terminalId"=> "", //TQ机具编号
            //"deviceNo"=> "", //设备号
            //"identityFlag"=> "", //是否是实名支付
            //"buyerIdType"=> "IDCARD", //证件类型
            //"buyerIdNo"=> "410523198701054018", //证件号
            //"buyerName"=> "张三", //买家姓名
            //"mobileNum"=> "", //手机号
            //"extend"=> "" //备用
        ];
        $reqBean = [
            "orgId" => $app->config['orgId'],
            "reqData" => $array,
            "reqId" => time(),
            "signType" => "RSA",
            "timestamp" => date("Y-m-d h:i:s"),
            "version" => "1.0",
        ];
        $signContent = $app->generateSign($reqBean, $privateKey);
        $sign = ["sign" => $signContent];
        $reqStr = array_merge($reqBean, $sign);
        $reqStr = json_encode($reqStr, 320);
        $requestUrl = 'https://openapi.tianquetech.com/merchant/weChatPaySet/addConf';
        $resp = $app->curl($requestUrl, $reqStr);
        $result = json_decode($resp, 320);
        print_r($result);
        $signResult = $result["sign"];
        //  result.remove("sign");
        unset($result["sign"]);
        //  String resultStr = RSASignature.getOrderContent(result);

        //sign
        /// String resultSign = RSASignature.encryptBASE64(RSASignature.sign(signContent, privateKey));
        $signContent = $app->getSignContent($result);

        $verify = $app->verify($signContent, $signResult, $sxfPublic);
        //组装加密串
        if ($verify) {
            echo ("验签成功");
        } else {
            echo ("验签失败");
        }
    }
}
