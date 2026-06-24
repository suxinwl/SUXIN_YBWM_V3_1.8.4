<?php

namespace App\Models;

use App\Services\ConfigService;
use http\Env\Request;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BaiduAi extends BaseModel
{


    public static function getToken($uniacid){
        $config = ConfigService::getChannelConfig('baidu_ai', $uniacid);
        $key=$config['client_id']?:'B3RneoDggdUENabOBTE8rhPx';
        $secret=$config['client_secret']?:'zeLG2EEGSp7Z590SuwMlaFDt7RUIT3vH';
        $url='https://aip.baidubce.com/oauth/2.0/token?grant_type=client_credentials&client_id='.$key.'&client_secret='.$secret.'&';
        $data=file_get_contents($url);
        $access_token=json_decode($data,true)['access_token'];
        return $access_token;
    }

    public static function draw($uniacid,$text){
        $token=self::getToken($uniacid);
        $url='https://aip.baidubce.com/rpc/2.0/ernievilg/v1/txt2img?access_token='.$token;
        $arr=[
            'text'=>$text,
            'style'=>'写实风格',
            'resolution'=>'1024*1024',
            'num'=>1
        ];
        /*
         * 目前支持风格有：探索无限、古风、二次元、写实风格、浮世绘、low poly 、
         * 未来主义、像素风格、概念艺术、赛博朋克、洛丽塔风格、巴洛克风格、
         * 超现实主义、水彩画、蒸汽波艺术、油画、卡通画*/
        $data=httpRequest($url,$arr);
        if($data['error_msg']){
            echo json_encode(['code'=>400,'msg'=>$data['error_msg']]);die;
        }
        $taskId=$data['data']['taskId'];
        return $taskId;
    }

    public static function getImg($uniacid,$taskId){
        $token=self::getToken($uniacid);
        $url='https://aip.baidubce.com/rpc/2.0/ernievilg/v1/getImg?access_token='.$token;
        $arr=[
            'taskId'=>$taskId
        ];
        $data=httpRequest($url,$arr);
        if($data['error_msg']){
            echo json_encode(['code'=>400,'msg'=>$data['error_msg']]);die;
        }
        return $data;
    }

    public static function ernievilg(){
        $token=self::getToken();
        $url='https://aip.baidubce.com/rpc/2.0/ernievilg/v1/txt2imgv2?access_token='.$token;
        $arr=[
            'prompt'=>'西瓜',
            'width'=>1024,
            'height'=>1024,
        ];
        $data=httpRequest($url,$arr);

        if($data['error_msg']){
            echo json_encode(['code'=>400,'msg'=>$data['error_msg']]);die;
        }
        $taskId=$data['data']['taskId'];
        return $taskId;
    }

    public static function getImgV2($taskId){
        $token=self::getToken();
        $url='https://aip.baidubce.com/rpc/2.0/ernievilg/v1/getImgv2?access_token='.$token;
        $arr=[
            'task_id'=>$taskId
        ];
        $data=httpRequest($url,$arr);
        dd($data);die;
        if($data['error_msg']){
            echo json_encode(['code'=>400,'msg'=>$data['error_msg']]);die;
        }
        return $data;
    }
}
