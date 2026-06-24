<?php

namespace App\Http\Controllers\ChannelApi\Drinks;

use App\Http\Controllers\ChannelApi\ApiController;
use App\Http\Requests\Admin\ChangePassword;
use App\Http\Resources\ChannelApi\User\Profix;
use App\Models\Ad;
use App\Models\Admin;
use App\Models\Drinks\Log;
use App\Models\Drinks\Order;
use App\Models\Member;
use App\Models\Member\Address;
use App\Models\MemberAddress;
use App\Models\MemberBind;
use App\Models\Printer;
use App\Models\PrinterLog;
use App\Models\UserAccount;
use App\Services\MenuService;
use App\Services\Print\DaquContent;
use App\Services\Print\JiaboContent;
use App\Services\Print\SpyunContent;
use App\Services\UserService;
use Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Tymon\JWTAuth\Facades\JWTAuth;

class OrderController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $storeId = $this->storeId();
        $user = $this->user();
        $list = Order::where('uniacid', $this->uniacid())->with([
            'user', 'drink', 'store'
        ])
            ->where('storeId', $storeId)
            ->where('userId', $this->userId())
            ->when($request->keyword, function ($q) use ($request) {
                return $q->where(function ($q) use ($request) {
                    return $q->where("contacts", "like", "%{$request->keyword}%")
                        ->orWhere("mobile", "like", "%{$request->keyword}%")
                        ->orWhere("orderSn", "like", "%{$request->keyword}%");
                });
            })
            ->when($request->mobile, function ($q) use ($request) {
                return $q->where("mobile", "like", "%{$request->mobile}%");
            })->when($request->state, function ($q) use ($request) {
                if ($request->state == 'start') {
                    return $q->where('state', 1);
                } elseif ($request->state == 'expired') {
                    return $q->where('state', 3);
                } elseif ($request->state == 'over') {
                    return $q->where('state', 2);
                } else {
                    return $q;
                }
            })
            ->orderBy('id', 'desc')
            ->paginate(Request()->size ?? 20, '*', 'page');
        return $this->success($list);
    }

    public function show(Request $request, $id)
    {
        $model = Order::where('uniacid', $this->uniacid())
            ->with([
                'user', 'drink', 'store'
            ])
            ->where('userId', $this->userId())
            ->find($id);
        if (!$model) {
            return $this->failed('数据不存在');
        }
        return $this->success($model);
    }


}
