<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
class Voice extends BaseModel{
    use HasFactory;
    public static function getAccessToken($api_key,$secret_key){
        try {
            $curl = curl_init();
            $postData = array(
                'grant_type' => 'client_credentials',
                'client_id' =>$api_key,
                'client_secret' =>$secret_key
            );
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://aip.baidubce.com/oauth/2.0/token',
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_SSL_VERIFYPEER  => false,
                CURLOPT_SSL_VERIFYHOST  => false,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POSTFIELDS => http_build_query($postData)
            ));
            $response = curl_exec($curl);
            curl_close($curl);
            $rtn = json_decode($response);
            return $rtn->access_token;
        } catch (\Exception $e) {
            return true;
        }
    }
    /*tex  合成的文本内容，文本长度必须小于1024GBK字节
     *tok  开放平台获取到的开发者[access_token]获取 Access Token "access_token")
     *cuid 用户唯一标识，用来计算UV值。建议填写能区分用户的机器 MAC 地址或 IMEI 码，长度为60字符以内
     *ctp  客户端类型选择，web端填写固定值1
     *lan  固定值zh。语言选择,目前只有中英文混合模式，填写固定值zh
     *spd  语速，取值0-15，默认为5中语速
     *pit  音调，取值0-15，默认为5中语调
     *vol  音量，基础音库取值0-9，精品音库取值0-15，默认为5中音量（取值为0时为音量最小值，并非为无声）
     *per  度小宇=1，度小美=0，度逍遥（基础）=3，度丫丫=4
     *aue  3为mp3格式(默认)； 4为pcm-16k；5为pcm-8k；6为wav（内容同pcm-16k）; 注意aue=4或者6是语音识别要求的格式，但是音频内容不是语音识别要求的自然人发音，所以识别效果会受影响。
     * */
    public static function run($api_key,$secret_key,$txt,$cuid='ybv3',$spd=5,$pit=5,$vol=5,$per=0,$aue=3) {
        try {
            $access_token=self::getAccessToken($api_key,$secret_key);
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://tsn.baidu.com/text2audio",
                CURLOPT_TIMEOUT => 30,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER  => false,
                CURLOPT_SSL_VERIFYHOST  => false,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => 'tex='.$txt.'&tok='. $access_token .'&cuid='.$cuid.'&ctp=1&lan=zh&spd='.$spd.'&pit='.$pit.'&vol='.$vol.'&per='.$per.'&aue='.$aue,
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/x-www-form-urlencoded',
                    'Accept: */*'
                ),
            ));
            $response = curl_exec($curl);
            curl_close($curl);
            if(strpos($response,'err_detail')!== false){
                $response=json_decode($response,true);
                if($response['err_detail']){
                   echo json_encode(['code'=>400,'msg'=>$response['err_detail']]);die;
                }
            }else{
                switch ($aue){
                    case 3;
                        $famat='mp3';
                        break;
                    case 4;
                        $famat='pcm';
                        break;
                    case 6;
                        $famat='wav';
                        break;

                }
                $path = date('Ymdhis').rand(1000,9999).'.'.$famat;
                file_put_contents($path,$response);
                return getDomain().'/'.$path;
            }
        } catch (\Exception $e) {
            return true;
        }
    }
}
