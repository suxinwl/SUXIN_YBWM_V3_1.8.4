<?php

namespace App\Http\Controllers\Channel;

use App\Http\Resources\Channel\StorePayConfig\StoreResources;
use App\Models\Admin\Apply;
use App\Models\PayConfig;
use App\Models\PayTemplate;
use App\Models\Store;
use App\Services\PayConfigService;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class StorePayConfigController extends ApiController
{

    public function index(Request $request)
    {
        $user = $this->user();
        $storeId = $this->storeId();
        $list = Store::select(['id', 'name', 'sort', 'uniacid', 'payChange', 'updated_at', 'isolate'])
            ->where('uniacid', $this->uniacid())
            ->when($storeId, function ($q) use ($storeId) {
                return $q->where("id", $storeId);
            })
            ->when($request->name, function ($q) use ($request) {
                return $q->where('name', 'like', "%$request->name%");
            })
            ->when($user, function ($q) use ($user) {
                if ($user->isAdmin == 0) {
                    if (empty($user->storeId)) {
                        $q->where('id', 0);
                    } else {
                        $q->whereIn('id', $user->storeId);
                    }
                }
                return $q;
            })
            ->orderBy('sort', 'asc')
            ->paginate($request->pageSize ?? 20, '*', 'pageNo');
        return $this->success(new StoreResources($list));
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $info = Apply::select(['id', 'payChange'])->find($this->uniacid());
        if (empty($info)) {
            throw new BadRequestException("店铺不存在");
        }
        $store = Store::where('uniacid', $this->uniacid())->where('id', $id)->first();
        if (empty($store)) {
            throw new BadRequestException("门店不存在");
        }
        $model = PayConfig::select(['id', 'payType', 'templateId', 'state', 'channel', 'isDefault'])
            ->where('uniacid', $this->uniacid())
            ->where('storeId', $id)
            ->where('channel', 'mini')
            ->get();
        $data = [
            'isolate' => $store->isolate,
            'pay' => collect($model)->keyBy('payType'),
            'payChange' => $store->payChange,
            'default' => collect($model)->where('isDefault', 1)
                ->first()->payType,
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
            $store = Store::where('uniacid', $this->uniacid())
                ->where('id', $request->storeId)
                ->first();
            if (empty($store)) {
                throw new BadRequestException("门店不存在");
            }
            // if (empty($store->payChange)) {
            //     throw new BadRequestException("禁止修改");
            // }
            DB::beginTransaction();
            foreach ($request->pay as $key => $v) {
                $templateId = 0;
                $model = PayConfig::where('uniacid', $this->uniacid())
                    ->where('channel', $request->channel)
                    ->where('payType', $key)
                    ->where('storeId', $store->id)
                    ->first();
                if ($key == 'weixin' || $key == 'alipay') {
                    if (empty($model) || ($v['data']['mch_id'] != $model->data['mch_id']) || ($v['data']['type'] != $model->data['type'])) {
                        $payTemplate = PayTemplate::create([
                            'data' => $v['data'],
                            'uniacid' => $this->uniacid(),
                            'title' => 'store:' . $store->id . $key,
                            "type" => $v['data']['type'],
                            "channel" => $key,
                            'state' => 1,
                            'storeId' => $store->id
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
                        "state" => 1,
                        "channel" => $request->channel,
                        "payType" => $key,
                        'storeId' => $store->id,
                        'templateId' => $templateId,
                        "isDefault" => $request->default == $key ? 1 : 0
                    ]);
                } else {
                    $model->fill([
                        'uniacid' => $this->uniacid(),
                        "state" => 1,
                        "channel" => $request->channel,
                        "payType" => $key,
                        'templateId' => $templateId,
                        "isDefault" => $request->default == $key ? 1 : 0
                    ]);
                    $model->save();
                }
            }
            $store->payChange = $request->payChange;
            $store->save();
            DB::commit();
            return $this->success([], '支付配置成功');
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            DB::rollBack();
            return $this->failed($e->getMessage());
        }
    }
}
