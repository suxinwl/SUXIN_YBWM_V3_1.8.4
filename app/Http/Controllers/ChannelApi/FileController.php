<?php

namespace App\Http\Controllers\ChannelApi;

use App\Http\Controllers\Controller;
use App\Models\Admin\Apply;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use App\Models\Config;
use App\Models\Storage;
use App\Models\File;
use App\Models\Category;
use App\Services\ConfigService;
use Illuminate\Support\Facades\Request as FacadesRequest;
use OSS\Core\OssException;
use OSS\OssClient;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class FileController extends ApiController
{

    // //图片上传
    // public function upload(Request $request)
    // {
    //     $uniacid = $this->uniacid;
    //     $module = config('app.appKey');
    //     $configModel = new Config();
    //     $config = $configModel->getSystemSet('storage', $uniacid);

    //     if (!$config) {
    //         $config = $configModel->getSystemSet('storage', 0);
    //     }
    //     //dd($config);die;
    //     // 允许上传的图片后缀
    //     $allowedExts = array("gif", "jpeg", "jpg", "png", "jfif");
    //     $temp = explode(".", $_FILES["file"]["name"]);
    //     $extension = end($temp);        // 获取文件后缀名
    //     if (!in_array($extension, $allowedExts)) {
    //         echo json_encode(['code' => '405', 'msg' => '文件类型格式不允许上传']);
    //         die;
    //     }
    //     $storageModel = new Storage();
    //     if ($config) {
    //         if ($config->type == 1) {
    //             $fname = $storageModel->qiniuUpload($request->file, $module, $uniacid, $config);
    //         } elseif ($config->type == 2) {
    //             $fname = $storageModel->aliUpload($request->file, $module, $uniacid, $config);
    //         } elseif ($config->type == 3) {
    //             $fname = $storageModel->txyUpload($request->file, $module, $uniacid, $config);
    //         } elseif ($config->type == 4) {
    //             $fname = $storageModel->ftpUpload($request->file, $module, $uniacid, $config);
    //         }
    //     } else {
    //         $fname = $storageModel->channelUploadImage($request->file, '', $module, $uniacid);
    //     }
    //     $file = new File();
    //     $file->categoryId = $request->category ?: 0;
    //     $file->url = $fname;
    //     $file->name = $_FILES['file']['name'];
    //     $file->storeId = $request->storeId ?: 0;
    //     $file->uniacid = $uniacid;
    //     $res = $file->save();
    //     return $this->success($fname, __('success'));
    // }

    //图片上传
    public function upload(Request $request)
    {
        $file = $request->file('file');
        $attachmentSettings = ConfigService::getSystemSet('attachmentSettings');
        if (!$file->isValid()) {
            return $this->failed("上传文件不合法");
        }
        $fileName = $file->getClientOriginalName();
        $entension = $file->getClientOriginalExtension();
        if (!in_array($entension, $attachmentSettings->picType ?: [])) {
            return $this->failed("允许图片上传的格式为:", implode(',', $attachmentSettings->picType));
        }
        $filesize = $file->getSize();
        if (ceil($filesize / 1024) > $attachmentSettings->picSize) {
            return $this->failed("上传图片大小超出限制:" . $attachmentSettings->picSize . "Kb");
        }
        $image = getimagesize($file->getRealPath());
        $width = $image[0];
        $height = $image[1];
        $uniacid = $this->uniacid();
        //Storage::imageCensor(base64_encode(file_get_contents($file->getRealPath())), $uniacid);
        $storageConfig = ConfigService::getSystemSet('storage');
        if (!empty($uniacid)) {
            $apply = Apply::find($uniacid);
            if (empty($apply)) {
                return $this->failed("参数错误");
            }
            if (!empty($apply->attachmentData)) {
                if ($apply->attachmentData['attachmentType'] == 0) {
                    $type = $storageConfig->type;
                } else {
                    $type = $apply->attachmentData['type'];
                    $storageConfig = (object)$apply->attachmentData;
                }
            }
        }
        $pathName = config('app.appKey') . '/' . date('Y') . "/" . date('m') . "/" . date('d');

        $domain = $request->server('SERVER_PORT') == 443 ? 'https://' . $request->server('HTTP_HOST') : "http://" . $request->server('HTTP_HOST');
        $module = $request->dir ?? 'user';
        switch ($storageConfig->type) {
            case 0;
                $path = Storage::channelUploadImage($file, $pathName, $module, $uniacid);
                $data = $domain . $path;
                break;
            case 1;
                $path = Storage::qiniuUpload($file, $module, $uniacid, $storageConfig);
                $data = $storageConfig->qn_url . $path;
                break;
            case 2;
                $data = Storage::aliUpload($file, $module, $uniacid, $storageConfig);
                $path = $data['path'];
                $data = $data['url'];
                break;
            case 3;
                $path = Storage::txyUpload($file, $module, $uniacid, $storageConfig);
                $data =  $storageConfig->xplqcloud_url . $path;
                break;
            case 4;
                $path = Storage::ftpUpload($file, '', $module, $uniacid);
                $data = $path;
                break;
            default;
                $path = Storage::channelUploadImage($file, $pathName, $module, $uniacid);
                $data = $domain . $path;
                break;
        }
        return $this->success($data, '图片上传成功');
    }

    //图片上传
    public function uploadBase64(Request $request)
    {
        $file = $request->post('file');
        $ext =  $request->ext;
        if (empty($file) || empty($ext)) {
            throw new BadRequestException("缺少参数");
        }
        $uniacid = $this->uniacid();
        $domain = $request->server('SERVER_PORT') == 443 ? 'https://' . $request->server('HTTP_HOST') : "http://" . $request->server('HTTP_HOST');
        $module = 'uploads';
        $data = Storage::channelUploadBase64($file,  $module, $uniacid, $ext, $domain);
        return $this->success($data['url'], __('success'));
    }
}
