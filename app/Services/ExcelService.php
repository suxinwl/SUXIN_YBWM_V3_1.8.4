<?php

namespace App\Services;

use App\Exports\ExcelExport;
use App\Traits\ResourceTrait;
use Maatwebsite\Excel\Facades\Excel;

class ExcelService
{

    public static function export($data, $headers, $name)
    {
        return  Excel::download(new ExcelExport($data, $headers), $name);
    }
}
