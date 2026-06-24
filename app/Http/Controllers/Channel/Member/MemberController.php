<?php

namespace App\Http\Controllers\Channel\Member;

use App\Events\MemberRegisteredEvent;
use App\Exports\ExcelExport;
use App\Http\Requests\Channel\BalanceRequest;
use App\Http\Resources\Channel\Member\MemberList;
use App\Http\Resources\Channel\Member\MemberRequest;

use App\Jobs\ImportMembersJob;
use App\Listeners\Member\ImportListener;
use App\Models\Member;
use App\Models\MemberAccountLog;
use App\Models\MemberBind;
use App\Services\MemberAccountService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MemberDataExport;
use App\Http\Controllers\Channel\ApiController;
use App\Http\Requests\Member\MemberForm;
use App\Jobs\CreateSyncWechatJob;
use App\Models\Member\MemberQrCode;
use App\Models\Member\Vip;
use App\Models\StatisticsDay;
use App\Services\ExcelService;
use App\Services\OpenWechat\ChannelOpenWechat;
use App\Services\SwooleJobService;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class MemberController extends ApiController
{
    //选择用户绑定
    public function bindUser(Request $request)
    {
        $query = new Member();
        $query->with(['member']);
        $data = $query->whereHas('memberBind', function ($query) {
            $query->where('type', 2);
        })->where(function ($q) use ($request) {
            if ($request->keyword) {
                $q->orWhere('realname', "like", "%$request->keyword%")
                    ->orWhere('nickname', "like", "%$request->keyword%")
                    ->orWhere('mobile', "like", "%$request->keyword %");
            }
            return $q;
        })->where('uniacid', $this->uniacid())
            ->paginate($request->pageSize ?? 20, '*', 'pageNo');
        return $this->success($data);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $uniacid = $this->uniacid();
        $model = Member::where('uniacid', $this->uniacid())
            ->where(function ($q) use ($request, $uniacid) {
                if ($request->keyword) {
                    if (mb_strlen($request->keyword, 'UTF8') == 18) {
                        $model = MemberQrCode::where('uniacid', $uniacid)
                            ->where('qrcode', $request->keyword)
                            ->where('expired', '>=', date("Y-m-d H:i:s"))
                            ->first();
                        return $q->where('id', $model->userId ?? 0);
                    } else {
                        $q->where(function ($q) use ($request, $uniacid) {
                            $q->orWhere('id', $request->keyword);
                            $q->orWhere('mobile', 'like', "%$request->keyword%");
                            $q->orWhere('nickname', 'like', "%$request->keyword%");
                            $q->orWhere('realname', 'like', "%$request->keyword%");
                            $q->orWhere('vipCard', 'like', "%$request->keyword");
                            return $q;
                        });
                    }
                }
                if ($request->tourists) {
                    $q->tourists();
                } else {
                    $q->members();
                }
                if ($request->score) {
                    $q->where('score', $request->score);
                }
                if ($request->labelId) {
                    $q->whereHas("label", function ($q) use ($request) {
                        return  $q->where('labelId', $request->labelId);
                    });
                }
                if ($request->groupId) {
                    $q->where("groupId", $request->groupId);
                }
                if ($request->level) {
                    $q->whereHas('vip', function ($q) use ($request) {
                        $q->where("id", $request->level);
                    });
                }
                if ($request->timeType) {
                    $q->where('created_at', '>=', $this->timeArr(true)['startTime']);
                    $q->where('created_at', '<=', $this->timeArr(true)['endTime']);
                }
                return $q;
            })->when($request->qrcode, function ($q) use ($request, $uniacid) {
                $model = MemberQrCode::where('uniacid', $uniacid)
                    ->where('qrcode', $request->qrcode)
                    ->where('expired', '>=', date("Y-m-d H:i:s"))
                    ->first();
                return $q->where('id', $model->userId ?? 0);
            })->when($request->mobile, function ($q) use ($request, $uniacid) {
                $q->where('mobile', 'like', "%$request->mobile");
            });
        $list = $model->orderBy('id', 'desc')->paginate($request->pageSize ?? 20, '*', 'pageNo');
        return $this->success(new MemberList($list));
    }

    public function show(Request $request, $id)
    {
        $model =  Member::where('uniacid', $this->uniacid())->find($id);
        if (!$model) {
            return $this->failed('数据不存在');
        }
        return $this->success(new MemberRequest($model));
    }



    /**
     * 删除用户
     */
    public function destroy($id)
    {
        $idArray = array_filter(explode(',', $id), function ($item) {
            return is_numeric($item);
        });
        $list =  Member::where('uniacid', $this->uniacid())->whereIn('id', $idArray)->get();
        foreach ($list as $key => $value) {
            DB::table('member_bind')->where('userId', $value->id)->delete();
            $value->forceDelete();
        }
        return $this->success([], '删除成功');
    }

    public function update(Request $request, $id)
    {
        $uniacid = $this->uniacid();
        $model =  Member::where('uniacid', $this->uniacid())->find($id);
        if (!$model) {
            return $this->failed('数据不存在');
        }
        $model->fill($request->all());
        if ($model->isDirty('labelId')) {
            $model->label()->sync($model->labelId);
        }
        $model->save();
        return $this->success([], __('base.success'));
    }

    /**
     * 回收站
     */
    public function recycle(Request $req, Member $member)
    {
        $data = Member::onlyTrashed()->where('uniacid', $this->uniacid())->where(function ($q) use ($req) {
            if ($req->keyowrd) {
                $q->where('mobile', $req->mobile);
            }
            if ($req->startTime && $req->endTime) {
                $q->where('created_at', '>=', $req->startTime);
                $q->where('created_at', '<=', $req->endTime);
            }
            return $q;
        })->orderBy('deleted_at', 'desc')->paginate($req->pageSize ?? 30, '*', 'pageNo');
        return $this->success(new MemberList($data));
    }


    public function restore($id)
    {
        $idArray = array_filter(explode(',', $id), function ($item) {
            return is_numeric($item);
        });
        Member::withTrashed()->where('uniacid', $this->uniacid())->whereIn('id', $idArray)->restore();
        MemberBind::withTrashed()->where('uniacid', $this->uniacid())->whereIn('userId', $idArray)->restore();
        return $this->success([], '恢复成功');
    }

    /**
     * 回收站删除
     */
    public function del(Request $request, $id)
    {
        try {
            $idArray = array_filter(explode(',', $id), function ($item) {
                return is_numeric($item);
            });
            //DB::beginTransaction();
            MemberBind::where('uniacid', $this->uniacid())->whereIn('id', $idArray)->forceDelete($idArray, true);
            //MemberBind::whereIn('id', $idArray)->forceDelete($idArray, true);
            Member::where('uniacid', $this->uniacid())->withTrashed()->whereIn('id', $idArray)->forceDelete($idArray, true);
            return $this->success([], '删除成功');
        } catch (\Exception $e) {
            return $this->failed('删除失败');
        }
    }

    /**
     * 拉黑/洗白
     */
    public function state(Request $request, $id)
    {
        $model = Member::where('uniacid', $this->uniacid())->where('id', $id)->first();
        if (!$model) {
            return $this->failed('数据不存在');
        }
        $model->state = $model->state == 1 ? 0 : 1;
        $model->save();
        return $this->success([], '状态修改成功');
    }


    /**
     * 改变余额
     */
    public function changeBalance(BalanceRequest $request, $id)
    {
        $res = MemberAccountService::changeBalance(intval($id), intval($request->type), $request->value, MemberAccountLog::BASE, $this->user()->id, $request->notes);
        if ($res) {
            return $this->success([], '余额调整成功');
        }
        return $this->failed([], '余额调整失败');
    }

    /**
     * 改变积分
     */
    public function changeIntegral(BalanceRequest $request, $id)
    {
        $res = MemberAccountService::changeIntegral(intval($id), intval($request->type), $request->value, MemberAccountLog::BASE, $this->user()->id, $request->notes);
        if ($res) {
            return $this->success([], '积分调整成功');
        }
        return $this->failed([], '积分调整失败');
    }


    /**
     * 改变积分
     */
    public function changeExp(BalanceRequest $request, $id)
    {
        $res = MemberAccountService::changeExp(intval($id), intval($request->type), $request->value, MemberAccountLog::BASE, $this->user()->id, $request->notes);
        if ($res) {
            return $this->success([], '成长值调整成功');
        }
        return $this->failed([], '成长值调整失败');
    }

    /**
     * 改变积分
     */
    public function changeVip(Request $request, $id)
    {
        $vip = Vip::find($request->vipId);
        if (!$vip) {
            return $this->failed('会员等级不存在');
        }
        $model = Member::where('uniacid', $this->uniacid())->where('id', $id)->first();
        if (!$model) {
            return $this->failed('用户不存在');
        }
        $model->vipId=$request->vipId;
        $model->save();
        MemberAccountService::changeExp(intval($id), 3, $vip->exp, MemberAccountLog::BASE, $this->user()->id, '后台修改用户等级');
        return $this->success([], '会员等级调整成功');

    }


    //用户数据
    public function memberData(Request $request)
    {
        return $this->success();
    }

    //用户导出
    public function memberExport(Request $request)
    {
        $params = $request->all();
        $uniacid = $this->uniacid();
        $params['uniacid'] = $uniacid;
        $timeArr = $this->timeArr(true);
        $params['timeArr'] = $timeArr;
        $params['storeId'] = $this->isolateStore();
        return Excel::download(new MemberDataExport($params), 'memberData.xlsx');
    }


    public function syncWechatUser()
    {
        $work = Cache::get('syncWechatUser:' . $this->uniacid(), 0);
        $app = ChannelOpenWechat::officialAccount($this->uniacid());
        if ($work) {
            return $this->failed('任务正在进行中,请勿重复操作');
        }
        if (!SwooleJobService::check()) {
            return $this->failed('swoole已处于关闭状态，请开启swoole后再试');
        }
        $res = SwooleJobService::job(CreateSyncWechatJob::class, ['uniacid' => $this->uniacid(), 'start' => false]);
        $work = Cache::put('syncWechatUser:' . $this->uniacid(), 1, 600);
        return $this->success($res, '正在后台为您同步公众号粉丝用户');
    }

    public function store(MemberForm $request)
    {
        $uniacid = $this->uniacid();
        function isElevenDigitNumber($number) {
            return preg_match('/^\d{11}$/', $request->mobile) === 1;
        }
        if (preg_match('/^\d{11}$/', $request->mobile) === 1) {
            $model =  new Member();
            $model->fill($request->all());
            $model->uniacid = $this->uniacid();
            $model->vipId = $request->vipId ?? $model->initVip();
            $model->vipCard = getVipCardNo();
            $model->registerStore = $this->storeId();
            $model->storeId = $this->isolateStore();
            $model->vipCreateTime = date("Y-m-d H:i:s", time());
            $model->score = $this->appType();
            $model->save();
            $storeId =  $this->storeId();
            Event(new MemberRegisteredEvent($model));
            StatisticsDay::where("uniacid", $this->uniacid())
                ->where(function ($q) use ($storeId) {
                    return $q->where('storeId', $storeId)->orWhere('storeId', 0);
                })
                ->where("day", Carbon::now()->toDateString())
                ->increment('newMember', 1);
            return $this->success(new MemberRequest($model), "会员注册成功");
        } else {
            return $this->failed('手机号码不正确');
        }


    }

    public function group(Request $request, $id)
    {
        $idArray = array_filter(explode(',', $id), function ($item) {
            return is_numeric($item);
        });
        Member::where("uniacid", $this->uniacid())->whereIn('id', $idArray)->update(['groupId' => $request->groupId]);
        return $this->success([], '操作成功');
    }

    public function label(Request $request, $id)
    {
        $idArray = array_filter(explode(',', $id), function ($item) {
            return is_numeric($item);
        });
        $list = Member::where("uniacid", $this->uniacid())->whereIn('id', $idArray)->get();
        foreach ($list as $key => $user) {
            $user->labelId = $request->labelId;
            if ($user->isDirty('labelId')) {
                $user->label()->sync($user->labelId);
                $user->save();
            }
        }
        return $this->success([], '操作成功');
    }


    public function importMembers(Request $request)
    {
        $domain = 'https://' . $request->domain_url;
        $userName = $request->userName;
        $passWord = $request->passWord;
        if ($userName && $passWord) {
            $arr = [
                'userName' => $userName,
                'passWord' => $passWord,
            ];
            $row = httpRequest($domain . '/channel/export/register', $arr);
            if ($row['code'] == 200) {
                dispatch(new ImportMembersJob($domain, $row['data']['uniacid'], $this->uniacid()));
               // file_put_contents("ImportMembersJob.log", '来自V2后台' . $row['data']['uniacid'] . '导入V3后台' . $this->uniacid() . PHP_EOL, FILE_APPEND);
                return $this->success([], '导入任务添加成功', $row['count']);
            } else {
                return $this->failed([], $row['msg']);
            }
        }
    }
    public function batchForm(Request $request){
        $data=$userName = $request->data;
        foreach ($data as $v){
            $model = Member::where('id', $v)->first();
            $model->vipId=$request->vipId;
            $model->save();
        }
        return $this->success([], '操作成功');
    }


    public function filterUser(Request $request)
    {
        $uniacid = $this->uniacid();
        $model = Member::with(['account','coupons'])->withCount('coupons')->where('uniacid', $this->uniacid())
            ->where(function ($q) use ($request, $uniacid) {
                if ($request->keyword) {
                    if (mb_strlen($request->keyword, 'UTF8') == 18) {
                        $model = MemberQrCode::where('uniacid', $uniacid)
                            ->where('qrcode', $request->keyword)
                            ->where('expired', '>=', date("Y-m-d H:i:s"))
                            ->first();
                        return $q->where('id', $model->userId ?? 0);
                    } else {
                        $q->where(function ($q) use ($request, $uniacid) {
                            $q->orWhere('id', $request->keyword);
                            $q->orWhere('mobile', 'like', "%$request->keyword%");
                            $q->orWhere('nickname', 'like', "%$request->keyword%");
                            $q->orWhere('realname', 'like', "%$request->keyword%");
                            $q->orWhere('vipCard', 'like', "%$request->keyword");
                            return $q;
                        });
                    }
                }


                if ($request->groupId) {
                    $q->where("groupId", $request->groupId);
                }
                if ($request->level) {
                    $q->whereHas('vip', function ($q) use ($request) {
                        $q->where("id", $request->level);
                    });
                }
                return $q;
            })->when($request->filterUser, function ($q) use ($request) {
                if(in_array('1',$request->filterUser)){
                    return $q->whereHas("account", function ($q) use ($request) {
                        return $q->where('balance', '>',0);
                    });
                }
            })->when($request->filterUser, function ($q) use ($request) {
                if(in_array('2',$request->filterUser)){
                    return $q->whereHas("coupons", function ($q) use ($request) {
                        return $q->where('state',1);
                    });
                }
            });

        $list = $model->orderBy('id', 'desc')->paginate($request->pageSize ?? 20, '*', 'pageNo');
        return $this->success(new MemberList($list));
    }
}
