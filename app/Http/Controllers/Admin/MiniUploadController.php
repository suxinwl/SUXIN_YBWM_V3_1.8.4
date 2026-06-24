<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Config;
use App\Models\OpenWecahtExtJson;
use App\Models\openWechat;
use App\Models\OpenWecahtVersion;
use App\Services\ConfigService;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Storage;

class MiniUploadController extends ApiController
{

    // public function upload()
    // {
    //     $config = ConfigService::getSystemSet('openWechat');
    //     if (empty($config->kfMiniID) || empty($config->kfMiniAppSecret)) {
    //         return $this->failed('请配置微信开放平台开发小程序ID和开发小程序上传秘钥');
    //     }
    //     if (Request()->type == 'preview') {
    //         $type = "preview";
    //     } else {
    //         $type = "upload";
    //     }
    //     $res = Http::get("", ["miniType" => "ybwm_open"]);
    //     $res->throw();
    //     if ($res['code'] == 200) {
    //         $version = $res['data'];
    //     } else {
    //         return $this->failed($res['msg']);
    //     }
    //     $data = [
    //         'host' => Request()->getSchemeAndHttpHost(),
    //         'type' => $type,
    //         'appId' => $config->kfMiniID,
    //         'uniacid' => 'a',
    //         'version' => $version['version'],
    //         'desc' => $version['desc'] ?: '',
    //         'privKey' => $config->kfMiniAppSecret,
    //         'plugin' => Request()->plugin ?: [],
    //         'miniType' => 'ybwm_open',
    //     ];
    //     $data = json_encode($data, 320);
    //     $curl = curl_init();
    //     curl_setopt($curl, CURLOPT_URL, '');
    //     curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    //     curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
    //     curl_setopt(
    //         $curl,
    //         CURLOPT_HTTPHEADER,
    //         array(
    //             'Content-Type: application/json',
    //             'Content-Length: ' . strlen($data)
    //         )
    //     );
    //     if (!empty($data)) {
    //         curl_setopt($curl, CURLOPT_POST, 1);
    //         curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    //     }
    //     curl_setopt($curl, CURLOPT_HEADER, 0); //返回response头部信息
    //     curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    //     //执行
    //     $output = curl_exec($curl);
    //     curl_close($curl);
    //     $output = json_decode($output, true);
    //     if ($output['code'] == 200) {
    //         $model = OpenWecahtExtJson::where('version', $output['data']['version'])->first();
    //         if (!$model) {
    //             $model = new OpenWecahtExtJson();
    //             $model->version = $output['data']['version'];
    //         }
    //         $model->extJson = $output['data']['extJson'];
    //         $model->save();
    //         return $this->success(null);
    //     }
    //     return $this->failed($output['msg']);
    // }


    public function upload()
    {
        $config = ConfigService::getSystemSet('openWechat');
        if (empty($config->kfMiniID) || empty($config->kfMiniAppSecret)) {
            return $this->failed('请配置微信开放平台开发小程序ID和开发小程序上传秘钥');
        }
        $json = Storage::disk('local')->get('weixinOpen/ybwm_open/ext.json');
        $extJson = json_decode($json, true);
        try{
            $model = OpenWecahtExtJson::where('version', $extJson['ext']['version'])->first();
            if (!$model) {
                $model = new OpenWecahtExtJson();
                $model->version = $extJson['ext']['version'];
            }
            $model->extJson = $extJson;
            $model->save();
        } catch (\Exception $e) {
            echo  $e->getMessage();die;

        }
        $data = [
            'type' => "upload",
            'appId' => $config->kfMiniID,
            'version' => $extJson['ext']['version'],
            'desc' => $extJson['ext']['version_desc'],
            'projectPath' => storage_path('app/weixinOpen/ybwm_open'),
            'privateKeyPath' => storage_path('app/weixinOpen/updata.key'),
            'qrcodeOutputDest' => storage_path('app/weixinOpen/qrcode.jpg'),
        ];
        Storage::disk('local')->put('weixinOpen/updata.key', $config->kfMiniAppSecret);
        $jsonSet = Storage::disk('local')->put('weixinOpen/package.json', json_encode($data, 320));
        $shell = "node " . storage_path('app/weixinOpen/') . 'upload.js';
        exec($shell . " 2>&1", $res, $state);
        $str = end($res);
        $arr = explode(" Error: ", $str);
        if (count($arr) > 1) {
            return $this->failed($arr[1]);
        }

        return $this->success([], '小程序上传成功');
    }

    public function version()
    {
        $json = Storage::disk('local')->get('weixinOpen/ybwm_open/ext.json');
        $extJson = json_decode($json, true);
        $data['config'] = ConfigService::getSystemSet('openWechat');
        $data['newVersion']['version'] = $extJson['ext']['version'];
        $data['newVersion']['desc'] = $extJson['ext']['desc'];
        $data['online'] = OpenWecahtVersion::select(['version', 'desc', 'created_at', 'release_time'])->first();
        if ($data['newVersion']['version'] != $data['online']['version']) {
            $data['isUpload'] = true;
        } else {
            $data['isUpload'] = false;
        }
        return $this->success($data);
    }

    public function merchant()
    {
        $config = ConfigService::getSystemSet('merchantMini');
        if (empty($config->appId) || empty($config->upload_key)) {
            return $this->failed('请配置店铺助手appid和开发小程序上传秘钥');
        }
        $host = Request()->getSchemeAndHttpHost();
        $configJsonPath = Storage::disk('local')->get('merchant/ybv3_merchant/project.config.json');
        $projectConfig = json_decode(file_get_contents($configJsonPath), true);
        $projectConfig['appid'] = $config->appId;
        $projectConfig['condition'] =  (object)[];
        Storage::disk('local')->get('merchant/ybv3_merchant/project.config.json', json_encode($projectConfig, 320));
        $json = Storage::disk('local')->get('merchant/version.json');
        $json = json_decode($json, true);
        $data = [
            'type' => Request()->type ?? 'upload',
            'appId' => $config->appId,
            'version' => $json['version'],
            'desc' => $json['version_desc'],
            'projectPath' => storage_path('app/merchant/ybv3_merchant'),
            'privateKeyPath' => storage_path('app/merchant/updata.key'),
            'qrcodeOutputDest' => storage_path('app/merchant/qrcode.jpg'),
        ];
        $setinfoContent = <<<EOD
        var site= {
            siteroot: "{$host}",
            version:'{$json['version']}',
        }
        module.exports = site
EOD;
        Storage::disk('local')->put('merchant/ybv3_merchant/siteroot.js', $setinfoContent);
        Storage::disk('local')->put('merchant/updata.key', $config->upload_key);
        $jsonSet = Storage::disk('local')->put('merchant/package.json', json_encode($data, 320));
        $shell = "node " . storage_path('app/merchant/') . 'upload.js';
        exec($shell . " 2>&1", $res, $state);
        Storage::disk('local')->delete('merchant/ybv3_merchant/siteroot.js');
        $str = end($res);
        $arr = explode(" Error: ", $str);
        if (count($arr) > 1) {
            return $this->failed($str);
        }
        if(Request()->type == 'preview'){
            $url = 'data:image/jpg;base64,' . base64_encode(Storage::disk('local')->get('merchant/qrcode.jpg'));
        }
        return $this->success($url, '小程序上传成功');
    }
}
