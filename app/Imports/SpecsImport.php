<?php

namespace App\Imports;

use AlibabaCloud\SDK\Iot\V20180120\Models\ListAnalyticsDataRequest\condition;
use App\Events\MemberRegisteredEvent;
use App\Models\Store;
use App\Models\GoodsCat;
use App\Models\GoodsUnit;
use App\Models\GoodsSku;
use App\Models\GoodsSpu;
use App\Models\Material;
use App\Models\GoodsLabel;
use App\Models\Member;
use App\Models\Attr;
use App\Models\AttrValue;
use App\Models\Goods\Channel;
use App\Models\GoodsCatLabel;
use App\Models\GoodsMark;
use App\Models\MaterialCat;
use App\Models\Member\Group;
use App\Models\MemberAccountLog;
use App\Models\MemberLabel;
use App\Models\Spec;
use App\Models\SpecValue;
use App\Models\Store\StoreGoods;
use App\Models\Store\StoreGoodsSku;
use App\Models\Wechat\Kernel\Exceptions\Exception;
use App\Services\MemberAccountService;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use App\Models\Recipe\Recipe;
use App\Models\Recipe\RecipeGoods;
use App\Models\Recipe\RecipeGoodsSku;
use App\Models\Recipe\RecipeStore;
//use Maatwebsite\Excel\Concerns\ToModel;
//use Maatwebsite\Excel\Concerns\WithStartRow;

/*
 * 控制器调用导入
 * $a=Excel::import(new SpecsImport(2,1),$request->file('file'));
   echo json_encode(['code'=>1,'msg'=>'success']);die;
*/

class SpecsImport implements ToCollection
{
    private $uniacid;
    private $importsType;
    private $storeId;
    public function __construct($uniacid, $importsType, $storeId = 0)
    {
        $this->uniacid = $uniacid;
        $this->importsType = $importsType;
        $this->storeId = $storeId;
    }
    /**
     * @param array $row
     *
     * @return User|null
     */
    public function collection(Collection  $row)
    {
        $data = $row->toArray();
        $importsType = $this->importsType;
        try {
            switch ($importsType) {
                case 'goodsCat'; //商品分类
                    $this->categoryImport($data);
                    break;
                case 'spec'; //规格管理
                    $this->specImport($data);
                    break;
                case 'material'; //加料管理
                    $this->materialImport($data);
                    break;
                case 'attr'; //属性管理
                    $this->attrImport($data);
                    break;
                case 'unit'; //单位管理
                    $this->unitImport($data);
                    break;
                case 'label'; //标签管理
                    $this->labelImport($data);
                    break;
                case 'mark'; //标签管理
                    $this->markImport($data);
                    break;
                case 'goods'; //标签管理
                    $recipeId=0;
                    if($this->storeId){
                        $recipeStore=RecipeStore::where('storeId',$this->storeId)->first();
                        if (empty($recipeStore)) {
                            $storeInfo=Store::where('id',$this->storeId)->first();
                            $recipeModel =Recipe::create([
                                'uniacid'=>$this->uniacid,
                                'name'=>$storeInfo->name,
                            ]);
                            RecipeStore::create([
                                'uniacid'=>$this->uniacid,
                                'recipeId'=>$recipeModel->id,
                                'storeId'=>$this->storeId,
                            ]);
                            $recipeId=$recipeModel->id;
                        }else{
                            $recipeId=$recipeStore->recipeId;
                        }

                    }
                    $this->goodsImport($data,$recipeId);
                    break;
                case 'member'; //标签管理
                    $this->memberImport($data);
                    break;
            }
        } catch (Exception $e) {
            throw new BadRequestException($e->getMessage());
        }
    }

    //商品分类
    public function categoryImport($row)
    {
        DB::beginTransaction();
        try {
            $headers = array_shift($row);
            foreach ($row as $key => $v) {
                $line = $key + 2;
                if (empty($v[1])) {
                    DB::rollBack();
                    throw new BadRequestException("第{$line}行的" . $headers[1] . '不能为空');
                }
                if (!empty($v[4])) {
                    $label = GoodsCatLabel::where('uniacid', $this->uniacid)
                        ->where('storeId', $this->storeId)
                        ->where("name", $v[4])
                        ->first();
                    if (empty($label)) {
                        $label = GoodsCatLabel::create(['name' => $v[4], 'sort' => 0, 'uniacid' => $this->uniacid]);
                    }
                    $v[4] = $label->id;
                }
                $v[5] = $v[5] == '是' ? 1 : 0;
                $res = [
                    'uniacid' => $this->uniacid,
                    'sort' => $v[0] ?? 0,
                    'name' => $v[1],
                    'notes' => $v[2],
                    'logo' => $v[3] ?? null,
                    'labelId' => $v[4] ?? 0,
                    'isMust' => $v[5],
                    'created_at' => date('Y-m-d H:i:s', time()),
                    'storeId' => $this->storeId
                ];
                $cat=GoodsCat::where('uniacid',$this->uniacid)->where('name',$v[1])->first();
                if($cat){
                    continue;
                }else{
                    GoodsCat::insert($res);
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new BadRequestException($e->getMessage());
        }
    }
    //商品规格
    public function specImport($row)
    {
        DB::beginTransaction();
        try {
            $headers = array_shift($row);
            foreach ($row as $key => $v) {
                $line = $key + 2;
                if (empty($v[1])) {
                    DB::rollBack();
                    throw new BadRequestException("第{$line}行的" . $headers[1] . '不能为空');
                }
                if (empty($v[3])) {
                    DB::rollBack();
                    throw new BadRequestException("第{$line}行的" . $headers[3] . '不能为空');
                }
                $uniacid = $this->uniacid;
                $res = Spec::create([
                    'uniacid' => $this->uniacid,
                    'sort' => $v[0] ?? 0,
                    'name' => $v[1],
                    'desc' => $v[2] ?? null,
                    'imgSwitch' => 0,
                    'storeId' => $this->storeId
                ]);
                $value = collect(explode(',', $v[3]))->map(function ($item) use ($res, $uniacid, $v) {
                    return ['name' => $item, 'img' => $v[4] ?? null, 'specId' => $res->id, 'uniacid' => $uniacid];
                })->toArray();
                if ($value) {
                    SpecValue::insert($value);
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new BadRequestException($e->getMessage());
        }
    }
    //商品加料
    public function materialImport($row)
    {
        DB::beginTransaction();
        try {
            $headers = array_shift($row);
            foreach ($row as $key => $v) {
                $line = $key + 2;
                if (empty($v[1])) {
                    DB::rollBack();
                    throw new BadRequestException("第{$line}行的" . $headers[1] . '不能为空');
                }
                if (empty($v[2])) {
                    DB::rollBack();
                    throw new BadRequestException("第{$line}行的" . $headers[2] . '不能为空');
                }
                $cat = MaterialCat::where('uniacid', $this->uniacid)->where("name", $v[2])->first();
                if (empty($label)) {
                    $cat = MaterialCat::create(['name' => $v[2], 'sort' => 0, 'uniacid' => $this->uniacid]);
                }
                $res = [
                    'uniacid' => $this->uniacid,
                    'sort' => $v[0],
                    'name' => $v[1],
                    'catId' => $cat->id,
                    'image' => $v[3],
                    'price' => $v[4] ?? 0,
                    'inventory' => $v[5] ?? 0,
                    'autoReplenish' => $v[6] ?? 1,
                    'storeId' => $this->storeId,
                    'created_at' => date('Y-m-d H:i:s', time())
                ];
                Material::insert($res);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new BadRequestException($e->getMessage());
        }
    }
    //商品属性
    public function attrImport($row)
    {
        DB::beginTransaction();
        try {
            $headers = array_shift($row);
            foreach ($row as $key => $v) {
                $line = $key + 2;
                if (empty($v[1])) {
                    DB::rollBack();
                    throw new BadRequestException("第{$line}行的" . $headers[1] . '不能为空');
                }
                if (empty($v[2])) {
                    DB::rollBack();
                    throw new BadRequestException("第{$line}行的" . $headers[2] . '不能为空');
                }
                $uniacid = $this->uniacid;
                $res = Attr::create([
                    'uniacid' => $this->uniacid,
                    'sort' => $v[0] ?? 0,
                    'name' => $v[1],
                    'notes' => null,
                    'desc' => null,
                    'multipleSwitch' => 0,
                    'mustSwitch' => 0,
                    'storeId' => $this->storeId
                ]);
                $value = collect(explode(',', $v[2]))->map(function ($item) use ($res, $uniacid) {
                    return ['name' => $item, 'desc' => null, 'attrId' => $res->id, 'uniacid' => $uniacid];
                })->toArray();
                if ($value) {
                    AttrValue::insert($value);
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new BadRequestException($e->getMessage());
        }
    }
    //商品单位
    public function unitImport($row)
    {
        DB::beginTransaction();
        try {
            $headers = array_shift($row);
            foreach ($row as $key => $v) {
                $line = $key + 2;
                if (empty($v[1])) {
                    DB::rollBack();
                    throw new BadRequestException("第{$line}行的" . $headers[1] . '不能为空');
                }
                $res = [
                    'uniacid' => $this->uniacid,
                    'sort' => $v[0] ?? 0,
                    'name' => $v[1],
                    'storeId' => $this->storeId,
                    'created_at' => date('Y-m-d H:i:s', time())
                ];
                GoodsUnit::insert($res);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new BadRequestException($e->getMessage());
        }
    }
    //商品标签
    public function labelImport($row)
    {
        DB::beginTransaction();
        try {
            $headers = array_shift($row);
            foreach ($row as $key => $v) {
                $line = $key + 2;
                if (empty($v[1])) {
                    DB::rollBack();
                    throw new BadRequestException("第{$line}行的" . $headers[1] . '不能为空');
                }
                $res = [
                    'uniacid' => $this->uniacid,
                    'sort' => $v[0] ?? 0,
                    'name' => $v[1],
                    'bgColor' => $v[2] ?? null,
                    'storeId' => $this->storeId,
                    'created_at' => date('Y-m-d H:i:s', time())
                ];
                GoodsLabel::insert($res);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new BadRequestException($e->getMessage());
        }
    }

    //商品角标
    public function markImport($row)
    {
        DB::beginTransaction();
        try {
            $headers = array_shift($row);
            foreach ($row as $key => $v) {
                $line = $key + 2;
                if (empty($v[1])) {
                    DB::rollBack();
                    throw new BadRequestException("第{$line}行的" . $headers[1] . '不能为空');
                }
                $res = [
                    'uniacid' => $this->uniacid,
                    'sort' => $v[0] ?? 0,
                    'name' => $v[1],
                    'bgColor' => $v[2] ?? null,
                    'startTime' => date("Y-m-d 00:00:00", time()),
                    'endTime' => date("Y-m-d H:i:s", strtotime('+90 Years')),
                    'created_at' => date('Y-m-d H:i:s', time()),
                    'storeId' => $this->storeId
                ];
                GoodsMark::insert($res);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new BadRequestException($e->getMessage());
        }
    }


    //商品标签
    public function goodsImport($row,$recipeId)
    {
        DB::beginTransaction();
        try {
            $headers = array_shift($row);

            foreach ($row as $key => $v) {
                $line = $key + 2;
                if (empty($v[1])) {
                    DB::rollBack();
                    throw new BadRequestException("第{$line}行的" . $headers[1] . '不能为空');
                }
                if (empty($v[3])) {
                    DB::rollBack();
                    throw new BadRequestException("第{$line}行的" . $headers[3] . '不能为空');
                }
                if (empty($v[10])) {
                    DB::rollBack();
                    throw new BadRequestException("第{$line}行的" . $headers[10] . '不能为空');
                }
                if (empty($v[13])) {
                    DB::rollBack();
                    throw new BadRequestException("第{$line}行的" . $headers[13] . '不能为空');
                }
                // $cat = GoodsCat::where("name", $v[3])->first();
                // if (empty($label)) {
                //     $cat = GoodsCat::create(['name' => $v[3], 'sort' => 0, 'uniacid' => $this->uniacid]);
                // }
                $v[3] = collect(explode(',', $v[3]))->map(function ($item, $key) {
                    $model = GoodsCat::where('uniacid', $this->uniacid)
                        ->where("name", $item)
                        ->first();
                    if (empty($model)) {
                        $model = GoodsCat::create(['name' => $item, 'sort' => 0,  'uniacid' => $this->uniacid]);
                        return  $model;
                    }
                    return  $model;
                })->pluck('id')->all();
                if(!empty($v[4])){
                    $v[4] = collect(explode(',', $v[4]))->map(function ($item, $key) {
                        $label = GoodsLabel::where('uniacid', $this->uniacid)
                            ->where("name", $item)
                            ->where('storeId', $this->storeId)
                            ->first();
                        if (empty($label)) {
                            $model = GoodsLabel::create(['name' => $item, 'sort' => 0, 'storeId' => $this->storeId, 'uniacid' => $this->uniacid]);
                            return $model;
                        }
                        return  $label;
                    })->pluck('id')->all();
                }

                if (!empty($v[5])) {
                    $label = GoodsMark::where('uniacid', $this->uniacid)
                        ->where("name", $v[5])
                        ->where('storeId', $this->storeId)
                        ->first();
                    if (empty($label)) {
                        $label = GoodsMark::create(['name' => $v[5], 'storeId' => $this->storeId, 'sort' => 0, 'uniacid' => $this->uniacid]);
                    }
                    $v[5] = $label->id;
                }

                if (!empty($v[9])) {
                    $unit = GoodsUnit::where('uniacid', $this->uniacid)
                        ->where('storeId', $this->storeId)
                        ->where("name", $v[9])->first();
                    if (empty($label)) {
                        $label = GoodsUnit::create(['name' => $v[9], 'storeId' => $this->storeId, 'sort' => 0, 'uniacid' => $this->uniacid]);
                    }
                    $v[9] = $label->id;
                }

                if (!empty($v[17])) {
                    $channelStr='';
                    switch($v[16]){
                        case '外卖';
                            $v[16]=[1];
                            break;
                        case  '店内';
                            $v[16]=[2];
                            break;
                        case '外卖,店内';
                            $v[16]=[1,2];
                            break;
                        default;
                            break;
                    }



                }

                if (!empty($v[18])) {
                    $v[18] = explode(',', $v[18]);
                }

                $spu = [
                    'uniacid' => $this->uniacid,
                    'type' => 1,
                    'sort' => $v[0],
                    'name' => $v[1],
                    'desc' => $v[2],
                    'catId' => $v[3],
                    'labelId' => $v[4],
                    'markId' => $v[5] ?? 0,
                    'pinYin' => $v[6] ?? null,
                    'initialSales' => $v[7] ?? 0,
                    'logo' => $v[8] ?? null,
                    'unitId' => $v[9] ?? 0,
                    'sales' => 0,
                    'isExhibition' => 0,
                    'cover' => null,
                    'video' => null,
                    'isShow' => 0,
                    'specSwitch' => 0,
                    'specData' => [],
                    'attrSwitch' => 0,
                    'attrData' => [],
                    'materialSwitch' => 0,
                    'salesTimeSwitch' => [],
                    'salesTimeData' => [],
                    'salesType' => 1,
                    'orderlimitSwitch' => 0,
                    'orderlimit' => 1,
                    'userlimitSwitch' => 0,
                    'userlimit' => 1,
                    'daylimitSwitch' => 0,
                    'daylimit' => 1,
                    'oneDeliverySwitch' => 0,
                    'min' => 1,
                    'vipPriceSwitch' => 0,
                    'state' => 1,
                    'channelIds' => $v[16],
                    'images' => $v[18] ?? []
                ];
                $model = new GoodsSpu();
                $model->fill($spu);
                $model->uniacid = $this->uniacid;
                $model->storeId = $this->storeId;
                $model->save();
                $model->category()->sync($model->catId ?? [], ['uniacid', $model->uniacid]);
                $model->label()->sync($model->labelId ?? [], ['uniacid', $model->uniacid]);
                $singleSpec = new GoodsSku();
                $singleSpec->type = 1;
                $singleSpec->sort = 0;
                $singleSpec->spuId = $model->id;
                $singleSpec->uniacid = $model->uniacid;
                $singleSpec->specMd5 = md5($model->id . 'spec:' . $model->specSwitch);
                $singleSpec->price = $v[10] ?? 0;
                $singleSpec->linePrice = $v[10] ?? 0;
                $singleSpec->costPrice = 0;
                $singleSpec->boxMoney = $v[12] ?? 0;
                $singleSpec->inventory = $v[13];
                $singleSpec->component = $v[14] ?? 0;
                $singleSpec->dayFilling = 1;
                $singleSpec->barcode = $v[15];
                $singleSpec->sn = $v[16];
                $singleSpec->save();
                if ($model->channelIds) {
                    foreach ($model->channelIds as $key => $id) {
                        Channel::create([
                            'uniacid' => $model->uniacid,
                            'spuId' => $model->id,
                            'channelId' => $id
                        ]);
                    }
                }
                if ($model->storeId > 0) {
                    RecipeGoods::create([
                        "uniacid" => $model->uniacid,
                        'recipeId' =>$recipeId,
                        'spuId' => $model->id,
                        'state' => 1,
                        'type' => 1,
                    ]);

                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new BadRequestException($e->getMessage());
        }
    }

    public function memberImport($row)
    {
        DB::beginTransaction();
        try {
            $headers = array_shift($row);
            foreach ($row as $key => $v) {
                $line = $key + 2;
                $user = Member::where('uniacid', $this->uniacid)->where('mobile', $v[0])->first();
                if (empty($v[0])) {
                    DB::rollBack();
                    throw new BadRequestException("第{$line}行的" . $headers[0] . '不能为空');
                }
                if ($user) {
                    throw new BadRequestException("第{$line}行的" . $headers[0] . '重复');
                }
                if (!empty($v[6])) {
                    $v[6] = collect(explode(',', $v[6]))->map(function ($item, $key) {
                        $label = MemberLabel::where('uniacid', $this->uniacid)
                            ->where("title", $item)
                            ->first();
                        if (empty($label)) {
                            $model = MemberLabel::create(['title' => $item, 'sort' => 0, 'uniacid' => $this->uniacid]);
                            return $model;
                        }
                        return  $label;
                    })->pluck('id')->all();
                }
                if (!empty($v[7])) {
                    $model = Group::where('uniacid', $this->uniacid)->where("name", $v[7])->first();
                    if (empty($model)) {
                        $model = Group::create(['name' => $v[7], 'sort' => 0, 'uniacid' => $this->uniacid]);
                    }
                    $v[7] =  $model->id;
                }
                if (!empty($v[2])) {
                    $v[2] = $v[2] == '男' ? 1 : 0;
                } else {
                    $v[2] = null;
                }

                $model =  new Member();
                $model->nickname = "用户_" . rand(10000, 99999);
                $model->mobile = $v[0];
                $model->uniacid = $this->uniacid;
                $model->realname = $v[1] ?? null;
                $model->sex = $v[2];
                $model->birthday = $v[3] ?? null;
                $model->labelId = $v[6] ?? null;
                $model->groupId = $v[7] ?? 0;
                $model->vipId = $model->initVip();
                $model->vipCard = getVipCardNo();
                $model->score = 9;
                $model->vipCreateTime = date("Y-m-d H:i:s", time());
                $model->save();
                $model->label()->sync($model->labelId);
                Event(new MemberRegisteredEvent($model));
                if (!empty($v[4])) {
                    MemberAccountService::changeBalance($model->id, 1, $v[4], MemberAccountLog::BASE, 0, '导入用户余额');
                }
                if (!empty($v[5])) {
                    MemberAccountService::changeIntegral($model->id, 1, $v[5], MemberAccountLog::BASE, 0, '导入用户积分');
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new BadRequestException($e->getMessage());
        }
    }
}
