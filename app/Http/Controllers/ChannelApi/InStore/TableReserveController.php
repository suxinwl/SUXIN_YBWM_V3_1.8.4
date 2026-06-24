<?php

namespace App\Http\Controllers\ChannelApi\InStore;

use App\Enums\WorkEnum;
use App\Events\StoreMessageEvent;
use App\Http\Controllers\ChannelApi\ApiController;
use App\Http\Requests\Admin\ChangePassword;
use App\Http\Resources\ChannelApi\User\Profix;
use App\Models\Ad;
use App\Models\Admin;
use App\Models\InStore\Cart;
use App\Models\Member;
use App\Models\Member\Address;
use App\Models\MemberAddress;
use App\Models\MemberBind;
use App\Models\Table\ReserveOrder;
use App\Models\Tables\Area;
use App\Models\Tables\Table;
use App\Models\Tables\Type;
use App\Models\TablesReserve\Checkout;
use App\Models\UserAccount;
use App\Services\ConfigService;
use App\Services\MenuService;
use App\Services\UserService;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB as FacadesDB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Tymon\JWTAuth\Facades\JWTAuth;

class TableReserveController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $areaList = Area::where('uniacid', $this->uniacid())
                ->where('storeId', $this->storeId())->get();
            $typeList = Type::where('uniacid', $this->uniacid())
                ->where('storeId', $this->storeId())
                ->where('reserveSwitch', 1)
                ->get();
            $config = ConfigService::getStoreConfig('bookTable', $this->storeId());
            $timeArr = $config['timeType'] == 1 ? [['start' => '00:00', 'end' => '23:59']] : $config['timeArr'];
            $timeArr = collect($timeArr)->sortBy('start');
            $i = $config['startDays'];
            $timeDataArr = [];
            if ($config) {
                for ($i; $i <= $config['days']; $i++) {
                    $startDay = strtotime("+{$i}day");
                    $week  = date('w', $startDay);
                    $startDay = date("Y-m-d", $startDay);
                    if (in_array($week, $config['week'] ?? [])) {

                        //$startDay = date("Y-m-d", strtotime($startDay) + 3600 * 24 * intval($config['startTime']));
                        $toDay = ($startDay ==  date("Y-m-d", time())) ? 1 : 0;
                        foreach ($timeArr as $key => $v) {
                            $itemTime = null;
                            if ($v['start'] == '00:00' && $v['end'] == "00:00") {
                                $startTime = strtotime($startDay . ' 00:00:00');
                            } else {
                                $startTime = strtotime($startDay . $v['start']);
                                if ($v['end'] == "00:00") {
                                    $endTime = strtotime($startDay . '23:59:59');
                                } else {
                                    $endTime = strtotime($startDay . $v['end']);
                                }
                            }
                            $interval = intval($config['steep']);
                            if ($toDay && $startTime < time()) {
                                $startTime =  time();
                            }
                            $timeNum = ceil(($endTime - $startTime) / (60 * $interval));
                            for ($j = 0; $j < $timeNum; $j++) {
                                $itemTime = $startTime +  60 * intval($interval) * $j;
                                if ($itemTime > time() && $itemTime <= $endTime) {
                                    if ($toDay) {
                                        $data[$i]['title'] = '今天';
                                        $data[$i]['week'] = WorkEnum::format($week);
                                        $data[$i]['timeArr'][] = ['title' => date("H:i", $itemTime), 'value' => date("Y-m-d H:i", $itemTime)];
                                    } else {
                                        $data[$i]['title'] = date("m-d", $itemTime);
                                        $data[$i]['week'] = WorkEnum::format($week);
                                        $data[$i]['timeArr'][] = ['title' => date("H:i", $itemTime), 'value' => date("Y-m-d H:i", $itemTime)];
                                    }
                                }
                            }
                        }
                    };
                }
            }
            return $this->success([
                'config' => $config,
                'timeDataArr' => collect($data)->values(),
                'area' => $areaList,
                'typeList' => $typeList
            ]);
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }

    public function tableType(Request $request)
    {
        $table = FacadesDB::table('table')->where('uniacid', $this->uniacid())
            ->where('storeId', $this->storeId())
            ->where('areaId', $request->areaId)
            ->get();
        if ($table) {
            $typeList = Type::where('uniacid', $this->uniacid())
                ->where('storeId', $this->storeId())
                ->where('reserveSwitch', 1)
                ->whereIn('id', collect($table)->pluck('typeId')->all() ?? [])
                ->get();
        }
        return $this->success($typeList);
    }


    public function checkout(Request $request)
    {
        $model = new Checkout([
            'uniacid' => $this->uniacid(),
            'userId' => $this->userId(),
            'storeId' => $this->storeId(),
            'typeId' => intval($request->typeId),
            'areaId' => intval($request->areaId),
            'notes' => $request->notes,
            'score' => $this->appType(),
            'person' => $request->person,
            'mobile' => $request->mobile,
            'num' => $request->num ?? 1,
            'contact' => $request->contact,
            'appointmentTime' => $request->appointmentTime,
        ]);
        return $this->success($model);
    }
}
