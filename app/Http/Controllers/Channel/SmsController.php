<?php

namespace App\Http\Controllers\Channel;

use App\Http\Controllers\Controller as BaseController;
use App\Http\Helpers\ApiResponse;
use App\Http\Resources\Admin\Sms\SmsCollection;
use App\Models\Admin;
use App\Models\Sms;
use App\Models\Member;

use App\Models\SmsAccount;
use App\Models\SmsLog;
use App\Models\Store;
use App\Services\ConfigService;
use App\Services\SmsService;
use Illuminate\Http\Request;
use App\Models\Config;
class SmsController extends ApiController
{

    public function retrieve(Request $request)
    {
        $user = Admin::where('mobile', $request->mobile)->first();
        if (empty($user)) {
            return $this->failed(__('base.not_user'));
        }
        $sms = new SmsService();
        if ($sms->retrieveSms($request->mobile)) {
            return $this->success([], __('sms.code_success'));
        } else {
            return $this->failed([], __('sms.error'));
        }
    }

    public function register(Request $request)
    {
        $config = ConfigService::getSystemSet('userSettings');
        if ($config->registerWay != 1) {
            return $this->failed('当前站点禁止注册');
        }
        $user = Admin::where(function ($q) use ($request) {
            return $q->where('mobile', $request->mobile)->orWhere('username', $request->mobile);
        })->whereNotIn('status', [3])->first();
        if ($user) {
            return $this->failed('用户账号（手机号）已存在');
        }
        $sms = new SmsService();
        if ($sms->registerSms($request->mobile)) {
            return $this->success([], __('sms.code_success'));
        } else {
            return $this->failed([], __('sms.error'));
        }
    }





    public function login(Request $request)
    {
        $user = Admin::where('mobile', $request->mobile)->first();
        if (empty($user)) {
            return $this->failed(__('base.not_user'));
        }
        $sms = new SmsService();
        if ($sms->loginSms($request->mobile)) {
            return $this->success([], __('sms.code_success'));
        } else {
            return $this->failed([], __('sms.error'));
        }
    }

    public function loginSms(Request $request)
    {
        $user = Admin::where('mobile', $request->mobile)->first();
        if (empty($user)) {
            return $this->failed(__('base.not_user'));
        }
        $sms = new SmsService();
        if ($sms->loginSms($request->mobile)) {
            return $this->success([], __('sms.code_success'));
        } else {
            return $this->failed([], __('sms.error'));
        }
    }


    public function index(Request $request)
    {
        $list = SmsLog::where('uniacid', $this->uniacid())->orderBy('id', 'desc')->paginate($request->per_page ?? 30);
        return $this->success(new SmsCollection($list));
    }

    public function smsSend(Request $request)
    {
        switch ($request->classify){
            case 1;//按会员等级
                $user=Member::where('vipId',$request->level)->where('mobile','>','')->get();
                break;
            case 2;//按会员标签
                $user=Member::whereIn('labelId',$request->tags)->where('mobile','>','')->get();
                $user=empty($user)?array():$user->toArray();
                break;
            case 3;// 按会员分组
                $user=Member::where('groupId',$request->group)->where('mobile','>','')->get();
                $user=empty($user)?array():$user->toArray();
                break;
            case 4;//自定义用户
                $ids=array_column($request->checkMemberList,'id');
                $user=Member::whereIn('id',$ids)->where('mobile','>','')->get();
                $user=empty($user)?array():$user->toArray();
                break;
        }
        if($request->storeType==1){
            $storeData=Store::where('uniacid', $this->uniacid())->select('id','name','address')->get();
        }else{
            $storeData=Store::whereIn('id',$request->storeIds)->where('uniacid', $this->uniacid())->select('id','name','address')->get();
        }
        if(empty($user)){
            return $this->failed('没有可发送的用户');
        }
        if(empty($storeData)){
            return $this->failed('没有可发送的门店');
        }
        if(empty($request->sceneList)){
            return $this->failed('没有可发送的场景');
        }
        if(in_array('1',$request->sceneList)&&empty($request->time)){
            return $this->failed('请选择开业时间');
        }
        $model = Config::where('ident','sms')->first();
        $phone=array_column($user,'mobile');
        $uniacid=$this->uniacid();
        $sceneList=$request->sceneList;
        $smsConfig = ConfigService::getSystemSet('sms');
        $timestamp = strtotime(substr($request->time, 0, 19)); // 截取日期时间字符串的前19个字符进行转换
        $chinaTime = date("Y-m-d H:i:s", $timestamp + 8 * 3600); // 加上8小时的时差，得到中国正常时间


        $storage = new Sms();
        foreach ($storeData as $j){
            foreach ($sceneList as $v){

                switch ($v){
                    case 1;
                        if($model&&$model->data){
                            if($model->data->ali_openingReminder){
                                $data=['name'=>$j->name,'address'=>$j->address,'time'=>$chinaTime];
                                $bool = $storage->aliyunSendMessage($smsConfig, $phone, $model->data->ali_openingReminder, $data , $uniacid = 0, $islog = true, 'marketingSms');

                            }
                        }

                        break;
                    case 2;
                        if($model&&$model->data){
                            foreach ($user as $vo){
                                if($model->data->ali_couponExpirationReminder){
                                    $data=['name'=>'vip会员','shopname'=>$j->name];
                                    $bool = $storage->aliyunSendMessage($smsConfig, $phone, $model->data->ali_couponExpirationReminder, $data, $uniacid, $islog = true, $channel = '');
                                }
                            }
                        }
                        break;
                }

            }
        }
        return $this->success([], '短信发送成功');

    }
}
