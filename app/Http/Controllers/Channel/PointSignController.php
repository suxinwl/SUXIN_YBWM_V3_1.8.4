<?php
namespace App\Http\Controllers\Channel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use App\Models\PointSign;
use App\Models\SignList;
class PointSignController extends ApiController{
    public function index(Request $request){
        $row=PointSign::where('uniacid',$this->uniacid())->first();
        return $this->success($row);
    }
    public function store(Request $request){
        $row=PointSign::where('uniacid',$this->uniacid())->first();
        if($row){
            return $this->failed('无效的请求');
        }
        try {
            $model = new PointSign();
            $model->fill($request->all());
            $model->uniacid = $this->uniacid();
            $model->save();
            return $this->success([], '添加成功');
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }
    public function update(Request $request, $id)
    {
        try {
            $model = PointSign::where('uniacid', $this->uniacid())->find($id);
            if (empty($model)) {
                throw new BadRequestException('数据不存在');
            }
            $model->fill($request->all());
            $model->uniacid = $this->uniacid();
            $model->save();
            return $this->success([], '保存成功');
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }
}
