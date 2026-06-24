<?php

namespace App\Http\Controllers\Admin;

use App\Models\publicMiniProgram\PublicMiniprogramModel;
use Illuminate\Http\Request;


class PublicMiniProgramController extends ApiController
{
    public function index()
    {
        return $this->success("成功");
    }
    /*
     * 获取公域小程序配置
     * */
    public function getMiniProgramInfo(Request $request)
    {
        $type = $request->get('type');
        if ($type == 1 || $type == 2){
            return $this->success(PublicMiniprogramModel::where('type', '=', $type)->get()->first());
        } else {
            return $this->success(PublicMiniprogramModel::all());
        }
    }
    /*
     * 修改公域小程序配置
     * */
    public function editMiniProgram(Request $request)
    {
        $params = $request->all();
        if (empty($params)){
            return $this->failed('参数不能为空！');
        }
        $data = PublicMiniprogramModel::where('type', '=', $params['type']);
        if ($data->get('type')->isEmpty()){
            $state = $data->insert($params);
            return $state==1?$this->success($state): $this->failed('创建失败！');
        }else {
            $state = $data->update($params);
            return $state==1?$this->success($state): $this->failed('更新失败！');
        }
    }
}
