<?php

namespace App\Imports;

use App\Models\Store;
use App\Models\GoodsCat;
use App\Models\GoodsUnit;
use App\Models\GoodsSku;
use App\Models\GoodsSpu;
use App\Models\Material;
use App\Models\GoodsLabel;
use App\Models\Member;
use App\Models\Attr;
use App\Models\Region;
use App\Models\StoreGroup;
use App\Models\StoreLabel;
use App\Models\Wechat\Kernel\Exceptions\Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

//use Maatwebsite\Excel\Concerns\ToModel;
//use Maatwebsite\Excel\Concerns\WithStartRow;

/*
 * 控制器调用导入
 * $a=Excel::import(new SpecsImport(2,1),$request->file('file'));
   echo json_encode(['code'=>1,'msg'=>'success']);die;
*/

class StoreImport implements ToArray
{
    private $uniacid;
    private $pro = [];
    private $city = [];
    private $area = [];
    private $group = [];
    private $label = [];
    private $store = [];
    private $apply = [];
    public function __construct($uniacid)
    {
        $this->uniacid = $uniacid;
        $this->apply = DB::table('apply')->where('id', $this->uniacid)->first();
        if (empty($this->apply)) {
            throw new BadRequestException('店铺不存在');
        }
        $this->store = DB::table('store')->select(['id', 'name', 'storeSn'])->where('uniacid', $this->uniacid)->get();
        $this->pro = DB::table('core_district')->select(['id', 'name'])->where('level', 0)->where('pid', 0)->get();
        $this->city = DB::table('core_district')->select(['id', 'name'])->where('level', 1)->get();
        $this->area = DB::table('core_district')->select(['id', 'name'])->where('level', 2)->get();
        $this->group = DB::table('store_group')->select(['id', 'name'])->where('uniacid', $this->uniacid)->get();
        $this->label = DB::table('store_label')->select(['id', 'name'])->where('uniacid', $this->uniacid)->get();
    }

    /**
     * @param array $row
     *
     * @return User|null
     */
    public function array(array  $rows)
    {
        $headers = array_shift($rows);
        if ($this->apply->storeNumInfinite == 0  && count($rows) > ($this->apply->storeNum - count($this->store))) {
            throw new BadRequestException('当前店铺最多还可以创建' . ($this->apply->storeNum - count($this->store)) . "个门店");
        }
        DB::beginTransaction();
        try {
            DB::beginTransaction();
            $list =  collect($rows)->map(function ($item, $key) use ($headers) {
                $line = $key + 2;
                if (empty($item[1])) {
                    throw new BadRequestException("第{$line}行的" . $headers[1] . '不能为空');
                }

                if (!empty($item[2] && !empty(collect($this->store)->where("storeSn", $item[2])->first()))) {
                    throw new BadRequestException("第{$line}行的" . $headers[2] . '重复');
                }

                $pro = collect($this->pro)->where("name", "like", "{$item[3]}")->first();
                if (empty($pro)) {
                    throw new BadRequestException("第{$line}行的" . $headers[3] . '不正确');
                }
                $city = collect($this->city)->where("name", "like", "$item[4]")->first();
                if (empty($city)) {
                    throw new BadRequestException("第{$line}行的" . $headers[4] . '不正确');
                }
                $area = collect($this->area)->where("name", "like", "$item[5]")->first();
                if (empty($area)) {
                    throw new BadRequestException("第{$line}行的" . $headers[5] . '不正确');
                }
                if (empty($item[6])) {
                    throw new BadRequestException("第{$line}行的" . $headers[6] . '不能为空');
                }
                if (empty($item[7])) {
                    throw new BadRequestException("第{$line}行的" . $headers[7] . '不能为空');
                }
                if (empty($item[8])) {
                    throw new BadRequestException("第{$line}行的" . $headers[8] . '不能为空');
                }
                if (empty($item[9])) {
                    throw new BadRequestException("第{$line}行的" . $headers[9] . '不能为空');
                }
                if (empty($item[10])) {
                    throw new BadRequestException("第{$line}行的" . $headers[10] . '不能为空');
                }
                if (empty($item[11])) {
                    throw new BadRequestException("第{$line}行的" . $headers[11] . '不能为空');
                }
                if (!empty($item[12])) {
                    $item[12] = collect(explode(',', $item[12]))->map(function ($item, $key) {
                        $label = collect($this->label)->where("name", $item)->first();
                        if (empty($label)) {
                            $model = StoreLabel::create(['name' => $item, 'sort' => 0]);
                            $this->label = collect($this->label)->add($model);
                            return $model;
                        }
                        return  $label;
                    })->pluck('id')->all();
                }
                if (!empty($item[13])) {
                    $group = collect($this->group)->where("name", $item)->first();
                    if (empty($group)) {
                        $model = StoreGroup::create(['name' => $item[13], 'sort' => 0]);
                        $this->group =  collect($this->group)->add($model);
                        $item[13] =  $model->id;
                    }
                    $item[13] =   $group->id;
                }
                $data['businessData'] = json_decode('{"week":[1,2,3,4,5,6,7,0],"times":[{"start":"00:00","end":"23:45","ciri":false}]}', true);
                if (!empty($item[14])) {
                    $data['businessData']['times']  = collect(explode(',', $item[14]))->map(function ($item) {
                        $item = explode('-', $item);
                        $ciri = false;
                        if (strtotime("2023-01-01 " . $item[0]) > strtotime("2023-01-01 " . $item[1])) {
                            $ciri = true;
                        }
                        return ['start' => $item[0], 'end' => $item[1], 'ciri' => $ciri];
                    });
                }
                $data['sort'] = $item[0]  ?? 0;
                $data['name'] = $item[1];
                $data['storeSn'] = $item[2];
                $data['region'] = [$pro->id, $city->id, $area->id];
                $data['address'] = $item[6];
                $data['lat'] = $item[7];
                $data['lng'] = $item[8];
                $data['contact'] = $item[9];
                $data['mobile'] = $item[10];
                $data['storeMobile'] = $item[11];
                $data['labelId'] = $item[12] ?? [];
                $data['groupId'] = $item[13] ?? 0;
                $data['surroundings'] = $item[14] ? explode(',',$item[14]) : null;
                $data['businessLicense'] = $item[15] ?? '';
                $data['tradeLicense'] = $item[16] ?? '';
                $data['operatingStatus'] = 1;
                $data['businessStatus'] = 1;
                $data['isShowSwitch'] = 1;
                $data['uniacid'] = $this->uniacid;
                $store = Store::create($data);
                $store->label()->attach($store->labelId);
                DB::commit();
            })->toArray();
            DB::commit();
            return $list;
        } catch (\Exception $e) {
            DB::rollBack();
            return throw new BadRequestException($e->getMessage());
        }
    }
}
