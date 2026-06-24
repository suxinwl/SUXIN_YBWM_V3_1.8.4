<?php

namespace App\Http\Controllers\Channel;

use App\Http\Controllers\Controller;
use App\Models\Config;
use App\Models\MiniPrivacysetting;
use App\Models\MiniVersion;
use App\Models\OpenWecahtVersion;
use App\Models\openWechat;
use App\Models\OpenWechatAuth;
use App\Models\Tester;
use App\Services\ConfigService;
use App\Services\OpenWechat\AdminOpenWechat;
use App\Services\OpenWechat\ChannelOpenWechat;
use Illuminate\Broadcasting\Channel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use SebastianBergmann\CodeCoverage\Report\Xml\Tests;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class MiniUploadController extends ApiController
{
    public function index()
    {
        $model = ChannelOpenWechat::getConfig($this->uniacid());
        $list = MiniVersion::where('appid', $model->authorizer_appid)->orderBy('id', 'desc')->paginate(Request()->pageSize ?? 20, '*', 'pageNo');
        return $this->success($list);
    }

    /**
     * 小程序版本信息
     */
    public function version()
    {
        $mini = OpenWechatAuth::where('uniacid', $this->uniacid())->where('type', 'mini')->first();
        if (!$mini) {
            return $this->failed('请先授权小程序');
        }
        $adminWecaht = AdminOpenWechat::openPlatform();
        $app = ChannelOpenWechat::miniProgram($this->uniacid());
        $res = $app->code->httpPostJson('wxa/getversioninfo');
        if ($res['code'] != 0) {
            return $this->failed($res['errmsg']);
        }
        try {
            $miniVersion = ChannelOpenWechat::getMiniVersion($this->uniacid(), true);
        } catch (\Exception $e) {
            $miniVersion = null;
        }
        $releaseVersion =  MiniVersion::where('appid', $app->getConfig()['app_id'])
            ->where('version', $res['release_info']['release_version'])
            ->where('state', 9)
            ->orderBy('id', 'desc')
            ->first();
        if (!empty($releaseVersion) && !empty($res['release_info'])) {
            $res['release_info']['release_time'] = date("Y-m-d H:i:s", $res['release_info']['release_time']);
            $res['release_info']['audit_ok_time'] = $releaseVersion->audit_ok_time;
        } else {
            $res['release_info'] = null;
            $res['exp_info'] = null;
        }
        $info = $adminWecaht->getAuthorizer(ChannelOpenWechat::getConfig($this->uniacid())->authorizer_appid);
        $version = ['exp_info' => $res['exp_info'], 'release_info' => $res['release_info']];
        $data['info'] =   $info;
        $paths=public_path('storage' . '/' . $this->uniacid());
        if(!file_exists($paths)){
            mkdir($paths);
            chmod($paths,0777);
        }
        if(!file_exists(public_path('storage' . '/' . $this->uniacid().'/'.$this->uniacid().'.png'))){
            $durl=$info['authorizer_info']['qrcode_url'];
            $domain = 'https://' . Request()->server('HTTP_HOST');
            $ch = curl_init();
            curl_setopt($ch,CURLOPT_URL,$durl);
            curl_setopt($ch,CURLOPT_TIMEOUT,2);
            curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
            $r=curl_exec($ch);
            curl_close($ch);
            //file_put_contents(public_path('storage' . '/' . $this->uniacid().'/'.$this->uniacid().'.png'),$r);
        }
        //$data['logo'] =  'data:image/jpg;base64,' . base64_encode(file_get_contents($info['authorizer_info']['qrcode_url']));
        $data['logo'] =$domain.'/storage' . '/' . $this->uniacid().'/'.$this->uniacid().'.png';





        $data['online'] = $version;
        $data['auditstatus'] = $miniVersion;
        $data['newVersion'] = OpenWecahtVersion::select(['id', 'template_id', 'version', 'desc', 'created_at'])->first();
        return $this->success($data);
    }

    public function miniData()
    {
        $mini = OpenWechatAuth::where('uniacid', $this->uniacid())->where('type', 'mini')->first();
        if (!$mini) {
            return $this->failed('请先授权小程序');
        }
        $mini->miniData = Request()->miniData;
        $mini->save();
        return $this->success();
    }


    public function refreshAudit()
    {
        $version = ChannelOpenWechat::getMiniVersion($this->uniacid());
        if (!in_array($version->state, [2, 4])) {
            throw new BadRequestHttpException('数据不存在');
        }
        $res = ChannelOpenWechat::getAuditStatus($this->uniacid(), $version->auditid);
        if ($res['errcode'] == 0) {
            if ($res['status'] == 1) {
                $version->state = 1;
                $version->reason = $res['reason'];
                $version->screenshot = "";
                $version->save();
            }

            if ($res['status'] == 4) {
                $version->state = 4;
                $version->reason = $res['reason'];
                $version->screenshot = "";
                $version->save();
            }
            if ($res['status'] == 0) {
                $version->state = 0;
                $version->audit_ok_time = date("Y-m-d H:i:s", time());
                $res = ChannelOpenWechat::release($this->uniacid());
                if ($res['code'] == 0) {
                    $version->state = 9;
                    $version->release_time = date("Y-m-d H:i:s", time());
                }
                $version->save();
            }
        }
        return $this->success('数据刷新成功');
    }

    /**
     * 小程序代码上传
     */
    public function upload()
    {
        $res = ChannelOpenWechat::commit($this->uniacid());
        if ($res['errcode'] == 0) {
            return $this->success($res, '代码上传成功');
        }
        return $this->failed($res['errmsg']);
    }

    /**
     * 获取已上传的代码列表
     */

    public function codeList()
    {
        $app = ChannelOpenWechat::miniProgram($this->uniacid());
        $res = $app->code->getPage();
        if ($res['errcode'] == 0) {
            return $this->success([]);
        }
        return $this->failed($res['errmsg']);
    }


    /**
     * 获取体验版二维码
     */
    public function get_qrcode()
    {
        $res = ChannelOpenWechat::previewQrcode($this->uniacid());
        if ($res == false) {
            return $this->failed('生成二维码失败');
        }
        return $this->success($res);
    }

    /**
     * 提交审核
     */
    public function submitAudit()
    {
        $res = ChannelOpenWechat::submitAudit($this->uniacid(), intval(Request()->autoRelease));
        if ($res['errcode'] == 0) {
            return $this->success([], '提交审核成功');
        }
        return $this->failed($res['errmsg']);
    }

    public function speedupCodeAudit()
    {
        $res = ChannelOpenWechat::speedupCodeAudit($this->uniacid());
        if ($res['errcode'] == 0) {
            return $this->success([], '加急审核成功');
        }
        return $this->failed($res['errmsg']);
    }



    /**
     * 撤回审核
     */
    public function undocodeaudit()
    {
        $res = ChannelOpenWechat::undocodeaudit($this->uniacid());
        if ($res['errcode'] == 0) {
            return $this->success([], '撤回成功');
        }
        return $this->failed($res['errmsg']);
    }

    /**
     * 发布小程序
     */
    public function release()
    {
        $res = ChannelOpenWechat::release($this->uniacid());
        if ($res['errcode'] == 0) {
            return $this->success([], '发布成功');
        }
        return $this->failed($res['errmsg']);
    }


    /**
     * 绑定体验者
     */
    public function bind_tester(Request $request)
    {
        $res = ChannelOpenWechat::bindTester($this->uniacid(), $request->wechatid);
        $model = ChannelOpenWechat::getConfig($this->uniacid());
        if ($res['errcode'] == 0) {
            $tester = new Tester();
            $tester->wechatid = $request->wechatid;
            $tester->appid = $model->authorizer_appid;
            $tester->save();
            return $this->success([], '绑定成功');
        }
        return $this->failed($res['errmsg']);
    }

    /**
     * 解绑体验者
     */
    public function unbind_tester(Request $request)
    {
        $res = ChannelOpenWechat::unbindTester($this->uniacid(), $request->wechatid);
        if ($res['errcode'] == 0) {
            Tester::where('wechatid', $request->wechatid)->delete();
            return $this->success([], '解绑成功');
        }
        return $this->failed($res['errmsg']);
    }

    /**
     * 体验者列表
     */
    public function tester_list()
    {
        $model = ChannelOpenWechat::getConfig($this->uniacid());
        $list = Tester::where('appid', $model->authorizer_appid)->orderBy('id', 'desc')->paginate(Request()->pageSize ?? 10, '*', 'pageNo');
        return $this->success($list);
    }

    public function getprivacysetting(Request $request)
    {
        $model = MiniPrivacysetting::where('uniacid', $this->uniacid())->first();
        $app = ChannelOpenWechat::miniProgram($this->uniacid());
        $res = $app->privacy->get();
        return $this->success(['data' => $model, 'res' => $res]);
    }

    public function setprivacysetting(Request $request)
    {
        $app = ChannelOpenWechat::miniProgram($this->uniacid());
        $res = $app->privacy->set($request->all());
        if ($res['errcode'] != 0) {
            throw new BadRequestException($res['errmsg']);
        }
        $model =  MiniPrivacysetting::where('uniacid', $this->uniacid())->first();
        if ($model) {
            $model->data = $request->all();
            $model->save();
        } else {
            $model = new MiniPrivacysetting();
            $model->uniacid = $this->uniacid();
            $model->data = $request->all();
            $model->save();
        }
        return $this->success([], '隐私设置成功');
    }
}
