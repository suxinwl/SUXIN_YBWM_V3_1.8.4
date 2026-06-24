<?php
namespace App\Http\Controllers\Channel;
use Illuminate\Http\Request;
use App\Models\PointsMallClassification;
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class PointsMallClassificationController extends ApiController{
    use SoftDeletes;
    public function Index(Request $request){
        $list=PointsMallClassification::where('uniacid', $this->uniacid())
            ->where('storeId',$this->storeId())
            ->when($request->name, function ($q) use ($request) {
                return $q->where('name', $request->name);
            })
            ->orderBy('sort', 'asc')->paginate($request->pageSize ?? 20, '*', 'pageNo');
        return $this->success($list);
    }

    public function store(Request $request){
        PointsMallClassification::create(
            [
                'uniacid'=>$this->uniacid(),
                'sort'=>$request->sort,
                'name'=>$request->name,
                'icon'=>$request->icon,
                'display'=>$request->display,
                'storeId'=>$this->storeId()
            ]
        );
        return $this->success();
    }

    public function show(Request $request, $id)
    {
        $model = PointsMallClassification::where('uniacid', $this->uniacid())->find($id);
        if (empty($model)) {
            throw new BadRequestException('数据不存在');
        }
        return $this->success($model);
    }


    public function update(Request $request, $id)
    {
        try {
            $model = PointsMallClassification::where('uniacid', $this->uniacid())->find($id);
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


    public function destroy(Request $request, $id)
    {
        try {
            $idArray = array_filter(explode(',', $id), function ($item) {
                return is_numeric($item);
            });
            $models = PointsMallClassification::where('uniacid', $this->uniacid())
                ->whereIn('id', $idArray)->get();
            foreach ($models as $key => $model) {
                $model->delete();
            }
            return $this->success([], '删除成功');
        } catch (\Exception $e) {
            return $this->failed('删除失败');
        }
    }
}
