<?php
//退款查询
header("Content-type: text/html; charset=utf-8");
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'AopClient.php';

$privateKey = "MIICdQIBADANBgkqhkiG9w0BAQEFAASCAl8wggJbAgEAAoGBAI6aysW0U9OcTN+kn+EOXlE4MHHDiL0THc2aYb83sDX5vLTfbNlmAsul02HzLmi2MVjQTfs3KvgUYoVFJK4OJOTy9/Hem/oiZLVHGOODip1Uch+qEJ4HRnZk+4EPcZuPyEcUS1dmrz6awAW7/llCOJWGCzlZYGOHngthZK6b5337AgMBAAECgYAEqku37A5R/esF5fzVAANV5OCw2BTBGr7+2u4Xs1qwaVsjD6wf8JZm0yX3Ll12T3+NyELE1SkytHgEpB5vE0dhqW0BEft+Z0RWERxyszAbW2y/lk23rN0TMefmxbGUYR2CJF1x5cGv0Cl+s8RtJ/3OcpNmiZoystRvWhMbySClAQJBAN3ewdzK5i/T/kXXEmNkptQO0AI0eNdj9v0s2NWIDj7q7yE8OK7U4cPv+E7qvzq3IrJATwRFJUzZ4xsqAvLjTOUCQQCkio+mhoY+p/VmNeYvGMNz9RnMevQplhKhtMj9sPI/cfg0EEhdoktsoG1Gfnn6u+dRqVl/DGa0LuHEBmODO9FfAkAIIAA5dbS4S6skI5wox6bUXTaA3isOuDpzSxElwLXE2BWpwerRfDpIUqFlQnN+UvaSUIiUP3P+PHx0ojU5b9mBAkBZ8m0IyW1FfyeFUl2czVq7XvdVcrlaqnFQ+LUPCdXDnRfjzirhFMFKhoB2Etm3mVSgrYUBENRsF1zPffaUXPTdAkBsy1U3YULc1lbgIZoa5N+nn60IbEuLkcW22DM+GGS+BejCR4cGF/4NPw6yVLEikGGxvaX6jg+/jAoF0GDlYmVH";
$sxfPublic =
    "MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCOmsrFtFPTnEzfpJ/hDl5RODBxw4i9Ex3NmmG/N7A1+by032zZZgLLpdNh8y5otjFY0E37Nyr4FGKFRSSuDiTk8vfx3pv6ImS1Rxjjg4qdVHIfqhCeB0Z2ZPuBD3Gbj8hHFEtXZq8+msAFu/5ZQjiVhgs5WWBjh54LYWSum+d9+wIDAQAB";
$aopClient = new AopClient();
	$array = [
            //业务参数
            "mno"=> "399190910000387", //商户编号
		    //下面两个至少传一个
		    "ordNo"=> "", //商户订单号
		    "uuid"=> "" //科技公司订单号
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
	$requestUrl = 'https://openapi-test.tianquetech.com/query/refundQuery';
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