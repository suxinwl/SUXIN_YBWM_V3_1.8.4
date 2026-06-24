<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ConfigRequest;
use App\Models\Ad;
use App\Models\Config;
use App\Models\News;
use App\Services\ConfigService;
use Illuminate\Http\Request;

class AdController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $list = Ad::where('id', ">", 0);
        if ($request->keyword) {
            $list->where('title', 'like', "%{$request->keyword}%");
        }
        $list = $list->orderBy('sort', 'asc')->orderBy('id', 'desc')->paginate($request->pageSize ?? 20, '*', 'pageNo');
        return $this->success($list);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Ad $model)
    {
        $model->create([
            'title' => $request->title,
            'banner' => $request->input('banner'),
        ]);
        return $this->success([], __('base.success'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Ad $model, $id)
    {
        $news = $model::find($id);
        return $this->success($news, __('base.success'));
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
    public function update(Request $request, $id)
    {
        $model = Ad::find($id);
        if (!$model) {
            return $this->failed('数据不存在');
        }
        $model->title = $request->title;
        $model->banner = $request->input('banner');
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
        $model = new Ad();
        $model->destroy($idArray);
        return $this->success([], __('base.success'));
    }
}
