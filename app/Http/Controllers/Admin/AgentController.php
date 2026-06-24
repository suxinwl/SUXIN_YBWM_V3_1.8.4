<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin;
use App\Models\AdminRole;
use DB;
class AgentController extends ApiController
{
    public function index(Request $request,Admin $admin_model){
        if(!empty($request->keyword)){
            $admin_model = $admin_model->where('username','like','%'.$request->keyword.'%');
        }
        //DB::connection()->enableQueryLog();#开启执行日志
        $admin_model = $admin_model->where('status',$request->state);
        $data=$admin_model->where('role_id',3)->paginate($request->pageSize??30,'*','pageNo');
        //print_r(DB::getQueryLog());die;
        return $this->success($data);
    }

    public function store(Request $request){
        \DB::beginTransaction();
        try {
            $admin=new Admin();
            $admin->username = $request->username;
            $admin->password = $request->password;
            $admin->mobile = $request->mobile;
            $admin->role_id =2;
            $admin->createStoreNum = $request->createStoreNum;
            $data=array(
                'musterId'=>$request->musterId,
                'timeType'=>$request->timeType,
                'day'=>$request->day,
                'plug'=>$request->plug,
            );
            $admin->data = json_encode($data);
            $admin->save();
            $id = $admin->id;
            $adminRole=new AdminRole();
            $adminRole->admin_id =$id;
            $adminRole->role_id = 2;
            $adminRole->save();
            \DB::commit();
        } catch (Exception $e) {
            \DB::rollback();
            echo $e->getMessage();die;
        }
        return $this->success([],__('base.success'));
    }



    public function show(Admin $admin_model,$id){
        $info = $admin_model->find($id);
        return $this->success($info);
    }

    public function update(Request $request,Admin $admin_model,$id){
        $admin_model = $admin_model->find($id);
        $admin_model->username = $request->username;
        $admin_model->password = $request->password;
        $admin_model->mobile = $request->mobile;
        $admin_model->createStoreNum = $request->createStoreNum;
        $data=array(
            'musterId'=>$request->musterId,
            'timeType'=>$request->timeType,
            'day'=>$request->day,
            'plug'=>$request->plug,
        );
        $admin_model->data = json_encode($data);
        $admin_model->save();
        return $this->success([],__('base.success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Admin $admin_model,$id){
        $idArray = array_filter(explode(',',$id),function($item){
            return is_numeric($item);
        });
        $admin_model->destroy($idArray);
        return $this->success([],__('base.success'));
    }

    public function recovery(Request $request,Admin $admin_model){
        $id=$request->id;
        $admin_model = $admin_model->find($id);
        $admin_model->status = $request->status;
        $admin_model->save();
        return $this->success([],__('base.success'));
    }

}
