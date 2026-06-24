<?php

namespace App\Http\Controllers\Channel;

use App\Models\LuckyWheel;
use App\Models\LuckyWheelLog;
use App\Models\VoiceMessage;
use App\Services\DataSeederService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class LuckyWheelRecordController extends ApiController
{

    public function records(Request $request)
    {
        // 获取查询参数
        $userInfo = $request->input('userInfo');
        $lucky = $request->input('lucky');
        $state = $request->input('state');

        $query = LuckyWheelLog::where('uniacid', $this->uniacid());
        // 用户名称或手机号
        if (!empty($userInfo)) {
            $query->whereHas('user', function ($q) use ($userInfo) {
                $q->where('nickname', 'like', "%{$userInfo}%")
                    ->orWhere('mobile', 'like', "%{$userInfo}%");
            });
        }
        // 中奖状态
        if ($lucky != null) {
            $query->where('reward_name', $lucky == 0 ? '=' : '!=', '谢谢参与');
        }

        // 根据发放状态过滤
        if (!is_null($state)) {
            $query->where('state', $state);
        }
        // 分页
        $list = $query->orderBy('created_at', 'desc')
            ->paginate($request->pageSize ?? 20, '*', 'pageNo');

        return $this->success($list);
    }

    public function update(Request $request, $id)
    {

        try {
            $model = LuckyWheelLog::where('uniacid', $this->uniacid())
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

    public function destroy(Request $request, $id)
    {
        try {
            $idArray = array_filter(explode(',', $id), function ($item) {
                return is_numeric($item);
            });
            $models = LuckyWheelLog::where('uniacid', $this->uniacid())->whereIn('id', $idArray)->get();
            foreach ($models as $key => $model) {
                $model->delete();
            }
            return $this->success([], '删除成功');
        } catch (\Exception $e) {
            return $this->failed('删除失败');
        }
    }

}
