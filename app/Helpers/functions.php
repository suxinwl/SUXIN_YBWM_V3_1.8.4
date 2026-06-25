<?php

use App\Models\Install;
use Illuminate\Support\Facades\Crypt;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
//腾讯转百度坐标转换  $a = Latitude , $b = Longitude
function coordinateSwitchf($a, $b)
{
    $x = (double)$b ;
    $y = (double)$a;
    $x_pi = 3.14159265358979324*3000/180;
    $z = sqrt($x * $x+$y * $y) + 0.00002 * sin($y * $x_pi);
    $theta = atan2($y,$x) + 0.000003 * cos($x*$x_pi);
    $gb = number_format($z * cos($theta) + 0.0065,6);
    $ga = number_format($z * sin($theta) + 0.006,6);
    return ['lat' => $ga, 'lng' => $gb];
}
function dateStartEnd($date)
{
    $time = strtotime($date);
    if (!$time) {
        return false;
    }

    $date = date('Y-m-d', strtotime($date));
    $time = strtotime($date);

    $start_date_time = date('Y-m-d H:i:s', $time);

    $end_time = $time + (24 * 60 * 60 - 1);
    $end_date_time = date('Y-m-d H:i:s', $end_time);

    return [
        'start_date_time' => $start_date_time,
        'end_date_time' => $end_date_time,
    ];
}
/**
 * 获取最近七天日期
 */
function get_week($time = '', $format = 'm-d')
{
    $time = $time != '' ? $time : time();
    //组合数据
    $date = [];
    for ($i = 1; $i <= 7; $i++) {
        $date[$i] = date($format, strtotime('+' . $i - 7 . ' days', $time));
    }
    return $date;
}
function modifyEnv(array $data)
{
    if (!count($data)) {
        return;
    }
    if (array_keys($data) === range(0, count($data) - 1)) {
        return;
    }
    $pattern = '/([^\=]*)\=[^\n]*/';
    $envFile = base_path() . '/.env';
    if (!is_writable($envFile)) {
        chmod($envFile, 0777);
    }
    $lines = file($envFile);
    $newLines = [];
    foreach ($lines as $line) {
        preg_match($pattern, $line, $matches);
        if (!count($matches)) {
            $newLines[] = $line;
            continue;
        }
        if (!key_exists(trim($matches[1]), $data)) {
            $newLines[] = $line;
            continue;
        }
        $line = trim($matches[1]) . "={$data[trim($matches[1])]}\n";
        $newLines[] = $line;
    }
    $newContent = implode('', $newLines);
    file_put_contents($envFile, $newContent);
    return true;
}
function randomAESKey($length = 6, $chars = '0123456789')
{
    $hash = '';
    $max = strlen($chars) - 1;
    for ($i = 0; $i < $length; $i++) {
        $hash .= $chars[mt_rand(0, $max)];
    }
    return $hash;
}
//查询一月到12月每月的数据
function recordSort()
{
    //每月的销量
    $startTime = date('Y') . '-01-01';
    $endTime = date('Y') . '-12-31';
    #统计一年内注册用户数量按月份进行分组
    $statistics = [];
    //    $statistics= $apply_model->whereBetween('created_at',[$startTime,$endTime])
    //        ->selectRaw('DATE_FORMAT(created_at,"%Y-%m") as date,COUNT(*) as value')
    //        ->groupBy('date')
    //        ->get();

    #在进行图表统计的时候直接从数据库取得的数据有的月份可能是没有的,不过月份比较少可直接写死,同样也需要补全
    $year = date('Y', time());
    #一年的月份
    $months = [
        0 => $year . '-01',
        1 => $year . '-02',
        2 => $year . '-03',
        3 => $year . '-04',
        4 => $year . '-05',
        5 => $year . '-06',
        6 => $year . '-07',
        7 => $year . '-08',
        8 => $year . '-09',
        9 => $year . '-10',
        10 => $year . '-11',
        11 => $year . '-12'

    ];
    #循环补全月份
    foreach ($months as $key => $month) {
        $data[$key] = [
            'date' => $month,
            'value' => 0
        ];
        foreach ($statistics as $k => $v) {
            if ($month == $v['date']) {
                $data[$k] = $v;
            }
        }
    }
}
//对象转数组
function object_array($array)
{
    if (is_object($array)) {
        $array = (array)$array;
    }
    if (is_array($array)) {
        foreach ($array as $key => $value) {
            $array[$key] = object_array($value);
        }
    }
    return $array;
}
function getSysInfo()
{
    $json = Install::where('type', 'secret')->first();
    if ($json) {
        $json = $json->data;
    } else {
        $json =  file_get_contents(public_path() . '/secret.json');
    }
    if (empty($json)) {
        throw new BadRequestHttpException('站点授权文件丢失，系统无法正常运行;请联系官方客服：18038018206（微信同号）');
    }
    $json = Crypt::decryptString($json);
    $data = json_decode($json, true);
    return $data;
}

function getVersionInfo()
{
    $json = file_get_contents(public_path() . '/version.json');
    $data = json_decode($json, true);
    return $data;
}
//删除文件夹
function removeDir($dirName)
{
    if (!is_dir($dirName)) {
        return false;
    }
    $handle = @opendir($dirName);
    while (($file = @readdir($handle)) !== false) {
        if ($file != '.' && $file != '..') {
            $dir = $dirName . '/' . $file;
            is_dir($dir) ? removeDir($dir) : @unlink($dir);
        }
    }
    closedir($handle);

    return rmdir($dirName);
}
//curl远程下载压缩包
function getFile($url, $save_dir = '', $filename = '', $type = 0)
{
    if (trim($url) == '') {
        return false;
    }
    if (trim($save_dir) == '') {
        $save_dir = './';
    }
    if (0 !== strrpos($save_dir, '/')) {
        $save_dir .= '/';
    }
    //创建保存目录
    if (!file_exists($save_dir) && !mkdir($save_dir, 0777, true)) {
        return false;
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $content = curl_exec($ch);
    if (curl_error($ch)) {
        echo json_encode(['code' => 2, 'msg' => curl_error($ch)]);
        die;
    }

    //文件大小
    $fp2 = @fopen($save_dir . $filename, 'a');
    fwrite($fp2, $content);
    fclose($fp2);
    unset($content, $url);
    return array(
        'file_name' => $filename,
        'save_path' => $save_dir . $filename
    );
}
//上传图片
if (!function_exists("upload_img")) {
    function upload_img($saveDir, $file)
    {
        $rule = ['jpg', 'png', 'gif', 'jpeg', 'jfif'];
        if ($file->isValid()) {
            $clientName = $file->getClientOriginalName();
            $tmpName = $file->getFileName();
            $realPath = $file->getRealPath();
            $newName = md5(date("Y-m-d H:i:s") . $clientName) . "." . $entension;
            $path = $file->move(public_path($saveDir), $newName);
            $namePath = $saveDir . '/' . $newName;
            return $namePath;
        }
    }
}

if (!function_exists('pd')) {
    // 传递数据以易于阅读的样式格式化后输出
    function pd($data)
    {
        $array = [];
        // 定义样式
        echo '<pre style="display: block;padding: 9.5px;margin: 44px 0 0 0;font-size: 13px;line-height: 1.42857;color: #333;word-break: break-all;word-wrap: break-word;background-color: #F5F5F5;border: 1px solid #CCC;border-radius: 4px;">';
        foreach ($data as $key => $value) {
            $array[$key] = json_decode(json_encode($value), true);
        }
        print_r($array);
        echo '</pre>';
    }
}

if (!function_exists('modifyEnv')) {
    function modifyEnv(array $data)
    {
        $envPath = base_path() . DIRECTORY_SEPARATOR . '.env';

        $contentArray = collect(file($envPath, FILE_IGNORE_NEW_LINES));

        $contentArray->transform(function ($item) use ($data) {
            foreach ($data as $key => $value) {
                if (str_contains($item, $key)) {
                    return $key . '=' . $value;
                }
            }

            return $item;
        });

        $content = implode($contentArray->toArray(), "\n");

        \File::put($envPath, $content);
    }
}
if (!function_exists('httpRequest')) {
    function httpRequest($url, $data = [], $header = [], $type = 'post', $bool = true)
    {
        $authorizeHost = parse_url(config('app.authorizeDomain'), PHP_URL_HOST);
        $requestHost = parse_url($url, PHP_URL_HOST);
        if ((config('app.env') === 'local' || env('DISABLE_REMOTE_AUTHORIZE_HTTP', false)) && $authorizeHost && $requestHost === $authorizeHost) {
            return localAuthorizeDomainResponse($url, $data, $bool);
        }

        $http = new Client([
            'headers'   => [
                'Content-Type'  => 'application/json'
            ],
            'connect_timeout' => 3,
            'timeout' => 5,
        ]);
        if (empty($data)) {
            $type = 'get';
        }
        try {
            if ($type == 'get') {
                $data = $http->get($url, ['headers' => $header, 'query' => $data])->getBody()->getContents();
            } else {
                $data = $http->post($url, ['headers' => $header, 'json' => $data])->getBody()->getContents();
            }
        } catch (\Throwable $e) {
            if ($authorizeHost && $requestHost === $authorizeHost && localAuthorizeDomainFallbackAvailable($url)) {
                logger()->warning('Remote authorize request failed, using local fallback', [
                    'url' => $url,
                    'message' => $e->getMessage(),
                ]);
                return localAuthorizeDomainResponse($url, $data, $bool);
            }

            throw $e;
        }
        if ($bool) {
            return json_decode($data, true);
        }
        return $data;
    }
}

if (!function_exists('localAuthorizeDomainFallbackAvailable')) {
    function localAuthorizeDomainFallbackAvailable($url)
    {
        $path = parse_url($url, PHP_URL_PATH) ?: '';
        $fallbackPaths = [
            '/cloud/upgraded/getUpgradeInfo',
            '/cloud/artice/getarticelist',
            '/cloud/notice/getnoticelist',
            '/cloud/code',
            '/api/order/get-order-list',
        ];

        foreach ($fallbackPaths as $fallbackPath) {
            if (strpos($path, $fallbackPath) !== false) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('localAuthorizeDomainResponse')) {
    function localAuthorizeDomainResponse($url, $data = [], $bool = true)
    {
        $path = parse_url($url, PHP_URL_PATH) ?: '';
        $currentVersion = getVersionInfo();
        $remoteSkipped = config('app.env') === 'local' || env('DISABLE_REMOTE_AUTHORIZE_HTTP', false);
        $response = [
            'status' => 'success',
            'msg' => $remoteSkipped ? 'Remote authorize request skipped' : 'Remote authorize request unavailable',
            'code' => 200,
            'data' => [],
        ];

        if (strpos($path, '/cloud/upgraded/getUpgradeInfo') !== false) {
            $versionData = is_array($data) ? array_merge(is_array($currentVersion) ? $currentVersion : [], $data) : $currentVersion;
            $response['data'] = [
                'version' => $versionData['version'] ?? '1.0.0',
                'version_release' => $versionData['version_release'] ?? '20220611',
                'diskName' => $versionData['diskName'] ?? ($currentVersion['diskName'] ?? 'online'),
                'remote_available' => false,
            ];
        } elseif (strpos($path, '/cloud/artice/getarticelist') !== false || strpos($path, '/cloud/notice/getnoticelist') !== false || strpos($path, '/api/order/get-order-list') !== false) {
            $response['data'] = [
                'data' => [],
                'list' => [],
                'total' => 0,
                'current_page' => (int)($data['pageNo'] ?? 1),
                'per_page' => (int)($data['pageSize'] ?? 20),
            ];
        } elseif (strpos($path, '/cloud/code') !== false) {
            $appUrl = ($data['domain'] ?? env('APP_URL')) ?: Request()->getSchemeAndHttpHost();
            $host = parse_url($appUrl, PHP_URL_HOST) ?: trim(str_replace(['https://', 'http://'], '', $appUrl), '/');
            $seed = implode('|', ['suxin-auth-code', $host, config('app.key')]);
            $response['data'] = [
                'code' => strtoupper(substr(hash('sha256', $seed), 0, 12)),
            ];
        }

        return $bool ? $response : json_encode($response, JSON_UNESCAPED_UNICODE);
    }
}

if (!function_exists('safeGetUpgradeInfo')) {
    function safeGetUpgradeInfo(array $versionData = [])
    {
        $currentVersion = getVersionInfo();
        $versionData = array_merge(is_array($currentVersion) ? $currentVersion : [], $versionData);
        $isLocal = config('app.env') === 'local' || env('DISABLE_REMOTE_UPGRADE_CHECK', false);
        $fallback = [
            'code' => 200,
            'msg' => $isLocal ? 'Local environment: remote upgrade check skipped' : 'Remote upgrade check unavailable',
            'data' => [
                'version' => $versionData['version'] ?? '1.0.0',
                'version_release' => $versionData['version_release'] ?? '20220611',
                'diskName' => $versionData['diskName'] ?? ($currentVersion['diskName'] ?? 'online'),
                'remote_available' => false,
            ],
        ];

        if ($isLocal) {
            return $fallback;
        }

        try {
            $url = config('app.authorizeDomain') . '/cloud/upgraded/getUpgradeInfo';
            $data = httpRequest($url, $versionData);
            return is_array($data) ? array_replace_recursive($fallback, $data) : $fallback;
        } catch (\Throwable $e) {
            logger()->warning('Remote upgrade check failed', [
                'url' => config('app.authorizeDomain') . '/cloud/upgraded/getUpgradeInfo',
                'message' => $e->getMessage(),
            ]);
            return $fallback;
        }
    }
}





if (!function_exists('array_get')) {
    function array_get($array, $key, $default = null)
    {
        if (is_null($key)) {
            return $array;
        }
        if (isset($array[$key])) {
            return $array[$key];
        }
        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return value($default);
            }
            $array = $array[$segment];
        }
        return $array;
    }
}
if (!function_exists('FromXml')) {
    /**
     * 将xml转为array
     * @param string $xml
     * @throws WxPayException
     */
    function FromXml($xml)
    {
        //    if(!$xml){
        //        throw new Exception("xml数据异常！");
        //    }
        //将XML转为array
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $arr = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $arr;
    }
}
function unzip($src_file, $dest_dir = false, $create_zip_name_dir = true, $overwrite = true)
{
    if ($zip = zip_open($src_file)) {
        if ($zip) {
            $splitter = ($create_zip_name_dir === true) ? "." : "/";
            if ($dest_dir === false) {
                $dest_dir = substr($src_file, 0, strrpos($src_file, $splitter)) . "/";
            }
            //var_dump($src_file);die;
            // 如果不存在 创建目标解压目录
            create_dirs($dest_dir);
            // 对每个文件进行解压
            while ($zip_entry = zip_read($zip)) {
                // 文件不在根目录
                $pos_last_slash = strrpos(zip_entry_name($zip_entry), "/");
                if ($pos_last_slash !== false) {
                    // 创建目录 在末尾带 /
                    create_dirs($dest_dir . '/' . substr(zip_entry_name($zip_entry), 0, $pos_last_slash + 1));
                }

                // 打开包
                if (zip_entry_open($zip, $zip_entry, "r")) {
                    // 文件名保存在磁盘上,
                    $file_name = $dest_dir . '/' . zip_entry_name($zip_entry);

                    // 检查文件是否需要重写
                    if ($overwrite === true || $overwrite === false && !is_file($file_name)) {
                        // 读取压缩文件的内容
                        $fstream = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
                        @file_put_contents($file_name, $fstream);
                        // 设置权限
                        chmod($file_name, 0777);
                        //echo "save: ".$file_name."<br />";die;
                    }
                    // 关闭入口
                    zip_entry_close($zip_entry);
                }
            }
            // 关闭压缩包
            zip_close($zip);
        }
    } else {
        return false;
    }
    return true;
}

function timeToDays($startTime = '', $endTime = '')
{
    $s = strtotime($startTime) - strtotime($endTime);
    $day = ceil($s / (3600 * 24));
    $day = $day < 0 ? $day * -1 : $day;
    if ($day == 0) {
        return [date("m-d", strtotime($startTime))];
    } else {
        for ($i = 0; $i <= $day; $i++) {
            $data[] = date("m-d", strtotime($startTime) + 3600 * 24 * $i);
        }
        sort($data);
        return $data;
    }
}
function timeToDay($startTime = '', $endTime = '')
{
    $s = strtotime($startTime) - strtotime($endTime);
    $day = ceil($s / (3600 * 24));
    $day = $day < 0 ? $day * -1 : $day;
    if ($day == 0) {
        return [date("Y-m-d", strtotime($startTime))];
    } else {
        for ($i = 0; $i <= $day; $i++) {
            $data[] = date("Y-m-d", strtotime($startTime) + 3600 * 24 * $i);
        }
        sort($data);
        return $data;
    }
}

/**
 * 创建目录
 */
function create_dirs($path)
{
    if (!is_dir($path)) {
        $directory_path = "";
        $directories = explode("/", $path);
        array_pop($directories);
        foreach ($directories as $directory) {
            $directory_path .= $directory . "/";
            if (!is_dir($directory_path)) {
                mkdir($directory_path);
                chmod($directory_path, 0777);
            }
        }
    }
}
//xml转换成数组
function xmlToArray($xml)
{

    //禁止引用外部xml实体

    libxml_disable_entity_loader(true);

    $xmlstring = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);

    $val = json_decode(json_encode($xmlstring), true);

    return $val;
}
//数组转换成xml
function arrayToXml($arr)
{
    $xml = "<root>";
    foreach ($arr as $key => $val) {
        if (is_array($val)) {
            $xml .= "<" . $key . ">" . arrayToXml($val) . "</" . $key . ">";
        } else {
            $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
        }
    }
    $xml .= "</root>";
    return $xml;
}
function asimplexml_load_string($string, $class_name = 'SimpleXMLElement', $options = 0, $ns = '', $is_prefix = false)
{
    libxml_disable_entity_loader(true);
    if (preg_match('/(\<\!DOCTYPE|\<\!ENTITY)/i', $string)) {
        return false;
    }
    $string = preg_replace('/[\\x00-\\x08\\x0b-\\x0c\\x0e-\\x1f\\x7f]/', '', $string);
    return simplexml_load_string($string, $class_name, $options, $ns, $is_prefix);
}
function aaes_decode($message, $encodingaeskey = '', $appid = '')
{
    $key = base64_decode($encodingaeskey . '=');

    $ciphertext_dec = base64_decode($message);
    $iv = substr($key, 0, 16);
    $decrypted = openssl_decrypt($ciphertext_dec, 'AES-256-CBC', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);
    $block_size = 32;

    $pad = ord(substr($decrypted, -1));
    if ($pad < 1 || $pad > 32) {
        $pad = 0;
    }
    $result = substr($decrypted, 0, (strlen($decrypted) - $pad));
    if (strlen($result) < 16) {
        return '';
    }
    $content = substr($result, 16, strlen($result));
    $len_list = unpack('N', substr($content, 0, 4));
    $contentlen = $len_list[1];
    $content = substr($content, 4, $contentlen);
    $from_appid = substr($content, $xml_len + 4);
    if (!empty($appid) && $appid != $from_appid) {
        return '';
    }

    return $content;
}
//短信验证码生成规则
function generate_code($length = 6)
{
    $min = pow(10, ($length - 1));
    $max = pow(10, $length) - 1;
    return rand($min, $max);
}

/**
 * 获取客户端
 */
function appType($appType)
{
    $typeInfo = array(
        'mini' => 1,
        'wechat' => 2,
        'ali' => 3,
        'baidu' => 4,
        'h5' => 5,
        'pc' => 6,
        'toutiao' => 7,
        'kuaishou' => 8,
        'shoudong' => 9,
        'cashier' => 10,
        'store' => 11,
        'alih5' => 12
    );
    return $typeInfo[$appType];
}

function appTypeFormat($appType)
{
    $typeInfo = array(
        0 => "系统",
        1 => "微信小程序",
        2 => "微信公众号",
        3 => "支付宝小程序",
        9 => '后台添加',
        10 => '收银台',
        11 => '商户助手',
        12 => "支付宝"
    );
    return $typeInfo[$appType];
}

function appPayTypeFormat($appType)
{
    $typeInfo = array(
        1 => "微信小程序",
        2 => "微信公众号",
        3 => "支付宝小程序",
        9 => '后台添加',
        10 => '收银台',
        11 => '商户助手',
        13 => "支付宝H5"
    );
    return $typeInfo[$appType];
}

function checkDomain()
{
    $data = getSysInfo();
    $domain = $_SERVER['HTTP_HOST'];
    if ($data) {
        if ($data['status'] == 3) {
            throw new BadRequestException('当前站点已被拉黑，系统无法正常运行;请联系官方客服：18038018206（微信同号）');
        }
        if ($data['time_type'] == 2 && $data['time_end'] <= date('Y-m-d H:i:s', time())) {
            throw new BadRequestException('您的站点服务已到期，系统升级服务将被限制;如需解除限制请在后台支付服务费');
        }
        if ($data['subDomain']) {
            $subDomain = array_column(json_decode($data['subDomain'], true), 'url');
            if (in_array($domain, $subDomain)) {
                throw new BadRequestException('请使用主域名更新系统');
            } else {
                if ($data['domain_url'] !== $domain) {
                    throw new BadRequestException('请使用正版，系统无法正常运行，请联系开发者：18038018206（微信同号）');
                }
            }
        } else {
            if ($data['domain_url'] !== $domain) {
                throw new BadRequestException('请使用正版，系统无法正常运行，请联系开发者：18038018206（微信同号）');
            }
        }
    } else {
        throw new BadRequestException('请使用正版，系统无法正常运行，请联系开发者：18038018206（微信同号）');
    }
}


function GetRandStr($length)
{
    $str = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $len = strlen($str) - 1;
    $randstr = '';
    for ($i = 0; $i < $length; $i++) {
        $num = mt_rand(0, $len);
        $randstr .= $str[$num];
    }
    return $randstr;
}

function GetRandInt($length)
{
    $str = '0123456789';
    $len = strlen($str) - 1;
    $randstr = '';
    for ($i = 0; $i < $length; $i++) {
        $num = mt_rand(0, $len);
        $randstr .= $str[$num];
    }
    return $randstr;
}

function CouponRandInt()
{
    return "8" . GetRandInt(9);
}

function getDomain()
{
    $is_cli = preg_match("/cli/i", php_sapi_name()) ? true : false;
    if (!$is_cli) {
        $domain = 'https://' . $_SERVER['HTTP_HOST'];
        return $domain;
    }
}

function getTakeOutNo($type = false)
{
    $code = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J');
    $type == false ? $code[intval(date('Y')) - 2017] : $type  . strtoupper(date('m')) . date('d') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
    return date("YmdHis", time()) . rand(111111, 999999);
}

function getVipCardNo()
{
    return date('Y') . sprintf('%02d', date('m')) . sprintf('%02d', date('d')) . substr(microtime(), 2, 6) . sprintf('%02d', rand(1000, 9999));
}


function numFormat($num)
{
    switch ($num) {
        case 0:
            return null;
            break;
        case $num > 100000000:
            return number_format($num / 100000000) . '亿+';
            break;
            // case $num > 10000000:
            //     return number_format($num / 10000000) . '千万+';
            //     break;
            // case $num > 1000000:
            //     return number_format($num / 1000000) . '百万+';
            //     break;
            // case $num > 100000:
            //     return number_format($num / 10000) . '万+';
            //     break;
        case $num > 10000:
            return number_format($num / 10000) . '万+';
            break;
        case $num > 1000:
            return number_format($num / 1000) . '千+';
            break;
        default:
            return intval($num);
            break;
    }
}


/**
 * 判断是否cli运行环境
 */
function isCli()
{
    if (substr(PHP_SAPI_NAME(), 0, 3) !== 'cli') {
        return false;
    }
    return true;
}

//获取访客ip
function getIps()
{
    if (isset($_SERVER)) {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            foreach ($arr as $ip) {
                $ip = trim($ip);

                if ($ip != 'unknown') {
                    $realip = $ip;
                    break;
                }
            }
        } else if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $realip = $_SERVER['HTTP_CLIENT_IP'];
        } else if (isset($_SERVER['REMOTE_ADDR'])) {
            $realip = $_SERVER['REMOTE_ADDR'];
        } else {
            $realip = '0.0.0.0';
        }
    } else if (getenv('HTTP_X_FORWARDED_FOR')) {
        $realip = getenv('HTTP_X_FORWARDED_FOR');
    } else if (getenv('HTTP_CLIENT_IP')) {
        $realip = getenv('HTTP_CLIENT_IP');
    } else {
        $realip = getenv('REMOTE_ADDR');
    }
    preg_match('/[\\d\\.]{7,15}/', $realip, $onlineip);
    $realip = (!empty($onlineip[0]) ? $onlineip[0] : '0.0.0.0');
    return $realip;
}


/*****************************************************
 *      生成随机字符串 - 最长为32位字符串
 *****************************************************/
function wxNonceStr($length = 16, $type = FALSE, $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789")
{
    $str = "";
    for ($i = 0; $i < $length; $i++) {
        $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
    }
    if ($type == TRUE) {
        return strtoupper(md5(time() . $str));
    } else {
        return $str;
    }
}

function MergeData($data)
{
    $arr = [];
    for ($i = 1; $i <= 24; $i++) {
        $arr[$i] = 0;
    }
    $newArr = [];
    foreach ($data as $k => $v) {
        if (substr($v['date'], 0, 1) == '0') {
            $v['date'] = substr($v['date'], 1);
        }
        $newArr[$v['date']] = $v['count'];
    }
    if ($newArr) {
        $data_arr = $newArr + $arr;
    } else {
        $data_arr = $arr;
    }
    ksort($data_arr);
    $newData = [];
    foreach ($data_arr as $key => $v) {
        $newData[] = array(
            'date' => $key,
            'count' => $v
        );
    }
    return $newData;
}

function printLR($str_left, $str_right, $length)
{
    if (empty($str_left) ||  empty($length)) return '请输入正确的参数';
    $kw = '';
    $str_left_lenght = strlen(iconv("UTF-8", "GBK//IGNORE", $str_left));
    $str_right_lenght = strlen(iconv("UTF-8", "GBK//IGNORE", $str_right));
    $k = $length - ($str_left_lenght + $str_right_lenght);
    for ($q = 0; $q < $k; $q++) {
        $kw .= ' ';
    }
    return $str_left . $kw . $str_right;
}
//机器人返回信息
function returnRobotMsg($data)
{
    if ($data) {
        $array = array(
            'status' => '200',
            'msg' => '返回成功',
            'data' => $data
        );
        $msg = json_encode($array, JSON_UNESCAPED_UNICODE);
        //$msg='{"status":"200","msg":"返回成功","data":[{"s_id":12,"content":"文本内容，标题摘要之类","url":"https://www.baidu.com"},{"s_id":12,"content":"文本内容，标题摘要之类","url":"https://www.sohu.com"}]}';
        echo $msg;
        die;
    }
}

function  intToN2c($num)
{
    $arr = array("零", "一", "二", "三", "四", "五", "六", "七", "八", "九", "十");
    $lan = strlen($num);
    $str = '';
    for ($i = 0; $i < $lan; $i++) {
        $a = substr($num, $i, 1);
        if (isset($arr[$a])) {
            $a = $arr[$a];
        }
        $str .= $a;
    }
    return $str;
}
function replaceName($name, $number)
{
    if ($number) {
        $kw = '';
        for ($i = 0; $i < $number; $i++) {
            $kw .= ' ';
        }
        return $name . $kw;
    } else {
        return $name;
    }
}
//截取GB2312中文字符串
function mysubstr($str, $start, $len)
{
    $tmpstr = "";
    $strlen = $start + $len;
    for ($i = 0; $i < $strlen; $i++) {
        if (ord(substr($str, $i, 1)) > 0xa0) {
            $tmpstr .= substr($str, $i, 2);
            $i++;
        } else
            $tmpstr .= substr($str, $i, 1);
    }
    return $tmpstr;
}

function fixY($money)
{
    $money = floatval($money);
    $money  = bcdiv(intval($money), 10, 1);
    $floftMoney = floatval(sprintf("%.1f ", $money));
    return bcsub($floftMoney, intval($money), 1) * 10;
}

function fixJ($money)
{
    $money = floatval($money);
    $floftMoney = bcdiv($money, 1, 1);
    return bcsub($floftMoney, intval($money), 1);
}

function fixF($money)
{
    $money = floatval($money);
    $floftMoney = fixJ($money);
    return bcsub($money, bcadd(intval($money), $floftMoney, 1), 2);
}


// function  timeRegroup($startTime, $endTime, $ciri = false, $timeArr)
// {
//     return false;
// }

function arreach($arrData = [], $strChild = "children")

{

    if (empty($arrData) || !is_array($arrData)) {

        return $arrData;
    }

    $arrRes = [];

    foreach ($arrData as $k => $v) {

        $arrTmp = $v;

        unset($arrTmp[$strChild]);

        $arrRes[] = $arrTmp;

        if (!empty($v[$strChild])) {

            $arrTmp = arreach($v[$strChild]);

            $arrRes = array_merge($arrRes, $arrTmp);
        }
    }

    return $arrRes;
}

function getRedirectUrl($url)
{
    stream_context_set_default(array(
        'http' => array(
            'method' => 'GET'
        )
    ));
    $headers = get_headers($url, 1);
    if ($headers !== false && isset($headers['Location'])) {
        return $headers['Location'];
    }
    return false;
}
