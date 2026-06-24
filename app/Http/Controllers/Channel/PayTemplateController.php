<?php

namespace App\Http\Controllers\Channel;

use App\Http\Resources\ChannelApi\User\RoomResources;
use App\Models\PayTemplate;
use App\Services\ConfigService;
use App\Services\Pay\WechatPay;
use App\Services\PayService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class PayTemplateController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $list = PayTemplate::where('uniacid', $this->uniacid())->orderBy('id', 'desc')->paginate($request->pageSize ?? 20, '*', 'pageNo');
        return $this->success($list);
    }


    public function show(Request $request, $id)
    {
        $model = PayTemplate::where('uniacid', $this->uniacid())->find($id);
        if (empty($model)) {
            return $this->failed('数据不存在');
        }
        return $this->success($model);
    }


    public function store(Request $request, PayTemplate $model)
    {
        $model->create(array_merge($request->all(), ['uniacid' => $this->uniacid(), 'state' => 1]));
        return $this->success([], '支付模板创建成功');
    }


    public function update(Request $req, PayTemplate $model, $id)
    {
        $model = PayTemplate::where('uniacid', $this->uniacid())->find($id);
        if (empty($model)) {
            return $this->failed('数据不存在');
        }
        $model->fill($req->all());
        $model->save();
        return $this->success([]);
    }


    /**
     * 删除用户
     */
    public function destroy(PayTemplate $PayTemplate, $id)
    {
        $idArray = array_filter(explode(',', $id), function ($item) {
            return is_numeric($item);
        });
        $PayTemplate->destroy($idArray);
        return $this->success([], '删除成功');
    }


    /**
     * 拉黑/洗白
     */
    public function state(Request $request, $id)
    {
        $model = PayTemplate::where('uniacid', $this->uniacid())->where('id', $id)->first();
        if (!$model) {
            return $this->failed('数据不存在');
        }
        $model->state = $model->state == 1 ? 0 : 1;
        $model->save();
        return $this->success($model->state, '状态调整成功');
    }

    public function pickList()
    {
        $list = PayTemplate::select('id', 'type', 'title')->where('state', 1)->where('uniacid', $this->uniacid())->orderBy('id', 'desc')->get();
        $list = collect($list)->groupBy('type');
        $list = $list->toArray();
        foreach ($list as $key => $v) {
            if ($key != 3 && isset($list[3])) {
                $v = array_merge($v, $list[3]);
            }
            if ($key != 4 && isset($list[4])) {
                $v = array_merge($v, $list[4]);
            }
            if ($key == 1) {
                $key = 'weixin';
                $data[$key] = $v;
            }
            if ($key == 2) {
                $key = 'ali';
                $data[$key] = $v;
            }
        }
        return $this->success($data);
    }

    public function wxList()
    {
        $list = PayTemplate::select('id', 'title', 'notes')->where('state', 1)->where('uniacid', $this->uniacid())->where('type', 1)->orderBy('id', 'desc')->get();
        return $this->success($list);
    }


    public function withdrawal()
    {
        $config = ConfigService::getChannelConfig('paymentSet', $this->uniacid());
        if (empty($config)) {
            return $this->failed('请先配置打款设置');
        }
        $PayTemplate = PayTemplate::find($config['pAccount']);
        if (empty($payTemplate)) {
            return $this->failed('请先配置打款设置');
        }
        return PayService::withdrawal(Request()->all(), $this->uniacid(), $config['pAccount']);
    }
}
