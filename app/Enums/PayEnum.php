<?php

namespace App\Enums;

class PayEnum
{
    //支付方式
    const BALANCE = 0; //余额支付
    const WECHAT = 1; //微信支付
    const ALIPAY = 2; //支付宝支付
    const TOPAY = 4; //货到付款
    const PAID = 5; //好友代付
    const RMB = 6; //现金支付
    const MICROPAY = 7; //付款吗支付
    const POINTS = 8; //积分支付
    const CHANGE_RMB = 9; // 找零
    const COSTOM_PAY = 100; // 自有支付

    //支付渠道
    const WECHAT_JSAPI = 11; //微信支付-jsapi
    const WECHAT_NATIVE = 12; //微信支付-扫码支付
    const WECHAT_APP = 13; //微信支付-APP支付
    const WECHAT_MICROPAY = 14; //微信支付-付款码支付
    const WECHAT_MWEB = 15; //微信支付-H5支付
    const WECHAT_FACEPAY = 16; //微信支付-刷脸支付
    const WECHAT_FB_JSAPI = 17; //付呗-微信支付
    const WECHAT_SXF_JSAPI = 18; //随行付-微信支付
    const WECHAT_HLM_JSAPI = 19; //汇来米-微信支付
    const WECHAT_LKL_JSAPI = 20; //拉卡拉-微信支付
    const WECHAT_YDF_JSAPI = 21; //一点付-微信支付

    const ALIPAY_PAY = 26; //支付宝支付
    const ALIPAY_FB_JSAPI = 27; //（付呗)支付宝支付
    const ALIPAY_SXF_JSAPI = 28; //（随行付)支付宝支付
    const ALIPAY_HLM_JSAPI = 29; //（汇来米)支付宝支付
    const ALIPAY_LKL_JSAPI = 30; //拉卡拉支付宝支付


    public static function format($pay)
    {
        $data = [
            self::WECHAT => "微信",
            self::ALIPAY => "支付宝",
            self::BALANCE => "余额支付",
            self::TOPAY => "货到付款",
            self::PAID => "好友代付",
            self::RMB => "现金支付",
            // self::WECHAT_JSAPI => "微信支付一jsapi支付",
            // self::WECHAT_NATIVE => "微信支付-扫码支付",
            // self::WECHAT_APP => "微信支付-APP支付",
            // self::WECHAT_MICROPAY => "微信支付-付款码支付",
            // self::WECHAT_MWEB => "微信支付-H5支付",
            // self::WECHAT_FACEPAY => "微信支付-刷脸支付"
            self::WECHAT_JSAPI => "微信",
            self::WECHAT_NATIVE => "微信",
            self::WECHAT_APP => "微信",
            self::WECHAT_MICROPAY => "微信",
            self::WECHAT_MWEB => "微信",
            self::WECHAT_FACEPAY => "微信",
            self::WECHAT_FB_JSAPI => '付呗-微信支付', //付呗-微信支付
            self::WECHAT_SXF_JSAPI => '随行付-微信支付', //随行付-微信支付
            self::WECHAT_HLM_JSAPI => '汇来米-微信支付', //汇来米-微信支付
            self::WECHAT_LKL_JSAPI => '拉卡拉-微信支付', //拉卡拉-微信支付
            self::WECHAT_YDF_JSAPI=> '一点付-微信支付',
            self::ALIPAY_FB_JSAPI => '支付宝', //付呗-微信支付
            self::ALIPAY_SXF_JSAPI => '支付宝', //随行付-微信支付

            self::ALIPAY_HLM_JSAPI => '支付宝', //汇来米-支付宝支付
            self::ALIPAY_LKL_JSAPI => '支付宝', //拉卡拉-支付宝支付
            self::ALIPAY_PAY => "支付宝",
            self::POINTS => "积分",
            self::CHANGE_RMB => "找零",
            self::COSTOM_PAY => "自有支付",
        ];
        return $data[$pay];
    }

    public static function wexinPayChannel($type)
    {
        $data = [
            'JSAPI' =>  self::WECHAT_JSAPI,
            'NATIVE' => self::WECHAT_NATIVE,
            'APP' => self::WECHAT_APP,
            'MICROPAY' => self::WECHAT_MICROPAY,
            "MWEB" => self::WECHAT_MWEB,
            "FACEPAY" => self::WECHAT_FACEPAY
        ];
        return $data[$type];
    }
    public static function aliPayChannel($type)
    {
        $data = [
            'COUPON' =>  self::ALIPAY_PAY,
            'ALIPAYACCOUNT' => self::ALIPAY_PAY,
            'POINT' => self::ALIPAY_PAY,
            'DISCOUNT' => self::ALIPAY_PAY,
            "PCARD" => self::ALIPAY_PAY,
            "MCARD" => self::ALIPAY_PAY,
            'MDISCOUNT' =>  self::ALIPAY_PAY,
            'MCOUPON' => self::ALIPAY_PAY,
            'PCREDIT' => self::ALIPAY_PAY,
            'BANKCARD' => self::ALIPAY_PAY,
            "MONEYFUND" => self::ALIPAY_PAY,
            "VOUCHER" => self::ALIPAY_PAY
        ];
        return $data[$type];
    }

    public static function fubeiPayChannel($type)
    {
        $data = [
            'wxpay' =>  self::WECHAT_FB_JSAPI,
            'alipay' => self::ALIPAY_FB_JSAPI,
        ];
        return $data[$type];
    }

    public static function sxfPayChannel($type)
    {
        $data = [
            'WECHAT' =>  self::WECHAT_SXF_JSAPI,
            'ALIPAY' => self::ALIPAY_SXF_JSAPI,
        ];
        return $data[$type];
    }
    /**
     * 汇来米枚举文本
     * @param $type
     * @return int
     */
    public static function hlmPayChannel($type)
    {
        $data = [
            'W0'        =>  self::WECHAT_HLM_JSAPI,
            'A0'        => self::ALIPAY_HLM_JSAPI,
            'T_MINIAPP' => self::WECHAT_HLM_JSAPI,
            'A2'        => self::ALIPAY_HLM_JSAPI,
        ];
        return $data[$type];
    }

    /**
     * 拉卡拉枚举文本
     * @param $type
     * @return int
     */
    public static function lklPayChannel($type)
    {
        $data = [
            'WECHAT'        =>  self::WECHAT_LKL_JSAPI,
            'ALIPAY'        => self::ALIPAY_LKL_JSAPI,
        ];
        return $data[$type];
    }
}
