<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ConfigRequest;
use App\Models\Config;
use App\Models\Wechat;
use App\Services\ConfigService;
use EasyWeChat\Factory;
use Illuminate\Http\Request;
use App\Models\Admin\Apply;
class ConfigController extends ApiController{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $ident = $request->ident;
        $data=Config::getSystemSet($ident);
        return  $this->success($data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(ConfigRequest $request,Config $model)
    {
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request,Config $model){
        $model->create([
            'uniacid'=>0,
            'ident'=>$request->input('ident'),
            'identName'=>$request->input('identName'),
            'data'=>json_encode($request->all(),320),
        ]);
        return $this->success([],__('base.success'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show()
    {

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit()
    {

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(ConfigRequest $request,$ident){
        $model = Config::where('ident',$ident)->first();
        if(!$model){
            return $this->failed('数据不存在');
        }
        $model->ident=$request->ident;
        $model->identName=$request->identName;
        $model->data=json_encode($request->all(),320);
        $model->save();
        return $this->success([],__('base.success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function system(){
        $data['loginSwitch'] = ConfigService::getSystemSet('loginSwitch');
        $openWechat =ConfigService::getSystemSet('openWechat');
        $data['openWechat'] = [
            'status'=>$openWechat->status,
        ];
        $data['site'] = ConfigService::getSystemSet('site');
        $data['copyrightSetting'] = ConfigService::getSystemSet('copyrightSetting');
        return $this->success($data);
    }
    public function getSystemInfo(Request $request){
        $data = getSysInfo();
        $data['domain_name'] = '速信';
        $data['domain_url'] = $request->getHost();
        $data['corporate_name'] = '速信';
        $data['username'] = '速信';
        $data['appName'] = '速信';
        $data['phone'] = '';
        $data['qq'] = '';
        $data['wechat'] = '';
        $data['version'] = getVersionInfo()['version'] ?? ($data['version'] ?? '1.8.4');
        $data['time_start'] = $data['time_start'] ?? date('Y-m-d H:i:s');
        $data['time_end'] = '2099-12-31 23:59:59';
        $data['email'] = $data['email'] ?? '';
        $applyCount = Apply::applyTotal();
        $data['platforms_number']=$applyCount;
        return $this->success($data);
    }

    //发送测试
    public function sendStoreTemplates(Request $request){
        $result=$request->all();
        $res= ConfigService::getSystemSet('official_account');
        if(!$res->appId|| !$res->appSecret){
            return $this->failed('公众号配置信息不能为空');
        }
        $row= ConfigService::getSystemSet('template_message');
        if(!$row->openId){
            return $this->failed('请填写管理员公众号OpenId');
        }
        $app=Wechat::config();
        $data=[];
        $type=$result['type'];
        if($type=='newOrder'){   //新订单通知
            $data=Wechat::newOrder();
        }
        if($type=='refundApply'){  //订单售后通知
            $data=Wechat::refundApply();
        }
        if($type=='deliveryAbnormal'){//配送订单异常通知
            //$data=Wechat::deliveryAbnormal();
        }
        if($type=='inStoreNewOrder'){//堂食订单通知
            $data=Wechat::newOrder();
            //$data=Wechat::inStoreNewOrder();
        }

        try{
            $meassageData=[
                'touser' => $row->openId,
                'template_id' => $row->$type,
                'data' => $data,
            ];
            $res=$app->template_message->send($meassageData);
        }catch (\Exception $exception){
            return $this->failed($exception->getMessage());
        }
        if($res['errcode']==0){
            return $this->success($data);
        }else{
            return $this->failed($res['errmsg']);
        }
    }

}
