<?php

namespace App\Http\Controllers\Channel;

use App\Models\LuckyWheel;
use App\Models\LuckyWheelLog;
use App\Models\VoiceMessage;
use App\Services\DataSeederService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class LuckyWheelController extends ApiController
{

    public function index(Request $request)
    {
        $data = LuckyWheel::where('uniacid', $this->uniacid())->first();
        if (!$data) {
            DataSeederService::applyLuckyWheelSeed($this->uniacid());
            $data = LuckyWheel::where('uniacid', $this->uniacid())
                ->first();
        }
        return $this->success($data);
    }

    public function update(Request $request, $id)
    {
        try {
            $model = LuckyWheel::where('uniacid', $this->uniacid())
                ->find($id);
            if (empty($model)) {
                throw new BadRequestException('数据不存在');
            }
            $model->fill($request->all());
            $model->uniacid = $this->uniacid();
            $model->save();
            return $this->success([], '保存成功');
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }

}
