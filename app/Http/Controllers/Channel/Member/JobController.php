<?php

namespace App\Http\Controllers\Channel\Member;

use App\Http\Controllers\Channel\ApiController;
use App\Imports\MemberJobImport;
use App\Models\Member\Job;
use App\Models\Member\Vip;
use App\Models\Member\VipPower;
use App\Services\CouponService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class JobController extends ApiController
{

    public function index(Request $request)
    {
        $list = Job::where('uniacid', $this->uniacid())
            ->where('storeId', $this->storeId())
            ->when($request->type, function ($q) use ($request) {
                return $q->where('type', $request->type);
            })
            ->orderBy('id', 'desc')
            ->paginate($request->pageSize ?? 20, '*', 'pageNo');
        return $this->success($list);
    }


    public function store(Request $request)
    {
        try {
            $model = new Job();
            $model->fill($request->all());
            $model->uniacid = $this->uniacid();
            $model->storeId = $this->storeId();
            $model->save();
            if ($model->jobType == 3) {
                Excel::import((new MemberJobImport($this->uniacid(), $model->id)), $request->file);
            }

            return $this->success([], '添加成功');
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }
}
