<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sms;
use Illuminate\Support\Facades\Crypt;
use App\Models\Admin;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;

class InstallController extends Controller
{
    //激活码激活
    public function getCode(Request $request)
    {
        if ($request->isMethod('post')) {
            $phone = $request->input('mobile');
            $code = generate_code();
            $smsConfig = (object)config('app.smsConfig');
            $sms = new Sms();
            $bool = $sms->aliyunSendSms($smsConfig, $phone, $smsConfig->smsCode, $code);
            if ($bool === true) {
                Cache::put("install_code", $code);
                echo json_encode(['code' => 200, 'msg' => 'success']);
                die;
            } else {
                echo json_encode(['code' => 400, 'msg' => $bool]);
                die;
            }
        }
    }
    //激活码激活
    public function activation(Request $request)
    {
        if ($request->isMethod('post')) {
            $code = $request->input('code');
            $auth_code = $request->input('auth_code');
            $sessionCode = strval(Cache::get('install_code'));

            if (!$sessionCode || $code !== $sessionCode) {
                echo json_encode(['code' => 400, 'msg' => '无效的验证码']);
                die;
            }
            $array = array(
                'domain_url' => 'https://' . $_SERVER['HTTP_HOST'],
                'isDev' => false,
                'phone' => $request->input('mobile'),
                'domain_name' => $request->input('domain_name'),
                'corporate_name' => $request->input('corporate_name'),
                'email' => $request->input('email'),
                'server_id' => sg_get_machine_id(),
            );
            $url = config('app.authorizeDomain') . '/cloud/auth/checkAuth';
            $result = httpRequest($url, $array);

            $data = json_decode($result, true);

            if ($data['code'] !== 200) {
                echo json_encode(['code' => 400, 'msg' => $data['msg']]);
                die;
            }
            $authData = json_decode($result, true)['authData'];
            $row = Crypt::encryptString(json_encode($authData));
            touch('secret.json');
            chmod('secret.json', 0777);
            try {
                file_put_contents('secret.json', $row);
                $cacheData = array(
                    'username' => $request->input('username'),
                    'mobile' => $request->input('mobile'),
                    'password' => $request->input('password'),
                );
                Cache::forever('installCache', $cacheData);
            } catch (Exception $e) {
                echo json_encode(['code' => 400, 'msg' => $e->getMessage()]);
                die;
            }
            if ($result) {
                echo json_encode(['code' => 200, 'msg' => '验证成功']);
                die;
            } else {
                echo json_encode(['code' => 400, 'msg' => '验证失败,请保证数据合法性!']);
                die;
            }
        }
    }

    //激活码验证
    public function veryCode(Request $request)
    {
        if ($request->isMethod('post')) {
            $encrypted = file_get_contents('secret.json');
            $decrypted = Crypt::decryptString($encrypted);
            var_dump($decrypted);
        }
    }
    //激活码验证
    public function installAuth(Request $request)
    {
        if ($request->isMethod('post')) {
            $array = array(
                'domain_url' => 'https://' . $_SERVER['HTTP_HOST'],
            );
            $url = config('app.authorizeDomain') . '/cloud/auth/installAuth';
            $result = httpRequest($url, $array);
            echo $result;
            die;
        }
    }
    //环境检测
    public function checkEnvironment()
    {
        $disfun = ini_get('disable_functions');
        $disfunArr = explode(',', $disfun);
        $webServer = strtolower($_SERVER['SERVER_SOFTWARE']);
        $domain_url='https://' . $_SERVER['HTTP_HOST'];
        $domain_url = preg_replace("(^https?://)", "", $domain_url);
        $data = array(
            'system_version' => php_uname('s'),
            'server_version' => $webServer,
            'cpu_core' => 0,
            'php_version' => PHP_VERSION,
            'sg13' => extension_loaded("SourceGuardian") ? true : false,
            'redis_extend' => extension_loaded("redis") ? true : false,
            'swoole_extend' => extension_loaded("swoole") ? true : false,
            'memory_limit' => get_cfg_var("memory_limit") ? get_cfg_var("memory_limit") : "无",
            'execute_max' => get_cfg_var("upload_max_filesize") ? get_cfg_var("upload_max_filesize") : "不允许",
            'domain_url' => $domain_url,
            'ip_address' => gethostbyname($domain_url),
            'server_id' => sg_get_machine_id(),
            );
        if (!in_array('shell_exec', $disfunArr)) {
            $command = "cat /proc/cpuinfo | grep cores | wc -l";
            $memory_command = "cat more /proc/meminfo";
            $str = shell_exec($memory_command);
            $pattern = "/(.+):\s*([0-9]+)/";
            preg_match_all($pattern, $str, $out);
            $memory_total = bcdiv(bcdiv($out[2][0], 1024), 1024) + 1;
            $data['cpu_core'] = (int) shell_exec($command) . '核/' . $memory_total . 'G';
            $data['system_version'] = shell_exec('cat /etc/redhat-release');
        }
        if (in_array('shell_exec', $disfunArr)) {
            echo json_encode(['code' => 400, 'msg' => '请删除php禁用函数shell_exec!', 'data' => $data]);
            die;
        }
        if (!$data['redis_extend']) {
            echo json_encode(['code' => 400, 'msg' => '请安装redis扩展!', 'data' => $data]);
            die;
        }
        if (!$data['swoole_extend']) {
            echo json_encode(['code' => 400, 'msg' => '请安装swoole扩展!', 'data' => $data]);
            die;
        }

        if (!$data['sg13']) {
            echo json_encode(['code' => 400, 'msg' => '请安装sg13扩展!', 'data' => $data]);
            die;
        }
        echo json_encode(['code' => 200, 'msg' => 'success', 'data' => $data]);
        die;
    }
    //数据库配置
    public function configureMysql(Request $request)
    {
        $cacheData = Cache::get('installCache');
        if (!$cacheData['username'] || !$cacheData['password']) {
            echo json_encode(['code' => 400, 'msg' => '数据异常']);
            die;
        }
        $data = array(
            'DB_HOST' => $request->input('db_host'),
            'DB_DATABASE' => $request->input('db_database'),
            'DB_PORT' => $request->input('db_port'),
            'DB_USERNAME' => $request->input('db_username'),
            'DB_PASSWORD' => $request->input('db_password'),
            'DB_PREFIX' => $request->input('db_prefix'),
            'REDIS_HOST' => $request->input('redis_host'),
            'REDIS_PASSWORD' => $request->input('redis_password'),
            'REDIS_PORT' => $request->input('redis_port'),
        );
        modifyEnv($data);
        echo json_encode(['code' => 200, 'msg' => '数据库配置配置成功']);
        die;
    }

    public function init(){
        $cacheData = Cache::get('installCache');
        if (!$cacheData['username'] || !$cacheData['password']) {
            echo json_encode(['code' => 400, 'msg' => '数据异常']);
            die;
        }
        Artisan::call('migrate');
        Artisan::call('db:seed --class=CoreDistrictSeeder');
        Artisan::call('db:seed --class=MenusSeeder');
        Artisan::call('db:seed --class=RoleSeeder');
        DB::beginTransaction();
        $adminModel = new Admin();
        try {
            $res = $adminModel->where('username', $cacheData['username'])->first();
            if ($res) {
                echo json_encode(['code' => 400, 'msg' => '用户已存在']);
                die;
            }
            $adminModel->username = $cacheData['username'];
            $adminModel->mobile = $cacheData['mobile'];
            $adminModel->password = Hash::make($cacheData['password'] ?? '123456');
            $adminModel->role_id = 0;
            $adminModel->save();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            echo json_encode(['code' => 400, 'msg' => $e->getMessage()]);
            die;
        }
        echo json_encode(['code' => 200, 'msg' => '数据库配置配置成功']);
        die;
    }

    //二次安装获取站点信息
    public function getDomainInfo(){
        $domain='https://' . $_SERVER['HTTP_HOST'];
        $data=array(
            'domain'=>$domain
        );
        $url = config('app.authorizeDomain') . '/cloud/auth/installAuth';
        $re=httpRequest($url,$data);
        return $re;
    }


    //加密-----------------------------
     public function getConfig($machineid='',$domain=''){
        $config =  [
            // 用户名
            'username'         => '15307193890',

            // key
            'userkey'    => '12112f8d690dc8c2613dc3fadd71e9c4fffcfb9d',
            // 加密版本
            'sg_version'       => '13',
            // 加密版本
            'version'       => '8.0||8.1',

            // 加密级别
            'level'       => 0,

            // 版权信息
            'copyright'      => '',

            // 时间限制
            'etime'      => '',

            // 限制程序需要互联网运行，配合时间限制使用
            'server'   => 0,

            // 域名限制
            'domain' =>$domain,

            // ip限制
            'ip'          => '',

            // mac限制
            'mac'      => '',
            // 计算机ID限制
            'machineid'    => $machineid,


            // 自定义头部代码
            'mycode'      => file_get_contents(base_path().'/public/mycode.php'),

            // 使用双密模式 0关闭 1开启
            'is_use_license' => "1",

            // 双密模式id（自己设定）
            "license_id" =>"GYUdwaad5wadwa654ajoida111",
            // 双密模式key（自己设定）
            "license_key"=>"adawdwjkAWdwwasdasd48496498321",
            // 双密模式认证文件路径（可指定路径和文件后缀   比如license.lic 或者  http://www.*****.com/license.php）
            "license_path" => "license.lic",


            // 不显示默认加载信息;
            'no_show_tag_code' => 1,

            // 是否显示PHP错误信息error_reporting(0);
            'show_all_err' => 1,
            // 本加密程序版本，无需设置，勿改动
            "Tools_version" => "3.2"


        ];
        return $config;
    }

     public function upload_file($url,$r_file,$config,$filename){
        $varname = 'uploadfile';
        $name = $r_file;
        $type = 'text/plain';
        $key = "file";
        $config["j_cpuid"]  =sg_get_machine_id();
        $config["filename"] = $filename;
        $config["filecode"] = file_get_contents($r_file);

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL,$url);
        curl_setopt($curl,CURLOPT_POST,1);
        curl_setopt($curl,CURLOPT_POSTFIELDS,$config);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        $result = curl_exec($curl);
        curl_close($curl);

        $json_data = json_decode($result,true);
        return $json_data;
    }

    public function generated(){
        $server_id=sg_get_machine_id();
        $domain =$_SERVER['HTTP_HOST'];
        $url = 'http://www.vvxyz.com/index.php/home/apitools/codeapi.html';
        $config=self::getConfig($server_id,$domain);
        $res = self::upload_file($url,'Test.php',$config,'Test.php');
        if($res['status'] == 1){
            $code = base64_decode($res['code']);
            file_put_contents('encode.php',$code);
            echo "加密完成";
        }else{
            echo $res['message'];
            exit;
        }
    }

}
