<?php

namespace App\Http\Controllers\ChannelApi;

use App\Http\Requests\Admin\ChangePassword;
use App\Http\Resources\ChannelApi\User\Profix;
use App\Models\Ad;
use App\Models\Admin;
use App\Models\Member;
use App\Models\Member\Address;
use App\Models\Member\MemberBase;
use App\Models\MemberAddress;
use App\Models\MemberBind;
use App\Models\Partner;
use App\Models\PartnerOrder;
use App\Models\UserAccount;
use App\Services\ConfigService;
use App\Services\MenuService;
use App\Services\UserService;
use Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Image;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\Store\StoreGoods;
class PartnerController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $model = Partner::with(['user', 'account'])
            ->withCount(['order', 'downline'])
            ->where('uniacid', $this->uniacid())
            ->where('userId',$this->userId()
            )->first();
        $config = ConfigService::getChannelConfig('distributor', $this->uniacid(),$this->storeId());
        if (empty($config) || !$config['switch']) {
            return  $this->failed('分销已关闭');
        }
        if (!$model) {
            if ($config['authType'] == 1) {
                $model = new Partner();
                $model->userId = $this->userId();
                $model->uniacid = $this->uniacid();
                $model->parentId = $this->user()->partnerId;
                if ($config['authState'] == 1) {
                    $model->state = 1;
                }
                if ($config['authState'] == 2) {
                    $model->state = 0;
                }
                $model->save();
            }
        }
        return $this->success($model);
    }


    public function store(Request $request)
    {
        $config = ConfigService::getChannelConfig('distributor', $this->uniacid(),$this->storeId());
        if (!$config['switch']) {
            return  $this->failed('分销已关闭');
        }
        $model = Partner::where('uniacid', $this->uniacid())
            ->where('userId',$this->userId())->first();
        if ($model) {
            if ($model->state == 0) {
                return  $this->failed('您的申请在审核中');
            }
            if ($model->state == 1) {
                return  $this->failed('您已是分销合作伙伴,无需再次申请');
            }
        }
        if (empty($model)) {
            $model = new Partner();
            $model->userId = $this->userId();
            $model->uniacid = $this->uniacid();
            $model->parentId = $this->user()->partnerId;
        }
        $model->fill($request->all());
        if ($config['authState'] == 1) {
            $model->state = 1;
        }
        if ($config['authState'] == 2) {
            $model->state = 0;
        }
        $model->save();
        return $this->success($model);
    }


    public function share(Request $request)
    {
        if($request->shareType==1){
            $model = StoreGoods::with(['spu' => function ($q) {
                return $q->select(['*']);
            }])->where('storeId', $request->storeId)->where('spuId', $request->goodsId)->first();
var_dump($model->logo);die;
            $img = Image::make($model->logo)->resize(400, 600);


                $avatar = Image::make($model->name)->resize(100,100);
                $new = Image::canvas(100, 100);
                $r = $avatar->width() / 2;
                for ($x = 0; $x < $avatar->width(); $x++) {
                    for ($y = 0; $y < $avatar->height(); $y++) {
                        $c = $avatar->pickColor($x, $y, 'array');
                        if (((($x - $r) * ($x - $r) + ($y - $r) * ($y - $r)) < ($r * $r))) {
                            $new->pixel($c, $x, $y);
                        }
                    }
                }
                $avatar  = $new;
                $img->insert($avatar, 'top-left', 150, 20);


                $img->text($model->name, 160, 150, function ($font) use ($model) {
                    $font->file(public_path() . '/storage/default/kt.ttf');
                    $font->size(100);
                    $font->color("#000" ?? "#000");
                    $font->align('left');
                    $font->valign('top');
                });

            return $this->success('data:image/jpg;base64,' . base64_encode($img->encode('jpg', 50)));





        }else{
            $model = Partner::where('uniacid', $this->uniacid())
                ->where('userId', $this->userId())
                ->first();
            if (!$model) {
                return $this->failed('活动已结束');
            }
        }

        Image::configure(['driver' => 'gd']);
        $img = Image::canvas(400, 600, '#FFFFFF');
        $user = new Profix($this->user());
        $user = collect($user)->toArray();
        //用户
        $config = ConfigService::getChannelConfig('partnerShare', $this->uniacid(),$this->storeId());
        if (empty($config)) {
            return $this->failed('请先配置分销商海报设置');
        }
        collect($config)->values()->filter(function ($item) {
            return is_array($item);
        })->each(function ($item) use ($user, &$img, $model) {
            if ($item['type'] == 'bg') {
                $img = Image::make($item['url'])->resize(400, 600);
            }
            if ($item['type'] == 'avatar' && $item['switch']) {
                $avatar = Image::make($user['avatar'])->resize($item['size'], $item['size']);
                $new = Image::canvas($item['size'], $item['size']);
                $r = $avatar->width() / 2;
                for ($x = 0; $x < $avatar->width(); $x++) {
                    for ($y = 0; $y < $avatar->height(); $y++) {
                        $c = $avatar->pickColor($x, $y, 'array');
                        if (((($x - $r) * ($x - $r) + ($y - $r) * ($y - $r)) < ($r * $r))) {
                            $new->pixel($c, $x, $y);
                        }
                    }
                }
                $avatar  = $new;
                $img->insert($avatar, 'top-left', $item['left'], $item['top']);
            }
            if ($item['type'] == 'name' && $item['switch']) {
                $img->text($user['nickname'], $item['left'], $item['top'], function ($font) use ($item) {
                    $font->file(public_path() . '/storage/default/kt.ttf');
                    $font->size($item['size']);
                    $font->color($item['color'] ?? "#000");
                    $font->align('left');
                    $font->valign('top');
                });
            }
            if ($item['type'] == 'qrcode' && $item['switch']) {
                $url = Request()->getSchemeAndHttpHost() . "/s/index/" . $this->uniacid() . '/?partnerId=' . $model->userId.'&storeId='.$this->storeId();
                $qrCode = QrCode::format('png')->size($item['size'])->generate($url);
                $qrCode = "data:image/png;base64," . base64_encode($qrCode);
                $img->insert($qrCode, 'top-left', $item['left'], $item['top']);
            }
        });
        return $this->success('data:image/jpg;base64,' . base64_encode($img->encode('jpg', 50)));
    }

    public function downline(Request $request)
    {
        $level = $request->level ?? 1;
        $userId = $this->userId();
        $model = MemberBase::select(['id', 'nickname', 'mobile', 'uniacid', 'partnerId', 'created_at'])->with(['partner'])
            ->where('uniacid', $this->uniacid())
            ->withCount(['partnerOrder' => function ($q) {
                return $q->where('level', 1)
                    ->whereIn('state', [2, 3, 4, 5, 6, 7]);
            }])
            ->withCount(['partnerOrder as partnerOrder_sum' => function ($query) {
                return   $query->select(DB::raw("sum(orderMoney) as partnerOrderSum"))
                    ->where('level', 1)
                    ->whereIn('state', [2, 3, 4, 5, 6, 7]);
            }])
            ->when($level, function ($q) use ($level, $userId) {
                if ($level == 2) {
                    $ids = Partner::where('parentId', $userId)->where('state', 1)->get();
                    $ids = collect($ids)->pluck('userId')->all();
                    return $q->whereIn('partnerId', $ids ?? []);
                } else {
                    return $q->where('partnerId', $userId);
                }
            })
            ->orderBy('id', 'desc')
            ->paginate($request->size ?? 10, '*', 'page');
        return $this->success($model);
    }

    public function order(Request $request)
    {
        $model = PartnerOrder::with(['user'])
            ->where('uniacid', $this->uniacid())
            ->where('state', ">", 1)
            ->where('partnerId', $this->userId())
            ->when($request->state, function ($q) use ($request) {
                if ($request->state == 'start') {
                    return $q->whereIn('state', [2, 3, 4, 5, 7]);
                }
                if ($request->state == 'bill') {
                    return $q->whereIn('state', [6, 10]);
                }
                if ($request->state == 'refund') {
                    return $q->whereIn('state', [8]);
                }
                return $q;
            })
            ->orderBy('id', 'desc')
            ->paginate($request->size ?? 10, '*', 'page');
        return $this->success($model);
    }
}
