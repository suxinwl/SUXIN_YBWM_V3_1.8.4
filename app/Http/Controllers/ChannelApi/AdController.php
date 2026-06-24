<?php

namespace App\Http\Controllers\ChannelApi;

use App\Http\Requests\Admin\ChangePassword;
use App\Http\Resources\ChannelApi\User\Profix;
use App\Models\Ad;
use App\Models\Admin;
use App\Models\Member;
use App\Models\Member\Address;
use App\Models\MemberAddress;
use App\Models\MemberBind;
use App\Models\UserAccount;
use App\Services\MenuService;
use App\Services\UserService;
use Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AdController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $userId = $this->userId();
        $rule = $this->user()->rule;
        $storeId = $request->storeId;
        $list = Ad::where("uniacid", $this->uniacid())
            ->where('storeId',$this->isolateStore())
            ->where('location', $request->location)
            ->where("startTime", "<=", date("Y-m-d H:i:s", time()))
            ->where("endTime", ">=", date("Y-m-d H:i:s", time()))
            ->where(function ($q) use ($rule) {
                return $q->where("userType", 1)->orWhere('userType', $rule);
            })
            ->orderBy("sort", "asc")
            ->orderBy("endTime", "asc")
            ->orderBy("id", "desc")
            ->get();
        $list = collect($list)->filter(function ($v, $key) use ($userId, $storeId) {
            if ($v->location == 2) {
                if ($v->storeType == 2 && !in_array($storeId, $v->storeIds)) {
                    return false;
                }
                if ($v->storeType == 3 && in_array($storeId, $v->storeIds)) {
                    return false;
                }
            }
            if ($v->type == 1) {
                if ($v->countType == 1) {
                    $key = "ad:" . $v->id . $v->type . $userId . date("d", time());
                }
                if ($v->countType == 2) {
                    $key = "ad:" . $v->id . $userId;
                }
                $num = Cache::get($key);
                if (($v->count == 0 || $num < $v->count)) {
                    $v->count == 0 ? Cache::set($key, 0) : Cache::set($key, $num + 1);
                    return true;
                }
                return false;
            }
            return true;
        })->groupBy('typeKey')->map(function ($item, $key) {
            return collect($item)->pluck("data")->flatten(1);
        })->all();
        return $this->success($list);
    }
}
