<?php

use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\Admin\MiniUploadController;
use App\Http\Controllers\Admin\PlugController;
use App\Http\Controllers\Admin\SmsController;
use App\Http\Controllers\Admin\WxPayNotifyController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\Common\CloudController;
use App\Http\Controllers\ChannelApi\BulkPackageController;
use App\Http\Controllers\ChannelApi\PostController;
use App\Http\Controllers\Common\ShortLinkController;
use App\Http\Controllers\Common\WechatLoginController;
use App\Services\ConfigService;
use App\Services\OpenWechat\ChannelOpenWechat;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    if (!file_exists(public_path() . '/secret.json')) {
        return redirect('/install/start');
    }
    $config = collect(ConfigService::getSystemSet('home'))->toArray();
    if (empty($config) || empty($config['homeSwitch'])) {
        return redirect('/admin');
    }
    return redirect('/admin/#/enterprise');
});
Route::get('/ws', function () {
    // 响应状态码200的任意内容
    return 'ok';
});

Route::prefix('s')->group(function () {
    Route::controller(ShortLinkController::class)->group(function () {
        Route::get('/{type}/{uniacid}', "show")->name('shortLink.show');
        Route::get('/{type}/{uniacid}/{shortLink}', "show")->name('shortLink.show');
    });
});

Route::get('/wechat/{id}', [WechatLoginController::class, 'show']);

Route::prefix('install')->group(function () {
    Route::controller(InstallController::class)->group(function () {
        Route::get('start', "getStep")->name('install.getStep');
        Route::get('step1', "step1")->name('install.step1');
        Route::get('step2', "step2")->name('install.step2');
        Route::get('step3', "step3")->name('install.step3');
        Route::get('step4', "step4")->name('install.step4');
        Route::get('step5', "step5")->name('install.step5');
        Route::post('get-code', "getCode")->name('install.getCode');
        Route::post('activation', "activation")->name('install.activation');
        Route::post('very-code', "veryCode")->name('install.veryCode');
        Route::post('install-auth', "installAuth")->name('install.installAuth');
        Route::post('check-environment', "checkEnvironment")->name('install.checkEnvironment');
        Route::post('configure-mysql', "configureMysql")->name('install.configureMysql');
        Route::post('getDomainInfo', "getDomainInfo")->name('install.getDomainInfo');
        Route::post('generated', "generated")->name('install.generated');
        Route::post('send-ceshi', "sendCeshi")->name('install.sendCeshi');
    });
});
