<?php

namespace App\Http\Controllers\ChannelApi;

use App\Http\Controllers\Controller;
use App\Models\MemberSignIn\MemberSignIn;
use App\Models\MemberSignIn\SignIn;
use App\Services\ConfigService;
use App\Services\MemberAccountService;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use App\Services\CouponService;
use Illuminate\Support\Facades\DB;

class SignInController extends ApiController
{
    public function index(Request $request)
    {
        $row = MemberSignIn::where('uniacid', $this->uniacid())
            ->where('storeId', $this->isolateStore())
            ->where('userId', $this->userId())
            ->first();
        if (empty($row)) {
            $row = new MemberSignIn([
                'uniacid' => $this->uniacid(),
                'userId' => $this->userId(),
                'first' => null,
                'last' => null,
                'total' => 0,
                'continuous' => 0,
                'max' => 0,
                'couponCount' => 0,
                'balance' => 0.00,
                'integral' => 0,
                'storeId' => $this->isolateStore()
            ]);
        }
        $row->setAppends(['today']);
        $y = $request->year;
        $m = $request->month;
        $row->singInList = $row->getSignInLog($y, $m);
        return $this->success($row);
    }

    //签到
    public function store(Request $request)
    {
        $uniacid = $this->uniacid();
        $userId = $this->userId();
        try {
            DB::beginTransaction();
            $memberSignIn = MemberSignIn::where('uniacid', $this->uniacid())->where('userId', $this->userId())->first();
            $count = SignIn::where('uniacid', $this->uniacid())->where('userId', $userId)->count();
            $count = $count + 1;
            $today = date("Y-m-d", time());
            $todaySign = SignIn::where('uniacid', $this->uniacid())->where("userId", $this->userId())->where('day', $today)->first();
            if ($todaySign) {
                return $this->failed('今天已经签到过了');
            }
            $config =  ConfigService::getChannelConfig('singInSetting', $this->uniacid(),$this->isolateStore());
            if ($memberSignIn) {
                $memberSignIn->total = $memberSignIn->total + 1;
                if ($memberSignIn->isLx) {
                    $memberSignIn->last = $today;
                    $memberSignIn->continuous = $memberSignIn->continuous + 1;
                    $memberSignIn->max = $memberSignIn->continuous > $memberSignIn->max ?  $memberSignIn->continuous : $memberSignIn->max;
                } else {
                    $memberSignIn->last = $today;
                    $memberSignIn->continuous = 1;
                    $memberSignIn->max = 1;
                }
                $memberSignIn->save();
            } else {
                $memberSignIn = MemberSignIn::create([
                    "uniacid" => $this->uniacid(),
                    'userId' => $this->userId(),
                    'first' => $today,
                    'last' => $today,
                    'total' => 1,
                    'continuous' => 1,
                    'max' => 1,
                    'couponCount' => 0,
                    'balance' => 0,
                    'storeId'=>$this->isolateStore()
                ]);
            }
            if ($config && $config['switch'] == 1) {
                if ($config['daily']['switch'] == 1) {
                    $daily = $config['daily'];
                }
                if ($config['seriesSwitch'] == 1) {
                    $plusRewards = collect($config['series'])->where('days', $memberSignIn->continuous)->first();
                }
            }
            SignIn::create([
                'uniacid' => $uniacid ?: 0,
                'userId' => $userId,
                'day' => $today,
                'storeId'=>$this->isolateStore(),
                'daily' => collect($daily ?? [])->toArray(),
                'plusRewards' => collect($plusRewards ?? [])->toArray(),
            ]);
            DB::commit();
            return $this->success([], '签到成功');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failed($e->getMessage());
        }
    }
}
