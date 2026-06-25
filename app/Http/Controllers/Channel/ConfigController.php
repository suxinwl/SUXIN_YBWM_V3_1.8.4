<?php

namespace App\Http\Controllers\Channel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Channel\ConfigRequest;
use App\Models\ChannelConfig as Config;
use App\Models\ChannelConfig;

use App\Models\FollowWechat;
use App\Models\OpenWechatAuth;
use App\Models\StoreConfig;
use App\Models\TopLevel;
use App\Models\Voice;
use App\Services\ChannelConfigService;
use App\Services\ConfigService;
use EasyWeChat\Factory;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\Ali;
use App\Models\Aliauth;
use App\Models\ShopAccount;
use App\Models\Admin\Apply;
use App\Models\Circle;
use App\Imports\SpecsImport;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\ReallysavesMoney;

class ConfigController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $ident = $request->ident;
        $data = ConfigService::getChannelConfig($ident, $this->uniacid());
        return  $this->success($data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(ConfigRequest $request, ChannelConfig $model)
    {
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ConfigRequest $request, ChannelConfig $model)
    {
        $unique = Config::where('ident', $request->ident)
            ->where('uniacid', $this->uniacid())
            ->where('storeId', 0)
            ->first();
        if ($unique) {
            return $this->failed(__("base.unique"), 422);
        }
        $model->create([
            'uniacid' => $this->uniacid(),
            'ident' => $request->ident,
            'name' => $request->identName,
            'data' => $request->all(),
        ]);
        return $this->success([], '保存成功');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show()
    {
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit()
    {
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(ConfigRequest $request, $ident)
    {
        $model = ChannelConfig::where('ident', $ident)
            ->where('uniacid', $this->uniacid())->where('storeId', 0)
            ->first();
        if (!$model) {
            return $this->failed('数据不存在');
        }
        $model->data = $request->all();
        $model->save();
        return $this->success([], __('base.success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $ident)
    {
        $model = ChannelConfig::where('ident', $ident)->where('uniacid', $this->uniacid())->where('storeId', 0)->first();
        if ($model) {
            $model->delete();
        }
        return $this->success([], __('base.success'));
    }



    //查询授权结果
    public function authInfo(Request $request)
    {
        $uniacid = 1;
        $storeId = 1;
        $aliAuthModel = new Aliauth();
        $info = $aliAuthModel->where('storeId', $storeId)->first();
        if (!$info['batch_no']) {
            $info['state'] = 0;
            return $this->result(1, '成功', $info);
        }
        $res = Ali::getAuthorization($uniacid, $info['batch_no']);
        if ($res['code'] == 1) {
            $state = 1;
            if ($res['data']['handle_status'] == 'SUCCESS') {
                $state = 2;
            }
            $aliAuthModel->merchant_no = $res['data']['merchant_no'];
            $aliAuthModel->changeAt = date('Y-m-d H:i:s', time());
            $aliAuthModel->state = $state;
            $aliAuthModel->save();
        }
        $list = $aliAuthModel->where('storeId', $storeId)->first();
        return $this->result(1, '成功', $list);
    }

    //授权
    public function sendAuth(Request $request)
    {
        $uniacid = 1;
        $storeId = 1;
        $aliAuthModel = new Aliauth();
        $account = trim($request->input('account'));
        $info = $aliAuthModel->where('storeId', $storeId)->first();
        $res = Ali::authorization($uniacid, $account);
        if ($res['code'] == 1) {
            if ($info) {
                $aliAuthModel->account = $account;
                $aliAuthModel->batch_no = $res['data'];
                $aliAuthModel->changeAt = date('Y-m-d H:i:s', time());
                $aliAuthModel->state = 1;
                $aliAuthModel->save();
            } else {
                $aliAuthModel->storeId = $storeId;
                $aliAuthModel->uniacid = $uniacid;
                $aliAuthModel->account = $account;
                $aliAuthModel->state = 1;
                $aliAuthModel->createdAt = date('Y-m-d H:i:s', time());
                $aliAuthModel->batch_no = $res['data'];
                $aliAuthModel->save();
            }
            return $this->result(1, '成功');
        } else {
            return $this->result(2, $res['msg']);
        }
    }

    public function system()
    {
        $data['userSettings'] = ConfigService::getSystemSet('userSettings');
        $data['copyrightSetting'] = ConfigService::getSystemSet('copyrightSetting');
        $openWechat = ConfigService::getSystemSet('platformWechat');
        $data['platformWechat'] = [
            'status' => $openWechat->status,
        ];
        $data['site'] = ConfigService::getSystemSet('site');
        $data['storeSetting'] = ConfigService::getSystemSet('storeSetting');
        $deliverySetting = ConfigService::getSystemSet('deliverySetting');
        unset($deliverySetting->AppKey, $deliverySetting->AppSecret);
        $data['deliverySetting'] = $deliverySetting;
        $data['merchantMini'] = ConfigService::getSystemSet('merchantMini');
        $data['home'] = ConfigService::getSystemSet('home');
        $data['service'] = ConfigService::getSystemSet('service');
        return $this->success($data);
    }

    //批量导入
    public function batchImport(Request $request)
    {
        $uniacid = $this->uniacid();
        $importsType = $request->importsType;
        $file = $_FILES;
        if ($uniacid && $importsType) {
            $a = Excel::import(new SpecsImport($uniacid, $importsType), $request->file('file'));
            return $this->success([]);
        } else {
            return $this->failed('无效的请求');
        }
    }

    //下载导出模板
    public function downTemplate(Request $request)
    {
        $type = $request->type;
        switch ($type) {
            case 1; //门店管理
                $fileName = '导入门店表格模版';
                break;
            case 2; //商品分类
                $fileName = '商品库_标准商品分类导入模版';
                break;
            case 3; //商品管理
                $fileName = '商品库_标准商品导入模版';
                break;
            case 4; //规格管理
                $fileName = '商品库_标准商品规格导入模版';
                break;
            case 5; //加料管理
                $fileName = '商品加料示例';
                break;
            case 6; //属性管理
                $fileName = '商品库_标准商品属性示例';
                break;
            case 7; //单位管理
                $fileName = '商品库_标准商品单位示例';
                break;
            case 8; //标签管理
                $fileName = '商品库_标准商品标签示例';
                break;
            case 9; //用户管理
                $fileName = '会员列表导入模版';
                break;
            case 10; //商品角标
                $fileName = '商品库_标准商品角标示例';
                break;
            case 11; //会员积分
                $fileName = '会员积分批量修改示例';
                break;
            case 12; //商品角标
                $fileName = '会员余额批量修改示例';
                break;
            case 13; //商品角标
                $fileName = '会员成长值批量修改示例';
                break;
        }
        return response()->download(public_path('excelExample/' . $fileName . '.xls'));
    }
    public function authCode()
    {
        $uniacid = $this->uniacid() ?: '0';
        $key = "authCode:" . $uniacid;
        $code = Cache::remember($key, 86400, function () use ($uniacid) {
            $appUrl = env('APP_URL') ?: Request()->getSchemeAndHttpHost();
            $host = parse_url($appUrl, PHP_URL_HOST) ?: trim(str_replace(['https://', 'http://'], '', $appUrl), '/');
            $seed = implode('|', ['suxin-auth-code', $host, $uniacid, config('app.key')]);
            return strtoupper(substr(hash('sha256', $seed), 0, 12));
        });
        return $this->success(['code' => $code], 'success');

        try {
            $key = "authCode:" . $this->uniacid();
            $code = Cache::get($key);
            if (empty($code)) {
                $url = config('app.authorizeDomain') . '/cloud/code';
                $res = httpRequest($url, ['domain' => env('APP_URL')]);
                if ($res['code'] != 200) {
                    return $this->failed($res['msg']);
                }
                $code = $res['data']['code'];
                Cache::set($key, $code, 3600);
            }
            return $this->success(['code' => $code], '绑定成功,请登录');
        } catch (\Exception $e) {
            return $this->failed('授权码获取失败');
        }
    }

    //试听百度语音
    public function audition(Request $request)
    {
        $res = ChannelConfig::where('ident', 'voice')->where('uniacid', $this->uniacid())->first();
        if (!$res) {
            return $this->failed('请先配置语音设置');
        }
        $data = $res->data;
        $api_key = $data->api_key;
        $secret_key = $data->secret_key;
        $txt = trim($request->content);
        $spd = $data->spd ?: 5;
        $pit = $data->pit ?: 5;
        $vol = $data->vol ?: 10;
        $per = $data->per ?: 0;
        $aue = $data->aue ?: 3;
        $url = Voice::run($api_key, $secret_key, $txt, $cuid = 'ybv3', $spd, $pit, $vol, $per, $aue);
        return $this->success($url);
    }


    //生成一个绑定公众号的二维码
    public function followCode(Request $request)
    {
        $uniacid = $this->uniacid();
        $storeId = $request->storeId;
        $dir = public_path('followCode' . '/' . $uniacid);
        $file = $storeId . '.jpg';
        if (file_exists($dir . "/" . $file)) {
            return $this->success(getDomain() . '/' . 'followCode' . '/' . $uniacid . '/' . $file);
        }
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
            chmod($dir, 0777);
        }
        $res = ConfigService::getSystemSet('official_account');
        $config = [
            'app_id' => $res->appId,
            'secret' => $res->appSecret,
            'response_type' => 'array',
        ];
        $app = Factory::officialAccount($config);
        $result = $app->qrcode->forever($storeId);
        $ticket = $result['ticket'];

        if ($ticket) {
            $url = $app->qrcode->url($ticket);
            $content = file_get_contents($url); // 得到二进制图片内容
            file_put_contents($dir . '/' . $file, $content); // 写入文件
            return $this->success(getDomain() . '/' . 'followCode' . '/' . $uniacid . '/' . $file);
        } else {
            return $this->failed($result['message']);
        }
    }

    //返回列表
    public function getFollowList(Request $request)
    {
        $uniacid = $this->uniacid();
        $storeId = $request->storeId;
        $row = FollowWechat::where(['uniacid' => $uniacid, 'storeId' => $storeId])->get();
        return $this->success($row);
    }

    //解绑接口
    public function delFollow(Request $request)
    {
        $uniacid = $this->uniacid();
        $storeId = $request->storeId;
        $id = $request->id;
        $row = FollowWechat::where(['id' => $id])->first();
        if ($row) {
            $row->delete();
        }
        return $this->success($row);
    }

    //修改备注
    public function modifyName(Request $request)
    {
        $uniacid = $this->uniacid();
        $storeId = $request->storeId;
        $id = $request->id;
        $row = FollowWechat::where(['id' => $id])->first();
        if ($request->nickname) {
            $row->nickname = trim($request->nickname);
            $row->save();
        }
        return $this->success($row);
    }
}
