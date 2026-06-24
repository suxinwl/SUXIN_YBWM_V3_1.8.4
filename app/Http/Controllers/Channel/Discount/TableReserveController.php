<?php

namespace App\Http\Controllers\Channel\Discount;

use App\Enums\WorkEnum;
use App\Events\StoreMessageEvent;
use App\Http\Controllers\Channel\ApiController;
use App\Http\Requests\Admin\ChangePassword;
use App\Http\Resources\ChannelApi\User\Profix;
use App\Models\Ad;
use App\Models\Admin;
use App\Models\InStore\Cart;
use App\Models\Member;
use App\Models\Member\Address;
use App\Models\MemberAddress;
use App\Models\MemberBind;
use App\Models\Table\ReserveOrder;
use App\Models\Tables\Area;
use App\Models\Tables\Table;
use App\Models\Tables\Type;
use App\Models\TablesReserve\Checkout;
use App\Models\UserAccount;
use App\Services\ConfigService;
use App\Services\MenuService;
use App\Services\UserService;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Tymon\JWTAuth\Facades\JWTAuth;

class TableReserveController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }


    public function store(Request $request)
    {
    }
}
