<?php

namespace App\Http\Controllers\Channel\Store;

use App\Http\Controllers\Channel\ApiController;
use App\Http\Requests\Channel\BalanceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Auth;
use App\Models\ShopAccount;
use App\Models\Shop;
use App\Http\Requests\Channel\ShopAccountRequest;
use App\Http\Resources\Channel\StoreAccount\StoreAccountResources;
use App\Models\Store\Account;
use App\Models\Store\AccountLog;
use App\Services\StoreAccountService;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class AccountController extends ApiController
{
    public function index(Request $req)
    {
        $storeId = $this->storeId();
        $user = $this->user();
        $data = Account::with(['store'])
            ->when($storeId, function ($q) use ($storeId) {
                return $q->where("storeId", $storeId);
            })
            ->when($user, function ($q) use ($user) {
                if ($user->isAdmin == 0) {
                    if (empty($user->storeId)) {
                        $q->where('storeId', 0);
                    } else {
                        $q->whereIn('storeId', $user->storeId);
                    }
                }
                return $q;
            })
            ->where('uniacid', $this->uniacid())
            ->orderBy('storeId', 'asc')
            ->paginate($req->pageSize ?? 30, '*', 'pageNo');
        return $this->success(new StoreAccountResources($data));
    }

    public function show(Request $request, $id)
    {
        $data = Account::find($id);
        return $this->success($data);
    }


    public function update(Request $request, $id)
    {
        $account = Account::where('storeId', $id)->first();
        if (empty($account)) {
            throw new BadRequestException('门店账户不存在');
        }
        if ($request->withdrawalConfig) {
            $account->withdrawalConfig = $request->withdrawalConfig;
        }
        if ($request->rateConfig) {
            $account->rateConfig = $request->rateConfig;
        }
        $account->save();
        return $this->success();
    }

    public function log(Request $request)
    {
        $channel = 1;
        $storeId = $this->storeId();
        $user = $this->user();
        $list =  AccountLog::with(['store' => function ($q) {
            return $q->select(['id', 'name'])->withTrashed();
        }])->where('uniacid', $this->uniacid())
            ->where("channel", $channel)
            ->when($storeId, function ($q) use ($storeId) {
                return $q->where("storeId", $storeId);
            })
            ->when($user, function ($q) use ($user) {
                if ($user->isAdmin == 0) {
                    if (empty($user->storeId)) {
                        $q->where('storeId', 0);
                    } else {
                        $q->whereIn('storeId', $user->storeId);
                    }
                }
                return $q;
            })
            ->where(function ($q) use ($request) {
                if ($request->startTime && $request->endTime) {
                    $q->where('created_at', '>=', $request->startTime)->where('created_at', '<=', $request->endTime);
                }
                return $q;
            })->orderBy('id', 'desc')->paginate($request->pageSize ?? 10, '*', 'pageNo');
        return $this->success($list);
    }

    /**
     * 改变余额
     */
    public function change(BalanceRequest $request, $id)
    {
        $account = Account::where('storeId', $id)->first();
        if (empty($account)) {
            throw new BadRequestException('门店账户不存在');
        }
        $res = StoreAccountService::changeBalance(intval($id), intval($request->type), $request->value, AccountLog::AMOUNT_BASE, $this->userId(), $request->notes);
        if ($res) {
            return $this->success([], '门店余额调整成功');
        }
        return $this->failed([], '门店余额调整失败');
    }
}
