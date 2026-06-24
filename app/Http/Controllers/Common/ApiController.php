<?php

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller as BaseController;
use App\Http\Helpers\ApiResponse;

class ApiController extends BaseController
{
    use ApiResponse;
    protected  $uniacid;
    public function __construct(){
        $user=auth('admin')->user();
        $this->uniacid=$user->uniacid?:0;
    }
}
