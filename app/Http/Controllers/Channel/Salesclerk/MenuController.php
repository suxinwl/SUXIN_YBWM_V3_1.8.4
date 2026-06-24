<?php

namespace App\Http\Controllers\Channel\Salesclerk;

use App\Http\Controllers\Channel\ApiController;
use App\Models\Menu;
use App\Models\RoleMenu;
use App\Services\RoleService;
use App\Traits\HelperTrait;
use Illuminate\Support\Arr;

class MenuController extends ApiController
{
    use HelperTrait;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data['channel'] = $this->getChildren(Menu::select(['id', 'name', 'pid', 'meta'])->where('roleLevel', 'like', '%1%')->where('is_type', 1)->orderBy('pid', 'asc')->orderBy('is_sort', 'asc')->get()->toArray());

        if ($this->isolate()) {
            $data['store'] = $this->getChildren(Menu::select(['id', 'name', 'pid', 'meta'])->where('isolate', 1)->where('is_type', 1)->orderBy('pid', 'asc')->orderBy('is_sort', 'asc')->get()->toArray());
        } else {
            $data['store'] = $this->getChildren(Menu::select(['id', 'name', 'pid', 'meta'])->where('roleLevel', 'like', '%2%')->where('is_type', 1)->orderBy('pid', 'asc')->orderBy('is_sort', 'asc')->get()->toArray());
        }
        $data['storeRoleList'] = RoleService::storeRole();
        $data['cashierRoleList'] = RoleService::cashierRole();
        return $this->success($data);
    }
}
