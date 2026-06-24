<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PlugRequest;
use App\Http\Resources\Admin\Plug\PlugGroupCollection;
use App\Models\Plug;
use Illuminate\Support\Facades\DB;

class PlugController extends ApiController
{


    public function index(Request $req, Plug $model)
    {
        if (!empty($req->name)) {
            $model = $model->where('baseName', 'like', '%' . $req->name . '%');
        }
        if ($req->appType) {
            $model = $model->where('appType', $req->appType);
        }

        $list = $model->withTrashed()->where('status', 1)
            ->orderBy('sort', 'asc')
            ->orderBy('status', 'desc')
            ->orderBy('id', 'asc')
            ->paginate($req->pageSize ?? 999);
        //var_dump($list);die;
        if ($req->view == 'group') {
            //var_dump($list);die;
            $list = new PlugGroupCollection($list);
        }
        //var_dump($list);die;
        return $this->success($list);
    }

    public function list(Request $req, Plug $model)
    {
        $list = $model->withTrashed()->select(['id', 'appName'])
            ->where('status', 1)->whereNotIn('appName',['douyin','miniPlay','dividend'])
            ->orderBy('sort', 'asc')
            ->orderBy('status', 'desc')
            ->orderBy('id', 'asc')
            ->get();
            $list = collect($list)->pluck('appName')->all();
        return $this->success($list);
    }

    public function show($id)
    {
        return $this->success(Plug::find($id));
    }

    public function update(PlugRequest $req, Plug $model)
    {
        $model = Plug::find($req->id);
        if (empty($model)) {
            return $this->failed('数据不存在');
        }
        $model->fill($req->all());
        $model->save();
        return $this->success([]);
    }

    public function state(Request $req, Plug $model)
    {
        $model = Plug::find($req->id);
        if (empty($model)) {
            return $this->failed('数据不存在');
        }
        $model->status = intval(!$model->status);
        $model->save();
        return $this->success([]);
    }

    public function OrderList(Request $request)
    {
        $data = array(
            'domain_url' => Request()->server('HTTP_HOST'),
            'pageNo' => $request->pageNo ?: 1,
            'pageSize' => $request->pageSize ?: 30,
        );
        $url = config('app.authorizeDomain') . '/api/order/get-order-list';
        $data = httpRequest($url, $data);
        return $data;
    }

    public function createOrder(Request $request)
    {
        $server_id = sg_get_machine_id();
        $data = array(
            'domain_url' => 'https://' . Request()->server('HTTP_HOST'),
            'pay_method' => $request->input('pay_method') ?: 1,
            'plug_id' => $request->input('plug_id'),
            'server_id' => $server_id,
        );
        $url = config('app.authorizeDomain') . '/api/order/create-order';
        $data = httpRequest($url, $data);
        return $data;
    }

    public function checkPaymentStatus(Request $request)
    {
        $outTradeNo = $request->input('outTradeNo');
        $data = array(
            'domain_url' => 'https://' . Request()->server('HTTP_HOST'),
            'outTradeNo' => $outTradeNo,
        );
        $url = config('app.authorizeDomain') . '/api/order/check-payment-status';
        $data = httpRequest($url, $data);
        return $data;
    }
}
