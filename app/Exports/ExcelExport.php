<?php

namespace App\Exports;

use App\Models\Member;
use App\Models\BulkOrder;
use App\Models\MemberAccount;
use App\Models\MemberLabel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings; //导出excle表头
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;


// $header = [
//     ['ID', 'id', 'text'],
//     ['手机号码', 'mobile'], // 规则不填默认text
//     ['openid', 'fans.openid', 'text'],
//     ['昵称', 'fans.nickname', 'text'],
//     ['关注/扫描', 'type', 'selectd', [1 => '关注', 2 => '扫描']],
//     ['性别', 'sex', 'function', function($model){
//         return $model['sex'] == 1 ? '男' : '女';
//     }],
//     ['创建时间', 'created_at', 'date', 'Y-m-d'],
// ];
class ExcelExport implements FromArray, WithHeadings, WithColumnWidths
{
    public $_headings = [];
    public $_data = [];
    public $_columnWidths = [];
    public $_list = [];
    public $_header = [];
    public function __construct($list = [], $header = [])
    {
        $hk = 65;
        foreach ($header as $k => $v) {
            $this->_headings[$k] = $v[0];
            if (isset($v['width'])) {
                $this->_columnWidths[strtoupper(chr($hk))] = $v['width'];
            }
            $hk += 1;
        }
        $this->_list = $list;
        $this->_header = $header;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function array(): array
    {
        $header = $this->_header;
        return collect($this->_list)->map(function ($item, $key) use ($header) {
            $row = collect($item)->toArray();
            foreach ($header as $key2 => $value) {
                $rowValue = self::formattingField($row, $value[1]);
                $v[$key2] = self::formatting($header[$key2], is_array($rowValue) ? $rowValue : trim($rowValue), $row) ?? [];
            }
            return $v;
        })->toArray();
    }
    //添加指定表头
    public function headings(): array
    {

        return $this->_headings;
    }

    public function columnWidths(): array
    {
        return $this->_columnWidths;
    }

    /**
     * 格式化内容
     *
     * @param array $array 头部规则
     * @return false|mixed|null|string 内容值
     */
    protected static function formatting(array $array, $value, $row)
    {
        !isset($array[2]) && $array[2] = 'text';
        switch ($array[2]) {
                // 文本
            case 'text':
                return $value;
                break;
                // 日期
            case 'date':
                return !empty($value) ? date($array[3], $value) : null;
                break;
                // 选择框
            case 'selectd':
                return $array[3][$value] ?? null;
                break;
                // 匿名函数
            case 'function':
                return isset($array[3]) ? call_user_func($array[3], $row) : null;
                break;
                // 默认
            default:

                break;
        }

        return null;
    }

    /**
     * 解析字段
     *
     * @param $row
     * @param $field
     * @return mixed
     */
    protected static function formattingField($row, $field)
    {
        $newField = explode('.', $field);
        if (count($newField) == 1) {
            return $row[$field] ?? '';
        }

        foreach ($newField as $item) {
            if (isset($row[$item])) {
                $row = $row[$item];
            } else {
                break;
            }
        }
        return is_array($row) ? false : $row;
    }
}
