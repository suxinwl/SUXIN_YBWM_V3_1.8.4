<?php

namespace App\Http\Controllers\Admin;

use App\Models\File;
use App\Models\OpenWechat;
use App\Models\WechatList;
use Illuminate\Http\Request;
use App\Models\Sms;
use App\Services\ConfigService;
use App\Models\Storage;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File as FacadesFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage as FacadesStorage;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class SystemController extends ApiController
{
    //系统信息
    public function index(Request $request)
    {
        $data = getSysInfo();
        $data['domain_name'] = '速信';
        $data['domain_url'] = $request->getHost();
        $data['time_start'] = $data['time_start'] ?? date('Y-m-d H:i:s');
        $data['time_end'] = '2099-12-31 23:59:59';
        return $this->success($data, __('base.success'));
    }

    //检查更新
    public function checkForUpdates(Request $request)
    {
        $versionData = getVersionInfo();
        $versionData['authType'] = config('app.authType');
        $versionData['domain_url'] = getDomain();
        $versionData['diskName'] = $request->input('diskName') ?: 'online';
        $data = safeGetUpgradeInfo($versionData);
        $data['data']['diskName'] = getVersionInfo()['diskName'] ?: 'online';
        $data['data']['now_version'] = $versionData['version'] ?: "1.0.0";
        $data['data']['now_version_release'] = $versionData['version_release'] ?: "20220611";
        return $this->success($data);
    }
    //更新
    public function updateSystem(Request $request)
    {
        $info = getSysInfo();
        if ($info['status'] <> 1) {
            return $this->failed('您的站点已禁止更新，详情请咨询153-8718-6830');
        }
        if ($info['time_type'] == 2 && $info['time_end'] <= date('Y-m-d H:i:s', time())) {
            return $this->failed('您的站点已到期，系统无法正常运行;如需续费,请联系开发者：15307193890（微信同号）');
        }
        if ($request->isMethod('post')) {
            $diskName = $request->input('diskName') ?: 'online';
            $down_url = config('app.remoteUrl') . '/master/upgraded.zip';
            if ($diskName == 'development') {
                $down_url = config('app.remoteUrl') . '/development/upgraded.zip';
            }
            $version_arr = getVersionInfo();
            $version_arr['auth_type'] = config('app.authType') ?: 1;
            $version_arr['diskName'] = $diskName;
            $domain = 'https://' . Request()->server('HTTP_HOST');
            $version_arr['domain_url'] = preg_replace("(^https?://)", "", $domain);
            $result = safeGetUpgradeInfo($version_arr);
            $new_version = $result['data']['version'];
            if ($result['code'] == 200 && $result['msg'] == '版本升级信息已获取') {
                $save_dir = public_path() . '/upgraded';
                if (file_exists($save_dir)) {
                    removeDir($save_dir);
                }
                mkdir($save_dir);
                chmod($save_dir, 0755);
                $filename = 'upgrade.zip';
                //下载远程升級包
                getFile($down_url, $save_dir, $filename);
                //FacadesStorage::disk('index')->put($save_dir . '/' . $filename, Http::get($down_url)->getBody()->getContents()); //文件保存地址
                $outPath = base_path();
                if (file_exists($save_dir . '/' . $filename)) {
                    $miniPath = storage_path('app/weixinOpen/ybwm_open');
                    $shopPath = storage_path('app/merchant/ybv3_merchant');
                    if (FacadesFile::isDirectory($miniPath)) {
                        FacadesFile::deleteDirectory($miniPath);
                    }
                    if (FacadesFile::isDirectory($shopPath)) {
                        FacadesFile::deleteDirectory($shopPath);
                    }
                    unzip($save_dir . '/' . $filename, $outPath, true, true);
                    unlink($save_dir . '/' . $filename);
                    //記錄版本升級日誌
                    if (!is_writable(public_path() . '/version.json')) {
                        chmod(public_path() . '/version.json', 0755);
                    }
                    $new_version_release = date('Y-m-d H:i:s', time());
                    $json = json_encode(['diskName' => $diskName, 'version' => $new_version, 'version_release' => $new_version_release, 'update_time' => date('Y-m-d H:i:s', time())]);
                    file_put_contents(public_path() . '/version.json', $json);
                    Artisan::call('migrate');
                    //传递更新成功信息
                    Artisan::call('queue:restart');
                    return $this->success([], '系统升级成功');
                }
            } else {
                return $this->failed($result['msg']);
            }
        }
    }

    public function sendSms(Request $request)
    {
        $storage = new Sms();
        $smsConfig = ConfigService::getSystemSet('sms');
        $code = randomAESKey();
        $smsType = $request->smsType ?: 1;
        $sms_type = $request->sms_type;
        if (empty($request->template_code)) {
            return $this->failed("请填写");
        }
        if ($smsType == 1) {
            $message = [
                "ali_endTime_template_code" => ['shopName' => '测试店铺'],
                "ali_forgot_template_code" => ['code' => $code],
                'ali_login_template_code' => ['code' => $code],
                "ali_register_template_code" => ['code' => $code],
                "ali_create_order" => ["goodsName" => "蜜桃乌龙茶"],
                "ali_refund" => ["goodsName" => "蜜桃乌龙茶"],
                'ali_verification' => ["goodsName" => "蜜桃乌龙茶"],
                'ali_balanceChange' => ['money' => '-0.01', 'balance' => '999'],
                'ali_delivery' => ['storeName' => '测试'],
                'ali_newOrder' => ['storeName' => '测试'],
                'ali_pay' =>  ['storeName' => '测试'],
                'ali_receive' =>  ['storeName' => '测试'],
                'ali_refundOrder' =>  ['storeName' => '测试'],
                'ali_refundApply' => ['goodsName' => '测试商品', 'state' => '审核中'],
                'ali_takeMeal' => ['storeName' => '测试', 'packNo' => 9999],
                'ali_vipChange' => ['vipLevel' => '王者'],
                'ali_integralChange' =>['integral'=>'100', 'account'=>'999'],
                'ali_expChange'=>['integral'=>'999','accountIntegral'=>'888'],
                'ali_openingReminder'=>['name'=>'小食候湘光谷店','time'=>'2025-03-01','address'=>'武汉市光谷广场店'],
                'ali_couponExpirationReminder'=>['name'=>'张三','shopname'=>'肥肥虾庄汉口店'],
            ];
            $message = $message[$sms_type];
            $bool = $storage->aliyunSendMessage($smsConfig, $request->phone, $request->template_code, $message, 0, true, $sms_type);
        }
        if ($smsType == 2) {
            $message = [
                "tx_endTime_template_code" => ['测试店铺'],
                "tx_forgot_template_code" => [$code],
                'tx_login_template_code' => [$code],
                "tx_register_template_code" => [$code],
                "tx_create_order" => ["蜜桃乌龙茶"],
                "tx_refund" => ["蜜桃乌龙茶"],
                "tx_refund_apply" => ["您好"],
                'tx_verification' => ["蜜桃乌龙茶"],
                'tx_balanceChange' => ['-0.01', '999'],
                'tx_delivery' => ['测试'],
                'tx_newOrder' => ['测试'],
                'tx_pay' =>  ['测试'],
                'tx_receive' =>  ['测试'],
                'tx_refundOrder' => ['测试'],
                'tx_refundApply' => ['测试商品', '退款中'],
                'tx_takeMeal' => ['测试', '999'],
                'tx_vipChange' => ['王者'],
                'tx_integralChange' => ['积分', '-100', '积分', '999']
            ];
            $message = $message[$sms_type];
            $bool = $storage->qcloudSendMessage($smsConfig, $request->phone, $request->template_code, $message);
        }
        if ($bool === true) {
            return $this->success([], '短信发送成功');
        }
        throw new BadRequestHttpException($bool);
    }

    //快速创建微信小程序
    public function createWechat(Request $request)
    {
        $uniacid = 0;
        $name = $request->input('name');
        $code = $request->input('code');
        $legal_persona_name = $request->input('legal_persona_name');
        $legal_persona_wechat = $request->input('legal_persona_wechat');
        $component_phone = $request->input('component_phone');
        $code_type = $request->input('code_type');
        $result = OpenWechat::createWechatApplet($name, $code, $legal_persona_name, $legal_persona_wechat, $component_phone, $code_type);
        $data = json_decode($result, true);
        if ($data['errcode'] == 0 && $data['errmsg'] == 'ok') {
            $wechatListModel = new WechatList();
            $wechatListModel->name = $name;
            $wechatListModel->code = $code;
            $wechatListModel->code_type = $code_type;
            $wechatListModel->legal_persona_name = $legal_persona_name;
            $wechatListModel->legal_persona_wechat = $legal_persona_wechat;
            $wechatListModel->component_phone = $component_phone;
            $wechatListModel->save();
            return $this->success([], __('成功'));
        } else {
            return $this->failed($data['errmsg'], '405');
        }
    }

    //获取注册小程序列表
    public function getWechatList()
    {
        $wechatListModel = new WechatList();
        $data = $wechatListModel->get();
        return $this->success($data, __('success'));
    }

    //图片上传
    public function uploadImage(Request $request)
    {
        $storageConfig = ConfigService::getSystemSet('storage');
        $pathName = config('app.appKey') . '/' . date('Y') . "/" . date('m') . "/" . date('d');
        $file = $request->file('file');
        $domain = $request->server('SERVER_PORT') == 443 ? 'https://' . $request->server('HTTP_HOST') : "http://" . $request->server('HTTP_HOST');
        $uniacid = 0;
        $module = 'uploads';
        $image = getimagesize($file->getRealPath());
        $width = $image[0];
        $height = $image[1];
        $attachmentSettings = ConfigService::getSystemSet('attachmentSettings');
        //        if (!$file->isValid()) {
        //            return $this->failed("上传文件不合法");
        //        }
        $fileName = $file->getClientOriginalName();
        $entension = $file->getClientOriginalExtension();
        if ($attachmentSettings) {
            if (!in_array($entension, $attachmentSettings->picType ?: [])) {
                return $this->failed("允许图片上传的格式为:", implode(',', $attachmentSettings->picType));
            }
            $filesize = $file->getSize();
            if (ceil($filesize / 1024) > $attachmentSettings->picSize) {
                return $this->failed("上传图片大小超出限制:" . $attachmentSettings->picSize . "Kb");
            }
        }
        switch ($storageConfig->type) {
            case 0;
                $path = Storage::channelUploadImage($file, $pathName, $module, $uniacid);
                $domain = 'https://' . Request()->server('HTTP_HOST');
                $data = $domain . $path;
                break;
            case 1;
                $path = Storage::qiniuUpload($file, $module, $uniacid, $storageConfig);
                $domain = $storageConfig->qn_url;
                $data = $domain . $path;
                break;
            case 2;
                $data = Storage::aliUpload($file, $module, $uniacid, $storageConfig);
                $path = '/' . $data['path'];
                $domain = $storageConfig->aliyuncs_url;
                $data = $data['url'];
                break;
            case 3;
                $path = Storage::txyUpload($file, $module, $uniacid, $storageConfig);
                $domain = $storageConfig->xplqcloud_url;
                $data =  $storageConfig->xplqcloud_url . $path;
                break;
            case 4;
                $fileExt = $file->getClientOriginalExtension();        //获取文件后缀名
                $realPath = $file->getRealPath();        //获取文件真实路径
                $filename = date('YmdHis') . uniqid() . '.' . $fileExt;        //按照一定格式取名
                $filepath = $storageConfig->ftpFile;        //个人要求的路径
                $bool = FacadesStorage::disk('ftp')->put($filepath . $filename, file_get_contents($realPath));
                $data = $storageConfig->ftpDomain . $filepath . $filename;        //文件的url地址
                break;
            default;
                $path = Storage::channelUploadImage($file, $pathName, $module, $uniacid);
                $domain = 'https://' . Request()->server('HTTP_HOST');
                $data = $domain . $path;
                break;
        }

        File::create([
            "width" => $width,
            "height" => $height,
            'shopId' => 0,
            "categoryId" => $request->categoryId ?? 0,
            "url" => $data,
            'domain' => $domain,
            "uniacid" => $uniacid ?? 0,
            "name" => $fileName,
            "channel" => $storageConfig->type ?: 0,
            "fileType" => $entension,
            "fileSize" => ceil($filesize / 1024),
            "path" => $path ?? ''
        ]);
        return $this->success($data, __('success'));
    }

    //图片上传
    public function uploadBase64(Request $request)
    {
        $file = $request->post('file');
        $ext =  $request->ext;
        if (empty($file) || empty($ext)) {
            throw new BadRequestHttpException("缺少参数");
        }
        $uniacid = $this->uniacid;
        $domain = $request->server('SERVER_PORT') == 443 ? 'https://' . $request->server('HTTP_HOST') : "http://" . $request->server('HTTP_HOST');
        $module = 0;
        $data = Storage::channelUploadBase64($file,  $module, $uniacid, $ext, $domain);
        return $this->success($data['url'], __('success'));
    }


    public function getBucket(Request $request)
    {
        $accessKeyId = $request->input('aliyuncs_accesskey') ?: "";
        $accessKeySecret = $request->input('aliyuncs_secret') ?: "";
        // Endpoint以杭州为例，其它Region请按实际情况填写。
        $endpoint = $request->input('aliyuncs_endpoint') ?: "";
        $data = Storage::getBucket($accessKeyId, $accessKeySecret, $endpoint);
        return $this->success($data, __('success'));
    }
    //获取系统更新公告列表
    public function getUpdateAnnouncementList(Request $request)
    {
        if ($request->isMethod('post')) {
            $data = array('pageNo' => $request->input('pageNo'), 'pageSize' => $request->input('pageSize'), 'type' => 2);
            //获取项目的应用列表
            $url = config('app.authorizeDomain') . '/cloud/notice/getnoticelist';
            $result = httpRequest($url, $data);
            return $this->success($result, __('success'));
        }
    }

    public function checkswoole()
    {
        $url = 'http://127.0.0.1:' . config('laravels.listen_port') . '/api/login';
        $data['swoole'] = true;
        try {
            $res = httpRequest($url);
            return $this->success([], '检测成功，Swoole已正常启用');
        } catch (\Exception $e) {
            return $this->failed('Swoole启动失败，请联系您的运维工程师处理');
        }
    }

    public function checkRedis()
    {
        try {
            Redis::get("testRedis");
            return $this->success([], '检测成功，Redis已正常启用');
        } catch (\Exception $e) {
            return $this->failed('Redis启动失败，请联系您的运维工程师处理');
        }
    }

    public function clearCache()
    {
        try {
            Artisan::call("cache:clear");
            return $this->success([], '缓存已清理');
        } catch (\Exception $e) {
            return $this->failed('缓存清理失败');
        }
    }


    public function clearQueue()
    {
        try {
            Artisan::call("queue:clear");
            return $this->success([], '队列任务已清除');
        } catch (\Exception $e) {
            return $this->failed('队列任务清理失败');
        }
    }

    public function queueRestart()
    {
        try {
            Artisan::call("queue:restart");
            return $this->success([], '任务队列已重启');
        } catch (\Exception $e) {
            return $this->failed('任务重启失败');
        }
    }


    public function swooleRestart()
    {
        try {
            Artisan::call("swooleKill");
            return $this->success([], 'swoole已重启');
        } catch (\Exception $e) {
            return $this->failed('swoole重启失败');
        }
    }
}
