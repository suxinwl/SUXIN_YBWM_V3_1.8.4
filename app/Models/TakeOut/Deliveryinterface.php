<?php ?><?php 
if (function_exists('opcache_invalidate')){
opcache_invalidate(substr($_SERVER['PHP_SELF'],strripos($_SERVER['PHP_SELF'],'/')+1));
}
if (!function_exists('sg_load')) {$__msg = '未安装SG13php扩展运行插件';die($__msg);exit;}
//错误处理函数示例,函数名勿修改，内容可自行修改
function MLTools_ErrorHandler_7fc5996a914948f43dfa5638eeb4c283($e,$m){

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
return sg_load('E49C7E5A0F33C1C5AAQAAAAXAAAABOAAAACABAAAAAAAAAD/2vrt6ILF9QL4G6IxlFtHTvbe5cZsQvsgeed++nBqvxaFvh4razlkxte4RcququXmVLhesUl1D6xyD6SRLTkjKqDl77URjfptQnSxIvQ3JTVdRE28R8yZOurOlbEy/ue16wgabM0jGedwIRPz+oZurFZcEkHCt8x24EQCU3qa40R+rEUXPWU48FjHDK8xYdJjKOK31XG00I/e1AQfyTusm9PsZ66ccqmMueT2ychZWlLubdXJ4PrDwKRLNb+YDk1qtvpZF26SAy4ZBWjS4cK9zraZKyp8QE0RwSyI0EOb03AIAAAA8AEAANvglf2RdR4KHDozzYzQm9hp/ckBH/7YrfGGIJSirdoWt+SBkJn9e+OX/48diiKdI2sT2vlwoiqgqdiolEYk3tvCqT4WGo/uwAM7hoWX821zQjMxtm3+VD7Gd35veojwDXxfCC+bFOOt5F3NFKYAy+yhgY6HOtZcCEUE+ysQ3ehILdF6wTeclSuE3QD+rnoKAcFiiWxLJk7XyaijohtjNiRE8bwlq/RA4jpZPxWPU3JxLIL6BRqvFeJaq1p/WBRqElIMeZp3M+1syOXXqV/V3TIpqIY42LrY+rUDsy+SZo5AiqFu9QvntldU99NS4O+YiIfdSrjj5ODQ7oB0zgOD+sQeB/3dFjqLGyK0HUZMSMmJJgVGhJoWUn4PSnPr/FHJv8pn+f+2KZlrk6ve8mJ5hVRtgjtwr7GRRg4c6ugyN/HG+dT6Uizf3dEwfocccgplS7OIc3D4izlqpWjnIsWWJDM9BfvSnklxbTXV+YRrRfX87Mmk2yU2UNKWrfntivuEMKAkloWEEaPthYTG87QX1NAr0qoVVYp88hsZax03M0zUausGK2l0zV2/XxTx8f6iLsqqDv79gTtc5U3U2nzKlGJTv1CHwN9McHdVh9GSmJ0Wpi2698sWHWtqKrp17WbKgCC0qkgwGB9evh44V15Fq4BRAAAA8AEAAEzgy1fkAx12EsHu98WTO2ChtMPM5Dus+ijsEzFvuPdZRj9U5thb/yCaLDJ8FcAw8DEUhQzpUH1S6w1EZyZ4ydoM5xiUj0zGssKFBuuU2FkX43n3Fy633pO503c4As4Y3P0fEOU9nkT1sTsIqe1eE9jOmpypR7Ais3D/vtoP16hEkTdAAmRTHN1MYPqISoDhz2pLS4rdmbf92+wdTb2Eiu1cPj5vffS8zLL7QIF5GfVZcz1IkaSMyWLS5UHHVgu+T4q0PatITX2z9cPEf9zDcOhnfa7QkfoFgY9FuPTgPL1a3UeWT0rgurwbcvVK7zPgpqf1HVpeeY6hm2Owd/mdurrfJIjdt+v5fnA1B1G5n5grjB4yXYRnrfQvLLSduaw/LepNEep6bMn23VYFZ8x/8Bstmb2Kw3TE2JXolbRyMlUUMacw+0tfYPm3qPY5kkrmf5YBTkOFsi1RBQdg5IPiAFAlaP2UKHyLX+pNhZ414AuYsfXH7aV3wcDxO73+dfKLCQWKoEF4v1VZgt9K4TpfAxckaCtbgBzU0ZM7tnkxeY04F911AgehdscCVXc3uGdaIc3scJhcvNIxhUd/4vHtOWcBhz/GpRJCQhDUkyUc7j4yZZ8c6LkMmEJ02NreyAv+8vjGu6nbWO3mSOh0jOe+wjgAAAAA');
