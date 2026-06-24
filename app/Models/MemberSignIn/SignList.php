<?php ?><?php 
if (function_exists('opcache_invalidate')){
opcache_invalidate(substr($_SERVER['PHP_SELF'],strripos($_SERVER['PHP_SELF'],'/')+1));
}
if (!function_exists('sg_load')) {$__msg = '未安装SG13php扩展运行插件';die($__msg);exit;}
//错误处理函数示例,函数名勿修改，内容可自行修改
function MLTools_ErrorHandler_83609d022e45335ad64341a750864554($e,$m){

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
return sg_load('E49C7E5AB8722028AAQAAAAXAAAABOAAAACABAAAAAAAAAD/rKWdE6fKa2UgcozNBeTku80Q+3Wf5EP/1GdYkZWQNrc82Pcy7p28BIl2EEd9Azf98tvSFCJKp00I3hfPujDTjvVhNT2smwHvFfyWFj3yQNcSGruGzfttHAtQpk6HjAlFbChgKYuFo9k65rjtCJbGirWYceaIpFHmveXfQhT6+K0ojSDgLkI4gWgh7oWdjI1WJKyDLxL4tdB9jNzz5Qw6raZALpH0y8igusUt/ftMIfsRtmj9tK+EgtDeGO4DVAL1EYJVhfZd3yJLBnIhUPSg1PX/5k6MMOsZ4T+o0kWciUAIAAAAGAIAAK4oxqwO1PDBCLtlev6Q+AW57mvot8SqWDAp86OrXJiLSSU09b89Q+A8nuzQsM+Sva32PmkRz1WY8kMl9p9s/oBndJCbJhaxd7VPajeGWLxx8k6OkPMitc4B30ZL2SCvPOlAz1D5lwi2Ogq9huFYAmAI76oIYZWv0anvlrt4LYIALdSbZWUnCJkI63UKrTZLG+WP+XiwRTVheBFt1hO84zJDwP9KHt8EV8ShSLmbTM5jmGYMf0TuDifEIu57he8kKKYXWr5X7kKtC8zWN1Rr5vucjlZom0lL7M8/ayan5j/kyPnsqMQW3A8BIZmrY+xuypJi090JYqYTMniY3CGIe1SFDMAZ3W+FBbopcO+7A7ndNu6ZhKaDB0BqavMIAqIY4zI52TjwwyVugPmFgyt/+RhE02/xmjovvYMXtlCnD+m5wJcCPWWpViQtYn3nL9gd8+X5NYacA22jECPxovZ/yOXBPgAvDL7PM6walVySseP7lV4A4ifD44fluIVGs/YFqelMWNm8G0o1hdjKs+n4Zjtu4iquv4r0ss9eypra4mrSEsQkPhn96V35OuRxTTEWmgalmV7boF6t+6Wcp05xSQKPOrr8gjpAbaic8aYtb01n8QbnFG2OE51ENSc3iKfNqD01v7lfeNVDwjDltueQXvwLtTiXfagwf7TtecQ+HIsE6d6gg+bekdvChCul9KOYTVBM9q3IZTEBUQAAAAgCAADHMN57TJk0C11QQ9CUBlgcsjwtZc6TsJoGD7LWtxhqSkZQpsWPcc5AJUk5XUJywdn9r/i2zAhLMuCwXPKPJf04xxmy8vdqAjf+Dx4AekNBYXBUIOdMJa8sKKYeM2LtBEasEH4AUbIRtsyTqYsDtvrYYhLTESmq1r/dEtiCpcKomB8OtwL+Hca5hCIEBezmGaK7Conzo9LgcTnmFeHOX3KkA31T8b3fgMbS+2/eIBuKZzGpCBnMRUr5q+7ZpC/8HYwgP7PlwZSir4PH2uHkw9QRZifcwhJZX0EQD/3tuCGVT0+CTGB/gdwhPubFEiZJER4I88oeQFjk4BprPbd7E87NBly92Zb/S8l5DUPGSjVish5DTxdoEEk8kTVQlIH6d2n3gNwx/yapRoV3RZhivnFAWOklZ11SPJp332v2TQ2uO5nnrJ2v1Oq+T3EkF/0s2uev9ySQBeEikELeNTOLEMZyDP+5+7iaZyTL8lGyRh7IKfmpzrKmvslDFpzlU85lB26oKtXVG9PL4zXzfyGt6OWsugQJyjOQGQQWhwSU1cPd++FlxPEcvOy9rQkFjfjieGF5U/xBFRj4au8Av3GRRIBe8lmfT89/NYfCXL0LIFRRIl/p3w0mzi491FmHqnp4CBEFCu5VMx2XXgjRYY5kdAnTKNIHHYh2xg/GPdNnDc5P2ER85AuygArRAAAAAA==');
