<?php

namespace App\Http\Controllers\Common;

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
use Illuminate\Support\Facades\File as FacadesFile;
use Illuminate\Support\Facades\Storage as FacadesStorage;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class FileController extends ApiController
{

    public function ceshi()
    {
        $uniacid = $this->uniacid;
        var_dump($uniacid);
    }
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
        $fileName = $file->getClientOriginalName();
        $entension = strtolower($file->getClientOriginalExtension());
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
        $uniacid = $request->header('uniacid', 0);
        $storageConfig = ConfigService::getSystemSet('storage');
        //Storage::imageCensor(base64_encode(file_get_contents($file->getRealPath())), $uniacid);
        if (!empty($uniacid)) {
            $apply = Apply::find($uniacid);
            if (empty($apply)) {
                return $this->failed("参数错误");
            }
            if (!empty($apply->attachmentData)) {
                if ($apply->attachmentData['attachmentType'] == 0) {
                    $type = $storageConfig->type;
                } else {
                    $type = $apply->attachmentData['attachmentType'];
                    $storageConfig = (object)$apply->attachmentData;
                }
            }
        }
        $pathName = config('app.appKey') . '/' . date('Y') . "/" . date('m') . "/" . date('d');

        $module = 'uploads';
        switch ($storageConfig->type) {
            case 0;
                $path = Storage::channelUploadImage($file, $pathName, $module, $uniacid);
                $domain = $request->server('SERVER_PORT') == 443 ? 'https://' . $request->server('HTTP_HOST') : "http://" . $request->server('HTTP_HOST');
                $data = $domain . $path;
                break;
            case 1;
                $path = Storage::qiniuUpload($file, $module, $uniacid, $storageConfig);
                $domain = $storageConfig->qn_url;
                $data = $domain . $path;
                break;
            case 2;
                $data = Storage::aliUpload($file, $module, $uniacid, $storageConfig);
                $path = '/' . $data['path'];
                $domain = $storageConfig->aliyuncs_url;
                $data = $data['url'];
                break;
            case 3;
                $path = Storage::txyUpload($file, $module, $uniacid, $storageConfig);
                $domain = $storageConfig->xplqcloud_url;
                $data =  $storageConfig->xplqcloud_url . $path;
                break;
            case 4;
                $fileExt = $file->getClientOriginalExtension();        //获取文件后缀名
                $realPath = $file->getRealPath();        //获取文件真实路径
                $filename = date('YmdHis') . uniqid() . '.' . $fileExt;        //按照一定格式取名
                $filepath = $storageConfig->ftpFile;        //个人要求的路径
                $bool = FacadesStorage::disk('ftp')->put($filepath.$filename, file_get_contents($realPath));
                $data = $storageConfig->ftpDomain.$filepath.$filename;        //文件的url地址
                break;
            default;
                $path = Storage::channelUploadImage($file, $pathName, $module, $uniacid);
                $domain = $request->server('SERVER_PORT') == 443 ? 'https://' . $request->server('HTTP_HOST') : "http://" . $request->server('HTTP_HOST');
                $data = $domain . $path;
                break;
        }

        File::create([
            'shopId' => 0,
            "categoryId" => $request->categoryId ?? 0,
            "url" => $data,
            "uniacid" => $uniacid ?? 0,
            "name" => $fileName,
            "channel" => $storageConfig->type,
            "fileType" => $entension,
            'domain' => $domain,
            "width" => intval($width),
            "height" => intval($height),
            "fileSize" => ceil($filesize / 1024),
            "path" => $path ?? ''
        ]);
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
        $uniacid = 0;
        $domain = $request->server('SERVER_PORT') == 443 ? 'https://' . $request->server('HTTP_HOST') : "http://" . $request->server('HTTP_HOST');
        $module = 'uploads';
        $data = Storage::channelUploadBase64($file,  $module, $uniacid, $ext, $domain);
        return $this->success($data['url'], __('success'));
    }

    //获取拖拽图片分类
    public function getCategory(Request $request)
    {
        $uniacid = $this->uniacid ?: 0;
        $storeId = $request->storeId ?: 0;
        $categoryModel = new Category();
        $res = $categoryModel->where('uniacid', $uniacid)
            ->where('storeId', $storeId)->where('item', 1)->whereNull('deleteAt')
            ->get();
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
        $uniacid = $this->uniacid ?: 0;
        $typeId = $request->category;
        $keyWords = $request->keywords;
        $storeId = $request->storeId ?: 0;
        $fileModel = new File();
        $fileModel->where('uniacid', $uniacid)->where('deleteAt', 0);
        if ($storeId) {
            $fileModel->where('storeId', $storeId);
        }
        if ($typeId) {
            $fileModel->where('categoryId', $typeId);
        }
        if ($keyWords) {
            $fileModel->where('name', 'like', '%' . $keyWords . '%');
        }
        $res = $fileModel->orderByDesc('id')->paginate($req->pageSize ?? 30, '*', 'pageNo');
        return $this->success($res, __('success'));
    }

    //删除图片分类
    public function delPictureCategory(Request $request)
    {
        $id = $request->id;
        $categoryModel = new Category();
        $categoryModel = Category::find($id);
        $categoryModel->deleteAt = date('Y-m-d H:i:s', time());
        $res = $categoryModel->save();
        if ($res) {
            return $this->success($res, __('success'));
        } else {
            return $this->failed($res, __('图片分类删除失败'));
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
        $fileModel = new File();
        $res = $fileModel->whereIn('id', $idArr)->update(['deleteAt' => date('Y-m-d H:i:s', time())]);
        if ($res) {
            return $this->success($res, __('success'));
        } else {
            return $this->failed($res, __('图片删除失败'));
        }
    }
    //添加分组
    public function saveCategory(Request $request)
    {
        $storeId = $request->storeId ?: 0;
        $name = $request->name ?: 0;
        $user = auth('admin')->user();
        $uniacid = $user->uniacid;
        if (!$request->name) {
            echo json_encode(['code' => 405, 'msg' => '分类名称不能为空!']);
            die;
        }
        $categoryModel = new Category();
        $info = $categoryModel->where('uniacid', $uniacid)
            ->where('storeId', $storeId)->where('name', $name)
            ->where('item', 1)->where('deleteAt', 0)
            ->first();
        if ($info) {
            echo json_encode(['code' => 405, 'msg' => '该名称已存在!']);
            die;
        }
        $categoryModel->name = $name;
        $categoryModel->storeId = $storeId;
        $categoryModel->item = 1;
        $categoryModel->uniacid = $uniacid;
        if ($request->id) {
            $categoryModel->updated_at = date('Y-m-d H:i:s', time());
            $categoryModel->save();
        } else {
            $categoryModel->save();
        }
        echo json_encode(['code' => 200, 'msg' => 'success']);
        die;
    }

    //获取图片回收站列表
    public function recyclePicture(Request $request)
    {
        $typeId = $request->category;
        $uniacid = $this->uniacid ?: 0;
        $keyWords = $request->keywords;
        $storeId = $request->storeId ?: 0;
        $fileModel = new File();
        $fileModel->where('uniacid', $uniacid)->where('deleteAt', '>', 0);
        if ($storeId) {
            $fileModel->andwhere('storeId', $storeId);
        }
        if ($typeId) {
            $fileModel->andwhere('categoryId', $typeId);
        }
        if ($keyWords) {
            $fileModel->andwhere(['name', 'like', '%' . $keyWords . '%']);
        }
        $res = $fileModel->orderByDesc('id')->paginate($req->pageSize ?? 30, '*', 'pageNo');
        return $this->success($res, __('success'));
    }


    //删除回收站的图片
    public function delRecyclePicture(Request $request)
    {
        $fileModel = new File();
        $id = $request->id;
        $row = $fileModel->where('id', $id)->first();
        $domain = 'https://' . $_SERVER['HTTP_HOST'];
        //var_dump('.'.strstr($row->url,"/public"));die;
        if (strpos($row->url, $domain) !== false) {
            unlink(strstr($row->url, "yqjz/"));
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

    public function creatImg(Request $request){
        $uniacid = $this->uniacid;
        $config = ConfigService::getChannelConfig('baidu_ai', $uniacid);
        if(empty($config)){
            return $this->failed([], __('请先配置营销设置-ai绘图参数'));
        }
        $taskId=BaiduAi::draw($uniacid,$request->text);
        sleep(2);
        $data=BaiduAi::getImg($uniacid,$taskId);
        //$img=$data['data']['img'];
        return $this->success($data, __('success'));
    }

    //保存图片
    public function saveImg(Request $request){
        File::create([
            'shopId' => 0,
            "categoryId" => $request->categoryId ?? 0,
            "url" => $request->file,
            "uniacid" => $uniacid ?? 0,
            "name" => $request->text,
            "channel" => '',
            "fileType" => '',
            'domain' => '',
            "width" => '',
            "height" => '',
            "fileSize" => '',
            "path" => '',
        ]);
    }
}
