<?php

namespace App\Http\Controllers\Channel;

use App\Http\Resources\Admin\Sms\SmsCollection;
use App\Http\Resources\Admin\Sms\SmsPayCollection;
use App\Models\SmsAccount;
use App\Models\SmsAccountLog;
use App\Models\Smscombo;
use App\Models\SmsLog;
use App\Models\SmsOrder;
use App\Services\ConfigService;
use Illuminate\Http\Request;
use App\Services\Pay\AdminWechatPay;
use Illuminate\Support\Facades\URL;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class SmsAccountController extends ApiController
{

    public function state(Request $request)
    {
        $order = SmsOrder::where('outTradeNo', $request->outTradeNo)->first();
        return $this->success($order);
    }
    public function payList()
    {
        $smsPostage = Smscombo::where('state', 1)->get();
        return $this->success($smsPostage);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function Pay(Request $request)
    {
        $payId = intval($request->payId);
        $smsPostage = Smscombo::where('state', 1)->where('id', $payId)->first();
        if (empty($smsPostage)) {
            return $this->failed('系统还未配置短信资费,请联系管理员');
        }
        $money = $smsPostage->price;
        $desc = "￥{$money}购买{$smsPostage->number条短信}";
        $data['outTradeNo'] = getTakeOutNo();
        $payConfig = ConfigService::getSystemSet('payConfig');
        if ($payConfig->alipay == 1) {
        }
        if ($payConfig->wechat == 1) {
            $app = AdminWechatPay::Payment();
            $response = $app->getClient()->postJson("v3/pay/transactions/native", [
                "mchid" => $app->getConfig()['mch_id'], // <---- 请修改为您的商户号
                "out_trade_no" => $data['outTradeNo'],
                "appid" => $app->getConfig()['app_id'], // <---- 请修改为服务号的 appid
                "description" => $desc,
                "notify_url" => Request()->getSchemeAndHttpHost() ."/api/wxPayNotify/sms/{$this->uniacid()}",
                "amount" => [
                    "total" => intval(bcmul($money, 100)),
                    "currency" => "CNY"
                ],
                'attach' => json_encode(['userId' => $this->user()->id, 'number' => $smsPostage->num])
            ]);
            $result = $response->toArray();
            $code_url = $result['code_url'];
            $img =  QrCode::format('png')->size(200)->generate($code_url);    //format 是指定生成文件格式  默认格式是svg,可以直接在浏览器打开，png不能直接显示
            $code_url = 'data:image/png;base64,' . base64_encode($img);
            $data['weixin'] = $code_url;
        }

        return $this->success($data);
    }

    public function index()
    {
        $model = SmsAccount::where('uniacid', $this->uniacid())->first();
        return $this->success($model);
    }


    public function order(Request $request)
    {
        $list = SmsOrder::where('uniacid', $this->uniacid())->orderBy('id', 'desc')->paginate($request->pageSize ?? 30);
        $list = new SmsPayCollection($list);
        return $this->success($list);
    }

    public function log(Request $request)
    {
        $list = SmsAccountLog::with(['order'])->where('uniacid', $this->uniacid())->whereIn('behavior', [0, 1, 2])->orderBy('id', 'desc')->paginate($request->pageSize ?? 30);
        return $this->success($list);
    }

    public function smsLog(Request $request)
    {
        $list = SmsLog::where('uniacid', $this->uniacid())->orderBy('id', 'desc')->paginate($request->pageSize ?? 30);
        return $this->success(new SmsCollection($list));
    }
}
