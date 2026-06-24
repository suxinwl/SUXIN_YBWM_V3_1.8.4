<?php

namespace App\Http\Controllers\ChannelApi;

use App\Http\Requests\Admin\ChangePassword;
use App\Http\Resources\ChannelApi\User\Profix;
use App\Models\Admin;
use App\Models\Member;
use App\Models\Member\Address;
use App\Models\MemberAddress;
use App\Models\MemberBind;
use App\Models\UserAccount;
use App\Services\MenuService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Services\MapService;
class AddressController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $list = Address::where('uniacid', $this->uniacid())
            ->where('userId', $this->userId())
            ->orderBy('isDefault', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($request->size ?? 20, '*', 'page');
        return $this->success($list);
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $request->all();
        $data['mobile'] = str_replace(' ', '', $data['mobile']);
        $data['uniacid'] = $this->uniacid();
        $data['userId'] = $this->userId();
        $res = MapService::region($request->lat, $request->lng, $this->uniacid());
        if($res['status']==0){
            $data['province']=$res['address_component']['province'];
            $data['city']=$res['address_component']['city'];
            $data['district']=$res['address_component']['district'];
        }
        Address::create($data);
        return $this->success();
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $model = Address::where(['uniacid' => $this->uniacid()])->where(['userId' => $this->userId()])->where(['id' => $id])->first();
        if (empty($model)) {
            throw new BadRequestException('数据不存在');
        }
        return $this->success($model);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $model = Address::where(['uniacid' => $this->uniacid()])->where(['userId' => $this->userId()])->where(['id' => $id])->first();
        if (empty($model)) {
            throw new BadRequestException('数据不存在');
        }
        $data = $request->all();
        $data['mobile'] = str_replace(' ', '', $data['mobile']);
        $data['uniacid'] = $this->uniacid();
        $data['userId'] = $this->userId();
        if(empty($model->city)){
            $res = MapService::region($request->lat, $request->lng, $this->uniacid());
            $data['province']=$res['address_component']['province'];
            $data['city']=$res['address_component']['city'];
            $data['district']=$res['address_component']['district'];
        }
        $model->fill($data);
        $model->save();
        return $this->success();
    }

    public function isDefault(Request $request, $id)
    {
        $model = Address::where(['uniacid' => $this->uniacid()])->where(['userId' => $this->userId()])->where(['id' => $id])->first();
        if (empty($model)) {
            throw new BadRequestException('数据不存在');
        }
        $model->isDefault = 1;
        $model->save();
        return $this->success();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        Address::destroy($id);
        return $this->success();
    }
}
