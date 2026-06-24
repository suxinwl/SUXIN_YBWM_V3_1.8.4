<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Smscombo;
use Illuminate\Http\Request;

class SmsComboController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $req, Smscombo $model)
    {
        $list = $model->orderBy('sort','asc')->orderBy('id', 'desc')->paginate($req->pageSize ?? 30);
        return $this->success($list);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $req, Smscombo $model)
    {
        $model->create($req->all());
        return $this->success([]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $req, Smscombo $model,$id)
    {
        $model = Smscombo::find($id);
        if (empty($model)) {
            return $this->failed('数据不存在');
        }
        $model->fill($req->all());
        $model->save();
        return $this->success([]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return $this->success(Smscombo::find($id));
    }

    public function state(Request $req, $id)
    {
        $model = Smscombo::find($id);
        if (empty($model)) {
            return $this->failed('数据不存在');
        }
        $model->state = intval(!$model->state);
        $model->save();
        return $this->success([]);
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
        $model = new Smscombo();
        $model->destroy($idArray);
        return $this->success([], __('base.success'));
    }
}
