<?php

namespace App\Http\Controllers\Channel;

use App\Models\MemberSignIn\MemberSignIn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use App\Models\PointSign;
use App\Models\SignList;

class SignListController extends ApiController
{
    public function index(Request $request)
    {
        $list = MemberSignIn::with('member')->where('uniacid', $this->uniacid())
            ->where('storeId', $this->isolateStore())
            ->when($request->keyword, function ($q) use ($request) {
                return $q->where('name', 'like', "%$request->keyword%");
            })
            ->orderBy('id', 'desc')
            ->paginate($request->pageSize ?? 20, '*', 'pageNo');
        return $this->success($list);
    }
}
