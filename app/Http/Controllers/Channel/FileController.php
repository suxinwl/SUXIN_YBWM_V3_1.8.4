<?php

namespace App\Http\Controllers\Channel;

use App\Http\Controllers\Controller;
use App\Models\Admin\Apply;
use App\Models\BaiduAi;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use App\Models\Config;
use App\Models\Storage;
use App\Models\File;
use App\Models\Category;
use App\Services\ConfigService;
use Illuminate\Support\Facades\Request as FacadesRequest;
use Illuminate\Support\Facades\Storage as FacadesStorage;
use OSS\Core\OssException;
use OSS\OssClient;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class FileController extends ApiController
{

    // //图片上传
    // public function upload(Request $request)
    // {
    //     $uniacid = $this->uniacid();
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
        $group = $request->header('group', $request->group ?? 'img');
        $attachmentSettings = ConfigService::getSystemSet('attachmentSettings');

        if (!$file->isValid()) {
            return $this->failed("上传文件不合法");
        }
        $fileName = $file->getClientOriginalName();
        $entension = strtolower($file->getClientOriginalExtension());
        if($attachmentSettings){
            if ($group == 'img') {
                if (!in_array($entension, $attachmentSettings->picType ?: [])) {
                    return $this->failed("允许图片上传的格式为:", implode(',', $attachmentSettings->picType));
                }
                $filesize = $file->getSize();
                if (ceil($filesize / 1024) > $attachmentSettings->picSize) {
                    return $this->failed("上传图片大小超出限制:" . $attachmentSettings->picSize . "Kb");
                }
            }
            if ($group == 'voice') {
                if (!in_array($entension, $attachmentSettings->videoType ?: [])) {
                    return $this->failed("允许视频上传的格式为:", implode(',', $attachmentSettings->videoType));
                }
                $filesize = $file->getSize();
                if (ceil($filesize / 1024) > $attachmentSettings->videoSize) {
                    return $this->failed("允许视频上传的格式为:" . $attachmentSettings->videoSize . "Kb");
                }
            }
        }

        $data = getimagesize($file->getRealPath());
        $width = $data[0];
        $height = $data[1];
        $uniacid = $this->uniacid() ?? 0;
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
        $module = $request->dir ?? 'uploads';
        switch ($storageConfig->type) {
            case 0;
                $path = Storage::channelUploadImage($file, $pathName, $module, $uniacid);
                $data = 'https://' . Request()->server('HTTP_HOST') . $path;
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
                $fileExt = $file->getClientOriginalExtension();        //获取文件后缀名
                $realPath = $file->getRealPath();        //获取文件真实路径
                $filename = date('YmdHis') . uniqid() . '.' . $fileExt;        //按照一定格式取名
                $filepath = $storageConfig->ftpFile;        //个人要求的路径
                $bool = FacadesStorage::disk('ftp')->put($filepath . $filename, file_get_contents($realPath));
                $data = $storageConfig->ftpDomain . $filepath . $filename;        //文件的url地址
                break;
            default;
                $path = Storage::channelUploadImage($file, $pathName, $module, $uniacid);
                $data = 'https://' . Request()->server('HTTP_HOST') . $path;
                break;
        }
        if ($group == 'img') {
            File::create([
                'shopId' => 0,
                'storeId' => $this->storeId(),
                "categoryId" => $request->header('categoryId') ?? 0,
                "url" => $data,
                "uniacid" => $uniacid,
                "width" => $width ?? 0,
                "height" => $height ?? 0,
                "name" => $fileName,
                "channel" => $type ?? 0,
                "fileType" => $entension,
                "fileSize" => ceil($filesize / 1024),
                "path" => $path ?? ''
            ]);
        }
        if ($group == 'voice') {
            File::create([
                'shopId' => 0,
                "categoryId" => $request->header('categoryId') ?? 0,
                "url" => $data,
                "uniacid" => $uniacid,
                "width" =>  0,
                'storeId' => $this->storeId(),
                "height" =>  0,
                "name" => $fileName,
                "channel" => $type ?? 0,
                "fileType" => $entension,
                "fileSize" => ceil($filesize / 1024),
                "path" => $path ?? ''
            ]);
        }
        return $this->success($data, __('success'));
    }

    //图片上传
    public function uploadBase64(Request $request)
    {
        $file = $request->post('file');
        $ext =  $request->ext;
        if (empty($file) || empty($ext)) {
            throw new BadRequestException("缺少参数");
        }
        $ext = strtolower($ext);
        $uniacid = $this->uniacid();
        $domain = $request->server('SERVER_PORT') == 443 ? 'https://' . $request->server('HTTP_HOST') : "http://" . $request->server('HTTP_HOST');
        $module = 'uploads';
        $data = Storage::channelUploadBase64($file,  $module, $uniacid, $ext, $domain, $request->categoryId ?? 0, $request->header('shopId') ?? 0);
        return $this->success($data['url'], __('success'));
    }

    //获取拖拽图片分类
    public function getCategory(Request $request)
    {
        $uniacid = $this->uniacid();
        $categoryModel = new Category();
        $res =  Category::where('item', 1)
            ->whereNull('deleteAt');
        if ($request->pub) {
            $res->where('uniacid', 0);
        } else {
            $res->where('uniacid', $uniacid)
                ->where('storeId', $this->storeId());
        }
        $res  = $res->get();
        return $this->success($res, __('success'));
    }

    //移动图片
    public function moveFile(Request $request)
    {
        if (is_array($request->id)) {
            $idArr = $request->id;
        } else {
            $idArr = [$request->id];
        }
        $fileModel = new File();
        $res = $fileModel->whereIn('id', $idArr)->update(['categoryId' => $request->categoryId]);
        if ($res) {
            return $this->success($res, __('success'));
        } else {
            return $this->failed($res, __('error'));
        }
    }


    //获取图片
    public function getPicture(Request $request)
    {
        $uniacid = $this->uniacid() ?: 0;
        $typeId = $request->categoryId;
        $keyWords = $request->name;
        $storeId = $request->storeId ?: 0;
        $material_type = $request->material_type;

        $fileModel =  File::whereNull('deleteAt');
        if ($request->pub) {
            $fileModel = $fileModel->where('iscommon', 1)->where('uniacid', 0);
        } else {
            $fileModel = $fileModel->where('iscommon', 0)
                ->where('storeId', $this->storeId())
                ->where('uniacid', $uniacid);
        }
        if ($typeId) {
            $fileModel = $fileModel->where('categoryId', $typeId);
        }
        if ($material_type) {
            if ($material_type == 4) {
                $fileModel = $fileModel->whereNotNull('deleteAt');
            } else {
                $fileModel = $fileModel->where('material_type', $material_type)
                    ->whereNull('deleteAt');
            }
        }
        if ($keyWords) {
            $fileModel = $fileModel->where('name', 'like', '%' . $keyWords . '%');
        }
        $res = $fileModel->orderByDesc('id')->paginate($request->pageSize ?? 30, '*', 'pageNo');
        return $this->success($res, __('success'));
    }

    //删除图片分类
    public function delPictureCategory(Request $request)
    {
        $id = $request->id;
        $categoryModel = Category::find($id);
        $categoryModel->deleteAt = date('Y-m-d H:i:s', time());
        $res = $categoryModel->save();
        if ($res) {
            return $this->success($res, __('success'));
        } else {
            return $this->failed(__('图片分类删除失败'));
        }
    }


    public function changeCategory(Request $request)
    {
        $id = $request->id;
        $categoryModel = Category::find($id);
        if (empty($categoryModel)) {
            return $this->failed('数据不存在');
        }
        $categoryModel->name = $request->name;
        $res = $categoryModel->save();
        if ($res) {
            return $this->success($res, __('success'));
        } else {
            return $this->failed('修改分组名称失败');
        }
    }
    //修改图片
    public function editPicture(Request $request)
    {
        if (is_array($request->id)) {
            $idArr = $request->id;
        } else {
            $idArr = [$request->id];
        }
        $fileModel = new File();
        $res = $fileModel->whereIn('id', $idArr)->update(['name' => $request->name]);
        if ($res) {
            return $this->success($res, __('success'));
        } else {
            return $this->failed($res, __('图片删除失败'));
        }
    }
    //删除图片
    public function delPicture(Request $request)
    {
        if (is_array($request->id)) {
            $idArr = $request->id;
        } else {
            $idArr = [$request->id];
        }
        $delete_type = $request->delete_type ?: 1;
        $fileModel = new File();
        if ($delete_type == 1) {
            $res = $fileModel->whereIn('id', $idArr)->update(['deleteAt' => date('Y-m-d H:i:s', time())]);
        } elseif ($delete_type == 2) {
            $res = $fileModel->whereIn('id', $idArr)->delete();
        } else {
            $res = $fileModel->whereIn('id', $idArr)->update(['deleteAt' => NULL]);
        }
        if ($res) {
            return $this->success($res, __('success'));
        } else {
            return $this->failed($res, __('图片删除失败'));
        }
    }
    //添加分组
    public function saveCategory(Request $request)
    {
        $storeId = $request->shopId ?: 0;
        $name = $request->name ?: 0;
        $uniacid = $this->uniacid();
        if (!$request->name) {
            throw new BadRequestException('分类名称不能为空');
        }
        if ($request->id) {
            $model = Category::where('uniacid', $uniacid)->where('name', $name)
                ->where('item', 1)->where('deleteAt', 0)
                ->where('storeId', $this->storeId())
                ->where('id', "!=", $request->id)
                ->first();
            if ($model) {
                throw new BadRequestException('该名称已存在');
            }
            $categoryModel = Category::find($request->id);
            if (empty($categoryModel)) {
                throw new BadRequestException('数据不存在');
            }
            $categoryModel->name = $name;
            $categoryModel->updated_at = date('Y-m-d H:i:s', time());
            $categoryModel->save();
        } else {
            $categoryModel = new Category();
            $categoryModel->name = $name;
            $categoryModel->shopId = $storeId;
            $categoryModel->item = 1;
            $categoryModel->uniacid = $uniacid;
            $categoryModel->storeId = $this->storeId();
            $categoryModel->save();
        }
        return $this->success();
    }

    //获取图片回收站列表
    public function recyclePicture(Request $request)
    {
        $typeId = $request->category;
        $uniacid = $this->uniacid() ?: 0;
        $keyWords = $request->keywords;
        $storeId = $request->storeId ?: 0;
        $fileModel = new File();
        $fileModel->where('uniacid', $uniacid)->where('deleteAt', '>', 0)->where('storeId', $this->storeId());
        if ($storeId) {
            $fileModel->andwhere('storeId', $storeId);
        }
        if ($typeId) {
            $fileModel->andwhere('categoryId', $typeId);
        }
        if ($keyWords) {
            $fileModel->andwhere(['name', 'like', '%' . $keyWords . '%']);
        }
        $res = $fileModel->orderByDesc('id')->paginate($request->pageSize ?? 30, '*', 'pageNo');
        return $this->success($res, __('success'));
    }


    //删除回收站的图片
    public function delRecyclePicture(Request $request)
    {
        $fileModel = new File();
        $id = $request->id;
        $row = $fileModel->where('id', $id)->first();
        $domain = 'https://' . FacadesRequest()->server('HTTP_HOST');
        //var_dump('.'.strstr($row->url,"/public"));die;
        if (strpos($row->url, $domain) !== false) {
            unlink(strstr($row->url, "storage/"));
        } else {
            $configModel = new Config();
            $storageConfig = $configModel->where('uniacid', $row['uniacid'])->where('ident', 'storage')->first();
            if (!$storageConfig) {
                $storageConfig = $configModel->where('uniacid', 0)->where('ident', 'storage')->first();
            }
            $config = json_decode($storageConfig['data'], true);
            //dd($config);die;
            $qnUrl = $config['qn_url'];
            $aliUrl = $config['aliyuncs_url'];
            $txUrl = $config['xplqcloud_url'];
            if (strpos($row['url'], $qnUrl) !== false) {
                $ak = $config['qn_accesskey'];
                $sk = $config['qn_secretkey'];
                $domain = $config['qn_url'];
                $bucket = $config['qn_bucket'];
                $zone = $config['qn_endpoint'];
                $qiniu = new Qiniu($ak, $sk, $domain, $bucket, $zone);
                $array = explode($qnUrl . '/', $row['url']);
                $url = $array[1];
                try {
                    $qiniu->delete($url, $bucket);
                } catch (\Exception $e) {
                }
            }
            if (strpos($row['url'], $aliUrl) !== false) {
                $accessKeyId = $config['aliyuncs_accesskey'];
                $accessKeySecret = $config['aliyuncs_secret'];
                $endpoint = $config['aliyuncs_endpoint'];
                $bucket = $config['aliyuncs_bucket'];
                try {
                    $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
                    $array = explode($aliUrl . '/', $row['url']);
                    $url = $array[1];
                    $ossClient->deleteObject($bucket, $url);
                } catch (OssException $e) {
                }
            }
            if (strpos($row['url'], $txUrl) !== false) {
                $array = explode($txUrl . '/', $row['url']);
                $url = $array[1];
                $data = [
                    'app_id' => $config['xplqcloud_appid'],
                    'secret_id' => $config['xplqcloud_secretid'],
                    'secret_key' => $config['xplqcloud_secretkey'],
                    'region' => $config['xplqcloud_endpoint'],
                    'bucket' => $config['xplqcloud_bucket'],
                    'timeout' => 60
                ];
                try {
                    $cos = new Cos($data);
                    $cos->delFile($url);
                } catch (OssException $e) {
                }
            }
        }
        $bool = $fileModel->where('id', $id)->delete();
        if ($bool) {
            return $this->success([], __('success'));
        } else {
            return $this->failed([], __('图片删除失败'));
        }
    }
}
