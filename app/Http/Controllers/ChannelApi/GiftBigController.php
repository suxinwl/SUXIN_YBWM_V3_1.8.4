<?php

namespace App\Http\Controllers\ChannelApi;

use App\Http\Requests\Admin\ChangePassword;
use App\Http\Resources\ChannelApi\User\Profix;
use App\Models\Ad;
use App\Models\Admin;
use App\Models\Coupon\Coupon;
use App\Models\Coupon\MemberCoupon;
use App\Models\Coupon\Regift;
use App\Models\GiftBig\GiftBig;
use App\Models\Member;
use App\Models\Member\Address;
use App\Models\MemberAddress;
use App\Models\MemberBind;
use App\Models\UserAccount;
use App\Services\CouponService;
use App\Services\MenuService;
use App\Services\UserService;
use Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Tymon\JWTAuth\Facades\JWTAuth;

class GiftBigController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $userId = $this->userId();
        $uniacid = $this->uniacid();
        if (!$this->user()->mobile) {
            $model =  GiftBig::where('uniacid', $this->uniacid())
                ->where('storeId', $this->isolateStore())
                ->whereDoesntHave('receives', function ($q) use ($uniacid, $userId) {
                    return $q->where('userId', $userId)->where('uniacid', $uniacid);
                })
                ->where('uniacid', $uniacid)
                ->where('startTime', '<=', date("Y-m-d H:i:s"))
                ->where('endTime', '>=', date("Y-m-d H:i:s"))
                ->first();
            if ($model) {
                $model->setAppends([
                    'stateFormat', 'couponList'
                ]);
            }
            return $this->success($model);
        }
        return $this->success(null);
    }
}
