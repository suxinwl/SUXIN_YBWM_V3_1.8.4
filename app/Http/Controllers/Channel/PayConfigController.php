<?php

namespace App\Http\Controllers\Channel;

use App\Models\Admin\Apply;
use App\Models\PayConfig;
use App\Models\PayTemplate;
use App\Services\Pay\Suixingfu;
use App\Services\Pay\WechatPay;
use App\Services\PayConfigService;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use WeChatPay\Crypto\Rsa;
use WeChatPay\Util\PemUtil;

class PayConfigController extends ApiController
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $info = Apply::select(['id', 'payChange'])->find($this->uniacid());
        if (empty($info)) {
            throw new BadRequestException("店铺不存在");
        }
        $model = PayConfig::select(['id', 'payType', 'templateId', 'state', 'channel', 'isDefault'])
            ->where('uniacid', $this->uniacid())
            ->where('channel', $request->channel)
            ->where('storeId', 0)
            ->get();
        $data = [
            'pay' => collect($model)->keyBy('payType'),
            'payChange' => $info->payChange,
            'default' => collect($model)->where('isDefault', 1)->first()->payType,
        ];
        return $this->success($data);
    }

    public function store(Request $request, PayConfig $model)
    {
        try {
            $info = Apply::find($this->uniacid());
            if (empty($info)) {
                throw new BadRequestException("店铺不存在");
            }
            if (empty($info->payChange)) {
                throw new BadRequestException("禁止修改");
            }
            DB::beginTransaction();
            foreach ($request->pay as $key => $v) {
                $templateId = 0;
                $model = PayConfig::where('uniacid', $this->uniacid())->where('channel', $request->channel)
                    ->where('payType', $key)
                    ->where('storeId', 0)
                    ->first();
                if ($key == 'weixin' || $key == 'alipay'||$key == 'zhongyin') {
                    if (empty($model) || ($v['data']['mch_id'] != $model->data['mch_id']) || ($v['data']['type'] != $model->data['type'])) {
                        $payTemplate = PayTemplate::create([
                            'data' => $v['data'],
                            'uniacid' => $this->uniacid(),
                            'title' => $this->uniacid() . $key,
                            "type" => $v['data']['type'],
                            "channel" => $key,
                            'state' => 1,
                            'storeId' => 0
                        ]);
                        if (empty($payTemplate)) {
                            DB::rollBack();
                            return $this->failed('保存失败');
                        }
                        $templateId = $payTemplate->id;
                    } else {
                        $model->payTemplate['data'] = $v['data'];
                        $model->payTemplate->save();
                        $templateId =  $model->templateId;
                    }
                } elseif ($key == "balance" && empty($model)) {
                    $templateId =  0;
                }

                if (empty($model)) {
                    PayConfig::create([
                        'uniacid' => $this->uniacid(),
                        "state" => $v['state'] ?? 0,
                        "channel" => $request->channel,
                        "payType" => $key,
                        'templateId' => $templateId,
                        "isDefault" => $request->default == $key ? 1 : 0
                    ]);
                } else {
                    $model->fill([
                        'uniacid' => $this->uniacid(),
                        "state" => $v['state'] ?? 0,
                        "channel" => $request->channel,
                        "payType" => $key,
                        'templateId' => $templateId,
                        "isDefault" => $request->default == $key ? 1 : 0
                    ]);
                    $model->save();
                }
            }
            DB::commit();
            return $this->success([], '支付配置成功');
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            DB::rollBack();
            return $this->failed($e->getMessage());
        }
    }

    public function  wxReceiversAdd(Request $request, $payId)
    {
        $app = WechatPay::Payment($this->uniacid(), $payId);
        if (!isset($app->getConfig()['sp_appid'])) {
            throw  new BadRequestException('店铺收款设置请更改为微信服务商');
        }
        $platformCertificateSerial = PemUtil::parseCertificateSerialNo(file_get_contents($app->getConfig()['platform_certs'][0]));
        $order = [
            'headers' => ['Wechatpay-Serial' => $platformCertificateSerial],
            "json" => [
                'appid' => $app->getConfig()['sp_appid'],
                'sub_appid' => $app->getConfig()['sub_appid'],
                'sub_mchid' => $app->getConfig()['sub_mchid'],
                "type" => $request->type,
                'account' => $request->account,
                'name' => $request->name,
                //'name' => Rsa::encrypt($request->name, file_get_contents($app->getConfig()['platform_certs'][0])),
                'relation_type' => "CUSTOM",
                'custom_relation' => $request->remark
            ]
        ];
        $response = $app->getClient()->postJson("v3/profitsharing/receivers/add", $order);
        if ($response->isFailed()) {
            throw  new BadRequestException($response->getContent(false));
        }
        return $this->success($request->all());
    }

    public function  setMnoArray(Request $request, $payId){
        $mno=$request->mno;
        $mnoArray=$request->mnoArray;
        $response=Suixingfu::setMnoArray($mno,$mnoArray);
        if ($response['code'] !== '0000') {
            $errMsg=$response['respData']['bizMsg']?:$response['msg'];
            throw  new BadRequestException($errMsg);
        }
        return $this->success($request->all());
    }

    public function  wxReceiversDel(Request $request, $payId)
    {
        $app = WechatPay::Payment($this->uniacid(), $payId);
        if (!isset($app->getConfig()['sp_appid'])) {
            throw  new BadRequestException('店铺收款设置请更改为微信服务商');
        }
        $order = [
            'appid' => $app->getConfig()['sp_appid'],
            'sub_appid' => $app->getConfig()['sub_appid'],
            'sub_mchid' => $app->getConfig()['sub_mchid'],
            "type" => $request->type,
            'account' => $request->account,
        ];
        $response = $app->getClient()->postJson("v3/profitsharing/receivers/delete", $order);
        if ($response->isFailed()) {
            throw  new BadRequestException($response->getContent(false));
        }
        return $this->success($response->toArray());
    }
}
