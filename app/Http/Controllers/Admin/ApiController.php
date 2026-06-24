<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller as BaseController;
use App\Http\Helpers\ApiResponse;
use App\Traits\Admin\InitTrait;

class ApiController extends BaseController
{
    public $user;
    use ApiResponse, InitTrait;
}
