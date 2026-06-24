<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AdminGroupController;
use App\Http\Controllers\Admin\ApplyController;
use App\Http\Controllers\Admin\ConfigController;
use App\Http\Controllers\Admin\HandleLogController;
use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\MiniUploadController;
use App\Http\Controllers\Admin\MusterController;
use App\Http\Controllers\Admin\NewsController;
use App\Http\Controllers\Admin\OpenWechatController;
use App\Http\Controllers\Admin\PlugController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\SystemController;
use App\Http\Controllers\Admin\UploadController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\FileController;
use App\Http\Controllers\Admin\PayConfigController;
use App\Http\Controllers\Admin\SmsAccountController;
use App\Http\Controllers\Admin\SmsComboController;
use App\Http\Controllers\Admin\SmsController;
use App\Http\Controllers\Admin\WaiSongBangController;
use App\Http\Controllers\Admin\WxPayNotifyController;
use App\Http\Controllers\Admin\AdvertisementController;
use App\Http\Controllers\Admin\HelpersController;
use App\Http\Controllers\Admin\Mini\RegisterController;
use App\Http\Controllers\Admin\PublicMiniProgramController;
use Illuminate\Support\Facades\Route;
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

Route::middleware(['checkDoman'])->group(function () {
    Route::controller(LoginController::class)->group(function () {
        Route::post('/login', "login")->name('admion.login');
        Route::post('/mobileLogin', "mobileLogin")->name('admin.mobileLogin');
        Route::post('/checkCode', "checkCode")->name('admin.checkCode');
        Route::post('/retrievePassword', "retrievePassword")->name('admin.retrievePassword'); //找回密码
        Route::prefix('login')->group(function () {
            Route::get('getOpenWechat', 'admin.getOpenWechat');
            Route::get('wechatLoginState', 'admin.wechatLoginState')->name('admin.wechatLoginState');
            Route::post('wechatBind', 'WechatBind')->name('admin.wechatBind');
            Route::any('wechatLogin/{channel}', 'wechatLogin')->name('admin.wechatLogin');
        });
        Route::any('/web', "web")->name('web'); //找回密码
    });
    //购买插件  续费
    Route::prefix('plug')->group(function () {
        Route::controller(PlugController::class)->group(function () {
            Route::get('list', 'list')->name('plug.list');
            Route::post('create-order', 'createOrder')->name('create-order');
            Route::post('check-payment-status', 'checkPaymentStatus')->name('check-payment-status');
            Route::post('state', "state")->name('state'); //下架上架
            Route::get('orderList', "orderList")->name('orderList'); //下架上架
        });
    });
    //客服
    Route::controller(CustomerController::class)->group(function () {
        Route::get('customer/get-customer-list', 'getCustomerList')->name('customer.getCustomerList');
        Route::post('customer/save-customer', 'saveCustomer')->name('customer.saveCustomer');
        Route::post('customer/change-customer', 'changeCustomer')->name('customer.changeCustomer');
    });
    Route::controller(SmsController::class)->group(function () {
        Route::post('/sms/login', "login")->name('loginSms'); //修改密码
        Route::post('/sms/test', "test")->name('test'); //修改密码
    });

    // 公域小程序
    Route::controller(PublicMiniProgramController::class)->group(function (){
        // 公域小程序配置
        Route::get('/publicProgramConfig', 'getMiniProgramInfo')->name('getMiniProgramInfo');
        Route::post('/editPublicProgram', 'editMiniProgram')->name('editMiniProgram');
    });
    //配置
    Route::controller(ConfigController::class)->group(function () {
        Route::get('/systemConfig', "system")->name('admin.systemConfig');
        Route::get('/getSystemInfo', "getSystemInfo")->name('admin.getSystemInfo');
        Route::post('/sendStoreTemplates', "sendStoreTemplates")->name('admin.sendStoreTemplates');
    });
    Route::group(['middleware' => ['jwt.admin', 'onlyLogin']], function () {
        Route::controller(LoginController::class)->group(function () {
            Route::post('/logout', "logout")->name('admin.logout');
            //进入平台获取新的token
            Route::post('login/enterPosition', 'enterPosition')->name('admin.enterPosition');
        });
        Route::controller(SmsController::class)->group(function () {
            Route::post('/sms/retrieve', "retrieve")->name('admin.retrieveSms'); //修改密码
        });
        //小程序上传
        Route::controller(MiniUploadController::class)->middleware('sysUpload')->group(function () {
            Route::get('mini-upload/version', 'version')->name('admin.miniUpload.version');
            Route::post('mini-upload/upload', 'upload')->name('admin.miniUpload.upload');
            Route::post('mini-upload/merchant', 'merchant')->name('admin.miniUpload.merchant');
        });
        //第三方开发平台
        Route::controller(OpenWechatController::class)->group(function () {
            Route::get('open-wechat/draftbox', 'draftbox')->name('OpenWechat.draftbox');
            Route::get('open-wechat/check', 'check')->name('OpenWechat.check');
            Route::post('open-wechat/release', 'release')->name('OpenWechat.release');
            Route::get('open-wechat/template-list', 'templateList')->name('OpenWechat.templateList');
            Route::post('open-wechat/template-select', 'templateSelect')->name('OpenWechat.templateSelect');
            Route::delete('open-wechat/template-delete', 'templateDelete')->name('OpenWechat.template-delete');
        });
        //管理员
        Route::controller(AdminController::class)->group(function () {
            Route::post('/admins/state/{id}', 'state')->name('admin.admins.state'); //拉黑
            Route::get('/admins/list', 'list')->name('admin.admins.list'); //所有管理员列表
            Route::post('/admins/audit/{id}', 'audit')->name('admin.admins.audit'); //管理员审核
            Route::get('/admins/recovery', 'recovery')->name('admin.admins.recovery'); //管理员回收站
            Route::delete('/admins/del/{admin}', 'del')->name('admin.admins.del'); //回收站删除
            Route::post('/admins/restore/{admin}', 'restore')->name('admin.admins.restore'); //管理员恢复
        });

        //平台
        Route::controller(ApplyController::class)->group(function () {
            Route::get('/apply/survey', 'survey')->name('admin.apply.surver'); //平台概况
            Route::get('/apply/recycle', 'recycle')->name('admin.apply.recycle'); //回收站
            Route::post('/apply/state/{apply}', 'state')->name('admin.apply.state'); //拉黑
            Route::delete('/apply/del/{apply}', 'del')->name('admin.apply.del'); //回收站删除
            Route::post('/apply/restore/{apply}', 'restore')->name('admin.apply.restore'); //回收站恢复
            Route::get('/apply/plugins/{apply}', "plugins")->name('admin.apply.plugins'); //获取插件;
            Route::post('apply/recovery', 'recovery')->name('admin.apply.recovery'); //回收站
            Route::post('apply/autio/{apply}', 'autio')->name('admin.apply.autio'); //店铺审核
            Route::post('apply/authPlug/{apply}', 'authPlug')->name('admin.apply.authPlug'); //授权插件
            Route::post('apply/display/{apply}', 'display')->name('admin.apply.display'); //店铺审核
        });

        //配置
        Route::controller(ConfigController::class)->group(function () {
            Route::get('/config', "index")->name('admin.config.get'); //获取菜单;
            Route::post('/config', "store")->name('admin.config.create'); //设置配置;
            Route::put('/config/{ident}', "update")->name('admin.config.update'); //更新配置;
        });
        //获取菜单
        Route::controller(UserController::class)->group(function () {
            Route::get('/loadMenus', "loadMenus")->name('admin.loadMenus'); //获取菜单;
            Route::post('/changePassword', "changePassword")->name('admin.changePassword'); //修改密码
            Route::get('/profix', "index")->name('profix'); //获取个人信息;
        });
        //配置
        Route::controller(SmsComboController::class)->group(function () {
            Route::prefix('smsCombo')->group(function () {
                Route::post('/state/{smsCombo}', "state")->name('smsCombo.state'); //设置配置;
            });
        });
        Route::controller(MenuController::class)->group(function () {
            Route::prefix('menus')->group(function () {
                Route::post('/batch', "batch")->name('menus.batch'); //设置配置;
            });
        });

        Route::prefix('mini')->group(function () {
            Route::apiResource("register", RegisterController::class, ['names' => 'admin.mini.register']);
        });

        //系统首页
        Route::controller(SystemController::class)->group(function () {
            Route::post('/system/get-bucket', "getBucket");
            Route::get('system/index', 'index');
            Route::get('system/checkswoole', 'checkswoole');
            Route::get('system/checkRedis', 'checkRedis');
            Route::get('system/clearCache', 'clearCache');
            Route::get('system/clearQueue', 'clearQueue');
            Route::get('system/swooleRestart', 'swooleRestart');
            Route::get('system/queueRestart', 'queueRestart');
            Route::post('system/checkForUpdates', 'checkForUpdates')->name('checkForUpdates')->middleware('sysUpload');
            Route::post('system/updateSystem', 'updateSystem')->name('updateSystem')->middleware('sysUpload');
            Route::post('system/sendSms', 'sendSms')->name('system.sendSms');
            Route::post('system/create-wechat', 'createWechat')->name('system.createWechat');
            Route::get('system/get-wechat-list', 'getWechatList')->name('system.getWechatList');
            Route::post('system/upload-image', 'uploadImage')->name('system.uploadImage');
            Route::post('system/uploadBase64', 'uploadBase64')->name('system.uploadBase64');
            Route::post('system/get-update-announcement-list', 'getUpdateAnnouncementList')->name('system.GetUpdateAnnouncementList');
            Route::get('system/syncSms', 'syncSms');
            Route::get('system/syncCircle', 'syncCircle');
        });

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
                Route::post('editPicture', 'editPicture');
                Route::post('changeCategory', 'changeCategory');
            });
        });
        Route::prefix('helpers')->group(function () {
            Route::controller(HelpersController::class)->group(function () {
                Route::post('/state/{id}', "state")->name('admin.helpers.state'); //查询充值状态
            });
        });

        Route::apiResource("waisongbang", WaiSongBangController::class, ['names' => 'admin.waisongbang']);
        Route::apiResource("admins", AdminController::class, ['names' => 'admin.admins']);
        Route::apiResource("roles", RoleController::class, ['names' => 'admin.roles']);
        Route::apiResource("menus", MenuController::class, ['names' => 'admin.menus']);
        Route::apiResource("apply", ApplyController::class, ['names' => 'admin.apply']);
        Route::apiResource("muster", MusterController::class, ['names' => 'admin.muster']);
        Route::apiResource("upload", UploadController::class, ['names' => 'admin.upload']);
        Route::apiResource("handleLog", HandleLogController::class, ['names' => 'admin.handleLog']);
        Route::apiResource("news", NewsController::class, ['names' => 'admin.news']);
        Route::apiResource("admin_group", AdminGroupController::class, ['names' => 'admin.admin_group']);
        Route::apiResource("plug", PlugController::class, ['names' => 'admin.plug']);
        Route::apiResource("order", OrderController::class, ['names' => 'admin.order']);
        Route::apiResource("smsCombo", SmsComboController::class, ['names' => 'admin.smsCombo']);
        Route::apiResource("payConfig", PayConfigController::class, ['names' => 'admin.payConfig']);
        Route::apiResource("smsAccount", SmsAccountController::class, ['names' => 'admin.smsAccount']);
        Route::apiResource("advertisement", AdvertisementController::class, ['names' => 'admin.advertisement']);
        Route::apiResource("helpers", HelpersController::class, ['names' => 'admin.helpers']);
    });
});
//购买插件/短信 回调  续费
Route::prefix('wxPayNotify')->group(function () {
    Route::controller(WxPayNotifyController::class)->group(function () {
        Route::any('sms/{uniacid}', 'sms')->name('wxPayNotify.sms');
        Route::any('muster', 'muster')->name('wxPayNotify.muster');
    });
});
