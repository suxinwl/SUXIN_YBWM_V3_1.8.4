<?php

namespace App\Http\Controllers\ChannelApi\OldWithNew;

use App\Events\PartyAEvent;
use App\Events\PartyBEvent;
use App\Http\Controllers\ChannelApi\ApiController;
use App\Http\Requests\Admin\ChangePassword;
use App\Http\Resources\ChannelApi\OldWithNew\Model;
use App\Http\Resources\ChannelApi\User\Profix;
use App\Models\Ad;
use App\Models\Admin;
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
use Event;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Intervention\Image\Facades\Image;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Tymon\JWTAuth\Facades\JWTAuth;

class PartyBController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $partyA = PartyA::where('uniacid', $this->uniacid())
            ->where('userId', $request->partyAid)
            ->first();
        if (!$partyA) {
            return $this->failed('邀请人不存在');
        }
        $partyB = PartyB::where('userId', $this->userId())
            ->where('uniacid', $this->uniacid())
            ->where('oldWithNewId', $partyA->oldWithNewId)
            ->first();
        return $this->success([
            'activity' => new Model($partyA->activity),
            'partyB' => $partyB,
        ]);
    }

    public function store(Request $request)
    {
        $partyA = PartyA::where('uniacid', $this->uniacid())
            ->where('userId', $request->partyAid)
            ->first();
        if (!$partyA) {
            return $this->failed('邀请人不存在');
        }
        if (empty($partyA->activity) || strtotime($partyA->activity->endTime) < time()) {
            return $this->failed('活动已结束');
        }
        $partyB = PartyB::where('userId', $this->userId())
            ->where('uniacid', $this->uniacid())
            ->where('oldWithNewId', $partyA->oldWithNewId)
            ->where('partyAid', $partyA->userId)
            ->first();
        if ($partyB && $partyB->partyBstate == 0) {
            Event(new PartyBEvent($partyB, $partyA, 'partyB'));
        }
        return $this->success();
    }
}
