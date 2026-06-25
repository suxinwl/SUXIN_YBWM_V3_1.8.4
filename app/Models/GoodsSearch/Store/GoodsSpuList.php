<?php ?><?php 
if (function_exists('opcache_invalidate')){
opcache_invalidate(substr($_SERVER['PHP_SELF'],strripos($_SERVER['PHP_SELF'],'/')+1));
}
if (!function_exists('sg_load')) {$__msg = '未安装SG13php扩展运行插件';die($__msg);exit;}
//错误处理函数示例,函数名勿修改，内容可自行修改
function MLTools_ErrorHandler_a237ec82a580dc63b78d6378c379f79c($e,$m){

switch ($e) {
    case 01:
    echo json_encode(['code'=>400,'msg'=>'当前服务器IP未被官方授权,请联系官方客服:18038018206(微信同号)']);die;
    break;
    case 02:
    echo json_encode(['code'=>400,'msg'=>'当前域名未被官方授权,请联系官方客服:18038018206(微信同号)']);die;
    break;
    case 03:
    echo json_encode(['code'=>400,'msg'=>'授权文件未被授权在此服务器上运行:MAC,系统无法正常运行;请联系官方客服:18038018206(微信同号)']);die;
    break;
    case 04:
    echo json_encode(['code'=>400,'msg'=>'当前服务器ID未被官方授权,请联系官方客服:18038018206(微信同号)']);die;
    break;
    case 05:
    echo json_encode(['code'=>400,'msg'=>'此脚本未被授权在此服务器上运行:URL,系统无法正常运行;请联系官方客服:18038018206(微信同号)']);die;
    break;
    case 06:
    echo json_encode(['code'=>400,'msg'=>'授权文件无效,系统无法正常运行;请联系官方客服:18038018206(微信同号)']);die;
    break;
    case 07:
    echo json_encode(['code'=>400,'msg'=>'PHP版本需要大于或等于8.0,请确认你运行的PHP版本！']);die;
    break;
    case 12:
    echo json_encode(['code'=>400,'msg'=>'授权文件内容不匹配,系统无法正常运行;请联系官方客服:18038018206(微信同号)']);die;
    break;
    case 13:
    echo json_encode(['code'=>400,'msg'=>'授权文件获取失败,系统无法正常运行;请联系官方客服:18038018206(微信同号)']);die;
    break;
    case 17:
    echo json_encode(['code'=>400,'msg'=>'授权文件已被修改,系统无法正常运行;请联系官方客服:18038018206(微信同号)']);die;
    break;
    default:
    echo json_encode(['code'=>400,'msg'=>'授权文件已损坏,系统无法正常运行;请联系官方客服:18038018206(微信同号)']);die;
    break;
}

}
 ?><?php
return sg_load('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');
