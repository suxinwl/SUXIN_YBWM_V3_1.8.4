<?php

use App\Http\Controllers\Common\CloudController;
use App\Http\Controllers\Common\FileController;
use App\Http\Controllers\Common\OpenPlatformController;
use App\Http\Controllers\Common\QrLoginController;
use App\Http\Controllers\Common\QueueController;
use App\Http\Controllers\Common\QueuingUpController;
use App\Http\Controllers\Common\RegionController;
use App\Http\Controllers\Common\SwooleJobController;
use App\Http\Controllers\Common\TakeScenanController;
use App\Http\Controllers\Common\TakeScreenController;
//use App\Http\Controllers\Common\TakeScreenController;
use App\Http\Controllers\Common\WechatController;
use App\Http\Controllers\Common\WechatEventController;
use App\Http\Controllers\Common\WechatMerchantController;
use App\Http\Controllers\Common\WechatCallbackController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('file')->group(function () {
    Route::controller(FileController::class)->group(function () {
        Route::post('upload', 'upload');
        Route::post('uploadBase64', 'uploadBase64');
        Route::any('ceshi', 'ceshi');
        Route::post('saveCategory', 'saveCategory');
        Route::get('getCategory', 'getCategory');
        Route::post('moveFile', 'moveFile');
        Route::get('getPicture', 'getPicture');
        Route::post('delPictureCategory', 'delPictureCategory');
        Route::post('delPicture', 'delPicture');
        Route::post('recyclePicture', 'recyclePicture');
        Route::post('delRecyclePicture', 'delRecyclePicture');
        Route::post('creatImg', 'creatImg');
    });
});

Route::prefix('cloud')->group(function () {
    Route::controller(CloudController::class)->group(function () {
        Route::get('ceshi', 'ceshi');
        Route::any('ceshis', 'ceshis');
        Route::post('writeAuth', 'writeAuth');
        Route::post('plugNotify', 'plugNotify');
        Route::any('replace-lic', 'replaceLic');
        Route::post('wechatCallBack', 'wechatCallBack');
        Route::get('getEmojiList', 'getEmojiList');
        Route::get('weComReceiveMessages', 'weComReceiveMessages');
        Route::get('weComMailMessages', 'weComMailMessages');
        Route::get('weComCeshi', 'weComCeshi');
        Route::any('weComCeshis', 'weComCeshis');

        Route::any('getuserinfo', 'getuserinfo');
        Route::any('messagesReceiving', 'messagesReceiving');
        Route::any('authorizationMessages', 'authorizationMessages');
        Route::any('ceshimess', 'ceshimess');
        Route::any('aaa', 'aaa');
        Route::any('bbb', 'bbb');
        Route::any('ccc', 'ccc');
        Route::any('ddd', 'ddd');
        Route::any('kwaiEmpower', 'kwaiEmpower');
    });
});
Route::prefix('wechatMerchant')->group(function () {
    Route::controller(WechatMerchantController::class)->group(function () {
        Route::post('uploadMedia', 'uploadMedia');
        Route::post('add', 'add');
        Route::post('query', 'query');
    });
});
Route::prefix('wechatCallback')->group(function () {
    Route::controller(WechatCallbackController::class)->group(function () {
        Route::any('valid', 'valid');
        Route::any('responseMsg', 'responseMsg');
    });
});
Route::prefix('openWechat')->group(function () {
    Route::controller(OpenPlatformController::class)->group(function () {
        Route::any('event', 'event')->name('openplatfom.event');
        Route::any('auth/{uniacid}', 'auth')->name('openplatfom.auth');
        Route::any('server/{appid}', 'server')->name('openplatfom.server');
    });
});
Route::prefix('wechat_event')->group(function () {
    Route::controller(WechatEventController::class)->group(function () {
        Route::any('authorization_receive', 'authorizationReceive');
        Route::any('news_receive', 'newsReceive');
    });
});
Route::prefix('wechat')->group(function () {
    Route::controller(WechatController::class)->group(function () {
        Route::get('qrCode', 'qrCode');
        Route::any('serve', 'Index');
    });
});
Route::prefix('region')->group(function () {
    Route::controller(RegionController::class)->group(function () {
        Route::get('region', 'region');
        Route::post('nameToRegion', 'nameToRegion');
    });
});


Route::prefix('swooleJob')->group(function () {
    Route::controller(SwooleJobController::class)->group(function () {
        Route::post('sendMessage', 'sendMessage');
    });
});

Route::prefix('qrLogin')->group(function () {
    Route::controller(QrLoginController::class)->group(function () {
        Route::get('qrCode', 'qrCode');
    });
});
Route::apiResources([
    'region' => RegionController::class, // 地区三级联动数据
    'swooleJob' => SwooleJobController::class, // 地区三级联动数据
    'takeScreen' => TakeScreenController::class,
    'qrLogin' => QrLoginController::class,
    'queuingUp' => QueuingUpController::class
]);
