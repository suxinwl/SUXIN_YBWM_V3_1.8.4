<?php

namespace App\Models\Admin;

use App\Http\Resources\Admin\Apply\ApplyListCollection;
use App\Models\Admin;
use App\Models\BaseModel;
use App\Models\Member;
use App\Models\Setmeal;
use App\Models\Visit;
use App\Models\Admin\Order;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class Index extends BaseModel
{
    use HasFactory;

    protected $table = '';



    protected $appends = [
        'todayApplyCount',
        'yesterdayApplyCount',
        'todayUserCount',
        'yesterdayUserCount',
        'todayOrderCount',
        'yesterdayOrderCount',
        'yesterdayOrderCount',
        'todayOrderMoney',
        'yesterdayOrderMoney',
        'applyCount',
        'applyRecycle',
        'userCount',
        'orderMoney',
        'newApply',
        'applyTop',
        'systemUpdate',
        'miniUpdate',
        'authorizeData',
        'viewData',
        'storeUserProfile',
        'paymentOverview',
        'applyTryOut',
        'applyTryOutExceed',
        'merchantUpdate'
    ];

    /**
     * 今天新增平台数
     */
    public function getViewDataAttribute()
    {
        $start = date("Y-m-d 00:00:00", strtotime('-6day'));
        $emd = date("Y-m-d 00:00:00", time());
        $dayArr = timeToDays($start, $emd);

        $list =  Visit::select(DB::raw("DATE_FORMAT(created_at,'%m-%d') as day, count(id) as value"))
            ->whereBetween('created_at', [$start, $emd])->groupBy(DB::raw("DATE_FORMAT(created_at,'%m-%d')"))->get();
        $list = collect($list)->groupBy('day')->toArray();

        $data = collect($dayArr)->map(function ($item) use ($list) {
            if (isset($list[$item])) {
                return $list[$item][0];
            } else {
                return ['day' => $item, 'value' => 0];
            }
        })->toArray();
        return $data;
    }

    /**
     * 今天新增平台数
     */
    public function getTodayApplyCountAttribute()
    {
        $start = date("Y-m-d 00:00:00", time());
        $emd =  date("Y-m-d H:i:s", strtotime($start) + 86400 - 1);
        return Apply::withTrashed()->whereBetween('created_at', [$start, $emd])->count();
    }


    /**
     * 昨天新增平台数
     */
    public function  getYesterdayApplyCountAttribute()
    {
        $start = date("Y-m-d 00:00:00", time() - 86400);
        $emd =  date("Y-m-d H:i:s", strtotime($start) + 86400 - 1);
        return Apply::whereBetween('created_at', [$start, $emd])->count();
    }

    /**
     * 今天新增用户数
     */
    public function getTodayUserCountAttribute()
    {
        $start = date("Y-m-d 00:00:00", time());
        $emd =  date("Y-m-d H:i:s", strtotime($start) + 86400 - 1);
        return Admin::whereNotIn('id', [1])
            ->whereBetween('created_at', [$start, $emd])
            ->where('status', 1)
            ->where('isAdmin', 1)
            ->count();
    }


    /**
     * 昨天新增用户数
     */
    public function  getYesterdayUserCountAttribute()
    {
        $start = date("Y-m-d 00:00:00", time() - 86400);
        $emd =  date("Y-m-d H:i:s", strtotime($start) + 86400 - 1);
        return Admin::whereNotIn('id', [1])->whereBetween('created_at', [$start, $emd])->where('status', 1)->where('isAdmin', 1)->count();
    }

    /**
     * 今天新增订单数
     */
    public function getTodayOrderCountAttribute()
    {
        return 0;
    }

    /**
     * 昨天新增订单数
     */
    public function getYesterdayOrderCountAttribute()
    {
        return 0;
    }

    /**
     * 今日营业额
     */
    public function getTodayOrderMoneyAttribute()
    {
        return 0;
    }

    /**
     * 昨天营业额
     */
    public function getYesterdayOrderMoneyAttribute()
    {
        return 0;
    }

    /**
     * 店铺总数/待审核数
     */
    public function getApplyCountAttribute()
    {
        return [
            'count' => intval(Apply::whereIn('status', [1, 2])->count()) + intval(Apply::onlyTrashed()->count()),
            'toAudit' => Apply::where('status', 0)->count()
        ];
    }

    /**
     * 店铺回收站/已过期数
     */
    public function getApplyRecycleAttribute()
    {
        return [
            'count' => Apply::onlyTrashed()->count(),
            'overdue' => Apply::where('endTime', '<=', date("Y-m-d H:i:s", time()))->count(),
            'duesoon' => Apply::where('endTime', '>=', date("Y-m-d H:i:s", time()))->where('endTime', '<=', date("Y-m-d H:i:s", time() + 15 * 86400))->count(),
        ];
    }

    /**
     * 用户总数/待审核数
     */

    public function getUserCountAttribute()
    {
        return [
            'count' => Admin::whereNotIn('id', [1])->where('isAdmin', 1)->count(),
            'overdue' => Admin::where('status', 0)->where('isAdmin', 1)->count()
        ];
    }

    public function getOrderMoneyAttribute()
    {
        return [
            'money' => 0,
            'count' => 0
        ];
    }

    public function getNewApplyAttribute()
    {
        return Apply::with([
            'admin' => function ($q) {
                return $q->select(['id', 'username', 'mobile', 'nickname']);
            }, 'muster'
        ])->where('musterId', ">", 0)->limit(10)->orderBy('id', 'desc')->get();
    }

    public function getApplyTopAttribute()
    {
        // return Apply::with([
        //     'admin' => function ($q) {
        //         return $q->select(['id', 'username', 'mobile', 'nickname']);
        //     }, 'muster'
        // ])->withCount('muster')->limit(10)->orderBy('muster_count', 'desc')->orderBy('id','desc')->get();
        return Setmeal::with('apply')->whereHas('apply')->orderBy('apply_count', 'desc')->orderBy('id', 'desc')->withCount('apply')->limit(20)->get();
    }

    public function getApplyTryOutAttribute()
    {
        return Apply::whereHas('muster', function ($q) {
            $q->where('type', 1);
            return $q;
        })->count();
    }

    public function getApplyTryOutExceedAttribute()
    {
        return Apply::whereHas('muster', function ($q) {
            $q->where('type', 1);
            return $q;
        })->where('endTime', '<', date('Y-m-d H:i:s', time()))->count();
    }

    public function getSystemUpdateAttribute()
    {
        $versionData = getVersionInfo();
        $versionData['authType'] = config('app.authType');
        $versionData['domain_url'] = getDomain();
        $versionData['diskName'] = 'online';
        $data = safeGetUpgradeInfo($versionData);
        $item['versionData'] = $versionData;
        $item['now_version'] = $data['data']['version'];
        $item['now_version_release'] =  $data['data']['version_release'];
        return $item;
    }
    public function getMiniUpdateAttribute()
    {
        $json = Storage::disk('local')->get('weixinOpen/ybwm_open/ext.json');
        $extJson = json_decode($json, true);
        $data['version'] = $extJson['ext']['version'];
        $data['desc'] = $extJson['ext']['desc'];
        return $data;
    }


    public function getMerchantUpdateAttribute()
    {
        $json = Storage::disk('local')->get('merchant/version.json');
        $extJson = json_decode($json, true);
        $data['version'] = $extJson['version'];
        $data['desc'] = $extJson['desc'];
        return $data;
    }

    public function getAuthorizeDataAttribute()
    {
        $data = getSysInfo();
        if ($data['domain_url'] == 'wm.y-qb.cn') {
            $data['domain_url'] = 'ybv3.b-ke.cn';
            $data['domain_name'] = '速信';
        }
        return $data;
    }
    //SELECT DATE_FORMAT(create_date, "%Y-%m-%d" ) AS time,
    // COUNT(*) AS total FROM survey_user where create_date
    // BETWEEN '2021-10-11 00:00:00' AND '2021-10-12 23:59:59'
    // GROUP BY DATE_FORMAT(create_date, "%Y-%m-%d") ORDER BY create_date DESC
    //店铺与用户概况
    public function getStoreUserProfileAttribute()
    {
        date_default_timezone_set('PRC');
        $ts = get_week();
        $adminArr = [];
        $applyArr = [];
        foreach ($ts as $v) {
            $time = date("Y") . '-' . $v;
            $applyArr[$v] = Apply::whereDate('created_at', '=', $time)->count();
            $adminArr[$v] = Admin::whereDate('created_at', '=', $time)->whereNotIn('id', [1])->where('status', 1)
                ->where('isAdmin', 1)
                ->count();
        }
        $data = [
            'platformOverview' => $applyArr,
            'userProfile' => $adminArr
        ];
        return $data;
    }

    //订单与支付金额概况
    public function getPaymentOverviewAttribute()
    {
        date_default_timezone_set('PRC');
        $ts = get_week();
        $orderArr = [];
        $moneyArr = [];
        foreach ($ts as $v) {
            $time = date("Y") . '-' . $v;
            $orderArr[$v] = Order::whereDate('created_at', '=', $time)->where('state', 1)->count();
            $moneyArr[$v] = Order::whereDate('created_at', '=', $time)->where('state', 1)->sum('money');
        }
        $data = [
            'orderOverview' => $orderArr,
            'amountOverview' => $moneyArr
        ];
        return $data;
    }
}
