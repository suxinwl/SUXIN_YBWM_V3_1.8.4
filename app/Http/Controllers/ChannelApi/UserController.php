<?php

namespace App\Http\Controllers\ChannelApi;

use App\Events\BirthdayGiftEvent;
use App\Events\MemberGiftBigEvent;
use App\Events\MemberRegisteredEvent;
use App\Http\Requests\Admin\ChangePassword;
use App\Http\Resources\ChannelApi\User\Profix;
use App\Models\Admin;
use App\Models\Circle;
use App\Models\Comment;
use App\Models\Coupon\MemberCoupon;
use App\Models\Member;
use App\Models\Member\MemberQrCode;
use App\Models\Member\Vip;
use App\Models\MemberAccount;
use App\Models\MemberAccountLog;
use App\Models\MemberBind;
use App\Models\MyCircle;
use App\Models\MyFollow;
use App\Models\OldWithNew\Activity;
use App\Models\OldWithNew\PartyA;
use App\Models\OldWithNew\PartyB;
use App\Models\Partner;
use App\Models\Post;
use App\Models\StatisticsDay;
use App\Models\UserAccount;
use App\Models\UserWithdrawal;
use App\Services\ConfigService;
use App\Services\MemberAccountService;
use App\Services\MenuService;
use App\Services\SmsService;
use App\Services\UserService;
use Event;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return $this->success(new Profix($this->user()));
    }

    public function loadMenus(Request $request, MenuService $menuService)
    {
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $user = member::where('id', $this->userId())->first();
        $user->avatar = $request->avatar ?: $user->avatar;
        $user->nickname = $request->nickname ?: $user->nickname;
        $user->region = $request->region ?: $user->region;
        $user->sex = $request->sex ?: $user->sex;
        $user->realname = $request->realName ?: $user->realname;
        $user->birthday = $request->birthday ?: $user->birthday;
        $birthdaychange = $user->isDirty('birthday');
        $user->save();
        $config = ConfigService::getChannelConfig('birthdayGift', $this->uniacid(), $user->storeId);
        if ($birthdaychange) {
            Event(new BirthdayGiftEvent($user, 'perfect'));
        }
        return $this->success([]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
    }

    public function coupon(Request $request)
    {
        $list = MemberCoupon::select('*')
            ->addSelect(DB::raw('IFNULL(sum(if(state = 1 and deleted_at is null,1,0)),0) as num'))
            ->where('uniacid', $this->uniacid())
            ->where('userId', $this->userId())
            ->when($request->state, function ($q) use ($request) {
                if ($request->state == "normal") {
                    return  $q->where('state', 1);
                } elseif ($request->state == "use") {
                    return  $q->where('state', 2);
                } elseif ($request->state == "overdue") {
                    return  $q->whereIn('state', [0, 3]);
                } else {
                    return $q->where('state', 1);
                }
            })
            ->groupBy('couponId', 'channel')
            ->orderBy('id', 'desc')
            ->paginate($request->size ?? 30, '*', 'page');
        return $this->success($list);
    }

    public function accountLog(Request $request)
    {
        $list = MemberAccountLog::where('uniacid', $this->uniacid())
            ->where("userId", $this->userId())
            ->when($request->state, function ($q) use ($request) {
                if ($request->state == 'storedValue') {
                    return $q->whereIn('behavior', [MemberAccountLog::BALANCE_GIVE, MemberAccountLog::BALANCE_BUY]);
                }
                if ($request->state == 'pay') {
                    return $q->where('type', 0);
                }
                return $q;
            })
            ->where('cat', 'balance')
            ->whereIn('channel', [2, 204])
            ->orderBy('id', 'desc')
            ->paginate($request->size ?? 30, '*', 'page');
        return $this->success($list);
    }

    public function integralLog(Request $request)
    {
        $list = MemberAccountLog::where('uniacid', $this->uniacid())
            ->where("userId", $this->userId())
            ->where('cat', 'integral')
            ->orderBy('id', 'desc')
            ->paginate($request->size ?? 30, '*', 'page');
        return $this->success($list);
    }



    public function qrCode(Request $request)
    {
        MemberQrCode::where('uniacid', $this->uniacid())
            ->where('userId', $this->userId())
            ->delete();
        $data  = MemberQrCode::create(['uniacid' => $this->uniacid(), 'userId' => $this->userId()]);
        return $this->success($data);
    }




    public function changeMobile(Request $request)
    {
        if ($request->code) {
            if (!SmsService::checkCode('register', $request->mobile, $request->code)) {
                throw new  BadRequestException('验证码错误');
            };
        }
        $userExt =  Member::where("uniacid", $this->uniacid())
            ->where("storeId", $this->storeId())
            ->where('mobile', $request->mobile)
            ->where('id', '!=', $this->userId())
            ->first();
        if ($this->user()->mobile  == $request->mobile) {
            $token = JWTAuth::fromUser($this->user());
            return  $this->success(['token' => $token, 'userInfo' => new Profix($this->user())]);
        }
        if ($this->user()->mobile && $userExt) {
            throw new BadRequestException('手机号已绑定其他用户');
        } elseif (!$this->user()->mobile && $userExt) {
            $userBinds = MemberBind::where('userId', $this->userId())->where("storeId", $this->storeId())->update(['userId' => $userExt->id, 'mobile' => $request->mobile]);
            $user = Member::find($this->userId());
            $user->delete();
            $token = JWTAuth::fromUser($userExt);
            $old = Cache::get("userLoginOpenid:" . $this->appType() . ':' . $this->userId());
            Cache::set("userLoginOpenid:" . $this->appType() . ':' . $userExt->id, $old);
            return  $this->success(['token' => $token, 'userInfo' => new Profix($userExt)]);
        } else {
            $user = $this->user();
            $userBinds = MemberBind::where('userId', $user->id)->update(['mobile' => $request->mobile]);
            $user->mobile = $request->mobile;
            $user->vipId = $user->initVip();
            $user->vipCard = getVipCardNo();
            $user->vipCreateTime = date("Y-m-d H:i:s", time());
            $user->save();
            StatisticsDay::where("uniacid", $user->uniacid)
                ->where('storeId', 0)
                ->limit(1)
                ->where("day", Carbon::now()->toDateString())
                ->increment('newMember', 1);
            $token = JWTAuth::fromUser($this->user());
            Event(new MemberRegisteredEvent($user));
            if ($request->partnerId) {
                $partner = Partner::where('uniacid', $this->uniacid())->where('state', 1)->where('userId', $request->partnerId)->first();
                if ($partner) {
                    $config = ConfigService::getChannelConfig('distributor', $this->uniacid(),0);
                    if($config['storeDistribution']!==1){
                        $user->partnerId = $partner->userId;
                        $user->partner_time =date('Y-m-d H:i:s',time());
                        $user->save();
                    }else{
                        $user->partnerId = $partner->userId;
                        $user->partner_time =date('Y-m-d H:i:s',time());
                        $user->save();
                    }

                }
            }
            if ($request->partyAid) {
                $oldWithNew = Activity::where('uniacid', $this->uniacid())
                    ->where('storeId', $this->isolateStore())
                    ->where("startTime", "<=", date("Y-m-d H:i:s", time()))
                    ->where("endTime", ">=", date("Y-m-d H:i:s", time()))
                    ->first();
                $partyA = PartyA::where('uniacid', $this->uniacid())
                    ->where('storeId', $this->isolateStore())
                    ->where('userId', $request->partyAid)
                    ->where('oldWithNewId', $oldWithNew->id)
                    ->first();
                if ($oldWithNew && $partyA) {
                    $partyB = PartyB::create(
                        [
                            'storeId', $this->isolateStore(),
                            'uniacid' => $this->uniacid(),
                            'userId' => $user->id,
                            'oldWithNewId' => $oldWithNew->id,
                            'partyAid' => $partyA->userId,
                        ]
                    );
                    if ($oldWithNew['newGiftSwitch'] == 2) {
                        Event(new MemberGiftBigEvent($user));
                    }
                }
            } else {
                Event(new MemberGiftBigEvent($user));
            }
            return  $this->success(['token' => $token, 'userInfo' => new Profix($user)]);
        }
    }

    public function logout()
    {
        $uniacid = $this->uniacid();
        $openid = $this->user()->getOpenId();
        $userId = $this->userId();
        MemberBind::whereHas('member', function ($q) use ($uniacid) {
            return $q->where('uniacid', $uniacid);
        })->where('openid', $openid)
            ->where('userId', $uniacid)
            ->delete();
        return $this->success('退出成功');
    }


    public function withdrawalConfig(Request $request)
    {
        $model = MemberAccount::where('userId', $this->userId())->first();
        if (!$model) {
            return $this->failed('用户账户不存在');
        }
        $model->withdrawalConfig = $request->withdrawalConfig;
        $model->save();
        return $this->success('提现方式设置成功');
    }
}
