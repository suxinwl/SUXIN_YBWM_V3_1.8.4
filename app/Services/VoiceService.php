<?php

namespace App\Services;

use App\Models\Voice;
use Hhxsv5\LaravelS\Swoole\WebSocketHandlerInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Swoole\Http\Request;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use App\Models\ChannelConfig;
use function Ramsey\Uuid\v1;

class VoiceService
{


    public static function text2audio($txt, $uniacid)
    {
        try {
            $res = ChannelConfig::where('ident', 'voice')->where('uniacid', $uniacid)->first();
            if (!$res) {
                throw new BadRequestException('请先配置语音设置');
            }
            $data = $res->data;
            $api_key = $data->api_key;
            $secret_key = $data->secret_key;
            $spd = $data->spd ?: 5;
            $pit = $data->pit ?: 5;
            $vol = $data->vol ?: 10;
            $per = $data->per ?: 0;
            $aue = $data->aue ?: 3;
            $cuid = 'ybv3';

            $access_token = Voice::getAccessToken($api_key, $secret_key);
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://tsn.baidu.com/text2audio",
                CURLOPT_TIMEOUT => 30,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => 'tex=' . $txt . '&tok=' . $access_token . '&cuid=' . $cuid . '&ctp=1&lan=zh&spd=' . $spd . '&pit=' . $pit . '&vol=' . $vol . '&per=' . $per . '&aue=' . $aue,
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/x-www-form-urlencoded',
                    'Accept: */*'
                ),
            ));
            $response = curl_exec($curl);
            curl_close($curl);
            if (strpos($response, 'err_detail') !== false) {
                $response = json_decode($response, true);
                if ($response['err_detail']) {
                    echo json_encode(['code' => 400, 'msg' => $response['err_detail']]);
                    die;
                }
            } else {
                switch ($aue) {
                    case 3;
                        $famat = 'mp3';
                        break;
                    case 4;
                        $famat = 'pcm';
                        break;
                    case 6;
                        $famat = 'wav';
                        break;

                }
                $name = md5($txt) . "." . $famat;
                // Storage::put("/public/{$uniacid}/voice/".$name);
                Storage::disk('index')->put("/storage/{$uniacid}/voice/{$name}", $response);
                return env("APP_URL") . Storage::disk('index')->url("/{$uniacid}/voice/$name");
            }


            $aue = 'mp3';
            $config = ConfigService::getChannelConfig('voice', $uniacid);
            if (empty($config)) {
                throw new BadRequestException('请先填写基础配置');
            }

            $key = "baiduToken:" . $uniacid;
            if (!Cache::has($key)) {
                $res = Http::asForm()->post("https://aip.baidubce.com/oauth/2.0/token", [
                    'grant_type' => 'client_credentials',
                    'client_id' => $config['api_key'],
                    'client_secret' => $config['secret_key']
                ])->throw()->json();
                if (isset($res['error_description'])) {
                    throw new BadRequestException($res['error_description']);
                }
                $access_token = $res['access_token'];
                Cache::set($key, $res['access_token'], $res['expires_in']);
            } else {
                $access_token = Cache::get($key);
            }
            $response = Http::asForm()->post("https://tsn.baidu.com/text2audio", [
                "tex" => $txt,
                'tok' => $access_token,
                'cuid' => "ybv3",
                'ctp' => 1,
                'lan' => "zh",
                'spd' => $config['spd'],
                'pit' => $config['pit'],
                'vol' => $config['vol'],
                'per' => $config['per'],
                'aue' => $aue
            ]);
            if ($response->header("Content-Type") == "application/json") {
                $response = $response->json();
                if ($response['err_detail']) {
                    throw new BadRequestException($response['err_detail']);
                }
            } else {
                $name = md5($txt) . "." . $aue;
                // Storage::put("/public/{$uniacid}/voice/".$name);
                Storage::disk('index')->put("/storage/{$uniacid}/voice/{$name}", $response->body());
                return env("APP_URL") . Storage::disk('index')->url("/{$uniacid}/voice/$name");
            }
        } catch (\Exception $e) {
            return true;
        }
    }
}
