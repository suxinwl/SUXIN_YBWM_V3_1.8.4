<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UploadController extends Controller
{
    public function saveImage(Request $request){
        $saveDir='file';
        $file = $request->file;
        upload_img($saveDir,$file);
    }
}
