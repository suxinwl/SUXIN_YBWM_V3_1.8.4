<?php

namespace App\Http\Controllers\Channel;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\HandeLog\HandeListCollection;
use Illuminate\Http\Request;
use App\Models\Admin\HandleLog;

class HandleLogController extends ApiController
{
    public function index(Request $req, HandleLog $handLog_model)
    {
        $type = $req->type ?: 1;
        $handLog_model = $handLog_model->where('uniacid', $this->uniacid())->where('type', $type);
        if (!empty($req->keyword)) {
            $handLog_model = $handLog_model->where('username', 'like', '%' . $req->keyword . '%');
        }
        if (!empty($req->startTime) && !empty($req->endTime)) {
            $handLog_model = $handLog_model->where('created_at', '>=', $req->startTime)
                ->where('created_at', '<=', $req->endTime);
        }
        $data = $handLog_model->orderByDesc('id')->paginate($req->pageSize ?? 30, '*', 'pageNo');
        return $this->success(new HandeListCollection($data));
    }
}
