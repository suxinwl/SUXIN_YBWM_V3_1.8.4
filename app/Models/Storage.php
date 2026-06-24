<?php

namespace App\Models;

use App\Models\Admin\Apply;
use App\Services\ConfigService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Storage as FacadesStorage;
use Image;
use OSS\OssClient;
use OSS\Core\OssException;
use Qcloud\Cos\Api;
use Qiniu\Auth;
use Qiniu\Storage\ArgusManager;
use Qiniu\Storage\BucketManager;

use Qiniu\Storage\UploadManager;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use App\Models\File;

class Storage extends BaseModel
{
    //获取阿里云bucket
    public static function getBucket($accessKeyId, $accessKeySecret, $endpoint)
    {
        $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
        $bucketListInfo = $ossClient->listBuckets();
        $bucketList = $bucketListInfo->getBucketList();
        $bucket_arr = [];
        foreach ($bucketList as $key => $v) {
            $bucket_arr[] = array(
                'value' => $key,
                'label' => array_values(object_array($v))[1]
            );
        }
        return $bucket_arr;
    }

    public static  function aliUpload($file, $module = 'uploads', $uniacid = 0, $config)
    {
        $url = $config->aliyuncs_url;
        $extension = $file->extension();     // 例如，png
        // 阿里云主账号AccessKey
        $accessKeyId = $config->aliyuncs_accesskey ?: "";
        $accessKeySecret = $config->aliyuncs_secret ?: "";
        // Endpoint以杭州为例，其它Region请按实际情况填写。
        $endpoint = $config->aliyuncs_endpoint ?: "";
        // 设置存储空间名称。
        $bucket = $config->aliyuncs_bucket ?: "";
        // 设置文件名称。
        //获取上传图片的临时地址
        $tmppath = $file->getRealPath();
        //生成文件名
        $fileName = $uniacid . "/" . $module  . "/" . date('Y') . "/" . date('m') . "/" . date('d') . "/" . date("YmdHis") . rand(1111, 9999) . '.' . $file->getClientOriginalExtension();

        // <yourLocalFile>由本地文件路径加文件名包括后缀组成，例如/users/local/myfile.txt。
        $filePath = $file->getRealPath();
        try {
            $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
            $bool = $ossClient->uploadFile($bucket, $fileName, $filePath);
        } catch (OssException $e) {
            throw new BadRequestException($e->getMessage());
        }
        return ['path' => $fileName, 'url' => $config->aliyuncs_url ? $config->aliyuncs_url . '/' . $fileName  : $bool['info']['url']];
    }

    public static function txyUpload($file, $module = 'uploads', $uniacid = 0, $config)
    {
        $extension = $file->extension();     // 例如，png
        $realPath = $file->getRealPath(); //临时文件的绝对路径
        $cosClient = new \Qcloud\Cos\Client([
            'region' => $config->xplqcloud_endpoint ?: '', // 华北-tj | 华南->gz | 华中->sh
            'credentials' => [
                'appId' => $config->xplqcloud_appid ?: '',
                'secretId'    => $config->xplqcloud_secretid ?: '',
                'secretKey' => $config->xplqcloud_secretkey ?: ''
            ]
        ]);
        //拼接保存的图片名
        $fileName =  $uniacid . "/" . $module . "/" . date('Y') . "/" . date('m') . "/" . date('d') . "/" . date("YmdHis") . rand(1111, 9999) . '.' . $extension;

        try {
            $result = $cosClient->putObject(array(
                'Bucket' => $config->xplqcloud_bucket ?: '',
                'Key' =>  $fileName,
                'Body' => fopen($realPath, 'rb'),
                'ServerSideEncryption' => 'AES256'
            ));

            return '/' . $fileName;
        } catch (\Exception $e) {
            throw new BadRequestException($e->getMessage());
        }
    }

    public static function qiniuUpload($file, $module = 'uploads', $uniacid, $config)
    {
        $extension = $file->extension();     // 例如，png
        // 需要填写你的 Access Key 和 Secret Key
        $accessKey = $config->qn_accesskey ?: '';
        $secretKey = $config->qn_secret ?: '';
        $qn_url = $config->qn_url ?: '';
        // 构建鉴权对象
        $auth = new Auth($accessKey, $secretKey);
        // 要上传的空间
        $bucket = $config->qn_bucket ?: '';
        // 生成上传 Token
        $token = $auth->uploadToken($bucket);
        // 要上传文件的本地路径
        $filePath = $file->getRealPath();
        // 上传到七牛后保存的文件名
        $key = $uniacid . "/" . $module . "/" . date('Y') . "/" . date('m') . "/" . date('d') . "/" . date("YmdHis") . rand(1111, 9999);
        // 初始化 UploadManager 对象并进行文件的上传。
        $uploadMgr = new UploadManager();
        // 调用 UploadManager 的 putFile 方法进行文件的上传。
        list($ret, $err) = $uploadMgr->putFile($token, $key . '.' . $extension, $filePath);
        //var_dump($token);var_dump($key.'.'.$extension);
        if ($err !== null) {
            throw new BadRequestException('七牛云配置信息有误');
        } else {
            return '/' . $ret['key'];
        }
    }

    public static function channelUploadImage($file, $pathName = '', $module = 'upload', $uniacid = 0)
    {
        try {
            $saveDir =  'storage/' . $uniacid . "/" . $module . "/" . date('Y') . "/" . date('m') . "/" . date('d');
            if (!file_exists($saveDir)) {
                mkdir($saveDir, 0755, true);
            }
            if ($file->isValid()) {
                $clientName = $file->getClientOriginalName();
                $tmpName = $file->getFileName();
                $realPath = $file->getRealPath();
                $entension = $file->getClientOriginalExtension();
                switch ($entension) {
                    case 'png':
                        $im = imagecreatefrompng($realPath);
                        break;
                    case 'jpeg':
                        $im = imagecreatefromjpeg($realPath);
                        break;
                    case 'jpg':
                        $im = imagecreatefromjpeg($realPath);
                        break;
                    default:
                        break;
                }
                if ($im) {
                    $newName = md5(date("Y-m-d H:i:s") . $clientName) . ".webp";
                    imagewebp($im, public_path($saveDir . '/' . $newName));
                } else {
                    $newName = md5(date("Y-m-d H:i:s") . $clientName) . "." . $entension;
                    $path = $file->move(public_path($saveDir), $newName);
                }
                $namePath = $saveDir . '/' . $newName;
                $namePath = '/' . $namePath;
                return $namePath;
            }
            throw new BadRequestException('图片验证失败');
        } catch (\Exception $e) {
            throw new BadRequestException($e->getMessage());
        }
    }

    public static function channelUploadBase64($file, $module = 'upload', $uniacid = 0, $ext = 'jpeg', $domain = '', $categoryId = 0, $shopId = 0)
    {
        if (empty($file) || empty($ext)) {
            throw new BadRequestException("缺少参数");
        }
        if (strpos($file, 'base64,')) {
            $file = explode(',', $file);
            $file = $file[1];
        }
        $img_len = strlen($file);
        $filesize = intval($img_len - ($img_len / 8) * 2);
        $filesize = number_format(($filesize / 1024), 2);
        $attachmentSettings = ConfigService::getSystemSet('attachmentSettings');
        if (!in_array($ext, $attachmentSettings->picType ?: [])) {
            throw new BadRequestException("允许图片上传的格式为:" . implode(',', $attachmentSettings->picType));
        }
        if ($filesize > $attachmentSettings->picSize) {
            throw new BadRequestException("上传图片大小超出限制:" . $attachmentSettings->picSize . "Kb");
        }
        if ($uniacid == 0) {
            $config = ConfigService::getSystemSet('storage');
        } else {
            $apply = Apply::find($uniacid);
            if (empty($apply)) {
                throw new BadRequestException('参数错误');
            }
            if (!empty($apply->attachmentData)) {
                if ($apply->attachmentData['attachmentType'] == 0) {
                    $config = ConfigService::getSystemSet('storage');
                } else {
                    $config = (object)$apply->attachmentData;
                }
            } else {
                $config = ConfigService::getSystemSet('storage');
            }
        }
        $newName = md5(date("Y-m-d H:i:s") . rand(0, 999999)) . "." . $ext;
        $tmpfname = tempnam("/tmp/", "FOO");
        $handle = fopen($tmpfname, "w");
        if (!fwrite($handle, base64_decode($file))) {
            throw new BadRequestException('保存失败');
        }
        $saveDir =  'storage/' . $uniacid . "/" . $module . "/" . date('Y') . "/" . date('m') . "/" . date('d');
        if (!file_exists($saveDir)) {
            mkdir($saveDir, 0755, true);
        }
        $image = getimagesize($tmpfname);
        $width = $image[0];
        $height = $image[1];
        switch ($config->type) {
            case 0;
                file_put_contents(public_path($saveDir . '/' . $newName), base64_decode($file));
                $path =   $saveDir . '/' . $newName;
                $data = $domain . '/' . $path;
                break;
            case 1;
                $accessKey = $config->qn_accesskey ?: '';
                $secretKey = $config->qn_secret ?: '';
                $qn_url = $config->qn_url ?: '';
                // 构建鉴权对象
                $auth = new Auth($accessKey, $secretKey);
                // 要上传的空间
                $bucket = $config->qn_bucket ?: '';
                // 生成上传 Token
                $token = $auth->uploadToken($bucket);
                // 要上传文件的本地路径
                // 上传到七牛后保存的文件名
                $key = $uniacid . "/" . $module . "/" . date('Y') . "/" . date('m') . "/" . date('d') . "/" . date("YmdHis") . rand(1111, 9999);
                // 初始化 UploadManager 对象并进行文件的上传。
                $uploadMgr = new UploadManager();
                // 调用 UploadManager 的 putFile 方法进行文件的上传。
                try {
                    list($ret, $err) = $uploadMgr->putFile($token, $key . '.' . $ext, $tmpfname);
                } catch (\Exception $e) {
                    throw new BadRequestException('七牛云上传失败');
                }
                if ($err !== null) {
                    throw new BadRequestException('七牛云配置信息有误');
                } else {
                    $data = $config->qn_url . '/' . $ret['key'];
                }
                break;
            case 2;
                $accessKeyId = $config->aliyuncs_accesskey ?: "";
                $accessKeySecret = $config->aliyuncs_secret ?: "";
                // Endpoint以杭州为例，其它Region请按实际情况填写。
                $endpoint = $config->aliyuncs_endpoint ?: "";
                // 设置存储空间名称。
                $bucket = $config->aliyuncs_bucket ?: "";
                // 设置文件名称。
                //获取上传图片的临时地址
                //$tmppath = $file->getRealPath();
                //生成文件名
                $fileName = $uniacid . "/" . $module  . "/" . date('Y') . "/" . date('m') . "/" . date('d') . "/" . date("YmdHis") . rand(1111, 9999) . '.' . $ext;

                // <yourLocalFile>由本地文件路径加文件名包括后缀组成，例如/users/local/myfile.txt。
                // $filePath = $file->getRealPath();
                try {
                    $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
                    $bool = $ossClient->uploadFile($bucket, $fileName, $tmpfname);
                } catch (OssException $e) {
                    throw new BadRequestException($e->getMessage());
                }
                $path = $fileName;
                $data = $bool['info']['url'];
                break;
            case 3;
                $cosClient = new \Qcloud\Cos\Client([
                    'region' => $config->xplqcloud_endpoint ?: '', // 华北-tj | 华南->gz | 华中->sh
                    'credentials' => [
                        'appId' => $config->xplqcloud_appid ?: '',
                        'secretId'    => $config->xplqcloud_secretid ?: '',
                        'secretKey' => $config->xplqcloud_secretkey ?: ''
                    ]
                ]);
                //拼接保存的图片名
                $fileName =  $uniacid . "/" . $module . "/" . date('Y') . "/" . date('m') . "/" . date('d') . "/" . date("YmdHis") . rand(1111, 9999) . '.' . $ext;

                try {
                    $result = $cosClient->putObject(array(
                        'Bucket' => $config->xplqcloud_bucket ?: 'qbwm',
                        'Key' =>  $fileName,
                        'Body' => fopen($tmpfname, 'rb'),
                        'ServerSideEncryption' => 'AES256'
                    ));

                    $data = $config->xplqcloud_url . '/' . $fileName;
                } catch (\Exception $e) {
                    throw new BadRequestException($e->getMessage());
                }
                break;
            case 4;
                $base64 = preg_replace("/\s/", '+', $file);
                $img = base64_decode($base64);

                //$fileExt = $file->getClientOriginalExtension();        //获取文件后缀名
                //$realPath = $file->getRealPath();        //获取文件真实路径
                $filename = date('YmdHis') . uniqid() . '.' . $ext;        //按照一定格式取名
                $filepath = $config->ftpFile;        //个人要求的路径
                $bool = FacadesStorage::disk('ftp')->put($filepath . $filename, $img);
                $data = $config->ftpDomain . $filepath . $filename;        //文件的url地址
                break;
            default;
                file_put_contents(public_path($saveDir . '/' . $newName), base64_decode($file));
                $path =   $saveDir . '/' . $newName;
                $data = $domain . '/' . $path;
        }
        fclose($handle);
        unlink($tmpfname);
        return File::create([
            'shopId' => $shopId,
            "categoryId" => $categoryId ?? 0,
            "url" => $data,
            "uniacid" => $uniacid,
            "width" => $width,
            "height" => $height,
            "name" => $newName,
            "channel" => intval($config->type),
            "fileType" => $ext,
            "fileSize" => ceil($filesize / 1024),
            "path" => $path ?? ''
        ]);
    }

    public static function ftpUpload($fileName, $module = "uploads", $uniacid = 0, $config, $type = 1)
    {
        $ftpService = $config->ftpid;
        $ftpUserName = $config->ftpUser;
        $ftpPwd = $config->ftpPass;
        if ($config->ftpSsl == 1) {
            $conn = ftp_ssl_connect($ftpService) or die("Could not connect");
        } else {
            $conn = ftp_connect($ftpService) or die("Could not connect");
        }
        $path = $config->ftpFile . "/" . $uniacid . "/" . $module . "/" . date('Y') . "/" . date('m') . "/" . date('d');
        ftp_login($conn, $ftpUserName, $ftpPwd);
        ftp_pasv($conn, true);
        //利用ftp创建目录
        File::makeDirectory($path, 0775, true);
        //make_directory($conn, $path);
        //利用ftp选择进入目录
        //ftp_chdir($conn,$path);
        if (count($_FILES) > 1) {
            $url = [];
            foreach ($_FILES as $v) {
                $tmpname = $v['tmp_name'];
                $filename = date("YmdHis") . rand(1111, 9999) . '.' . substr(strrchr($v['name'], '.'), 1);
                $remote = $path . '/' . $filename;
                $local = $tmpname;
                if (ftp_put($conn, $remote,  $local, FTP_BINARY)) {
                    $url[] = $config->ftpDomain . '/' . $remote;
                } else {
                    ftp_close($conn);
                    throw new BadRequestException('上传失败请重试');
                }
            }
            ftp_close($conn);
            return  $url;
        } else {
            $tmpname = $_FILES[$fileName]['tmp_name'];
            $filename = date("YmdHis") . rand(1111, 9999) . '.' . substr(strrchr($_FILES[$fileName]['name'], '.'), 1);
            $remote = $path . '/' . $filename;
            $local = $tmpname;
            if (ftp_put($conn, $remote,  $local, FTP_BINARY)) {
                ftp_close($conn);
                return '/' . $remote;
            } else {
                ftp_close($conn);
                throw new BadRequestException('上传失败请重试');
            }
        }
    }
    //验证远程附件参数
    public static function checkAttachmentSet($type, $data)
    {
        $data = json_decode($data, true);
        if ($type == 1) {
            $ak = $data['qn_accesskey'];
            $sk = $data['qn_secretkey'];
            $domain = $data['qn_url'];
            $bucket = $data['qn_bucket'];
            $zone = $data['qn_endpoint'];
            try {
                $qiniu = new Qiniu($ak, $sk, $domain, $bucket, $zone);
                $url = "https://wm.y-qb.cn/storage/53/uploads/2022/09/23/b6a5f561ae22ce5cdb5fa457bf4ef967.jpg";
                $qiniu->uploadByUrl($url);
            } catch (\Exception $e) {
                return false;
            }
        }
        if ($type == 2) {
            $accessKeyId = $data['aliyuncs_accesskey'];
            $aliyuncs_secret = $data['aliyuncs_secret'];
            $aliyuncs_bucket = $data['aliyuncs_bucket'];
            $aliyuncs_url = $data['aliyuncs_url'];
            $aliyuncs_endpoint = $data['aliyuncs_endpoint'];
            try {
                $ossClient = new OssClient($accessKeyId, $aliyuncs_secret, $aliyuncs_endpoint);
                $ossClient->listBuckets();
            } catch (\Exception $e) {
                return false;
            }
        }
        if ($type == 3) {
            $config = [
                'app_id' => $data['xplqcloud_appid'],
                'secret_id' => $data['xplqcloud_secretid'],
                'secret_key' => $data['xplqcloud_secretkey'],
                'region' => $data['xplqcloud_endpoint'],
                'bucket' => $data['xplqcloud_bucket'],
                'timeout' => 60
            ];

            try {
                $cos = new Cos($config);
                //创建文件夹
                $path = 'test';
                $result = $cos->createFolder($config['bucket'], $path);
                // var_dump($result);die;
                if ($result['code'] != 0) {
                    //var_dump($result['message']);die;
                    if ($result['message'] == 'ERROR_CMD_BUCKET_NOTEXIST') {
                        throw new BadRequestException('BUCKET参数有误');
                    } else {
                        return false;
                    }
                }
            } catch (\Exception $e) {
                return false;
            }
        }
        return true;
    }


    public static function imageCensor($file, $uniacid = 0)
    {
        try {
            $config = ConfigService::getChannelConfig('cricle_set', $uniacid);
            $accessKey =  $config['qn_accesskey'] ?: '';
            $secretKey = $config['qn_secret'] ?:  '';
            if (empty($config['qn_state']) || empty($accessKey) || empty($secretKey)) {
                return false;
            }
            $auth = new Auth($accessKey, $secretKey);
            $body = [
                'data' => ['uri' => 'data:application/octet-stream;base64,' . $file],
                'params' => [
                    'scenes' => ['pulp', 'terror', 'politician']
                ]
            ];
            $url  = "http://ai.qiniuapi.com/v3/image/censor";
            $res = httpRequest($url, $body, $auth->authorizationV2($url, 'POST', json_encode($body), 'application/json'));
            if ($res['code'] != 200) {
                throw new BadRequestException($res['message']);
            }
            if ($res['result']['suggestion'] != 'pass') {
                throw new BadRequestException('内容违规，请删除');
            }
            return $res;
        } catch (\Exception $e) {
            throw new BadRequestException($e->getMessage());
            return true;
        }
    }

    public static function textCensor($text = '', $uniacid = 0)
    {
        try {
            // $config  = '';
            // $accessKey = $config->qn_accesskey ?: 'jYH3n1LzO4gbqCksa8FXlh5gihAf29dq3bDqhHE7';
            // $secretKey = $config->qn_secret ?: 'pvLS_MCkXH83KNF1bwksCYTikAvBkJH6FU5bHP1Q';
            $config = ConfigService::getChannelConfig('cricle_set', $uniacid);
            $accessKey =  $config['qn_accesskey'] ?: '';
            $secretKey = $config['qn_secret'] ?:  '';
            if (empty($config['qn_state']) || empty($accessKey) || empty($secretKey)) {
                return false;
            }
            $auth = new Auth($accessKey, $secretKey);
            $body = [
                'data' => ['text' => $text],
                'params' => [
                    'scenes' => ['antispam']
                ]
            ];
            $url  = "http://ai.qiniuapi.com/v3/text/censor";
            $res = httpRequest($url, $body, $auth->authorizationV2($url, 'POST', json_encode($body), 'application/json'));
            if ($res['code'] != 200) {
                throw new BadRequestException($res['message']);
            }
            if ($res['result']['suggestion'] != 'pass') {
                throw new BadRequestException('内容违规，请删除');
            }
            return $res;
        } catch (\Exception $e) {
            throw new BadRequestException($e->getMessage());
            return true;
        }
    }
}
