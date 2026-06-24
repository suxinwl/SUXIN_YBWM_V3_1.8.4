<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Advertisement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AdvertisementController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $material_type = $request->material_type ?: 1;
        $list = Advertisement::select(['id', 'title', 'display', 'sort', 'created_at', 'content', 'subTitle', 'icon']);
        if ($request->keyword) {
            $list->where('title', 'like', "%{$request->keyword}%");
        }
        if ($request->material_type) {
            $list->where('material_type', $material_type);
        }
        $list = $list->orderBy('sort', 'asc')->orderBy('id', 'desc')
        ->paginate($request->pageSize ?? 20, '*', 'pageNo');
        return $this->success($list);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Advertisement $model)
    {
        if ($request->material_type == 3) {
            $count = Advertisement::where('material_type', 3)->count();
            if ($count >= 3) {
                return $this->failed('顶部导航数量已超过上限');
            }
        }
        $model->create([
            'material_type' =>  $request->material_type ?: 1,
            'display' => $request->display ?: 0,
            'title' => $request->input('title') ?: '',
            'subTitle' => $request->input('subTitle') ?: '',
            'content' => $request->input('content'),
            'icon' => $request->icon ?: '',
            'sort' => $request->sort ?: '',
        ]);
        return $this->success([], __('base.success'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Advertisement $model, $id)
    {
        $news = $model::find($id);
        return $this->success($news, __('base.success'));
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
        $model = Advertisement::find($id);
        if (!$model) {
            return $this->failed('数据不存在');
        }
        $model->title = $request->input('title');
        $model->display = $request->display;
        $model->subTitle = $request->input('subTitle') ?: '';
        $model->sort = $request->sort;
        $model->content = $request->input('content');
        $model->save();
        return $this->success([], __('base.success'));
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $idArray = array_filter(explode(',', $id), function ($item) {
            return is_numeric($item);
        });
        $model = new Advertisement();
        $model->destroy($idArray);
        return $this->success([], __('base.success'));
    }
}
