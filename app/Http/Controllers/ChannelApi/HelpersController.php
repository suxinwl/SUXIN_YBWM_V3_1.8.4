<?php

namespace App\Http\Controllers\ChannelApi;

use App\Http\Controllers\Channel\ApiController;
use App\Http\Requests\Admin\ChangePassword;
use App\Models\Ad;
use App\Models\Coupon\Coupon;
use App\Models\FullSub\FullSub;
use App\Models\FullSub\Goods;
use App\Models\FullSub\Store;
use App\Models\ShopHelpers;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class HelpersController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $list = ShopHelpers::where('uniacid', $this->uniacid())
            ->paginate(Request()->size ?? 20, '*', 'page');
        return $this->success($list);
    }
}
