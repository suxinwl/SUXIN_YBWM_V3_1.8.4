<?php
//交易主扫（C扫B）
header("Content-type: text/html; charset=utf-8");
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'AopClient.php';

$privateKey = "MIICdQIBADANBgkqhkiG9w0BAQEFAASCAl8wggJbAgEAAoGBAI6aysW0U9OcTN+kn+EOXlE4MHHDiL0THc2aYb83sDX5vLTfbNlmAsul02HzLmi2MVjQTfs3KvgUYoVFJK4OJOTy9/Hem/oiZLVHGOODip1Uch+qEJ4HRnZk+4EPcZuPyEcUS1dmrz6awAW7/llCOJWGCzlZYGOHngthZK6b5337AgMBAAECgYAEqku37A5R/esF5fzVAANV5OCw2BTBGr7+2u4Xs1qwaVsjD6wf8JZm0yX3Ll12T3+NyELE1SkytHgEpB5vE0dhqW0BEft+Z0RWERxyszAbW2y/lk23rN0TMefmxbGUYR2CJF1x5cGv0Cl+s8RtJ/3OcpNmiZoystRvWhMbySClAQJBAN3ewdzK5i/T/kXXEmNkptQO0AI0eNdj9v0s2NWIDj7q7yE8OK7U4cPv+E7qvzq3IrJATwRFJUzZ4xsqAvLjTOUCQQCkio+mhoY+p/VmNeYvGMNz9RnMevQplhKhtMj9sPI/cfg0EEhdoktsoG1Gfnn6u+dRqVl/DGa0LuHEBmODO9FfAkAIIAA5dbS4S6skI5wox6bUXTaA3isOuDpzSxElwLXE2BWpwerRfDpIUqFlQnN+UvaSUIiUP3P+PHx0ojU5b9mBAkBZ8m0IyW1FfyeFUl2czVq7XvdVcrlaqnFQ+LUPCdXDnRfjzirhFMFKhoB2Etm3mVSgrYUBENRsF1zPffaUXPTdAkBsy1U3YULc1lbgIZoa5N+nn60IbEuLkcW22DM+GGS+BejCR4cGF/4NPw6yVLEikGGxvaX6jg+/jAoF0GDlYmVH";
$sxfPublic =
    "MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCOmsrFtFPTnEzfpJ/hDl5RODBxw4i9Ex3NmmG/N7A1+by032zZZgLLpdNh8y5otjFY0E37Nyr4FGKFRSSuDiTk8vfx3pv6ImS1Rxjjg4qdVHIfqhCeB0Z2ZPuBD3Gbj8hHFEtXZq8+msAFu/5ZQjiVhgs5WWBjh54LYWSum+d9+wIDAQAB";
$aopClient = new AopClient();
	$array = [
            //业务参数
            "mno"=> "399190910000387", //商户编号
		    "ordNo"=> time(), //商户订单号
		    //"subMechId"=> "", //子商户号
		    //"subAppid"=> "", //微信 subAppId
			"amt"=> "0.02", //订单总金额
			//"discountAmt"=> "", //参与优惠金额
			//"unDiscountAmt"=> "", //不参与优惠金额
			"payType"=> "WECHAT", //支付渠道
			"subject"=> "C扫B测试",
            "tradeSource"=> "01", //交易来源 01服务商，02收银台，03硬件
			"trmIp"=> "127.0.0.1",
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
		"orgId" =>"26680846",
		"reqData"=>$array,
		"reqId" => time(),
		"signType" => "RSA",
		"timestamp" => date("Y-m-d h:i:s"),
		"version" => "1.0",
	];


	$signContent = $aopClient->generateSign($reqBean,$privateKey);
	$sign =["sign" => $signContent];
	$reqStr = array_merge($reqBean, $sign);
	$reqStr = json_encode($reqStr,320);
	$requestUrl = 'https://openapi-test.tianquetech.com/order/activeScan';
	$resp = $aopClient->curl($requestUrl, $reqStr);
	$result = json_decode($resp,320);
	print_r($result);
	$signResult = $result["sign"];
	//  result.remove("sign");
	unset($result["sign"]);
	//  String resultStr = RSASignature.getOrderContent(result);

	//sign
	/// String resultSign = RSASignature.encryptBASE64(RSASignature.sign(signContent, privateKey));
	$signContent = $aopClient->getSignContent($result);

	$verify = $aopClient->verify($signContent, $signResult,$sxfPublic);
	//组装加密串
	if ($verify) {
		echo("验签成功");
	}else{
		echo("验签失败");
	}
?>