<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sms;
use Illuminate\Support\Facades\Crypt;
use App\Models\Admin;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Request as FacadesRequest;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use App\Http\Helpers\ApiResponse;
use App\Models\Install;
use GuzzleHttp\Client;

class InstallController extends Controller
{
    use ApiResponse;
    public $stepData = [
        1 => 0,
        2 => 0,
        3 => 0,
        4 => 0,
        5 => 0
    ];
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            modifyEnv(['APP_URL' => 'https://' . $_SERVER['HTTP_HOST']]);
            if (file_exists(public_path('step.lock'))) {
                $stepStr = file_get_contents(public_path('step.lock'));
                $stepData = json_decode($stepStr, true);
                if (!empty($stepStr)) {
                    $this->stepData = $stepData;
                }
                if ($stepData[5] == 1) {
                    return redirect('/');
                }
            }
            return $next($request);
        });
    }

    public function getStep(Request $request)
    {

        if (file_exists(public_path('secret.json')) && $this->stepData[5] == 1) {
            return redirect('/');
        }
        foreach ($this->stepData as $k => $v) {
            if ($v == false) {
                return redirect('install/step' . $k);
                break;
            }
        }
    }

    public function step1()
    {
        if ($this->stepData[1] == false) {
            $this->stepData[1] = 1;
            file_put_contents(public_path('step.lock'), json_encode($this->stepData));
        }
        return view('install/step1');
    }

    public function step2()
    {
        if ($this->stepData[1] == 1) {
            return view('install/step2');
        }
        return redirect('install/start');
    }
    public function step3()
    {
        if ($this->stepData[2] == 1) {
            return view('install/step3');
        }
        return redirect('install/start');
    }
    public function step4()
    {
        if ($this->stepData[3] == 1) {
            return view('install/step4');
        }
        return redirect('install/start');
    }
    public function step5()
    {
        if ($this->stepData[4] == 1) {
            $this->stepData[5] = 1;
            file_put_contents(public_path('step.lock'), json_encode($this->stepData));
            return view('install/step5');
        }
        return redirect('install/start');
    }
    //激活码激活
    public function getCode(Request $request)
    {
        if ($request->isMethod('post')) {
            $phone = $request->input('mobile');
            $url = config('app.authorizeDomain') . '/cloud/auth/getCode';
            $res = httpRequest($url, ['phone' => $phone]);
            if ($res['code'] == 200) {
                return $this->success('短信发送成功');
            } else {
                return $this->failed($res['msg']);
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

            if (!$auth_code) {
                return $this->failed('请输入激活码');
            }
            $array = array(
                'auth_code' => $auth_code,
                'domain_url' => 'https://' . $_SERVER['HTTP_HOST'],
                'isDev' => false,
                'code' => $code,
                'phone' => $request->input('mobile'),
                'domain_name' => $request->input('domain_name'),
                'corporate_name' => $request->input('corporate_name'),
                'email' => $request->input('email'),
                'server_id' => sg_get_machine_id(),
            );
            $url = config('app.authorizeDomain') . '/cloud/auth/checkAuth';
            $data = httpRequest($url, $array);
            if ($data['code'] !== 200) {
                return $this->failed($data['msg']);
            }
            $lic_str = $data['lic_str'];
            if ($lic_str) {
                $name = 'admin.lic';
                $file_name = public_path() . '/' . $name;
                file_put_contents($file_name, base64_decode($lic_str));
            }
            $authData = $data['authData'];
            touch('secret.json');
            chmod('secret.json', 0777);
            try {
                file_put_contents(public_path('secret.json'), $authData);
                Install::insert(['type' => 'secret', 'data' => $authData]);
                $cacheData = array(
                    'username' => $request->input('username'),
                    'mobile' => $request->input('mobile'),
                    'password' => $request->input('password'),
                );
                Cache::forever('installCache', $cacheData);
            } catch (\Exception $e) {
                return $this->failed($e->getMessage());
            }
            if ($data) {
                $this->stepData[2] = 1;
                file_put_contents(public_path('step.lock'), json_encode($this->stepData));
                return $this->success('验证成功');
            } else {
                return $this->failed('验证失败,请保证数据合法性!');
            }
        }
    }

    //激活码验证
    public function veryCode(Request $request)
    {
        if ($request->isMethod('post')) {
            $encrypted = file_get_contents('secret.json');
            $decrypted = Crypt::decryptString($encrypted);
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
            if ($result['code'] == 200) {

                return $this->success([], '短信发送成功');
            }
            return $this->failed($result['msg']);
        }
    }
    //环境检测
    public function checkEnvironment(Request $request)
    {
        $disfun = ini_get('disable_functions');
        $disfunArr = explode(',', $disfun);
        $webServer = strtolower($request->server('SERVER_SOFTWARE'));
        $domain_url = 'https://' . $request->server('HTTP_HOST');
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
            'server_id' => extension_loaded("SourceGuardian") ? sg_get_machine_id() : '',
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
        if ($request->type == 'msg') {
            if (in_array('shell_exec', $disfunArr)) {
                throw new BadRequestHttpException('请删除php禁用函数shell_exec!');
            }
            if (!$data['redis_extend']) {
                throw new BadRequestHttpException('请安装redis扩展!');
            }
            if (!$data['swoole_extend']) {
                throw new BadRequestHttpException('请安装swoole扩展!');
            }

            if (!$data['sg13']) {
                throw new BadRequestHttpException('请先安装SG13 PHP扩展，否则系统无法正常运行。安装教程：https://qbykj.yuque.com/books/share/aecef425-02a5-4909-8943-7a3e4a7cb263?#');
            }
            $this->stepData[3] = 1;
            file_put_contents(public_path('step.lock'), json_encode($this->stepData));
        }

        return $this->success($data);
    }

    //数据库配置
    public function configureMysql(Request $request)
    {
        try {
            $cacheData = Cache::get('installCache');
            if (!$cacheData['username'] || !$cacheData['password']) {
                return $this->failed('数据异常');
            }
            $db_host = trim($request->input('db_host'));
            $db_port = trim($request->input('db_port'));
            $db_database = trim($request->input('db_database'));
            $db_username = trim($request->input('db_username'));
            $db_password = trim($request->input('db_password'));
            $db_prefix = trim($request->input('db_prefix'));
            $redis_host = trim($request->input('redis_host'));
            $redis_password = trim($request->input('redis_password'));
            $redis_port = trim($request->input('redis_port'));
            $data = array(
                'APP_NAME' => $request->getHost(),
                'DB_HOST' => $db_host,
                'DB_HOST' => $db_host,
                'DB_DATABASE' => $db_database,
                'DB_PORT' => $db_port,
                'DB_USERNAME' => $db_username,
                'DB_PASSWORD' => $db_password,
                'DB_PREFIX' => $db_prefix,
                'REDIS_HOST' => $redis_host,
                'REDIS_PASSWORD' => $redis_password,
                'REDIS_PORT' => $redis_port,
                'APP_URL' => $request->getSchemeAndHttpHost(),
                'CACHE_DRIVER' => "redis",
                'LARAVELS_SERVER' => 'ybwmv3',
                'LARAVELS_WORKER_NUM' => 1,
                'LARAVELS_TASK_WORKER_NUM' => 10,
                'LOG_LEVEL' => 'error',
                'APP_NAME' => 'YBWMV3'
            );

            $bool = modifyEnv($data);

            if (!$bool) {
                return $this->failed('.env配置文件写入失败');
            }
            DB::disconnect();

            \Config::set("database.connections.mysql", [
                'driver' => 'mysql',
                "host" => $db_host,
                'port' => $db_port,
                'prefix' => $db_prefix,
                "database" => $db_database,
                "username" => $db_username,
                "password" => $db_password,
            ]);
            Artisan::call('jwt:secret -f');
            Artisan::call('migrate');
            Artisan::call('db:seed --class=DatabaseSeeder');
            $file_name = public_path() . '/admin.lic';
            $md5 = md5_file($file_name);
            Install::insert(['type' => "md5", 'data' => $md5]);
            DB::beginTransaction();
            $adminModel = new Admin();

            $res = $adminModel->where('username', $cacheData['username'])->first();
            if ($res) {
                return $this->failed('用户已存在');
            }
            $password = $cacheData['password'] ?? '123456';
            $adminModel->username = $cacheData['username'];
            $adminModel->nickname = '超级管理员';
            $adminModel->mobile = $cacheData['mobile'];
            $adminModel->password = Hash::make($password);
            $adminModel->role_id = 0;
            $adminModel->isAdmin = 1;
            $adminModel->group_id = 0;
            $adminModel->save();
            DB::commit();
            $this->stepData[4] = 1;
            file_put_contents(public_path('step.lock'), json_encode($this->stepData));
            return $this->success('数据库配置配置成功');
        } catch (\Exception $e) {
            DB::rollback();
            return $this->failed($e->getMessage());
        }
    }


    //二次安装获取站点信息
    public function getDomainInfo(Request $request)
    {
        $domain = 'https://' . $request->server('HTTP_HOST');
        $data = array(
            'domain' => $request->getSchemeAndHttpHost()
        );
        $url = config('app.authorizeDomain') . '/cloud/auth/installAuth';
        $re = httpRequest($url, $data);
        return $re;
    }
}
