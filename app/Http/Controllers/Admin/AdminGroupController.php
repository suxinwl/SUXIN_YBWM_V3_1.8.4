<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminGroup;
use Illuminate\Http\Request;

class AdminGroupController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, AdminGroup $adminGroup)
    {
        $list = $adminGroup->withCount(['admins'])->with(['customer']);
        if ($request->keyword) {
            $list =  $list->where('title', 'like', "%$request->keyword%");
        }
        $list = $list->orderBy('sort', 'asc')->paginate($request->pageSize ?? 30);
        return $this->success($list);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, AdminGroup $model)
    {
        $model->create([
            'sort' => $request->sort ?: 0,
            'title' => $request->title,
            'service' => $request->service ?: 0,
        ]);
        return $this->success([], __('base.success'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(AdminGroup $model, $id)
    {
        $data = $model::find($id);
        return $this->success($data, __('base.success'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
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
        $model = AdminGroup::find($id);
        if (!$model) {
            return $this->failed('数据不存在');
        }
        $model->title = $request->sort;
        $model->title = $request->title;
        $model->service = $request->service ?: 0;
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
        $model = new AdminGroup();
        $model->destroy($idArray);
        return $this->success([], __('base.success'));
    }
}
