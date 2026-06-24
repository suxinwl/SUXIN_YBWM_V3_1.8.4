<?php

namespace App\Http\Controllers\Channel;

use App\Models\MemberLabel;
use App\Models\Member;
use App\Models\PayConfig;
use Illuminate\Http\Request;

class LabelController extends ApiController
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $list = MemberLabel::withCount(['member'])->where(function ($q) use ($request) {
            $q->where('uniacid', $this->uniacid());
        })->orderBy('sort', 'asc')->paginate($request->pageSize ?? 20, '*', 'pageNo');
        return $this->success($list);
    }

    public function show(Request $request, $id)
    {
        $model = MemberLabel::where('uniacid', $this->uniacid())->find($id);
        if (empty($model)) {
            return $this->failed('数据不存在');
        }
        return $this->success($model);
    }



    public function store(Request $request, MemberLabel $model)
    {
        MemberLabel::create([
            'sort' => $request->sort,
            'title' => $request->title,
            'uniacid' => $this->uniacid(),
            'state' => $request->state ?: 1,
            'isPay' => $request->isPay ?: $model->isPay,
            'payModel' => $request->payModel ?: 0,
            'payNum' => $request->payNum ?: 0,
        ]);
        return $this->success([],'创建成功');
    }


    public function update(Request $request, MemberLabel $model, $id){
        $model = MemberLabel::where('uniacid', $this->uniacid())->find($id);
        if (empty($model)) {
            return $this->failed('数据不存在');
        }
        $model->fill([
            'sort' => intval($request->sort),
            'title' => $request->title,
            'state' => $request->state ?? $model->state,
            'isPay' => $request->isPay ?: $model->isPay,
            'payModel' => $request->payModel ?: 0,
            'payNum' => $request->payNum ?: 0,
        ]);
        $model->updated_at=date('Y-m-d H:i:s',time());
        $model->save();
        Member::setLabel($model);
        return $this->success([]);
    }


    /**
     * 删除用户
     */
    public function destroy($id)
    {
        $idArray = array_filter(explode(',', $id), function ($item) {
            return is_numeric($item);
        });
        MemberLabel::where('uniacid', $this->uniacid())->whereIn('id', $idArray)->delete();
        return $this->success([], '删除成功');
    }


    /**
     * 拉黑/洗白
     */
    public function state(Request $request, $id)
    {
        $model = MemberLabel::where('uniacid', $this->uniacid())->where('id', $id)->first();
        if (!$model) {
            return $this->failed('数据不存在');
        }
        $model->state = $model->state == 1 ? 0 : 1;
        $model->save();
        return $this->success([], '状态调整成功');
    }
}
