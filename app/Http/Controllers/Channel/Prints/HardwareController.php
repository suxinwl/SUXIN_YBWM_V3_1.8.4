<?php

namespace App\Http\Controllers\Channel\Prints;

use App\Http\Controllers\Channel\ApiController;
use App\Models\Hardware;
use App\Models\Printer;
use App\Models\PrintRule;
use App\Models\Store;
use Http;
use Illuminate\Http\Request;

class HardwareController extends ApiController
{

    public function storeList(Request $request)
    {
        try {
            $storeId = $this->storeId();
            $user = $this->user();
            $res = Store::select(["id", 'name', 'uniacid', 'sort', 'address'])->addSelect([
                'xiaopiaoCount' => Hardware::selectRaw('COUNT(*)')->whereColumn('storeId', 'store.id')->where(['type' => 1]),
                'biaoqianCount' => Hardware::selectRaw('COUNT(*)')->whereColumn('storeId', 'store.id')->where(['type' => 2]),
                'yunlabaCount' => Hardware::selectRaw('COUNT(*)')->whereColumn('storeId', 'store.id')->where(['type' => 3])
            ])->when($request->name, function ($q) use ($request) {
                return $q->where('name', "like", "%{$request->name}%");
            })->when($this->storeId(), function ($q) use ($storeId) {
                return $q->where('id', $this->storeId());
            })->when($user, function ($q) use ($user) {
                if ($user->isAdmin == 0) {
                    if (empty($user->storeId)) {
                        $q->where('id', 0);
                    } else {
                        $q->whereIn('id', $user->storeId);
                    }
                }
                return $q;
            })
                ->where('uniacid', $this->uniacid())
                ->orderBy('id', 'asc')
                ->paginate($request->pageSize ?? 20, '*', 'pageNo');
            return $this->success($res);
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }

    public function index(Request $request)
    {
        $list = Hardware::with(['rule'])->where('uniacid', $this->uniacid())
            ->where('storeId', $this->storeId())
            ->when($request->keyword, function ($q) {
                return $q->where('notes', 'like', "$q->keyword%");
            })
            ->orderBy('sort', 'asc')
            ->orderBy('id', 'desc')
            ->paginate($request->pageSize ?? 20, '*', 'pageNo');
        return $this->success($list);
    }


    public function store(Request $request)
    {
        try {
            $model = new Hardware();
            $model->fill($request->all());
            $model->uniacid = $this->uniacid();
            $model->storeId = $this->storeId();
            $model->save();
            $res=PrintRule::where('printId',$model->id)->first();
            if(!$res){
                $printRuleModel=new PrintRule();
                $printRuleModel->uniacid=$model->uniacid;
                $printRuleModel->storeId=$model->storeId;
                $printRuleModel->printId=$model->id;
                $printRuleModel->type=1;
                $printRuleModel->scene=[1,2];
                if($model->type==1){
                    $printRuleModel->config=[
                        'czPrintNum'=>1,
                        'dmfPrintNum'=>1,
                        'hcWmGoodsClass'=>[],
                        'hcWmPrintMet'=>1,
                        'hcWmPrintNum'=>1,
                        'hcWmPrintWay'=>1,
                        'hcWmSelectGoods'=>[],
                        'jzdPrintNum'=>1,
                        'kdPrintNum'=>1,
                        'qtWmBusiness'=>1,
                        'qtWmCustomer'=>1,
                        'qtWmJoin'=>1,
                        'qtWmRefund'=>1,
                        'turntable'=>1,
                        'yjdPrintNum'=>1,
                        'zdPrintNum'=>1,
                    ];
                }
                if($model->type==2){
                    $printRuleModel->config=[
                        'hcWmGoodsClass'=>[],
                        'hcWmPrintMet'=>1,
                        'hcWmPrintNum'=>1,
                        'hcWmPrintWay'=>1,
                        'hcWmSelectGoods'=>[],
                        'qtWmBusiness'=>1,
                        'qtWmCustomer'=>1,
                        'qtWmJoin'=>1,
                    ];
                }
                $printRuleModel->md5Str=1;
                $printRuleModel->name=$model->vendor;
                $printRuleModel->save();
            }
            return $this->success([], '添加成功');
        } catch (\Exception $e) {
            return $this->failed('添加失败');
        }
    }

    public function show(Request $request, $id)
    {
        try {
            $model = Hardware::where('uniacid', $this->uniacid())
                ->where('storeId', $this->storeId())
                ->find($id);
            if (!$model) {
                return $this->failed('数据不存在');
            }
            return $this->success($model);
        } catch (\Exception $e) {
            return $this->failed('失败');
        }
    }
    public function update(Request $request, $id)
    {
        try {
            $model = Hardware::where('uniacid', $this->uniacid())
                ->where('storeId', $this->storeId())
                ->find($id);
            if (!$model) {
                return $this->failed('数据不存在');
            }
            $model->fill($request->all());
            $model->uniacid = $this->uniacid();
            $model->storeId = $this->storeId();
            $model->save();
            return $this->success([], '更新成功');
        } catch (\Exception $e) {
            return $this->failed('更新失败');
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $idArray = array_filter(explode(',', $id), function ($item) {
                return is_numeric($item);
            });
            Hardware::where('uniacid', $this->uniacid())
                ->whereIn('id', $idArray)->delete();
            PrintRule::where('uniacid', $this->uniacid())
                ->whereIn('printId', $idArray)->delete();
            return $this->success([], '删除成功');
        } catch (\Exception $e) {
            return $this->failed('删除失败');
        }
    }

    //打印测试
    public function printTest(Request $request, $id)
    {
        $params = Hardware::where('id', $id)->first();
        if ($params) {
            $params = empty($params) ? array() : $params->toArray();
            if ($params['type'] == 1) {
                if ($params['vendor'] == 'feie') {
                    Printer::printTest($params, 1);
                }
                if ($params['vendor'] == 'esLink') {
                    Printer::printTest($params, 2);
                }
                if ($params['vendor'] == 'spyun') {
                    Printer::printTest($params, 4);
                }
                if ($params['vendor'] == 'daqu') {
                    Printer::printTest($params, 5);
                }
                if ($params['vendor'] == 'jiabo') {
                    Printer::printTest($params, 6);
                }
            }
            if ($params['type'] == 2) {
                Printer::printTest($params, 3);
            }
            if ($params['type'] == 3) {
                $data = [
                    'cmd' => 'voice',
                    'msg' => '测试云喇叭链接成功',
                    'msgid' => time() . rand(1, 999999)
                ];

                if(substr($params->config['sn'],1,2)=='MS'){
                    $url='http://cs.mqlinks.com/txmsgpush/';
                    $map = [
                        'sbx_id' => $params->config['sn'],
                        'agent_id' => json_encode($data, true),
                    ];
                }else{
                    $url='http://cs.mqlinks.com/msgpush2/qos0.php';
                    $map = [
                        'sbx_id' => $params->config['sn'],
                        'agent_id' => base64_encode(json_encode($data, true)),
                    ];
                }
                Http::asJson()->post($url, $map)->body();
            }
            return $this->success([], '打印成功');
        } else {
            return $this->failed(__('打印机不存在'));
        }
    }
}
