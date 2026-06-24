<?php

namespace App\Http\Controllers\Admin;

use App\Http\Resources\Admin\Sms\SmsPayCollection;
use App\Models\SmsOrder;
use Illuminate\Http\Request;


class SmsPayController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $list = SmsOrder::orderBy('sort', 'asc')->paginate($request->pageSize ?? 30);
        return $this->success(new SmsPayCollection($list));
    }
}
