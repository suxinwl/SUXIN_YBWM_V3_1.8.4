<?php ?><?php 
if (function_exists('opcache_invalidate')){
opcache_invalidate(substr($_SERVER['PHP_SELF'],strripos($_SERVER['PHP_SELF'],'/')+1));
}
if (!function_exists('sg_load')) {$__msg = '未安装SG13php扩展运行插件';die($__msg);exit;}
//错误处理函数示例,函数名勿修改，内容可自行修改
function MLTools_ErrorHandler_46f6afe7e5bc0a970bcb5aa2629ca8ef($e,$m){

switch ($e) {
    case 01:
    echo json_encode(['code'=>400,'msg'=>'当前服务器IP未被官方授权,请联系官方客服:15307193890(微信同号)']);die;
    break;
    case 02:
    echo json_encode(['code'=>400,'msg'=>'当前域名未被官方授权,请联系官方客服:15307193890(微信同号)']);die;
    break;
    case 03:
    echo json_encode(['code'=>400,'msg'=>'授权文件未被授权在此服务器上运行:MAC,系统无法正常运行;请联系官方客服:15307193890(微信同号)']);die;
    break;
    case 04:
    echo json_encode(['code'=>400,'msg'=>'当前服务器ID未被官方授权,请联系官方客服:15307193890(微信同号)']);die;
    break;
    case 05:
    echo json_encode(['code'=>400,'msg'=>'此脚本未被授权在此服务器上运行:URL,系统无法正常运行;请联系官方客服:15307193890(微信同号)']);die;
    break;
    case 06:
    echo json_encode(['code'=>400,'msg'=>'授权文件无效,系统无法正常运行;请联系官方客服:15307193890(微信同号)']);die;
    break;
    case 07:
    echo json_encode(['code'=>400,'msg'=>'PHP版本需要大于或等于8.0,请确认你运行的PHP版本！']);die;
    break;
    case 12:
    echo json_encode(['code'=>400,'msg'=>'授权文件内容不匹配,系统无法正常运行;请联系官方客服:15307193890(微信同号)']);die;
    break;
    case 13:
    echo json_encode(['code'=>400,'msg'=>'授权文件获取失败,系统无法正常运行;请联系官方客服:15307193890(微信同号)']);die;
    break;
    case 17:
    echo json_encode(['code'=>400,'msg'=>'授权文件已被修改,系统无法正常运行;请联系官方客服:15307193890(微信同号)']);die;
    break;
    default:
    echo json_encode(['code'=>400,'msg'=>'授权文件已损坏,系统无法正常运行;请联系官方客服:15307193890(微信同号)']);die;
    break;
}

}
 ?><?php
return sg_load('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');
