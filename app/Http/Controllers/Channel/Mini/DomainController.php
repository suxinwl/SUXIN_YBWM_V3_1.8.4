<?php

namespace App\Http\Controllers\Channel\Mini;

use App\Http\Controllers\Channel\ApiController;
use App\Services\OpenWechat\ChannelOpenWechat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use App\Services\OpenWechat\AdminOpenWechat;
use App\Models\OpenWechatAuth;
class DomainController extends ApiController
{
    public function index()
    {
        $app = ChannelOpenWechat::miniProgram($this->uniacid());
        $res = $app->domain->httpPostJson("wxa/get_effective_webviewdomain");
        if ($res['errcode'] != 0) {
            throw new BadRequestException($res['errmsg']);
        }
        return $this->success($res);
    }

    public function store(Request $request)
    {
        $app = ChannelOpenWechat::miniProgram($this->uniacid());
        $post['action'] = $request->action ?? 'add';
        $post['webviewdomain'] = is_array($request->domains) ? $request->domains : [$request->domains];
        $res = $app->domain->httpPostJson('wxa/setwebviewdomain_directly', $post);
        if ($res['errcode'] != 0) {
            throw new BadRequestException($res['errmsg']);
        }
        return $this->success([], '业务域名设置成功');
    }


    public function file(Request $request)
    {
        $app = ChannelOpenWechat::miniProgram($this->uniacid());
        $res = $app->httpPostJson("wxa/get_webviewdomain_confirmfile");
        if ($res['errcode'] != 0) {
            throw new BadRequestException($res['errmsg']);
        }
        $file = Storage::disk('public')->put($res['file_name'], $res['file_content']);
        return Storage::disk('public')->download($res['file_name'], $res['file_name'],['filename'=>$res['file_name']]);
    }

    public function getDomainList(Request $request)
    {
        $uniacid=$this->uniacid();
        $app = AdminOpenWechat::openPlatform();
        $miniData = OpenWechatAuth::where('uniacid', $uniacid)->where('type','mini')->first();
        $domain= preg_replace("(^https?://)", "", 'https://' .  Request()->header('HTTP_HOST'));
        $progrom =  $app->miniProgram($miniData['authorizer_appid'], $miniData['authorizer_refresh_token']);
        $res =$progrom->domain->getEffectiveDomain([]);
        return $this->success($res, '业务域名设置成功');

    }


    public function setDomain(Request $request)
    {
        if(!is_array($request->requestdomain)){
            $requestdomain=explode(',',$request->requestdomain);
        }
        if(!is_array($request->downloaddomain)){
            $downloaddomain=explode(',',$request->downloaddomain);
        }
        $uniacid=$this->uniacid();
        $app = AdminOpenWechat::openPlatform();
        $miniData = OpenWechatAuth::where('uniacid', $uniacid)->where('type','mini')->first();
        $domain= preg_replace("(^https?://)", "", 'https://' .  Request()->header('HTTP_HOST'));
        $progrom =  $app->miniProgram($miniData['authorizer_appid'], $miniData['authorizer_refresh_token']);
        $doman=[
            'action'=>'add',
            'requestdomain'=>$requestdomain,
            'wsrequestdomain'=>["wss://".$domain],
            'uploaddomain'=>["https://".$domain],
            'downloaddomain'=>$downloaddomain,
            'udpdomain'=>["udp://".$domain],
            'tcpdomain'=>["tcp://".$domain],
        ];

        $res=$progrom->domain->modify($doman);

        return $this->success($res, '业务域名设置成功');
    }
}
