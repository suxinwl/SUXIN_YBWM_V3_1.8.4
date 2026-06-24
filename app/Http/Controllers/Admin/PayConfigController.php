<?php

namespace App\Http\Controllers\Admin;

use App\Models\Admin\Apply;
use App\Models\PayConfig;
use App\Models\PayTemplate;
use App\Services\PayConfigService;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class PayConfigController extends ApiController
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $info = Apply::select(['id','payChange'])->find($id);
        if (empty($info)) {
            throw new BadRequestException("店铺不存在");
        }
        $model = PayConfig::select(['id', 'payType', 'templateId', 'state', 'channel','isDefault'])
        ->where('uniacid', $id)
        ->where('storeId',0)
        ->where('channel', $request->channel)
        ->get();
        $data = [
            'pay' => collect($model)->keyBy('payType'),
            'payChange' => $info->payChange,
            'default' => collect($model)->where('isDefault', 1)->first()->payType,
        ];
        return $this->success($data);
    }

    public function update(Request $request, PayConfig $model, $id)
    {
        try {
            $info = Apply::find($id);
            $info->payChange = $request->payChange;
            if (empty($info)) {
                throw new BadRequestException("店铺不存在");
            }
            DB::beginTransaction();
            foreach ($request->pay as $key => $v) {
                $templateId = 0;
                $model = PayConfig::where('uniacid', $id)->where('channel', $request->channel)
                ->where('payType', $key)
                ->where('storeId',0)
                ->first();
                if ($key == 'weixin' || $key == 'alipay') {
                    if (empty($model) || ($v['data']['mch_id'] != $model->data['mch_id']) || ($v['data']['type'] != $model->data['type'])) {
                        $payTemplate = PayTemplate::create([
                            'data' => $v['data'],
                            'uniacid' => $id,
                            'title' => $id . $key,
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
                        'uniacid' => $id,
                        "state" => $v['state'] ?? 0,
                        "channel" => $request->channel,
                        "payType" => $key,
                        'templateId' => $templateId,
                        "isDefault" => $request->default == $key ? 1 : 0
                    ]);
                } else {
                    $model->fill([
                        'uniacid' => $id,
                        "state" => $v['state'] ?? 0,
                        "channel" => $request->channel,
                        "payType" => $key,
                        'templateId' => $templateId,
                        "isDefault" => $request->default == $key ? 1 : 0
                    ]);
                    $model->save();
                }
            }
            $info->save();
            DB::commit();
            return $this->success([], '支付配置成功');
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            DB::rollBack();
            return $this->failed($e->getMessage());
        }
    }
}
