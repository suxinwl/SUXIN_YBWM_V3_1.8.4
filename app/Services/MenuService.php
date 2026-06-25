<?php

namespace App\Services;

use App\Models\Menu;
use App\Models\Role;
use App\Models\RoleMenu;
use App\Traits\HelperTrait;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Tymon\JWTAuth\Facades\JWTAuth;

class MenuService extends BaseService
{
    use HelperTrait;
    public function getMenus($isSeller = false, $isolate)
    {
        $is_type = 0;
        if ($isSeller) {
            $is_type = 1;
        }
        $menu_model = new Menu();
        $cache_name = $is_type == 0 ? 'admin_menus_cache' : 'seller_menus_cache';
        Cache::forget($cache_name);
        $list = [];
        if (!Cache::has($cache_name)) {
            if ($isolate) {
                $menus_list = $menu_model->where('is_type', $is_type)->where('isolate', 1)->orderBy('is_sort')->get()->toArray();
            } else {
                $menus_list = $menu_model->where('is_type', $is_type)->orderBy('is_sort')->get()->toArray();
            }
            $list = $this->filterRemovedMenus($this->menusTree($menus_list));
            Cache::set($cache_name, $list);
        } else {
            $list = Cache::get($cache_name);
        }
        return $list;
    }

    // 清空缓存
    public function clearCache()
    {
        $rs = Cache::forget('admin_menus_cache');
        $rs = Cache::forget('seller_menus_cache');
        return $this->format($rs);
    }


    /**
     * 获取前端菜单
     */
    public  function loadlMenus($isChannel = false, $isolate = 0)
    {
        $user = auth('admin')->user();
        if ($isChannel == true && $user->isAdmin == 1) {
            return $this->getMenus($isChannel, $isolate);
        } elseif ($user->role_id == 0) {
            return $this->getMenus($isChannel, $isolate);
        }
        return $this->getMenusForRoleId($user->role_id);
    }

    /**
     * 根据roleId获取菜单那
     */
    public function getMenusForRoleId($roleId = 0)
    {
        $menus = RoleMenu::where('role_id', $roleId)->get("menu_id")->toArray();
        return $this->filterRemovedMenus($this->getChildren(Menu::whereIn('id', Arr::pluck($menus, 'menu_id'))->orderBy('pid', 'asc')->orderBy('is_sort', 'asc')->get()->toArray()));
    }

    private function filterRemovedMenus(array $menus): array
    {
        $removedPaths = ['/authorize/index', '/authorize/management'];
        $filtered = [];

        foreach ($menus as $menu) {
            if (isset($menu['path']) && in_array($menu['path'], $removedPaths, true)) {
                continue;
            }

            if (isset($menu['children']) && is_array($menu['children'])) {
                $menu['children'] = $this->filterRemovedMenus($menu['children']);
                if (count($menu['children']) <= 0) {
                    unset($menu['children']);
                }
            }

            $filtered[] = $menu;
        }

        return array_values($filtered);
    }
}
