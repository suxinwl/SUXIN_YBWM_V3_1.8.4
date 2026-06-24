<?php

header("Content-type: text/html; charset=utf-8");
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'AopClient.php';

$privateKey = "MIICWwIBAAKBgQCOmsrFtFPTnEzfpJ/hDl5RODBxw4i9Ex3NmmG/N7A1+by032zZ
ZgLLpdNh8y5otjFY0E37Nyr4FGKFRSSuDiTk8vfx3pv6ImS1Rxjjg4qdVHIfqhCe
B0Z2ZPuBD3Gbj8hHFEtXZq8+msAFu/5ZQjiVhgs5WWBjh54LYWSum+d9+wIDAQAB
AoGABKpLt+wOUf3rBeX81QADVeTgsNgUwRq+/truF7NasGlbIw+sH/CWZtMl9y5d
dk9/jchCxNUpMrR4BKQebxNHYaltARH7fmdEVhEccrMwG1tsv5ZNt6zdEzHn5sWx
lGEdgiRdceXBr9ApfrPEbSf9znKTZomaMrLUb1oTG8kgpQECQQDd3sHcyuYv0/5F
1xJjZKbUDtACNHjXY/b9LNjViA4+6u8hPDiu1OHD7/hO6r86tyKyQE8ERSVM2eMb
KgLy40zlAkEApIqPpoaGPqf1ZjXmLxjDc/UZzHr0KZYSobTI/bDyP3H4NBBIXaJL
bKBtRn55+rvnUalZfwxmtC7hxAZjgzvRXwJACCAAOXW0uEurJCOcKMem1F02gN4r
Drg6c0sRJcC1xNgVqcHq0Xw6SFKhZUJzflL2klCIlD9z/jx8dKI1OW/ZgQJAWfJt
CMltRX8nhVJdnM1au173VXK5WqpxUPi1DwnVw50X484q4RTBSoaAdhLZt5lUoK2F
ARDUbBdcz332lFz03QJAbMtVN2FC3NZW4CGaGuTfp5+tCGxLi5HFttgzPhhkvgXo
wkeHBhf+DT8OslSxIpBhsb2l+o4Pv4wKBdBg5WJlRw==";
		//随行付公钥
$sxfPublic =
    "MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCOmsrFtFPTnEzfpJ/hDl5RODBxw4i9Ex3NmmG/N7A1+by032zZZgLLpdNh8y5otjFY0E37Nyr4FGKFRSSuDiTk8vfx3pv6ImS1Rxjjg4qdVHIfqhCeB0Z2ZPuBD3Gbj8hHFEtXZq8+msAFu/5ZQjiVhgs5WWBjh54LYWSum+d9+wIDAQAB";


    $array = array( //普通数组
        "reqId" => "c3ba4932950a42618bc17a85bd2ec846",
        "orgId" => "26680846",
        "mecDisNm" => "签购单的的名称", //商户简称
        "mblNo" => "185xxxxxx54",//商户联系电话
        "operationalType" => "01",//经营类型（01线下 02线上 03非盈利类 04缴费类 05保险类 06私立院校类 ）
        "haveLicenseNo" => "03",//资质类型 ( 01自然人 02个体户 03企业)
        "mecTypeFlag" => "00",//商户类型（00普通单店商户（非连锁商户） 01连锁总 02连锁分 03 1+n总 04 1+n分
        "cprRegNmCn" => "澧县xxxxx责任公司",//营业执照注册名称
        "registCode" => "914xxxxxxRLX3",//营业执照注册号
        "licenseMatch" => "00",//是否三证合一（00是  01否）
        "cprRegAddr" => "西商xxxxxx无校验",//地址
        "regProvCd" => "130000000000",//省编码（国标）
        "regCityCd" => "130700000000",//市编码（国标）
        "regDistCd" => "130728000000",//区编码（国标）
        "mccCd" => "5309",//微信类目（和mcc传其一，如果都传，以mcc为准）
        "csTelNo" => "13xxxxxx222",//客服电话

        "identityName" => "郭xx",//法人姓名
        "identityTyp" => "00",//法人证件类型
        "identityNo" => "4324xxxxxxxxx3X",//法人证件号
        "actNm" => "澧县xxxxxxxxxx公司",//结算账户名
        "actTyp" => "00",//结算账户类型（00对公 01对私）
        "stmManIdNo" => "4324xxxxxxX",//账户人身份证号
        "actNo" => "622848xxxxxxxxx000",//结算卡号
        "lbnkNo" => "1031xxxxxx17",//开户支行联行行号xxxxxx

        "licensePic" => "f94aa57552f0434d80026bccb9820f59",//营业执照
        "legalPersonidPositivePic" => "7fbd5e6d0610422292af5f88c32e58b7",//法人身份证正面
        "legalPersonidOppositePic" => "89535a39b5d9450294d59dae776ccc32",//法人身份证反面
        "storePic" => "d704fe706ad040a99981b7ac37b7cb89",//门头照片
        "insideScenePic" => "4236c4f475da4e79b50f9186ac048a27",//真实商户内景图片
        "openingAccountLicensePic" => "697b9ea61f08404abf80de59fd17f594",//开户许可证

    );

        $qrcodeType = array("01", "02", "06", "07");
        $qrcoderate = array("0.2", "0.2", "0.2", "0.2");
        $qrcodeList = array();
        for ($i = 0; $i < count($qrcodeType); $i++) {
            $arr = array(

                "rateType" => $qrcodeType[$i],
                "rate" => $qrcoderate[$i],
            );
            array_push($qrcodeList, $arr);
        }
        $qrcodeList = array(
            "qrcodeList" => $qrcodeList
        );
        $array = array_merge($array, $qrcodeList);
        $response = getJsonParam($array);
        $aopClient = new AopClient();
        $signContent = $aopClient->generateSign($response,$privateKey);
        $sign = array(
            "sign" => $signContent
        );
        $reqStr = array_merge($response, $sign);
        $reqStr = json_encode($reqStr,320);
        $requestUrl = 'https://openapi-test.tianquetech.com/merchant/income';
        $resp = $aopClient->curl($requestUrl, $reqStr);
        echo "resp" . $resp;

        $result = json_decode($resp,320);

        $signResult = $result["sign"];
        echo "signResult" . $signResult;
        //  result.remove("sign");
        unset($result["sign"]);
        //  String resultStr = RSASignature.getOrderContent(result);

        //sign
        /// String resultSign = RSASignature.encryptBASE64(RSASignature.sign(signContent, privateKey));
        $signContent = $aopClient->getSignContent($result);
        echo "<br><br><br><br>";
        echo "signContent:".$signContent;

        echo "<br><br><br><br>";

        $verify = $aopClient->verify($signContent, $signResult,$sxfPublic);
        //组装加密串
        if ($verify) {
            echo("验签成功");
        }



        function getJsonParam($reqData)
        {
            $reqDataJson = array(
                "mblNo" => $reqData["mblNo"],//商户联系电话
                "mecDisNm" => $reqData["mecDisNm"],//商户简称
                "operationalType" => $reqData["operationalType"],//经营类型（01线下 02线上 03非盈利类 04缴费类 05保险类 06私立院校类 ）
                //"attachMerces3hantNo"=>  $reqData["attachMerchantNo"],//挂靠平台商编
                "mecTypeFlag" => $reqData["mecTypeFlag"],//商户类型（新： 00普通单店商户（非连锁商户）01连锁总 02连锁分 03 1+n总 04 1+n分,旧：01线上平台入驻 02普通 03连锁总店 04连锁分店  05 1+n总  06 1+n分）
                "haveLicenseNo" => $reqData["haveLicenseNo"],//资质类型 ( 01自然人 02个体户 03企业)
                //"parentMno"=>  $reqData["parentMno"],//所属总店商户编号
                //"independentModel"=>  $reqData["independentModel"],//分店是否独立结算(00是 01否)
                "qrcodeList" => $reqData["qrcodeList"],//二维码费率（01微信 02支付宝）
                //"settleType"=>  $reqData["settleType"],//结算类型(新：03 T1 04 D1,旧：01-T1 02-D1)---默认值为D1
                //"supportPayChannels"=>  $reqData["supportPayChannels"],//支持的支付渠道(01微信 02支付宝 03银联)不填默认全开
                //"supportTradeTypes"=>  $reqData["supportTradeTypes"],//支持的交易类型(01主扫 02被扫 03公众号 04退货 05APP)不填默认全开
                //"specifyWechatChannel"=>  $reqData["specifyWechatChannel"],//指定微信渠道号
                //"onlineType"=>  $reqData["onlineType"],//线上普通商户类型 ( 01APP 02网站 03公众号)
                //"onlineName"=>  $reqData["onlineName"],//线上普通商户名称 (APP名称/网站网址/公众号名称)
                //"onlineTypeInfo"=>  $reqData["onlineTypeInfo"],//线上普通商户信息 (APP下载地址及账号信息)
                "cprRegNmCn" => $reqData["cprRegNmCn"],//营业执照注册名称
                "registCode" => $reqData["registCode"],//营业执照注册号
                "licenseMatch" => $reqData["licenseMatch"],//是否三证合一（00是  01否）
                //"orgCode"=>  $reqData["orgCode"],//组织机构代码
                //"taxRegNo"=>  $reqData["taxRegNo"],//税务登记号
                //"businessLicStt"=>  $reqData["businessLicStt"],//营业执照起始日
                //"businessLicEnt"=>  $reqData["businessLicEnt"],//营业执照到期日
                "cprRegAddr" => $reqData["cprRegAddr"],//地址
                "regProvCd" => $reqData["regProvCd"],//省编码（国标）
                "regCityCd" => $reqData["regCityCd"],//市编码（国标）
                "regDistCd" => $reqData["regDistCd"],//区编码（国标）
                "mccCd" => $reqData["mccCd"],//微信类目（和mcc传其一，如果都传，以mcc为准）
                "csTelNo" => $reqData["csTelNo"],//客服电话
                "identityName" => $reqData["identityName"],//法人姓名
                "identityTyp" => $reqData["identityTyp"],//法人证件类型
                "identityNo" => $reqData["identityNo"],//法人证件号
                //"legalPersonLicStt"=>  $reqData["legalPersonLicStt"],//法人身份证开始日期
                //"legalPersonLicEnt"=>  $reqData["legalPersonLicEnt"],//法人身份证结束日期
                "actNm" => $reqData["actNm"],//结算账户名
                "actTyp" => $reqData["actTyp"],//结算账户类型（00对公 01对私）
                "stmManIdNo" => $reqData["stmManIdNo"],//账户人身份证号
                //"accountLicStt"=>  $reqData["accountLicStt"],//账户人证件号起始日
                //"accountLicEnt"=>  $reqData["accountLicEnt"],//账户人证件号到期日
                "actNo" => $reqData["actNo"],//结算卡号
                "lbnkNo" => $reqData["lbnkNo"],//开户支行联行行号
                //"lbnkNm"=>  $reqData["lbnkNm"],//开户支行名称
                "licensePic" => $reqData["licensePic"],//营业执照
                //"taxRegistLicensePic"=>  $reqData["taxRegistLicensePic"],//税务登记证
                //"orgCodePic"=>  $reqData["orgCodePic"],//组织机构代码证
                "legalPersonidPositivePic" => $reqData["legalPersonidPositivePic"],//法人身份证正面
                "legalPersonidOppositePic" => $reqData["legalPersonidOppositePic"],//法人身份证反面
                "openingAccountLicensePic" => $reqData["openingAccountLicensePic"],//开户许可证
                //"bankCardPositivePic"=>  $reqData["bankCardPositivePic"],//银行卡正面
                //"bankCardOppositePic"=>  $reqData["bankCardOppositePic"],//银行卡反面
                //"settlePersonIdcardOpposite"=>  $reqData["settlePersonIdcardOpposite"],//结算人身份证反面
                //"settlePersonIdcardPositive"=>  $reqData["settlePersonIdcardPositive"],//结算人身份证正面
                //"merchantAgreementPic"=>  $reqData["merchantAgreementPic"],//商户协议照片
                "storePic" => $reqData["storePic"],//门头照片
                "insideScenePic" => $reqData["insideScenePic"],//真实商户内景图片
                //"businessPlacePic"=>  $reqData["businessPlacePic"],//经营场所-含收银台
                //"merchantEnterProtocol"=>  $reqData["merchantEnterProtocol"],//商家入驻协议
                //"icpLicence"=>  $reqData["icpLicence"],//ICP许可证
                //"handIdcardPic"=>  $reqData["handIdcardPic"],//手持身份证照片
                //"leaseAgreementThreePic"=>  $reqData["leaseAgreementThreePic"],//租赁协议三（签章页）
                //"leaseAgreementTwoPic"=>  $reqData["leaseAgreementTwoPic"],//租赁协议二（面积、有效期页）
                //"leaseAgreementOnePic"=>  $reqData["leaseAgreementOnePic"],//租赁协议一（封面）
                //"otherMaterialPictureOne"=>  $reqData["otherMaterialPictureOne"],//其他资料照片1
                //"otherMaterialPictureTwo"=>  $reqData["otherMaterialPictureTwo"],//其他资料照片2
                //"otherMaterialPictureThree"=>  $reqData["otherMaterialPictureThree"],//其他资料照片3
                //"otherMaterialPictureFour"=>  $reqData["otherMaterialPictureFour"],//其他资料照片4
                //"otherMaterialPictureFive"=>  $reqData["otherMaterialPictureFive"],//其他资料照片5
                //"agentPersonSignature"=>  $reqData["agentPersonSignature"],//代理人签名
                //"confirmPersonSignature"=>  $reqData["confirmPersonSignature"],//确认人签名
                //"letterOfAuthPic"=>  $reqData["letterOfAuthPic"],//非法人结算授权函
                //"unionSettleWithoutLicense"=>  $reqData["unionSettleWithoutLicense"],//统一结算无营业执照说明
                //"societyGroupLegPerPic"=>  $reqData["societyGroupLegPerPic"],//社会团体法人证书
                //"foundationLegPerRegPic"=>  $reqData["foundationLegPerRegPic"],//基金会法人登记证书
                //"schoolLicese"=>  $reqData["schoolLicese"],//办学许可证
                //"medicalInstitutionLicense"=>  $reqData["medicalInstitutionLicense"],//医疗机构办学许可证
                //"insuranceLicese"=>  $reqData["insuranceLicese"],//经营保险业务许可证
                //"insuranceLegPerGradePic"=>  $reqData["insuranceLegPerGradePic"],//保险业务法人等级证书
                //"privateEducationLicense"=>  $reqData["privateEducationLicense"],//民办教育许可证
                //"chargeProofPic"=>  $reqData["chargeProofPic"],//收费证明文件

                //"Add("societyGroupLegPerPic", $reqData["societyGroupLegPerPic"]),//其他资料照片1
                //"Add("foundationLegPerRegPic", $reqData["foundationLegPerRegPic"]),//其他资料照片2
                //"Add("schoolLicese", $reqData["schoolLicese"]),//其他资料照片3
                //"Add("medicalInstitutionLicense", $reqData["medicalInstitutionLicense"]),//其他资料照片4
                //"Add("insuranceLicese", $reqData["insuranceLicese"]),//其他资料照片5
                //"Add("insuranceLegPerGradePic", $reqData["insuranceLegPerGradePic"]),//其他资料照片5
                //"Add("privateEducationLicense", $reqData["privateEducationLicense"]),//其他资料照片5
                //"Add("chargeProofPic", $reqData["chargeProofPic"]),//其他资料照片5
            );


            $reqJson = array(
                "reqData" => $reqDataJson,
                "orgId" => $reqData["orgId"],
                "reqId" => $reqData["reqId"],
                "version" => "1.0",//OEM和代理商的要传2.0，服务商传1.0
                "signType" => "RSA",
                "timestamp" => 1583570048783,
            );


            return $reqJson;
        }

?>