<?php

namespace App\Http\Controllers\Channel;

use App\Models\Admin;
use App\Models\Admin\Apply;
use App\Models\Admin\Muster;
use App\Models\AdminOrder;
use App\Models\Setmeal;
use App\Services\ConfigService;
use App\Services\Pay\AdminAliPay;
use App\Services\Pay\AdminWechatPay;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class MusterController extends ApiController
{

    public function index(Request $req, Setmeal $model)
    {
        $list = $model->where('state', 1)->orderBy('type', 'desc')->orderBy('sort', 'asc')->paginate($req->pageSize ?? 30);
        return $this->success($list);
    }

    public function Pay(Request $request)
    {
        $admin = Admin::find($this->user()->id);
        $count =  Apply::withTrashed()->where("createUserId", $this->userId())->whereIn('status', [1, 2, 3])->where('musterId', ">", 0)->count();
        if ($admin->createStoreNum > 0 &&  $count > $admin->createStoreNum) {
            return $this->failed($admin->username . __('base.apply_top'));
        }
        $apply = Apply::find($this->uniacid());
        if ($apply->status != 1) {
            return $this->failed('店铺状态不正确');
        }
        $muster = Setmeal::where('type', 0)->where('state', 1)->find($request->musterId);
        $payConfig = ConfigService::getSystemSet('payConfig');
        $payId = intval($request->payId);
        if (empty($muster)) {
            return $this->failed('当前套餐不存在');
        }
        $list = $muster->money;
        if (!isset($list[$payId])) {
            return $this->failed('没有当前套餐费用');
        } else {
            $money = $list[$payId]['price'];
            $desc = "购买" . $muster->title;
        }
        $order = [
            'state' => 0,
            'payType' => 0,
            'userId' => $this->user()->id,
            'outTradeNo' => getTakeOutNo(),
            'transaction_id' => '',
            'money' => $money,
            'type' => 1,
            'applyId' => $this->uniacid(),
            'day' => $list[$payId]['day'],
            'goodsId' => $request->musterId,
            'attach' => ['applyId' => $this->uniacid()]
        ];
        $adminOrder = AdminOrder::create($order);
        if (!$adminOrder) {
            return $this->failed('订单创建失败');
        }
        $adminOrder->refresh();
        if ($payConfig->alipay == 1) {
            $order = [
                'out_trade_no' => time(),
                'total_amount' => $money,
                'subject' => $desc,
            ];
            $app = AdminAliPay::payment();
            $img =  QrCode::format('png')->size(200)->generate($app->scan($order)->qrCode);    //format 是指定生成文件格式  默认格式是svg,可以直接在浏览器打开，png不能直接显示
            $code_url = 'data:image/png;base64,' . base64_encode($img);
            $data['ali'] = $img;
        }

        if ($payConfig->wechat == 1) {
            $app = AdminWechatPay::Payment();
            $response = $app->getClient()->postJson("v3/pay/transactions/native", [
                "mchid" => $app->getConfig()['mch_id'], // <---- 请修改为您的商户号
                "out_trade_no" => $adminOrder->outTradeNo,
                "appid" => $app->getConfig()['app_id'], // <---- 请修改为服务号的 appid
                "description" => $desc,
                "notify_url" => URL::to("/api/wxPayNotify/muster"),
                "amount" => [
                    "total" => intval(bcmul($money, 100)) ?? 1,
                    "currency" => "CNY"
                ],
            ]);
            $result = $response->toArray();
            $code_url = $result['code_url'];
            $img =  QrCode::format('png')->size(200)->generate($code_url);    //format 是指定生成文件格式  默认格式是svg,可以直接在浏览器打开，png不能直接显示
            $code_url = 'data:image/png;base64,' . base64_encode($img);
            $data['order'] = $adminOrder;
            $data['weixin'] = $code_url;
        }
        if (empty($data)) {
            return $this->failed('平台未开启支付通道，无法购买套餐');
        }
        return $this->success($data);
    }

    public function experience(Request $request)
    {
        $admin = Admin::find($this->user()->id);
        $count =  Apply::withTrashed()->where("createUserId", $this->userId())->whereIn('status', [1, 2, 3])->where('musterId', ">", 0)->count();
        if ($admin->createStoreNum > 0 && $count >= $admin->createStoreNum) {
            return $this->failed($admin->username . __('base.apply_top'));
        }
        $muster = Setmeal::where('type', 1)->where('state', 1)->find($request->musterId);
        if (empty($muster)) {
            return $this->failed('当前套餐不存在');
        }
        $order = [
            'state' => 1,
            'payType' => 0,
            'userId' => $this->user()->id,
            'outTradeNo' => getTakeOutNo(),
            'transaction_id' => '',
            'money' => 0,
            'type' => 1,
            'applyId' => $this->uniacid(),
            'day' => $muster->day,
            'goodsId' => $request->musterId,
            'attach' => ['applyId' => $this->uniacid()]
        ];
        $adminOrder = AdminOrder::create($order);
        if (!$adminOrder) {
            return $this->failed('订单创建失败');
        }
        $adminOrder->createApply();
        return $this->success([
            'uniacid' => $adminOrder->applyId,
            'apply' => $adminOrder->apply,
        ]);
    }


    public function payState(Request $request)
    {
        $adminOrder = AdminOrder::where('userId', $this->user()->id)->where('outTradeNo', $request->outTradeNo)->where('state', 1)->first();
        $url = URL::to('/h5?uniacid=' . $adminOrder->uniacid);
        $img = QrCode::format('png')->size(200)->generate("https://www.baidu.com");
        return $this->success([
            'state' => empty($adminOrder) ? 0 : $adminOrder->state,
            'uniacid' => empty($adminOrder) ? null : $adminOrder->applyId,
            'apply' => empty($adminOrder) ? null : $adminOrder->apply,
        ]);
    }


    public function renewal(Request $request)
    {
        $userId = $this->user()->id;
        $apply = Apply::where(function ($q) use ($userId) {
            if ($userId != 1) {
                $q->where('createUserId', $userId);
            }
            return $q;
        })->where('status', 1)->where('id', $request->applyId)->first();
        if (empty($apply)) {
            return $this->failed('平台不存在');
        }
        if ($apply->timeType == 2  && strtotime($apply->endTime) < time()) {
            return $this->success(false);
        }
        $muster = $apply->muster;
        if (!$muster->prolongSwitch || $muster->type  == 1) {
            return $this->success(false);
        }
        if ($muster->state != 1 && $muster->soldOutSwitch == 0) {
            return $this->success(false);
        }
        //$list = Setmeal::where('id', $muster->id)->orderBy('type', 'desc')->orderBy('sort', 'asc')->paginate($request->pageSize ?? 30);
        return $this->success($muster);
    }

    public function renewalPlug(Request $request)
    {
        $admin = Admin::find($this->user()->id);
        if ($admin->createStoreNum > 0 && $admin->adminApply->count() >= $admin->createStoreNum) {
            return $this->failed($admin->username . __('base.apply_top'));
        }
        $muster = Setmeal::where('type', 0)->where('state', 1)->find($request->musterId);
        $payConfig = ConfigService::getSystemSet('payConfig');
        $payId = intval($request->payId);
        if (empty($muster)) {
            return $this->failed('当前套餐不存在');
        }
        $list = $muster->prolong;
        if (!isset($list[$payId])) {
            return $this->failed('没有当前套餐费用');
        } else {
            $money = $list[$payId]['price'];
            $desc = "续费" . $muster->title;
        }
        $order = [
            'state' => 0,
            'payType' => 0,
            'userId' => $this->user()->id,
            'outTradeNo' => getTakeOutNo(),
            'transaction_id' => '',
            'money' => $money,
            'type' => 1,
            'applyId' => 0,
            'day' => $list[$payId]['day'],
            'goodsId' => $request->musterId,
            'attach' => []
        ];
        $adminOrder = AdminOrder::create($order);
        if (!$adminOrder) {
            return $this->failed('订单创建失败');
        }
        if ($payConfig->alipay == 1) {
            $order = [
                'out_trade_no' => time(),
                'total_amount' => $money,
                'subject' => $desc,
            ];
            //$app = AdminAliPay::payment();
            //$data['ali'] = $app->scan($order)->qrCode;
        }

        if ($payConfig->wechat == 1) {
            $app = AdminWechatPay::Payment();
            $response = $app->getClient()->postJson("v3/pay/transactions/native", [
                "mchid" => $app->getConfig()['mch_id'], // <---- 请修改为您的商户号
                "out_trade_no" => $adminOrder->outTradeNo,
                "appid" => $app->getConfig()['app_id'], // <---- 请修改为服务号的 appid
                "description" => $desc,
                "notify_url" => URL::to("/api/wxPayNotify/xftc"),
                "amount" => [
                    "total" => intval(bcmul($money, 100)) ?? 1,
                    "currency" => "CNY"
                ],
            ]);
            $result = $response->toArray();
            $code_url = $result['code_url'];
            $img =  QrCode::format('png')->size(200)->generate($code_url);    //format 是指定生成文件格式  默认格式是svg,可以直接在浏览器打开，png不能直接显示
            $code_url = 'data:image/png;base64,' . base64_encode($img);
            $data['order'] = $adminOrder;
            $data['weixin'] = $code_url;
        }
        return $this->success($data);
    }
    public function renewalPay(Request $request)
    {
        $admin = Admin::find($this->user()->id);
        if ($admin->createStoreNum > 0 && $admin->adminApply->count() >= $admin->createStoreNum) {
            return $this->failed($admin->username . __('base.apply_top'));
        }
        $muster = Setmeal::where('type', 0)->where('state', 1)->find($request->musterId);
        $payConfig = ConfigService::getSystemSet('payConfig');
        $payId = intval($request->payId);
        if (empty($muster)) {
            return $this->failed('当前套餐不存在');
        }
        $list = $muster->money;
        if (!isset($list[$payId])) {
            return $this->failed('没有当前套餐费用');
        } else {
            $money = $list[$payId]['price'];
            $desc = "续费" . $muster->title;
        }
        $order = [
            'state' => 0,
            'payType' => 0,
            'userId' => $this->user()->id,
            'outTradeNo' => getTakeOutNo(),
            'transaction_id' => '',
            'money' => $money,
            'type' => 1,
            'applyId' => 0,
            'day' => $list[$payId]['day'],
            'goodsId' => $request->musterId,
            'attach' => []
        ];
        $adminOrder = AdminOrder::create($order);
        if (!$adminOrder) {
            return $this->failed('订单创建失败');
        }
        if ($payConfig->alipay == 1) {
            $order = [
                'out_trade_no' => time(),
                'total_amount' => $money,
                'subject' => $desc,
            ];

            try {
                // $app = AdminAliPay::payment();
                //  $app->scan($order);
            } catch (\Exception $e) {
            }
        }

        if ($payConfig->wechat == 1) {
            $app = AdminWechatPay::Payment();
            $response = $app->getClient()->postJson("v3/pay/transactions/native", [
                "mchid" => $app->getConfig()['mch_id'], // <---- 请修改为您的商户号
                "out_trade_no" => $adminOrder->outTradeNo,
                "appid" => $app->getConfig()['app_id'], // <---- 请修改为服务号的 appid
                "description" => $desc,
                "notify_url" => URL::to("/api/wxPayNotify/xftc"),
                "amount" => [
                    "total" => intval(bcmul($money, 100)) ?? 1,
                    "currency" => "CNY"
                ],
            ]);
            $result = $response->toArray();
            $code_url = $result['code_url'];
            $img =  QrCode::format('png')->size(200)->generate($code_url);    //format 是指定生成文件格式  默认格式是svg,可以直接在浏览器打开，png不能直接显示
            $code_url = 'data:image/png;base64,' . base64_encode($img);
            $data['order'] = $adminOrder;
            $data['weixin'] = $code_url;
        }
        return $this->success($data);
    }
}
