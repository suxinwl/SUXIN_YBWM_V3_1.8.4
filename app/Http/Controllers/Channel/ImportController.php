<?php

namespace App\Http\Controllers\Channel;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\HandeLog\HandeListCollection;
use App\Imports\SpecsImport;
use App\Imports\StoreImport;
use Illuminate\Http\Request;
use App\Models\Admin\HandleLog;
use Maatwebsite\Excel\Facades\Excel;

class ImportController extends ApiController
{
    public function store(Request $request, $type)
    {
        switch ($type) {
            case 'store':
                Excel::import((new StoreImport($this->uniacid())), $request->file);
                break;
            case 'goodsCat'; //商品分类
                Excel::import((new SpecsImport($this->uniacid(), 'goodsCat', $this->storeId())), $request->file);
                break;
            case 'spec'; //规格管理
                Excel::import((new SpecsImport($this->uniacid(), 'spec', $this->storeId())), $request->file);
                break;
            case 'material'; //加料管理
                Excel::import((new SpecsImport($this->uniacid(), 'material', $this->storeId())), $request->file);
                break;
            case 'attr'; //属性管理
                Excel::import((new SpecsImport($this->uniacid(), 'attr', $this->storeId())), $request->file);
                break;
            case 'unit'; //单位管理
                Excel::import((new SpecsImport($this->uniacid(), 'unit', $this->storeId())), $request->file);
                break;
            case 'label'; //标签管理
                Excel::import((new SpecsImport($this->uniacid(), 'label', $this->storeId())), $request->file);
                break;
            case 'mark'; //标签管理
                Excel::import((new SpecsImport($this->uniacid(), 'mark', $this->storeId())), $request->file);
                break;
            case 'goods'; //标签管理
                Excel::import((new SpecsImport($this->uniacid(), 'goods', $this->storeId())), $request->file);
                break;
            case 'member'; //标签管理
                Excel::import((new SpecsImport($this->uniacid(), 'member',)), $request->file);
                break;
        }
        return $this->success();
    }
}
