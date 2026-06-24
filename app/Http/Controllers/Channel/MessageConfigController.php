<?php

namespace App\Http\Controllers\Channel;

use App\Models\ApplyMessage;
use App\Models\MessageConfig;
use App\Services\MessageConfigService;
use App\Services\OpenWechat\ChannelOpenWechat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class MessageConfigController extends ApiController
{
    public function view(Request $request, $id)
    {
        $uniacid = $this->uniacid();
        $model = MessageConfig::where("id", $id)
            ->with(['msg' => function ($q) use ($uniacid) {
                return $q->where('uniacid', $uniacid);
            }])->first();
        return $this->success($model);
    }

    public function index(Request $request, $sendType)
    {
        $channel = $request->channel ?? 1;
        $uniacid = $this->uniacid();
        $list = MessageConfig::where("sendType", $sendType)
            ->where('channel', $channel)
            ->with(['msg' => function ($q) use ($uniacid) {
                return $q->where('uniacid', $uniacid);
            }])->get();
        return $this->success($list);
    }

    public function update(Request $request, $type)
    {
        $model = ApplyMessage::where("uniacid", $this->uniacid())->where("type", $type)->first();
        if (empty($model)) {
            $model = new ApplyMessage();
            $model->uniacid = $this->uniacid();
            $model->type = $request->type;
        }
        $model->notes = $request->notes;
        $model->state = 1;
        $model->save();
        return $this->success();
    }

    public function subMessage(Request $request, $type)
    {
        $uniacid = $this->uniacid();
        $model = MessageConfig::where("type", $type)->with(['msg' => function ($q) use ($uniacid) {
            return $q->where('uniacid', $uniacid);
        }])->first();
        if (empty($model)) {
            throw new BadRequestException('模板数据不存在');
        }
        if (empty($model->msg) || $model->msg->state == 0) {
            if ($model->sendType == 'mini') {
                $tempId = MessageConfigService::addTemplate($model->type, $this->uniacid());
            }
            if ($model->sendType == 'wechat') {
                $tempId = MessageConfigService::addWechatTemplate($model->type, $this->uniacid());
            }
            $model = ApplyMessage::where("uniacid", $this->uniacid())->where("type", $type)->first();
            if (empty($model)) {
                $model = new ApplyMessage();
                $model->uniacid = $this->uniacid();
                $model->type = $type;
                $model->state = 1;
            }
            $model->tempId = $tempId ?? 0;
            $model->state = 1;
            $model->save();
        } else {
            $model->msg->state = 0;
            $model->msg->save();
        }
        return $this->success();
    }
}
