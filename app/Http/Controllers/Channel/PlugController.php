<?php

namespace App\Http\Controllers\Channel;

use Illuminate\Http\Request;
use App\Models\ApplyPlugs;
use App\Models\Plug;

class PlugController extends ApiController
{


    public function index(Request $req, Plug $model)
    {
        $list = ApplyPlugs::with(['plug'=>function($q){
            return $q->select('id','baseName','baseLogo','baseDesc','name','logo','desc','appType','appName');
        }])->whereHas('plug', function ($q) use ($req) {
            if ($req->type = 'channel') {
                $q->where('appType', $req->type);
            }
            return $q;
        })->where('uniacid', $this->uniacid())->where('state', 1)->where(function ($q) {
            $q->where('endTime', '>=', date("Y-m-d H:i:s", time()))->orWhere(function($q){
                return $q->whereNull('endTime');
            });
            return $q;
        })->orderByWith('plug','sort','asc')->get();
        return $this->success(collect($list)->pluck('plug'));
    }
}
