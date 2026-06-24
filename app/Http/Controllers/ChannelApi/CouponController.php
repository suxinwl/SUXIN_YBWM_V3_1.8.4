<?php

namespace App\Http\Controllers\ChannelApi;

use App\Http\Requests\Admin\ChangePassword;
use App\Http\Resources\ChannelApi\User\Profix;
use App\Models\Ad;
use App\Models\Admin;
use App\Models\Coupon\Coupon;
use App\Models\Coupon\MemberCoupon;
use App\Models\Coupon\Regift;
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
use App\Models\Douyin;

class CouponController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $list = Coupon::where("uniacid", $this->uniacid())
            ->where('channel', 1)
            ->where("startTime", '<=', date("Y-m-d H:i:s", time()))
            ->where("endTime", '>=', date("Y-m-d H:i:s", time()))
            ->paginate($request->size ?? 10, '*', 'page');
        return $this->success($list);
    }

    public function store(Request $request)
    {
        CouponService::receive($request->couponId, $this->userId());
        return $this->success([], '领取成功，请到"我的优惠券"查看');
    }

    public function qrcode(Request $request, $id)
    {
        $coupon = MemberCoupon::where('uniacid', $this->uniacid())
            ->where('userId', $this->userId())
            ->where('id', $id)
            ->first();
        if (empty($coupon)) {
            throw new BadRequestException('优惠券不存在');
        }
        $img =  QrCode::format('png')->size(200)->generate($id);    //format 是指定生成文件格式  默认格式是svg,可以直接在浏览器打开，png不能直接显示
        $code_url = 'data:image/png;base64,' . base64_encode($img);
        return $this->success($code_url);
    }
}
