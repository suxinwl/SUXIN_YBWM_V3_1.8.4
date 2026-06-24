<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ConfigRequest;
use App\Models\Config;
use App\Models\News;
use App\Services\ConfigService;
use Illuminate\Http\Request;

class NewsController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $model = new News();
        $type = $request->type ?: 1;
        if ($request->type == 2) {
            $url = config('app.authorizeDomain') . '/cloud/artice/getarticelist';
            $params = array(
                'auth_type' => 1,
                'type' => 1,
                'pageNo' => $request->pageNo,
                'pageSize' => $request->pageSize
            );
            $data = httpRequest($url, $params);
            return $data;
        }
        $list = News::select(['id', 'title', 'sort', 'created_at', 'user', 'subTitle']);
        if ($request->keyword) {
            $list->where('title', 'like', "%{$request->keyword}%");
        }
        if ($request->type) {
            $list->where('type', $request->type);
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
    public function store(Request $request, News $model)
    {
        $model->create([
            'type' =>  $request->type ?: 1,
            'state' => $request->state ?: 0,
            'title' => $request->title ?: '',
            'subTitle' => $request->subTitle ?: '',
            "user" => $request->user ?: '',
            'content' => $request->input('content'),
        ]);
        return $this->success([], __('base.success'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(News $model, $id)
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
        $model = News::find($id);
        if (!$model) {
            return $this->failed('数据不存在');
        }
        $model->title = $request->title;
        $model->state = $request->state;
        $model->subTitle = $request->subTitle?:'';
        $model->user = $request->user;
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
        $model = new News();
        $model->destroy($idArray);
        return $this->success([], __('base.success'));
    }
}
