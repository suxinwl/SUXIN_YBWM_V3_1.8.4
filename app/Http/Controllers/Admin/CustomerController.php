<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class CustomerController extends ApiController
{
    //客服列表
    public function getCustomerList(Request $request)
    {
        if ($request->input('id')) {
            $data = Customer::find(intval($request->input('id')));
        } else {
            $customer_model = new Customer();
            if ($request->input('keyword')) {
                $customer_model = $customer_model->where('userName', 'like', '%' . $request->input('keyword') . '%');
            }
            $data = $customer_model->orderBy('sort', 'asc')->orderBy('id', 'desc')->paginate($req->pageSize ?? 30, '*', 'pageNo');
        }
        return $this->success($data, __('base.success'));
    }

    public function saveCustomer(Request $request)
    {
        $customer_model = new Customer();
        if ($request->input('id')) {
            $customer_model = Customer::find(intval($request->input('id')));
            $customer_model->sort = $request->input('sort');
            $customer_model->userName = $request->input('userName');
            $customer_model->contact_information = $request->input('contact_information');
            $customer_model->qq = $request->input('qq');
            $customer_model->gzh = $request->input('gzh');
            $customer_model->isDefault = $request->input('isDefault', 0);
            $customer_model->wechat_qrcode = $request->input('wechat_qrcode');
            if ($customer_model->isDefault == 1) {
                Customer::where('id', '>', 0)->update(['isDefault' => 0]);
            }
            $customer_model->save();
            return $this->success([], __('base.success'));
        } else {
            $customer_model->sort = $request->input('sort');
            $customer_model->userName = $request->input('userName');
            $customer_model->contact_information = $request->input('contact_information');
            $customer_model->qq = $request->input('qq');
            $customer_model->gzh = $request->input('gzh');
            $customer_model->isDefault = $request->input('isDefault', 0);
            $customer_model->wechat_qrcode = $request->input('wechat_qrcode');
            if ($customer_model->isDefault == 1) {
                Customer::where('id', '>', 0)->update(['isDefault' => 0]);
            }
            $customer_model->save();
            return $this->success([], __('base.success'));
        }
    }

    public function changeCustomer(Request $request)
    {
        $id = $request->input('id');
        $type = $request->input('type') ?: 1;
        if ($type == 1) {
            $customer_model = Customer::find($id);
            if ($customer_model->state == 1) {
                $state = 2;
            } else {
                $state = 1;
            }
            $customer_model->state = $state;
            $customer_model->save();
        } else {
            $customer_model = new Customer();
            $idArray = array_filter(explode(',', $id), function ($item) {
                return is_numeric($item);
            });
            $customer_model->whereIn('id', $idArray)->delete();
        }
        return $this->success([], __('base.success'));
    }
}
