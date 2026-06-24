<?php

namespace App\Http\Controllers\Channel;

use App\Http\Controllers\Controller as BaseController;
use App\Http\Helpers\ApiResponse;
use App\Traits\ChannelInitTrait;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Traits\StatisticsTrait;

class ApiController extends BaseController
{
    use ApiResponse, ChannelInitTrait, StatisticsTrait;
}
