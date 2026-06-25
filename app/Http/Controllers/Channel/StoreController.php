<?php

namespace App\Http\Controllers\Channel;

use App\Http\Controllers\Controller;
use App\Http\Requests\MenusRequest;
use App\Http\Resources\Channel\Menus\Menus;
use App\Imports\StoreImport;
use App\Models\Admin\Apply;
use App\Models\Menu;
use App\Models\RoleMenu;
use App\Models\ShortLink;
use App\Models\Store;
use App\Services\ConfigService;
use App\Services\OpenWechat\ChannelOpenWechat;
use Illuminate\Http\Request;
use App\Services\MenuService;
use App\Services\OpenWechat\AdminOpenWechat;
use App\Services\ShortLinkService;
use App\Traits\HelperTrait;
use Excel;
use Illuminate\Support\Arr;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Spatie\FlareClient\Http\Exceptions\BadResponseCode;
use App\Models\StoreConfig;
use App\Models\SmsAccount;
use App\Models\Admin;
use Storage;
class StoreController extends ApiController
{
    use HelperTrait;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = $this->user();
        $storeId = $this->storeId();
        $list = Store::select(['id', 'address', 'isolate', 'contact', 'mobile', 'businessData', 'name', 'expressSwitch','pickupSwitch', 'isShowSwitch', 'businessStatus', 'takeoutSwitch', 'inStoreSwitch', 'paySwitch', 'created_at'])
            ->with(['label'])
            ->where('uniacid', $this->uniacid())
            ->orderBy('sort', 'asc')
            ->when($storeId, function ($q) use ($storeId) {
                return $q->where("id", $storeId);
            })
            ->when($request->name, function ($q) use ($request) {
                return $q->where('name', 'like', "%$request->name%");
            })
            ->when($user, function ($q) use ($user) {
                if ($user->isAdmin == 0) {
                    if (empty($user->storeId)) {
                        $q->where('id', 0);
                    } else {
                        $q->whereIn('id', $user->storeId);
                    }
                }
                return $q;
            })
            ->when(!$request->isolate && $this->isolate() == 0, function ($q) use ($request) {
                return $q->where('isolate', 0);
            })
            ->when($request->labelId, function ($q) use ($request) {
                return $q->where('labelId', 'like', "%$request->labelId%");
            })
            ->when($request->groupId, function ($q) use ($request) {
                return $q->where('groupId', $request->groupId);
            })
            ->when($request->operatingStatus, function ($q) use ($request) {
                return $q->where('operatingStatus', $request->operatingStatus);
            })
            ->when($request->businessStatus, function ($q) use ($request) {
                return $q->where('businessStatus', $request->businessStatus);
            })
            ->when($request->recipeId, function ($q) use ($request) {
                return $q->whereDoesntHave('recipeStore');
            })
            ->when($request->recommendId, function ($q) use ($request) {
                return $q->whereDoesntHave('recommendStore', function ($q) use ($request) {
                    return $q->where('recommendId', $request->recommendId);
                });
            })->paginate($request->pageSize ?? 12, '*', 'pageNo');
        return $this->success($list);
    }

    public function show(Request $request, $id)
    {
        $list = Store::with(['label'])->where('uniacid', $this->uniacid())->find($id);
        if (empty($list)) {
            throw  new  BadResponseCode('数据不存在');
        }
        return $this->success($list);
    }

    public function store(Request $request)
    {
        try {
            $apply = Apply::where("id", $this->uniacid())->first();
            if ($apply->storeNumInfinite != 1 && $apply->storeNum <= $apply->store()->count()) {
                return $this->failed('门店数量已经达到上限，请联系管理员');
            }
            $model = new Store();
            $model->fill($request->all());
            $model->uniacid = $this->uniacid();
            $model->storeSn = $request->storeSn ?? '';
            if ($model->isolate == 1) {
                $model->payChange = 1;
            }
            $model->save();
            $model->label()->attach($model->labelId);
            return $this->success();
        } catch (\Exception $e) {
            return $this->failed('店铺添加失败');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $model = Store::where('uniacid', $this->uniacid())->find($id);
            if (empty($model)) {
                throw  new  BadResponseCode('数据不存在');
            }
            $model->fill($request->all());
            $model->uniacid = $this->uniacid();
            $model->storeSn = $request->storeSn ?? '';
            $model->save();
            $model->label()->sync($model->labelId);
            return $this->success();
        } catch (\Exception $e) {
            return $this->failed('店铺保存失败');
        }
    }

    public function destroy(Request $request, $id)
    {
        $model = Store::where('uniacid', $this->uniacid())->find($id);
        if (empty($model)) {
            throw  new  BadResponseCode('数据不存在');
        }
        $model->destroy($id);
        return $this->success();
    }

    public function switch(Request $request, $type, $id)
    {
        $model = Store::where('uniacid', $this->uniacid())->find($id);
        if (empty($model)) {
            throw  new  BadResponseCode('数据不存在');
        }
        if ($type == 'isShowSwitch') {
            $model->isShowSwitch = $model->isShowSwitch == 1 ? 0 : 1;
        }
        if ($type == 'takeoutSwitch') {
            $model->takeoutSwitch = $model->takeoutSwitch == 1 ? 0 : 1;
        }
        if ($type == 'inStoreSwitch') {
            $model->inStoreSwitch = $model->inStoreSwitch == 1 ? 0 : 1;
        }
        if ($type == 'paySwitch') {
            $model->paySwitch = $model->paySwitch == 1 ? 0 : 1;
        }
        if ($type == 'expressSwitch') {
            $model->expressSwitch = $model->expressSwitch == 1 ? 0 : 1;
        }
        if ($type == 'pickupSwitch') {
            $model->pickupSwitch = $model->pickupSwitch == 1 ? 0 : 1;
        }
        $model->save();
        return $this->success([], '操作成功');
    }

    public function businessStatus(Request $request, $id)
    {
        $model = Store::where('uniacid', $this->uniacid())->find($id);
        if (empty($model)) {
            throw  new  BadResponseCode('数据不存在');
        }
        $model->businessStatus = $request->businessStatus ?? $model->businessStatus;
        $model->save();
        return $this->success([], '操作成功');
    }

    public function qrcode(Request $request, $id)
    {
        $store = Store::where('uniacid', $this->uniacid())->find($id);
        if (empty($store)) {
            throw  new  BadResponseCode('数据不存在');
        }
        $model = ShortLink::where('uniacid', $this->uniacid())
            ->where('storeId', $id)
            ->where('type', $request->type)
            ->first();
        $miniPath='';
        if (empty($model)) {
            if ($request->type == 'personPay') {
                $model = ShortLinkService::createPayLink($store);
            }
            if ($request->type == 'storeGoods') {
                $model = ShortLinkService::createGoods($store);
            }
            if ($request->type == 'fastfood') {
                $model = ShortLinkService::fastfood($store);
            }
            if ($request->type == 'takeScreen') {
                $model = ShortLinkService::takeScreen($store);
            }
            if ($request->type == 'storeWifi') {
                $model = ShortLinkService::storeWifi($store);
            }
            if ($request->type == 'queuingUp') {
                $model = ShortLinkService::queuingUp($store);
            }
            if ($request->type == 'storeIndex') {
                $model = ShortLinkService::storeIndex($store);
            }
        }
        if ($request->type == 'personPay') {
            $name = $id . "-" . 'personPay.png';
            $path = '/' . $this->uniacid() . '/personPay/' . $id . '/';
            if (!Storage::disk('public')->exists($path . $name)) {
                $page = 'pages/shop/in/dmf?storeId=' . $id;
                $app = ChannelOpenWechat::miniProgram($this->uniacid());
                $response = $app->app_code->get($page);
                $image = $response->getBody()->getContents();
                Storage::disk('public')->put($path . $name, $image);
            }
            $imageContent = Storage::disk('public')->get($path . $name);
            $base64ImageContent = base64_encode($imageContent);
            $miniPath = 'data:image/jpeg;base64,' . $base64ImageContent;
        }
        if ($request->type == 'storeGoods') {
            $name = $id . "-" . 'storeGoods.png';
            $path = '/' . $this->uniacid() . '/storeGoods/' . $id . '/';
            if (!Storage::disk('public')->exists($path . $name)) {
                $page = 'pages/index/goods?storeId=' . $id;
                $app = ChannelOpenWechat::miniProgram($this->uniacid());
                $response = $app->app_code->get($page);
                $image = $response->getBody()->getContents();
                Storage::disk('public')->put($path . $name, $image);
            }
            $imageContent = Storage::disk('public')->get($path . $name);
            $base64ImageContent = base64_encode($imageContent);
            $miniPath = 'data:image/jpeg;base64,' . $base64ImageContent;
        }
        if ($request->type == 'fastfood') {
            $name = $id . "-" . 'fastfood.png';
            $path = '/' . $this->uniacid() . '/fastfood/' . $id . '/';
            if (!Storage::disk('public')->exists($path . $name)) {
                $page = 'pages/shop/in/goods?storeId=' . $id.'&diningType=6';
                $app = ChannelOpenWechat::miniProgram($this->uniacid());
                $response = $app->app_code->get($page);
                $image = $response->getBody()->getContents();
                Storage::disk('public')->put($path . $name, $image);
            }
            $imageContent = Storage::disk('public')->get($path . $name);
            $base64ImageContent = base64_encode($imageContent);
            $miniPath = 'data:image/jpeg;base64,' . $base64ImageContent;
        }
        if ($request->type == 'takeScreen') {
            $name = $id . "-" . 'takeScreen.png';
            $path = '/' . $this->uniacid() . '/takeScreen/' . $id . '/';
            if (!Storage::disk('public')->exists($path . $name)) {
                $page = 'pages/index/goods?storeId=' . $id.'&diningType=6';
                $app = ChannelOpenWechat::miniProgram($this->uniacid());
                $response = $app->app_code->get($page);
                $image = $response->getBody()->getContents();
                Storage::disk('public')->put($path . $name, $image);
            }
            $imageContent = Storage::disk('public')->get($path . $name);
            $base64ImageContent = base64_encode($imageContent);
            $miniPath = 'data:image/jpeg;base64,' . $base64ImageContent;
        }
        if ($request->type == 'storeWifi') {
            $name = $id . "-" . 'storeWifi.png';
            $path = '/' . $this->uniacid() . '/storeWifi/' . $id . '/';
            if (!Storage::disk('public')->exists($path . $name)) {
                $page = 'pages/other/wifi?storeId=' . $id;
                $app = ChannelOpenWechat::miniProgram($this->uniacid());
                $response = $app->app_code->get($page);
                $image = $response->getBody()->getContents();
                Storage::disk('public')->put($path . $name, $image);
            }
            $imageContent = Storage::disk('public')->get($path . $name);
            $base64ImageContent = base64_encode($imageContent);
            $miniPath = 'data:image/jpeg;base64,' . $base64ImageContent;
        }
        if ($request->type == 'queuingUp') {
            $name = $id . "-" . 'queuingUp.png';
            $path = '/' . $this->uniacid() . '/queuingUp/' . $id . '/';
            if (!Storage::disk('public')->exists($path . $name)) {
                $page = 'pages/index/goods?storeId=' . $id;
                $app = ChannelOpenWechat::miniProgram($this->uniacid());
                $response = $app->app_code->get($page);
                $image = $response->getBody()->getContents();
                Storage::disk('public')->put($path . $name, $image);
            }
            $imageContent = Storage::disk('public')->get($path . $name);
            $base64ImageContent = base64_encode($imageContent);
            $miniPath = 'data:image/jpeg;base64,' . $base64ImageContent;
        }
        if ($request->type == 'storeIndex') {
            $name = $id . "-" . 'storeIndex.png';
            $path = '/' . $this->uniacid() . '/storeIndex/' . $id . '/';
            if (!Storage::disk('public')->exists($path . $name)) {
                $page = 'pages/index/index?storeId=' . $id;
                $app = ChannelOpenWechat::miniProgram($this->uniacid());
                $response = $app->app_code->get($page);
                $image = $response->getBody()->getContents();
                Storage::disk('public')->put($path . $name, $image);
            }
            $imageContent = Storage::disk('public')->get($path . $name);
            $base64ImageContent = base64_encode($imageContent);
            $miniPath = 'data:image/jpeg;base64,' . $base64ImageContent;
        }
        $type = $request->type == 'storeIndex' ? 'index' : $request->type;
        $url = Request()->getSchemeAndHttpHost() . "/s/{$type}/" . $this->uniacid() . '/'  . $model->shortLink . '/?' . $model->wx['query'];
        $url = "data:image/png;base64," . base64_encode(QrCode::format('png')->size(400)->generate($url));


        $data=[
            'miniPath'=>$miniPath,
            'publicPath'=>$url
        ];
        return $this->success($data);
    }


    public function copyStore(Request $request)
    {
        $id=$request->contact_uniacid;
        $count = Apply::withTrashed()->count();
        $auth = getSysInfo();
        if ($auth['account_type'] == 2 && $auth['account_number'] <= $count) {
            return $this->failed('平台创建数量已达到上限');
        }
        $admin = Admin::find($this->user()->id);
        if ($admin->createStoreNum > 0 && $admin->adminApply->count() >= $admin->createStoreNum) {
            return $this->failed('该账号的创建店铺数量已达到上限');
        }
        // //复制店铺
        $originalModel = Apply::find($id);
        $newModel = $originalModel->replicate();
        $newModel->startTime= date('Y-m-d H:i:s', time());
        $newModel->applyName= '(复制)'.$originalModel->applyName;
        $newModel->save();

        $uniacid=$newModel->id;


        //复制店铺设置其他设置
        $smsModel = SmsAccount::where('uniacid',$id)->first();
        $smsnewModel = $smsModel->replicate();
        $smsnewModel->uniacid= $uniacid;
        $smsnewModel->save();


        //复制店铺插件
        foreach ($originalModel->plugs as $related) {
            $newRelated = $related->replicate();
            $newRelated->uniacid = $uniacid; // 设置外键值为新模型ID
            $newRelated->save();
        }

        //复制店铺装修
        foreach ($originalModel->drag as $related) {
            $newRelated = $related->replicate();
            $newRelated->uniacid = $uniacid; // 设置外键值为新模型ID
            $newRelated->save();
        }


        //复制店铺设置
        foreach ($originalModel->channelconfig as $related) {
            $newRelated = $related->replicate();
            $newRelated->uniacid = $uniacid; // 设置外键值为新模型ID
            $newRelated->save();
        }


        //复制门店以及门店设置
        foreach ($originalModel->store as $related) {
            $storeId=$related->id;

            $original= Store::where('uniacid',$id)->where('id',$storeId)->first(); // 获取原始模型对象
            unset($original->id);
            $newRelated = $original->replicate();
            $newRelated->uniacid = $uniacid; // 设置外键值为新模型ID
            $newRelated->save();
            $newStoreId=$newRelated->id;

            $original= StoreConfig::where('storeId',$storeId)->first(); // 获取原始模型对象
            $newModel = $original->replicate();

            $newModel->storeId= $newStoreId;
            $newModel->save();
        }
        return $this->success([], '操作成功');
    }
    public function placeSearch(Request $request){
        $uniacid=$this->uniacid();
        $url='https://apis.map.qq.com/ws/place/v1/search';
        $res = ConfigService::getChannelConfig('basicSetting', $uniacid);
        $key=$res['txKey'];
        $keyword =$request->keyword;
        $lat = $request->lat;
        $lng =$request->lng;
        $nearby='nearby('.$lat.','.$lng.',1000)';
        $url=$url.'?key='.$key.'&keyword='.$keyword.'&boundary='.$nearby;

        // 初始化cURL会话
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url); // 你要访问的URL
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // 将curl_exec()获取的信息以字符串返回，而不是直接输出
        $data = curl_exec($ch);
        if(curl_errno($ch)){
            throw new ValidateException(curl_error($ch));
        }
        curl_close($ch);
        $data=json_decode($data,true);
        return $this->result(1,'成功',$data);
    }

}
