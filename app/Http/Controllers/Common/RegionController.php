<?php

namespace App\Http\Controllers\Common;

use App\Models\Region;
use App\Traits\HelperTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class RegionController extends ApiController
{
    use HelperTrait;
    public function Index()
    {
        $data = Cache::get('regionDatas');
        if (empty($data)) {
            $data = $this->getChildren(Region::where('level', '<=', 2)->get()->toArray(), 0, 0, 'pid');
            Cache::put('regionDatas', $data);
        }
        return $this->success($data);
    }

    public function region(Request $request)
    {
        if (empty($request->region)) {
            $data = Region::where('level', 0)->where('pid', 0)->orderBy('pinyin_prefix', 'asc')->orderBy('id', 'asc')->get();
        } else {
            $data = Region::where('level', '<=', 2)->where('pid', $request->region)->orderBy('pinyin_prefix', 'asc')->orderBy('id', 'asc')->get();
        }
        if ($request->pyGroup) {
            $data = collect($data)->groupBy('pinyin_prefix')->sortKeys();
        }
        return $this->success($data);
    }

    public function nameToRegion(Request $request)
    {
        $data = Region::whereIn('name', explode(',', $request->region))->get();
        return $this->success(collect($data)->pluck('id')->sortBy('id', SORT_ASC));
    }
}
