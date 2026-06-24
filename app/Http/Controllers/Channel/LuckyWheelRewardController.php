<?php

namespace App\Http\Controllers\Channel;

use App\Http\Controllers\Controller;
use App\Imports\MemberJobImport;
use App\Models\LuckyWheelLog;
use App\Models\LuckyWheelReward;
use App\Models\LuckyWheel;
use App\Models\Member\Job;
use App\Models\MemberAccount;
use App\Models\Wechat\Kernel\Exceptions\BadRequestException;
use App\Services\MemberAccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class LuckyWheelRewardController extends ApiController
{


    public function index(Request $request)
    {
        $list = LuckyWheelReward::where('uniacid', $this->uniacid())->orderBy('created_at', 'desc')
            ->paginate($request->pageSize ?? 20, '*', 'pageNo');
        return $this->success($list);
    }

    public function awardsForUser()
    {
        $list = LuckyWheelReward::where('uniacid', $this->uniacid())
            ->orderBy('created_at', 'desc')
            ->get();
        return $this->success($list);
    }

    public function store(Request $request)
    {
        try {
            // 检查是否已经有 6 条或更多记录
            $count = LuckyWheelReward::where('uniacid', $this->uniacid())->count();
            if ($count >= 6) {
                return $this->failed('添加失败，已达到最大数量限制');
            }
            $model = new LuckyWheelReward();
            $model->fill($request->all());
            $model->uniacid = $this->uniacid();
            $model->stock = $model->count;
            $model->save();
            return $this->success([], '添加成功');
        } catch (\Exception $e) {
            return $this->failed('添加失败' . $e->getMessage());
        }
    }

    public function show(Request $request, $id)
    {
        $model = LuckyWheelReward::where('uniacid', $this->uniacid())->find($id);
        if (empty($model)) {
            throw new BadRequestException('数据不存在');
        }
        return $this->success($model);
    }

    public function update(Request $request, $id)
    {
        try {
            $model = LuckyWheelReward::where('uniacid', $this->uniacid())->find($id);
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
            $models = LuckyWheelReward::where('uniacid', $this->uniacid())->whereIn('id', $idArray)->get();
            foreach ($models as $key => $model) {
                $model->delete();
            }
            return $this->success([], '删除成功');
        } catch (\Exception $e) {
            return $this->failed('删除失败');
        }
    }
}
