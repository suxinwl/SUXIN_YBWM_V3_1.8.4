<?php

namespace App\Http\Resources\Admin\Apply;

use App\Models\Admin\Apply;
use App\Models\ApplyPlugs;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ApplyListCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $authData = getSysInfo();
        if ($request->experience) {
            $label = [
                'applyCount' => intval(Apply::whereIn('status', [1, 2])->whereHas('muster', function ($q) {
                    return $q->where('type', 1);
                })->count()),
                'todayApplyCount' => Apply::where('created_at', '>=', date("Y-m-d 00:00:00", time()))->where('created_at', '<=', date("Y-m-d 23:59:59", time()))->whereHas('muster', function ($q) {
                    return $q->where('type', 1);
                })->count(),
                'expireCount' => Apply::where('endTime', '<=', date("Y-m-d H:i:s", time()))->whereHas('muster', function ($q) {
                    return $q->where('type', 1);
                })->count(),
                'goAutioCount' => Apply::where('status', 6)->count(),
                'recoveryCount' => Apply::onlyTrashed()->whereHas('muster', function ($q) {
                    return $q->where('type', 1);
                })->count(),
            ];
        } elseif ($request->route()->getAction()['as'] == 'apply.recycle') {
            $label = [
                'applyCount' => intval(Apply::onlyTrashed()->count()),
                'todayApplyCount' => Apply::onlyTrashed()->where('created_at', '>=', date("Y-m-d 00:00:00", time()))->where('created_at', '<=', date("Y-m-d 23:59:59", time()))->count(),
                'expireCount' => Apply::where('endTime', '<=', date("Y-m-d H:i:s", time()))->onlyTrashed()->count(),
                'goAutioCount' => Apply::where('status', 6)->onlyTrashed()->count(),
                'recoveryCount' => Apply::onlyTrashed()->count(),
            ];
        } else {
            $user = auth('admin')->user();
            $time = time() - 15 * 86400;
            $label = [
                'applyCount' => $user->id == 1 ? Apply::withTrashed()->whereIn('status', [1, 2, 3, 4, 5, 6])->count() : $user->adminApply->count(),
                'todayApplyCount' => Apply::where('created_at', '>=', date("Y-m-d 00:00:00", time()))->where('created_at', '<=', date("Y-m-d 23:59:59", time()))->count(),
                'expireCount' => Apply::where('endTime', '<=', date("Y-m-d H:i:s", time()))->count(),
                'dueSoonCount' => Apply::where('endTime', '<=', date("Y-m-d H:i:s", $time))->count(),
                'goAutioCount' => Apply::where('status', 6)->count(),
                'recoveryCount' => Apply::onlyTrashed()->where(function ($q) use ($user) {
                    if ($user->id != 1) {
                        return $q->where("createUserId", $user->id);
                    }
                    return $q;
                })->count(),
                'passCount' => Apply::pass()->count(),
                'account_number' => $authData['account_type'] == 1 ? '不限制' : $authData['account_number'],
            ];
        }
        return [
            'list' => $this->collection->map(function ($item) {
                $arr = $item->toArray();
                $arr['addressFormat'] = $item->addressFormat();
                $arr['admin']['username'] = empty($item->admin->nickname) ? $item->admin->username : $item->admin->username . "({$item->admin->nickname})";
                $arr['userChannel'] = $item->userChannel();
                $arr['memberCount'] = $item->memberCount();
                $arr['orderCount'] = $item->orderCount();
                $arr['storeCount'] = $item->storeCount();
                return $arr;
            }),
            'label' => $label,
            'total' => $this->total(), // 数据总数
            'pageSize' => $this->perPage(), // 每页数量
            'pageNo' => $this->currentPage(), // 当前页码
        ];
    }
}
