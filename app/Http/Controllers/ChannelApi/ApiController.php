<?php

namespace App\Http\Controllers\ChannelApi;

use App\Http\Controllers\Controller as BaseController;
use App\Http\Helpers\ApiResponse;
use App\Traits\ChannelApiInitTrait;
use App\Traits\ChannelInitTrait;
use Illuminate\Support\Facades\Request;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Tymon\JWTAuth\Facades\JWTAuth;

class ApiController extends BaseController
{
    use ApiResponse, ChannelApiInitTrait;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (empty($this->appType())) {
                throw new BadRequestException('缺少参数appType');
            }
            if (empty($this->uniacid())) {
                throw new BadRequestException('uniacid');
            }
            return $next($request);
        });
    }
}
