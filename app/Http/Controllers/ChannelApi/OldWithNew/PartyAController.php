<?php

namespace App\Http\Controllers\ChannelApi\OldWithNew;

use App\Http\Controllers\ChannelApi\ApiController;
use App\Http\Requests\Admin\ChangePassword;
use App\Http\Resources\ChannelApi\OldWithNew\Model;
use App\Http\Resources\ChannelApi\OldWithNew\PartyB as OldWithNewPartyB;
use App\Http\Resources\ChannelApi\User\Profix;
use App\Models\Ad;
use App\Models\Admin;
use App\Models\Coupon\MemberCoupon;
use App\Models\Member;
use App\Models\Member\Address;
use App\Models\MemberAddress;
use App\Models\MemberBind;
use App\Models\OldWithNew\Activity;
use App\Models\OldWithNew\PartyA;
use App\Models\OldWithNew\PartyB;
use App\Models\UserAccount;
use App\Services\MenuService;
use App\Services\UserService;
use Cache;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Intervention\Image\Facades\Image;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Tymon\JWTAuth\Facades\JWTAuth;

class PartyAController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $activityId = $request->activityId;
        $model = Activity::where('uniacid', $this->uniacid())
            ->where('storeId', $this->isolateStore())
            ->where(function ($q) use ($activityId) {
                if ($activityId) {
                    return $q->where('id', $activityId);
                } else {
                    return $q->where("startTime", "<=", date("Y-m-d H:i:s", time()))
                        ->where("endTime", ">=", date("Y-m-d H:i:s", time()));
                }
            })->first();
        if (!$model) {
            return $this->failed('活动已结束');
        }
        $partyA = PartyA::updateOrCreate([
            'storeId' => $this->isolateStore(),
            'oldWithNewId' => $model->id,
            'uniacid' => $this->uniacid(),
            'userId' => $this->userId()
        ], [
            'storeId' => $this->isolateStore(),
            'oldWithNewId' => $model->id,
            'uniacid' => $this->uniacid(),
            'userId' => $this->userId()
        ]);
        return $this->success([
            'activity' => new Model($model),
            'partyA' => $partyA->toArray(),
        ]);
    }

    public function shear(Request $request, $id)
    {
        $model = Activity::where('uniacid', $this->uniacid())

            ->where("startTime", "<=", date("Y-m-d H:i:s", time()))
            ->where("endTime", ">=", date("Y-m-d H:i:s", time()))
            ->where('id', $id)
            ->first();
        if (!$model) {
            return $this->failed('活动已结束');
        }
        Image::configure(['driver' => 'gd']);
        $img = Image::canvas(750, 1080, '#FFFFFF');
        $user = new Profix($this->user());
        $user = collect($user)->toArray();
        //用户
        $avatar = Image::make($user['avatar'])->resize(100, 100);
        // $new = Image::canvas(100, 100);
        // $r = $img->width() / 2;
        // for ($x = 0; $x < $img->width(); $x++) {
        //     for ($y = 0; $y < $img->height(); $y++) {
        //         $c = $img->pickColor($x, $y, 'array');
        //         if (((($x - $r) * ($x - $r) + ($y - $r) * ($y - $r)) < ($r * $r))) {
        //             $new->pixel($c, $x, $y);
        //         }
        //     }
        // }
        // $avatar  = $new;
        $img->insert($avatar, 'top-left', 20, 20);
        $img->text($user['nickname'],  125, 85, function ($font) {
            $font->file(public_path() . '/storage/default/kt.ttf');
            $font->size(30);
            $font->color('#000');
            $font->align('left');
        });
        $centerImg = collect($model->shearPage['image'])->first();
        $centerImg = is_array($centerImg) ? $centerImg[0] : $centerImg;
        $centerImg = Image::make($centerImg)->resize(750, 750);
        $img->insert($centerImg, 'top-left', 0, 125);
        $url = Request()->getSchemeAndHttpHost() . "/s/oldWithNew/" . $this->uniacid() . "/?activityId=$model->id&partyAid=" . $this->userId();
        $qrCode = QrCode::format('png')->size(100)->generate($url);
        $qrCode = "data:image/png;base64," . base64_encode($qrCode);
        $img->insert($qrCode, 'top-left', 20, 900);
        $img->text("长按识别小程序码帮助好友助力",  150, 950, function ($font) {
            $font->file(public_path() . '/storage/default/kt.ttf');
            $font->size(30);
            $font->color('#000');
            $font->align('left');
            $font->valign('top');
        });
        return $this->success('data:image/jpg;base64,' . base64_encode($img->encode('jpg', 50)));
    }

    public function recordA(Request $request, $id)
    {
        $model = Activity::where('uniacid', $this->uniacid())
            ->where('storeId', $this->isolateStore())
            ->find($id);
        if (!$model) {
            return $this->failed('活动已结束');
        }
        $couponList = MemberCoupon::select('*')
            ->addSelect(DB::raw('IFNULL(sum(if(channel=18,1,0)),0) as num'))
            ->where('uniacid', $this->uniacid())
            ->where('userId', $this->userId())
            ->where('channel', 18)
            ->where('source', 'oldWithNew:PartyA:' . $model->id)
            ->groupBy('couponId', 'channel')
            ->orderBy('id', 'desc')
            ->get();
        $partyA =  PartyA::where('uniacid', $this->uniacid())
            ->where('storeId', $this->isolateStore())
            ->where('userId', $this->userId())
            ->where('oldWithNewId', $model->id)
            ->first();
        $user =  PartyB::with(['user'])->where('uniacid', $this->uniacid())
            ->where('storeId', $this->isolateStore())
            ->where('partyAid', $this->userId())
            ->where('oldWithNewId', $model->id)
            ->get();
        return $this->success(['activity' => $model, 'partyA' => $partyA, 'couponList' => $couponList, 'partyBList' => $user]);
    }

    public function recordB(Request $request, $id)
    {
        $model = Activity::where('uniacid', $this->uniacid())
            ->where('storeId', $this->isolateStore())
            ->find($id);
        if (!$model) {
            return $this->failed('活动已结束');
        }
        $user =  PartyB::with(['user'])->where('uniacid', $this->uniacid())
            ->where('userId', $this->userId())
            ->where('oldWithNewId', $model->id)
            ->first();
        return $this->success(['activity' => $model, 'partyB' => new OldWithNewPartyB($user)]);
    }
}
