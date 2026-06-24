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
use App\Models\UserAccount;
use App\Services\ConfigService;
use App\Services\MenuService;
use App\Services\UserService;
use Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Tymon\JWTAuth\Facades\JWTAuth;

class LogController extends ApiController
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
        $list = Log::where('uniacid', $this->uniacid())
            ->with(['user', 'drink'])
            ->where('storeId', $storeId)
            ->when($request->contacts, function ($q) use ($request) {
                return $q->where("contacts", "like", "%{$request->name}%");
            })
            ->when($request->mobile, function ($q) use ($request) {
                return $q->where("mobile", "like", "%{$request->mobile}%");
            })->when($request->state, function ($q) use ($request) {
                if ($request->state == 'deposit') {
                    return $q->where('type', 1);
                } elseif ($request->state == 'fetch') {
                    return $q->where('type', 2);
                } else {
                    return $q;
                }
            })->when($request->drinksOrderId, function ($q) use ($request) {
                return $q->where('drinksOrderId', $request->drinksOrderId);
            })
            ->orderBy('id', 'desc')
            ->paginate(Request()->size ?? 20, '*', 'page');
        return $this->success($list);
    }

    public function store(Request $request)
    {
        try {
            $config = ConfigService::getChannelConfig('wineStock', $this->uniacid());
            if ($config['switch'] == 0) {
                return $this->failed('禁止取酒');
            }
            $model = new Log();
            $model->fill($request->all());
            $model->uniacid = $this->uniacid();
            $model->storeId = $this->storeId();
            $model->type = 2;
            if ($model->order->state == 2) {
                return $this->failed('该存酒批次已过期');
            }
            if ($model->order->residue < $model->num) {
                return $this->failed('取酒数量超出可取数量');
            }
            if ($config['takeSwitch'] == 1) {
                $model->state = 0;
                $model->save();
                return $this->success([], '取酒成功,等待门店审核');
            } else {
                $model->order->residue = $model->order->residue - $model->num;
                if ($model->order->residue == 0) {
                    $model->order->state = 2;
                }
                $model->residue = $model->order->residue;
                $model->save();
                $model->order->save();
            }
            return $this->success([], '取酒成功');
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }
}
