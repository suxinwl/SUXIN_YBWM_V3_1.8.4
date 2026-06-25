<?php ?><?php 
if (function_exists('opcache_invalidate')){
opcache_invalidate(substr($_SERVER['PHP_SELF'],strripos($_SERVER['PHP_SELF'],'/')+1));
}
if (!function_exists('sg_load')) {$__msg = '未安装SG13php扩展运行插件';die($__msg);exit;}
//错误处理函数示例,函数名勿修改，内容可自行修改
function MLTools_ErrorHandler_ed94c9407a0aadc7cc6c623babbd1f74($e,$m){

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
