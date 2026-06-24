<?php

namespace App\Traits;

use App\Enums\SceneEnum;
use App\Models\StatisticsOrder;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log as FacadesLog;

trait StatisticsTrait
{
    public $_dataList;
    public function timeArr($format = false, $day = null)
    {
        $data =  [
            -1 => [ //昨日
                'startTime' => date("Y-m-d", strtotime("-1 day")) . ($format ?  " 00:00:00" : ""),
                'endTime' => date("Y-m-d", strtotime("-1 day")) . ($format  ?  " 23:59:59" : ""),
            ],
            1 => [ //自定义
                'startTime' => Request()->startTime ?? date("Y-m-d", strtotime("-1 day")),
                'endTime' => Request()->endTime ?? date("Y-m-d", strtotime("-1 day")),
            ],
            2 => [ //今天
                'startTime' => date("Y-m-d", time()) . ($format  ?  " 00:00:00" : ""),
                'endTime' => date("Y-m-d", time()) . ($format ?  " 23:59:59" : ""),
            ],
            7 => [ //7天
                'startTime' => date("Y-m-d", strtotime("-6 day")) . ($format ?  " 00:00:00" : ""),
                'endTime' => date("Y-m-d", time()) . ($format ?  " 23:59:59" : ""),
            ],
            15 => [ //15天
                'startTime' => date("Y-m-d", strtotime("-15 day")) . ($format ?  " 00:00:00" : ""),
                'endTime' => date("Y-m-d", time()) . ($format ?  " 23:59:59" : ""),
            ],
            30 => [ //30天
                'startTime' => date("Y-m-d", strtotime("-29 day")) . ($format ?  " 00:00:00" : ""),
                'endTime' => date("Y-m-d", time()) . ($format ?  " 23:59:59" : ""),
            ]
        ];
        if ($day) {
            return $data[$day];
        } elseif (Request()->timeType) {
            return isset($data[Request()->timeType]) ? $data[Request()->timeType] : $data[2];
        } else {
            return $data[2];
        }
    }

    public function sum($data, $attrs)
    {
        if (is_array($data) && empty($attrs)) {
            return  round(collect($data)->sum(), 2);
        }
        return  round(collect($data)->pluck($attrs)->sum(), 2);
    }

    /**
     * 格式化折线图数据
     */
    public function lineData($items, $attrs = [], $format = true, $key = "day")
    {
        if (count($attrs) ==  0 || empty($items)) {
            return null;
        }
        foreach ($attrs as $keys => $attr) {
            if ($format) {
                $data['day30'][$attr]['day'] = collect($items)->map(function ($item) use ($attr, $key) {
                    $item = collect($item)->toArray();
                    return ["name" => $item[$key], 'value' => $item[$attr]];
                })->pluck('name')->all();
                $data['day30'][$attr]['value'] = collect($items)->map(function ($item) use ($attr) {
                    $item = collect($item)->toArray();
                    return ["name" => $item['day'], 'value' => $item[$attr]];
                })->pluck('value')->all();
                $data['day30'][$attr]['count'] = $this->sum($data['day30'][$attr]['value'], '');
                $data['day15'][$attr]['day'] =  array_slice($data['day30'][$attr]['day'], -15);
                $data['day15'][$attr]['value'] =  array_slice($data['day30'][$attr]['value'], -15);
                $data['day15'][$attr]['count'] = $this->sum($data['day15'][$attr]['value'], '');

                $data['day7'][$attr]['day'] = array_slice($data['day15'][$attr]['day'], -7);
                $data['day7'][$attr]['value'] = array_slice($data['day15'][$attr]['value'], -7);
                $data['day7'][$attr]['count'] =  $this->sum($data['day7'][$attr]['value'], '');
                $toDay = date("m-d", time());
                $index = array_search($toDay, $data['day7'][$attr]['day']);
                if ($index) {
                    $data['today'][$attr]['day'] = [$toDay];
                    $data['today'][$attr]['value'] = [$data['day7'][$attr]['value'][$index]];
                    $data['today'][$attr]['count'] =  $data['day7'][$attr]['value'][$index];
                } else {
                    $data['today'][$attr]['day'] = [$toDay];
                    $data['today'][$attr]['value'] = [0];
                    $data['today'][$attr]['count'] =  0;
                }
                $yesterday = date("m-d", strtotime(date("Y-m-d 00:00:00", time())) - 3600);
                $index = array_search($yesterday, $data['day7'][$attr]['day']);
                if ($index) {
                    $data['yesterday'][$attr]['day'] = [$yesterday];
                    $data['yesterday'][$attr]['value'] = [$data['day7'][$attr]['value'][$index]];
                    $data['yesterday'][$attr]['count'] =   $data['day7'][$attr]['value'][$index];
                } else {
                    $data['yesterday'][$attr]['day'] = [$yesterday];
                    $data['yesterday'][$attr]['value'] = [0];
                    $data['yesterday'][$attr]['count'] =   0;
                }
            } else {
                $data['day'] = collect($items)->map(function ($item) use ($attr, $key) {
                    $item = collect($item)->toArray();
                    return ["name" => $item[$key], 'value' => $item[$attr]];
                })->pluck('name')->all();
                $data['value'] = collect($items)->map(function ($item) use ($attr, $key) {
                    $item = collect($item)->toArray();
                    return ["name" => $item[$key], 'value' => $item[$attr]];
                })->pluck('value')->all();
            }
        }
        return $data;
    }

    /**
     * 格式化折线图数据
     */
    public function lineDataAttr($items, $attrs = [])
    {
        if (count($attrs) ==  0 || empty($items)) {
            return null;
        }
        foreach ($attrs as $key => $attr) {
            $data[$attr]['day'] = collect($items)->map(function ($item) use ($attr) {
                $item = collect($item)->toArray();
                return ["name" => $item['day'], 'value' => $item[$attr]];
            })->pluck('name')->all();
            $data[$attr]['value'] = collect($items)->map(function ($item) use ($attr) {
                $item = collect($item)->toArray();
                return ["name" => $item['day'], 'value' => $item[$attr]];
            })->pluck('value')->all();
            $data[$attr]['count'] =  $this->sum($data[$attr]['value'], '');
        }
        return $data;
    }




    /**
     * 格式化柱状图数据
     */
    public function pieData($arr, $attrs = [], $format = true)
    {
        if (count($attrs) ==  0 || empty($arr)) {
            return null;
        }
        foreach ($attrs as $key => $attr) {
            $items = collect($arr)->map(function ($item) use ($attr, $key) {
                $item = collect($item)->toArray();
                return ['value' => $item[$key]];
            })->toArray();
            if ($format) {
                $data['day30'][] = ['name' => $attr, 'value' => $this->sum($items, 'value')];
                $data['day15'][] = ['name' => $attr, 'value' => $this->sum(array_slice($items, -15), 'value')];
                $data['day7'][] = ['name' => $attr, 'value' => $this->sum(array_slice($items, -7), 'value')];
            } else {
                if (count($attrs) == 1) {
                    $data[] = ['name' => $attr, 'value' => $this->sum($items, 'value')];
                } else {
                    $data[$key][] = ['name' => $attr, 'value' => $this->sum($items, 'value')];
                }
            }
        }
        return $data;
    }

    public function getDataListAttribute()
    {
        if (!$this->_dataList) {
            $storeId = $this->storeId;
            $timeArr = $this->timeArr(true);
            $isolate = $this->isolate;
            $this->_dataList =  StatisticsOrder::where('uniacid', $this->uniacid)
                ->when(($storeId), function ($q) use ($storeId)
                 {
                    if (is_array($storeId)) {
                        $q->whereIn('storeId', $this->storeId);
                    } else {
                        $q->where('storeId', $this->storeId);
                    }
                })
                ->select([
                    'day',
                    'costomPayId',
                    'payType',
                    'storeId',
                    DB::raw("IFNULL(sum(if(type != 2,orderCount,0)),0) as orderCount"),
                    DB::raw("IFNULL(sum(if(type != 2,money,0)),0) as money"),
                    DB::raw("IFNULL(sum(if(type != 2,sellMoney,0)),0) as sellMoney"),
                    DB::raw("IFNULL(sum(if(type != 2,discountMoney,0)),0) as discountMoney"),
                    DB::raw("IFNULL(sum(if(type != 2 AND payType not in (0,6),money,0)),0) as onlineMoney"),
                    DB::raw("IFNULL(sum(if(type != 2 AND payType not in (0,6),orderCount,0)),0) as onlineOrder"),
                    DB::raw("IFNULL(sum(if(payType = 0 ,money,0)),0) as balanceMoney"),
                    DB::raw("IFNULL(sum(if(payType = 0 ,orderCount,0)),0) as balanceOrder"),
                    DB::raw("IFNULL(sum(if(payType = 0 ,money,0)),0) as weixinMoney"),
                    DB::raw("IFNULL(sum(if(payType between 11 and 19 ,orderCount,0)),0) as weixinOrder"),
                    DB::raw("IFNULL(sum(if(payType between 11 and 19 ,money,0)),0) as weixinMoney"),
                    DB::raw("IFNULL(sum(if(payType between 20 and 29 ,orderCount,0)),0) as alipayOrder"),
                    DB::raw("IFNULL(sum(if(payType between 20 and 29 ,money,0)),0) as alipayMoney"),
                    DB::raw("IFNULL(sum(if(type != 2 AND payType = 6 ,orderCount,0)),0) as cashOrder"),
                    DB::raw("IFNULL(sum(if(type != 2 AND payType = 6 ,money,0)),0) as cashMoney"),
                    DB::raw("IFNULL(sum(if(type != 2 AND scene = 2 ,orderCount,0)),0) as ziti"),
                    DB::raw("IFNULL(sum(if(type != 2 AND scene = 2 ,money,0)),0) as zitiMoney"),
                    DB::raw("IFNULL(sum(if(type != 2 AND scene = 1 ,orderCount,0)),0) as waisong"),
                    DB::raw("IFNULL(sum(if(type != 2 AND scene = 1 ,money,0)),0) as waisongMoney"),
                    DB::raw("IFNULL(sum(if(type =4 ,orderCount,0)),0) as zhuomaOrder"),
                    DB::raw("IFNULL(sum(if(type =4 ,money,0)),0) as zhuomaMoney"),
                    DB::raw("IFNULL(sum(if(type =3 ,orderCount,0)),0) as dmfOrder"),
                    DB::raw("IFNULL(sum(if(type =3 ,money,0)),0) as dmfMoney"),
                    DB::raw("IFNULL(sum(if(type =2 ,orderCount,0)),0) as chuzhiOrder"),
                    DB::raw("IFNULL(sum(if(type =2 ,money,0)),0) as chuzhiMoney"),
                    DB::raw("IFNULL(sum(deliveryMoney),0) as deliveryMoney"),
                    DB::raw("IFNULL(sum(boxMoney),0) as boxMoney"),
                    DB::raw("IFNULL(sum(refundOrder),0) as refundOrder"),
                    DB::raw("IFNULL(sum(refundMoney),0) as refundMoney"),
                    DB::raw("IFNULL(sum(if(state IN (6,10) AND userId > 0,orderCount,0)),0) as payMember"),
                ])
                ->where('payTime', '>=', $timeArr['startTime'])
                ->where('payTime', '<=', $timeArr['endTime'])
                ->orderBy('day', 'asc')
                ->orderBy('h', 'asc')
                ->orderBy('id', 'asc')
                ->groupBy('day')
                ->get();
            // $this->_dataList = collect($lists)->groupBy('day')->map(function ($list, $key) {
            //     return [
            //         'day' => $key,
            //         'orderCount' => collect($list)->whereNotIn("type", [2])->pluck('orderCount')->sum(),
            //         'money' => bcmul(collect($list)->whereNotIn("type", [2])->sum('money'), 1, 2),
            //         'sellMoney' => bcmul(collect($list)->whereNotIn("type", [2])->sum('sellMoney'), 1, 2),
            //         'discountMoney' => bcmul(collect($list)->whereNotIn("type", [2])->sum('discountMoney'), 1, 2),
            //         'onlineMoney' => bcmul(collect($list)->whereNotIn("type", [2])->whereNotIn("payType", [0, 6])->sum('money'), 1, 2),
            //         'onlineOrder' => collect($list)->whereNotIn("type", [2])->whereNotIn("payType", [0, 6])->sum('orderCount'),
            //         'balanceMoney' => bcmul(collect($list)->whereIn("payType", [0])->sum('money'), 1, 2),
            //         'balanceOrder' => collect($list)->whereIn("payType", [0])->sum('orderCount'),
            //         'weixinMoney' => bcmul(collect($list)->whereBetween("payType", [11, 19])->sum('money'), 1, 2),
            //         'weixinOrder' => collect($list)->whereNotIn("type", [2])->whereBetween("payType", [11, 19])->sum('orderCount'),
            //         'alipayMoney' => bcmul(collect($list)->whereBetween("payType", [20, 29])->sum('money'), 1, 2),
            //         'alipayOrder' => collect($list)->whereNotIn("type", [2])->whereBetween("payType", [21, 29])->sum('orderCount'),
            //         'cashMoney' => bcmul(collect($list)->whereNotIn("type", [2])->where("payType", 6)->sum('money'), 1, 2),
            //         'cashOrder' => collect($list)->whereNotIn("type", [2])->where("payType", 6)->sum('orderCount'),
            //         'zitiMoney' => bcmul(collect($list)->whereNotIn("type", [2])->where("scene", 2)->sum('money'), 1, 2),
            //         'ziti' => collect($list)->whereNotIn("type", [2])->where("scene", 2)->sum('orderCount'),
            //         'waisongMoney' => bcmul(collect($list)->whereNotIn("type", [2])->where("scene", 1)->sum('money'), 1, 2),
            //         'waisong' => collect($list)->whereNotIn("type", [2])->where("scene", 1)->sum('orderCount'),
            //         'zhuomaMoney' => bcmul(collect($list)->where("type", 4)->sum('money'), 1, 2),
            //         'zhuomaOrder' => collect($list)->where("type", 4)->sum('orderCount'),
            //         'dmfMoney' => bcmul(collect($list)->where("type", 3)->sum('money'), 1, 2),
            //         'dmfOrder' => collect($list)->where("type", 3)->sum('orderCount'),
            //         'chuzhiMoney' => bcmul(collect($list)->where("type", 2)->sum('money'), 1, 2),
            //         'chuzhiOrder' => collect($list)->where("type", 2)->sum('orderCount'),
            //         'deliveryMoney' => bcmul(collect($list)->sum('deliveryMoney'), 1, 2),
            //         'boxMoney' => bcmul(collect($list)->sum('boxMoney'), 1, 2),
            //         'refundMoney' => bcmul(collect($list)->sum('refundMoney'), 1, 2),
            //         'refundOrder' => collect($list)->sum('refundOrder'),
            //         'payMember' => collect($list)->whereIn("state", [6, 10])->where('userId', ">", 0)->groupBy('userId')->count(),
            //     ];
            // })->values();
        }
        return $this->_dataList;
    }

    public function getDataListSQLAttribute()
    {
        if (!$this->_dataList) {

            $storeId = $this->storeId;
            $timeArr = $this->timeArr(true);
            return  StatisticsOrder::where('uniacid', $this->uniacid)
                ->when(($storeId), function ($q) use ($storeId) {
                    if (is_array($storeId)) {
                        $q->whereIn('storeId', $this->storeId);
                    } else {
                        $q->where('storeId', $this->storeId);
                    }
                })
                ->select([
                    'day',
                    'costomPayId',
                    'payType',
                    DB::raw("IFNULL(sum(if(type != 2,orderCount,0)),0) as orderCount"),
                    DB::raw("IFNULL(sum(if(type != 2,money,0)),0) as money"),
                    DB::raw("IFNULL(sum(if(type != 2,sellMoney,0)),0) as sellMoney"),
                    DB::raw("IFNULL(sum(if(type != 2,discountMoney,0)),0) as discountMoney"),
                    DB::raw("IFNULL(sum(if(type != 2 AND payType not in (0,6),money,0)),0) as onlineMoney"),
                    DB::raw("IFNULL(sum(if(type != 2 AND payType not in (0,6),orderCount,0)),0) as onlineOrder"),
                    DB::raw("IFNULL(sum(if(payType = 0 ,money,0)),0) as balanceMoney"),
                    DB::raw("IFNULL(sum(if(payType = 0 ,orderCount,0)),0) as balanceCount"),
                    DB::raw("IFNULL(sum(if(payType = 0 ,money,0)),0) as weixinMoney"),
                    DB::raw("IFNULL(sum(if(payType between 11 and 19 ,orderCount,0)),0) as weixinOrder"),
                    DB::raw("IFNULL(sum(if(payType between 11 and 19 ,money,0)),0) as weixinMoney"),
                    DB::raw("IFNULL(sum(if(payType between 20 and 29 ,orderCount,0)),0) as alipayOrder"),
                    DB::raw("IFNULL(sum(if(payType between 20 and 29 ,money,0)),0) as alipayMoney"),
                    DB::raw("IFNULL(sum(if(type != 2 AND payType = 6 ,orderCount,0)),0) as cashOrder"),
                    DB::raw("IFNULL(sum(if(type != 2 AND payType = 6 ,money,0)),0) as cashMoney"),
                    DB::raw("IFNULL(sum(if(type != 2 AND scene = 2 ,orderCount,0)),0) as ziti"),
                    DB::raw("IFNULL(sum(if(type != 2 AND scene = 2 ,money,0)),0) as zitiMoney"),
                    DB::raw("IFNULL(sum(if(type != 2 AND scene = 1 ,orderCount,0)),0) as waisong"),
                    DB::raw("IFNULL(sum(if(type != 2 AND scene = 1 ,money,0)),0) as waisongMoney"),
                    DB::raw("IFNULL(sum(if(type =4 ,orderCount,0)),0) as zhuomaOrder"),
                    DB::raw("IFNULL(sum(if(type =4 ,money,0)),0) as zhuomaMoney"),
                    DB::raw("IFNULL(sum(if(type =3 ,orderCount,0)),0) as dmfOrder"),
                    DB::raw("IFNULL(sum(if(type =3 ,money,0)),0) as dmfMoney"),
                    DB::raw("IFNULL(sum(if(type =2 ,orderCount,0)),0) as chuzhiOrder"),
                    DB::raw("IFNULL(sum(if(type =2 ,money,0)),0) as chuzhiMoney"),
                    DB::raw("IFNULL(sum(deliveryMoney),0) as deliveryMoney"),
                    DB::raw("IFNULL(sum(boxMoney),0) as boxMoney"),
                    DB::raw("IFNULL(sum(refundOrder),0) as refundOrder"),
                    DB::raw("IFNULL(sum(refundMoney),0) as refundMoney"),
                    DB::raw("IFNULL(sum(if(state IN (6,10) AND userId > 0,orderCount,0)),0) as payMember"),
                ])
                ->where('created_at', '>=', $timeArr['startTime'])
                ->where('created_at', '<=', $timeArr['endTime']);
        }
        return $this->_dataList;
    }
}
