<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
class Meituan extends BaseModel{
    /*测试指南
        https://developer.waimai.meituan.com/home/guide/7

        订单推送
        https://developer.waimai.meituan.com/home/doc/food/7
    */
    //三方门店ID    8436696    罗曼林冰淇淋&茶（勒泰店）
    //appname  罗曼林冰淇淋&茶
    public function mkSign($array,$appid='7059',$app_secret='759da70801b3f445ecf870e95'){
        ksort($array,2);
        $str=$appid;
        foreach ($array as $k=>$v){
            $str.='&'.$k=$v;
        }
        $str.=$app_secret;
        $sign=Md5($str);
        return $sign;
    }



}
