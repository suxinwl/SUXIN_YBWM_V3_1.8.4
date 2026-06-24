<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MenusRequest;
use App\Models\Menu;
use Illuminate\Http\Request;
use App\Services\MenuService;

use function PHPSTORM_META\map;

class MenuController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $menu_service = new MenuService;
        $menu_service->clearCache();
        $list = $menu_service->getMenus($request->is_type ?? 0);
        return $this->success($list);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(MenusRequest $request, Menu $menu_model)
    {
        $menu_model->pid = $request->pid ?? 0;
        $menu_model->name = $request->name ?? "";
        $menu_model->path = $request->path ?? '';
        $menu_model->component = $request->component ?? '';
        $menu_model->meta = json_encode($request->meta, 320) ?? "";
        $menu_model->is_sort = $request->is_sort ?? 0;
        $menu_model->is_type = $request->is_type ?? 0;
        $menu_model->roleLevel = $request->roleLevel ?? [];
        $menu_model->save();
        $this->clear_cache(); // 修改则清空缓存
        return $this->success([], __('base.success'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Menu $menu_model, $id)
    {
        $info = $menu_model->find($id);
        return $this->success($info);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(MenusRequest $request, Menu $menu_model, $id)
    {
        $menu_model = $menu_model->find($id);
        if (empty($menu_model)) {
            return $this->failed(__('base.nodata'));
        }
        $menu_model->pid = $request->pid ?? 0;
        $menu_model->name = $request->name;
        $menu_model->path = $request->path ?? '';
        $menu_model->component = $request->component ?? '';
        $menu_model->meta = json_encode($request->meta, 320) ?? "";
        $menu_model->is_sort = $request->is_sort ?? 0;
        $menu_model->is_type = $request->is_type ?? 0;
        $menu_model->roleLevel = $request->roleLevel ?? [];
        $menu_model->save();
        $this->clear_cache(); // 修改则清空缓存
        return $this->success([], __('base.success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Menu $menu_model, $id)
    {
        $idArray = array_filter(explode(',', $id), function ($item) {
            return is_numeric($item);
        });
        $menu_model->destroy($idArray);
        $this->clear_cache(); // 修改则清空缓存
        return $this->success([], __('base.success'));
    }

    // 清除菜单缓存
    public function clear_cache()
    {
        $menu_service = new MenuService;
        $menu_service->clearCache();
        return $this->success([], __('base.success'));
    }


    public function batch(Request $request)
    {
        $count = Menu::where("is_type", 1)->where("pid", 0)->count();

        $list = json_decode($request->data, true);
        $data = [];
        foreach ($list as $key =>  $item) {
            $id = Menu::insertGetId([
                'is_type' => 1,
                'roleLevel' => json_encode([1, 2], 320),
                'redirect' => $item['redirect'] ?? null,
                'pid' => 0,
                'is_sort' => $count + 1,
                'path' => $item['path'],
                'name' => $item['name'],
                'meta' => json_encode($item['meta'], 320),
                'component' => $item['component']
            ]);
            if (isset($item['children']) && !empty($item['children'])) {
                $data = array_merge($data, $this->getItme($item['children'], $id));
            }
        }
        Menu::insert($data);
    }

    public function getItme($arr, $pid, $data = [])
    {
        foreach ($arr as $key =>  $item) {
            if (isset($item['children']) && !empty($item['children'])) {
                $id = Menu::insertGetId([
                    'is_type' => 1,
                    'roleLevel' => json_encode([1, 2], 320),
                    'pid' => $pid,
                    'is_sort' => $key,
                    'path' => $item['path'],
                    'name' => $item['name'],
                    'redirect' => $item['redirect'] ?? null,
                    'meta' => json_encode($item['meta'], 320),
                    'component' => $item['component']
                ]);
                $data = array_merge($data, $this->getItme($item['children'], $id));
            } else {
                $data[] = [
                    'is_type' => 1,
                    'roleLevel' => json_encode([1, 2], 320),
                    'pid' => $pid,
                    'is_sort' => $key,
                    'path' => $item['path'],
                    'name' => $item['name'],
                    'redirect' => $item['redirect'] ?? null,
                    'meta' => json_encode($item['meta'], 320),
                    'component' => $item['component']
                ];
            }
        }
        return  $data;
    }
}
