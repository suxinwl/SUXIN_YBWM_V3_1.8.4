<?php

namespace App\Http\Controllers\Channel;

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
use App\Models\MemberBind;
use App\Models\Shop;
use App\Models\ShopAccount;
use App\Models\ShopAccountLog;
use App\Models\ShopWithdrawal;
use App\Models\Store\Account;
use App\Models\Store\AccountLog;
use App\Models\StoreWithdrawal;
use App\Services\ConfigService;
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
        $storeId = $this->storeId();
        $user = $this->user();
        $timeArr = $this->timeArr(true);
        $list = StoreWithdrawal::where('uniacid', $this->uniacid())
            ->when($storeId, function ($q) use ($storeId) {
                $q->where('storeId',  $storeId);
            })
//            ->when($user, function ($q) use ($user) {
//                if ($user->isAdmin == 0) {
//                    if (!empty($user->storeId)) {
//                        $q->whereIn('storeId', $user->storeId);
//                    }
//                }
//                return $q;
//            })
            ->where('created_at', '>=', $timeArr['startTime'])
            ->where('created_at', '<=', $timeArr['endTime'])
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
            })->paginate($request->pageSize ?? 10, '*', 'pageNo');
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
            $model = new StoreWithdrawal();
            $model->storeId = $this->storeId();
            $model->uniacid = $this->uniacid();
            $model->withdrawalMoney = $request->withdrawalMoney;
            $model->withdrawalConfig = $model->getWithdrawalConfig();
            $model->withdrawalType = $model->getWithdrawalType();
            $model->rateConfig = $model->getRateConfig();
            $model->serviceMoney = $model->getServiceMoney();
            $model->money = $model->getMoney();
            $model->state = 0;
            $model->type = $request->type;
            $model->channel = 0;
            $model->save();
            if (!StoreAccountService::withdrawalApply($model->storeId, $model->withdrawalMoney, $this->userId(), $this->user()->realName ?? $this->user()->username)) {
                DB::rollBack();
                return $this->failed('申请提现失败');
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
        $model = StoreWithdrawal::where('uniacid', $this->uniacid())->where('storeId', $this->storeId())->find($id);
        if (empty($model)) {
            return $this->failed('数据不存在');
        }
        return $this->success($model);
    }

    /**
     * 取消提现
     */
    public function cancel($id)
    {
        DB::beginTransaction();
        try {
            $model = StoreWithdrawal::where('uniacid', $this->uniacid())->where('storeId', $this->storeId())->where('state', 0)->find($id);
            if (empty($model)) {
                return $this->failed('数据不存在');
            }
            if (!StoreAccountService::withdrawalDown($model->storeId, $model->withdrawalMoney, AccountLog::WITHDRAWAL_CANCEL, $this->userId(), $this->user()->realName ?? $this->user()->username)) {
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

    public function online($id)
    {
        DB::beginTransaction();
        try {
            if ($this->user()->isAdmin != 1) {
                throw new BadRequestException('无操作权限');
            }
            $model = StoreWithdrawal::where('uniacid', $this->uniacid())->where("state", 0)->find($id);
            if (empty($model)) {
                throw new BadRequestException('数据不存在');
            }
            if ($model->type != 1) {
                throw new BadRequestException('提现账户不是微信');
            }
            $config = ConfigService::getChannelConfig('paymentSet', $this->uniacid());
            if (empty($config)) {
                throw new BadRequestException('请先设置打款账户');
            }
            $order['app_id'] =  WechatPay::getAppId($model->uniacid);
            $order['amount'] = $model->money;
            $order['openid'] = $model->withdrawalConfig['userId'];
            $order['userName'] = $model->withdrawalConfig['realname'];
            if (!PayService::withdrawal($order, $model->uniacid, 0)) {
                throw new BadRequestException('打款失败');
            }
            if (!StoreAccountService::withdrawalPass($model->storeId, $model->withdrawalMoney, $this->userId(), $this->user()->realName ?? $this->user()->username)) {
                return $this->failed('线上打款失败');
            }
            $model->state = 1;
            $model->channel = 1;
            if ($model->save()) {
                DB::commit();
                return $this->success([], '线上打款成功');
            }
            DB::rollBack();
            return $this->failed('线上打款失败');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failed($e->getMessage());
        }
    }


    /**
     * 线下打款
     */
    public function offline($id)
    {
        DB::beginTransaction();
        try {
            if ($this->user()->isAdmin != 1) {
                throw new BadRequestException('无操作权限');
            }
            $model = StoreWithdrawal::where('uniacid', $this->uniacid())->where("state", 0)->find($id);

            if (empty($model)) {
                throw new BadRequestException('数据不存在');
            }
            if (!StoreAccountService::withdrawalPass($model->storeId, $model->withdrawalMoney, $this->userId(), $this->user()->realName ?? $this->user()->username)) {
                return $this->failed('线下打款失败');
            }
            $model->state = 1;
            $model->channel = 2;
            if ($model->save()) {
                DB::commit();
                return $this->success([], '线下打款成功');
            }
            DB::rollBack();
            return $this->failed('线下打款失败');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failed($e->getMessage());
        }
    }

    public function  reject(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            if ($this->user()->isAdmin != 1) {
                throw new BadRequestException('无操作权限');
            }
            $model = StoreWithdrawal::where('uniacid', $this->uniacid())->where("state", 0)->find($id);
            if (empty($model)) {
                throw new BadRequestException('数据不存在');
            }
            if (!StoreAccountService::withdrawalDown($model->storeId, $model->withdrawalMoney, AccountLog::WITHDRAWAL_REFUSE, $this->userId(), $this->user()->realName ?? $this->user()->username)) {
                return $this->failed('驳回失败');
            }
            $model->state = 2;
            $model->notes = $request->notes ?? "提现驳回";
            if ($model->save()) {
                DB::commit();
                return $this->success();
            }
            DB::rollBack();
            return $this->failed('驳回失败');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failed($e->getMessage());
        }
    }
}
