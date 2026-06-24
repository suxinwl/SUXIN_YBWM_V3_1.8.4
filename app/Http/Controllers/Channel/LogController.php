<?php
namespace App\Http\Controllers\Channel;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HandleLog;

class LogController extends ApiController{
    // GET 索引/列表
    public function index(Request $request){
       $type= $request->type?:1;
        $query=HandleLog::where('type',$type)
           ->where('uniacid', $this->uniacid());
       if (!empty($req->startTime) && !empty($req->endTime)) {
           $query=$query->where('created_at ', '>=', $req->startTime)
                 ->where('created_at ', '<=', $req->endTime);
       }
        if($request->input('keyword')){
            $query=$query->where('username ','like','%'.$request->input('keyword').'%');
        }
        $list=$query->orderBy('id', 'desc')
           ->paginate($request->pageSize ?? 20, '*', 'pageNo');
       return $this->success($list);
    }

    // GET /create 创建页展示
    public function create(){

    }

    // POST 保存创建的数据
    public function store(Request $request){

    }

    // GET /{id}/edit 展示编辑页面
    public function edit($id){

    }

    // PUT/PATHCH /{id} 保存编辑数据
    public function update(Request $request,$id){

    }

    // DELETE /{id} 删除
    public function destory($id){

    }
}
