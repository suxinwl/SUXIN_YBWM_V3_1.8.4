<?php

namespace App\Http\Controllers\ChannelApi;

use App\Http\Requests\Admin\ChangePassword;
use App\Http\Requests\Withdrawal\WithdrawalConfig;
use App\Models\Admin;
use App\Models\Config;
use App\Models\StaffShop;
use App\Services\MenuService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\Admin\AdminResource\Admin as AdminResource;
use App\Models\Admin\Apply;
use App\Models\MemberAccount;
use App\Models\MemberAccountLog;
use App\Models\MemberBind;
use App\Models\Shop;
use App\Models\ShopAccount;
use App\Models\ShopAccountLog;
use App\Models\ShopWithdrawal;
use App\Models\Store\Account;
use App\Models\Store\AccountLog;
use App\Models\StoreWithdrawal;
use App\Models\UserWithdrawal;
use App\Services\ConfigService;
use App\Services\MemberAccountService;
use App\Services\Pay\WechatPay;
use App\Services\PayService;
use App\Services\ShopAccountService;
use App\Services\StoreAccountService;
use App\Traits\StatisticsTrait;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class WithdrawalController extends ApiController
{
    use StatisticsTrait;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $userId = $this->userId();
        $user = $this->user();
        $timeArr = $this->timeArr(true);
        $list = UserWithdrawal::where('uniacid', $this->uniacid())
            ->where('storeId', $this->storeId())
            ->select(['userId', 'withdrawalConfig', 'withdrawalMoney', 'serviceMoney', 'money', 'state', 'notes', 'uniacid', 'created_at', 'updated_at', 'deleted_at', 'lastTime', 'type', 'channel', 'withdrawalType'])
            ->where('userId',  $userId)
            ->where(function ($q) use ($request) {
                if ($request->state == 'review') {
                    $q->review();
                }
                if ($request->state == 'pass') {
                    $q->pass();
                }
                if ($request->state == 'reject') {
                    $q->reject();
                }
                if ($request->state == 'cancel') {
                    $q->cancel();
                }
                if ($request->startTime && $request->endTime) {
                    $q->where('created_at', '>=', $request->startTime)->where('created_at', '<=', $request->endTime);
                }
                return $q;
            })
            ->paginate($request->size ?? 10, '*', 'page');
        return $this->success($list);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $account = MemberAccount::where('userId', $this->userId())->first();
            if (!$account) {
                return $this->failed('用户账户不存在');
            }
            $account->withdrawalConfig = $request->withdrawalConfig;
            $account->save();
            $model = new UserWithdrawal();
            $model->userId = $this->userId();
            $model->uniacid = $this->uniacid();
            $model->storeId = $this->isolateStore();
            $model->withdrawalMoney = $request->withdrawalMoney;
            $model->withdrawalConfig = $model->getWithdrawalConfig();
            $model->withdrawalType = $model->getWithdrawalType();
            $model->rateConfig = $model->getRateConfig();
            $model->serviceMoney = $model->getServiceMoney();
            $model->money = $model->getMoney();
            $model->state = 0;
            $model->type = 1;
            $model->channel = 0;
            $model->save();
            if (!MemberAccountService::withdrawalApply($model->userId, $model->withdrawalMoney, $this->userId())) {
                DB::rollBack();
                return $this->failed('申请提现失败');
            }
            if ($model->withdrawalType == 'balance') {
                if (!MemberAccountService::changeBalance($model->userId, 1, $model->withdrawalMoney, MemberAccountLog::BALANCE_WITHDRAWAL, $this->userId())) {
                    DB::rollBack();
                    return $this->failed('申请提现失败');
                }
                if (!MemberAccountService::withdrawalPass($model->userId, $model->withdrawalMoney, $this->userId())) {
                    DB::rollBack();
                    return $this->failed('提现失败');
                }
                $model->state = 1;
                $model->save();
            }
            DB::commit();
            return $this->success([], '申请提现成功');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failed($e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $model = StoreWithdrawal::where('uniacid', $this->uniacid())->where('userId', $this->userId())->find($id);
        if (empty($model)) {
            return $this->failed('数据不存在');
        }
        return $this->success($model);
    }

    /**
     * 取消提现
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $model = UserWithdrawal::where('uniacid', $this->uniacid())->where('userId', $this->userId())->where('state', 0)->find($id);
            if (empty($model)) {
                return $this->failed('数据不存在');
            }
            if (!StoreAccountService::withdrawalDown($model->userId, $model->withdrawalMoney)) {
                DB::rollBack();
                return $this->failed('申请提现失败');
            }
            $model->state = 3;
            if ($model->save()) {
                DB::commit();
                return $this->success([], '提现取消成功');
            }
            DB::rollBack();
            return $this->failed('取消提现失败');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failed($e->getMessage());
        }
    }
}
