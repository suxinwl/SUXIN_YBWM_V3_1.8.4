<?php
namespace App\Services;

class LengthAwarePaginatorService extends \Illuminate\Pagination\LengthAwarePaginator
{
    public function toArray()
    {
        return [
            'list' => $this->items->toArray(),
            'total'=>$this->total(), // 数据总数
            'pageSize'=>$this->perPage(), // 每页数量
            'pageNo'=>$this->currentPage(), // 当前页码
        ];
    }
}