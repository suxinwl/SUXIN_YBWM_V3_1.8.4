<?php

namespace App\Http\Controllers\Channel\Delivery;

use App\Http\Controllers\Channel\ApiController;
use App\Models\Delivery\Rule;
use App\Models\Delivery\Channel;
use App\Models\ReallysavesMoney;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use App\Models\Admin\Apply;
use App\Services\Delivery\MaiyatianService;
class ChannelController extends ApiController
{

    public function index(Request $request)
    {
        $list = Channel::where('uniacid', $this->uniacid())
            ->paginate($request->pageSize ?? 20, '*', 'pageNo');
        return $this->success($list);
    }

    public function store(Request $request)
    {
        $model = Channel::where('uniacid', $this->uniacid())
            ->where('storeId', $this->storeId())
            ->where('type', $request->type)->first();
        if ($model) {
            return $this->failed('刷新页面后重试');
        }
        try {
            $model = new Channel();
            $model->fill($request->all());
            $model->storeId =  $this->storeId();
            $model->uniacid = $this->uniacid();
            $model->save();
            return $this->success([], '添加成功');
        } catch (\Exception $e) {
            return $this->failed('添加失败');
        }
    }

    public function show(Request $request, $type)
    {
        try {
            $model = Channel::where('uniacid', $this->uniacid())
            ->where('storeId', $this->storeId())
            ->where('type', $type)->first();
            if(!empty($model)){
                $model->setAppends(['amount']);
            }
            return $this->success($model);
        } catch (\Exception $e) {
            return $this->failed('失败');
        }
    }

    public function update(Request $request, $id)
    {
//          $apply =  Apply::find($this->uniacid());
//          $app = MaiyatianService::app($this->uniacid());
//           $list = $app->getClient()->postJson('/channel/shop/save/', ['json' => [
//                 'origin_id' => 'shopid' . $this->uniacid(),
//                 'name' => $apply->applyName,
//                 'city' => '张掖',
//                 'district' => '甘州区',
//                 'phone' => '18093688326',
//                 'address' => '甘肃省张掖市甘州区西二环路蓝山公馆五期西门',
//                 'longitude' => '100.43418',
//                 'latitude' => '38.941317',
//                 'category' => '1',
//                 'map_type' => 1
//             ]])->toArray(false);
//             var_dump($list);die;
        try {
            $model = Channel::where('uniacid', $this->uniacid())->find($id);
            if (!$model) {
                return $this->failed('数据不存在');
            }
            $model->fill($request->all());
            $model->uniacid = $this->uniacid();
            $model->save();
            return $this->success([], '更新成功');
        } catch (\Exception $e) {
            return $this->failed('更新失败');
        }
    }

    /*-------------------------------------真省钱聚合配送部分----------------------------------------*/
    //真省钱配送授权
    public function createreally(Request $request){
        $uniacid=$this->uniacid();
        $storeId=$this->storeId()?:0;
        $res = Channel::where('uniacid', $uniacid)
                ->where('storeId', $storeId)
                ->where('type', 4)->first();
        if(!$res){
            return $this->failed('请先保存设置');
        }
        $callback=getDomain().'/channel/notify/reallySavesMoney';
        try{
            $appId='8075dfd334f7435fa98817bd4c3bcf0a';
            $secret='587bd9f20b0f43919b9473acab4d22fe';
            $params=[
                'name'=>$request->appName,
                'phone'=>$request->contactPhone,
                'callbackUrl'=>$callback
            ];
            $row=ReallysavesMoney::createApplication($appId,$secret,$params);
            file_put_contents('createreally.log',json_encode($row).PHP_EOL,FILE_APPEND);
            if($row['code']==200){
                $config=$res['config'];
                $config['appId']=$row['data']['appId'];
                $config['secret']=$row['data']['secret'];
                $config['reallyName']=$request->appName;
                $config['cityName']=$request->cityName;
                $res->config=$config;
                $res->save();
                return $this->success([]);
            }
            return $this->failed($row['message']);
        } catch (Exception $e) {
            return $this->failed($e->getMessage());
        }
    }

    //查询平台支持运力列表
    public function supplierQuery(Request $request){
        $uniacid=$this->uniacid();
        $storeId=$this->storeId()?:0;
        $res = Channel::where('uniacid', $uniacid)
            ->where('storeId', $storeId)
            ->where('type', 4)->first();
        $usableAmt=0;
        $deliveryData=[];
        $config=$res->config;
        if($config['appId']&&$config['secret']){
            $data=ReallysavesMoney::supplierQuery($config['appId'],$config['secret']);
            $balanceData=ReallysavesMoney::walletBalance($config['appId'],$config['secret']);
            file_put_contents('supplierQuery.log',json_encode($balanceData).PHP_EOL,FILE_APPEND);
            if($balanceData){
                if($balanceData['code']==200){
                    $usableAmt=bcdiv($balanceData['data']['usableAmt'],100,2);
                }
            }
            if($data){
                if($data['code']==200){
                    $deliveryData=$data['data'];
                }
            }
        }
        $re=[
            'usableAmt'=>$usableAmt,
            'deliveryData'=>$deliveryData
        ];
        return $this->success($re);
    }

    //创建发货门店
    public function storeAuthorization(Request $request){
        $uniacid=$this->uniacid();
        $storeId=$this->storeId()?:0;
        $res = Channel::where('uniacid', $uniacid)
            ->where('storeId', $storeId)
            ->where('type', 4)->first();
        $config=$res->config;
        if(!$config['appId']||!$config['secret']){
            return $this->failed('请先点击授权');
        }
        $params=array(
            'contactName'=>$request->contactName,
            'outShopId'=>$storeId?:$uniacid,
            'callOrderType'=>1,
            'shopName'=>$request->shopName,
            'shopAddress'=>$request->shopAddress,
            'cityName'=>$request->cityName,
            'industryType'=>$request->industryType,
            'deliverySupplierList'=>$request->deliverySupplierList,
            'coordinateType'=>1,
            'shopLng'=>$request->shopLng,
            'contactPhone'=>$request->contactPhone,
            'shopAddressDetail'=>$request->shopAddressDetail,
            'shopLat'=>$request->shopLat,
        );
        if($config['reallyStoreId']){
            $data=ReallysavesMoney::updateStore($config['appId'],$config['secret'],$params);
            file_put_contents('storeAuthorization.log',json_encode($data).PHP_EOL,FILE_APPEND);
            if($data['code']!==200){
                return $this->failed($data['message']);
            }else{
                $reallyStoreIddata=$data['data'];
                $config['reallyStoreId']=$reallyStoreIddata['shopId'];
                $res->config=$config;
                $res->save();
            }
            return $this->success([]);
        }else{
            $data=ReallysavesMoney::createStore($config['appId'],$config['secret'],$params);
            file_put_contents('storeAuthorization.log',json_encode($data).PHP_EOL,FILE_APPEND);
            if($data['code']!==200){
                return $this->failed($data['message']);
            }
            $reallyStoreIddata=$data['data'];
            $config['reallyStoreId']=$reallyStoreIddata['shopId'];
            $res->config=$config;
            $res->save();
            return $this->success([]);
        }

    }
    //真省钱配送充值
    public function storeValue(Request $request){
        $uniacid=$this->uniacid();
        $storeId=$this->storeId()?:0;
        $res = Channel::where('uniacid', $uniacid)
            ->where('storeId', $storeId)
            ->where('type', 4)->first();
        $config=$res->config;
        if(!trim($request->rechargePrice)){
            return $this->failed('请输入充值金额');
        }
        if($config['appId']&&$config['secret']){
            try{
                $params=[
                    'rechargeType'=>$request->rechargeType,
                    'rechargePrice'=>intval(bcmul($request->rechargePrice,100,2))
                ];
                $balanceData=ReallysavesMoney::accountRecharge($config['appId'],$config['secret'],$params);
                //file_put_contents('storeValue.log',json_encode($balanceData).PHP_EOL,FILE_APPEND);
                if($balanceData['code']==200){
                    return $this->success($balanceData['data']['qrCodeUrl']);
                }else{
                    return $this->failed($balanceData['message']);
                }
            }  catch (Exception $e) {
                return $this->failed($e->getMessage());
            }
        }else{
            return $this->failed('请先点击授权');
        }
    }
}
