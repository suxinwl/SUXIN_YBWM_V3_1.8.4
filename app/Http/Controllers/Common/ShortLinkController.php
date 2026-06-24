<?php

namespace App\Http\Controllers\Common;

use App\Models\Region;
use App\Traits\HelperTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\Controller as BaseController;
use App\Models\ShortLink;
use App\Services\OpenWechat\ChannelOpenWechat;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class ShortLinkController extends BaseController
{
    use HelperTrait;
    public function show(Request $request, $type, $uniacid, $shortLink = '', $storeId = 0)
    {
        if (strpos($shortLink, '.txt')) {
            echo  Storage::disk('index')->get($shortLink);
            exit;
        }
        if ($type == "takeScreen") {
            return redirect('/takeScreen/' . $shortLink);
        }
        $queryString = "?" . http_build_query($request->all());

        switch ($type) {
            case "index":
                $uri = "#/pages/index/index" . $queryString;
                break;
            case "storeGoods":
                $uri = "#/pages/index/goods" . $queryString;
                break;
            case "orderIndex":
                $uri = "#/pages/index/order-index" . $queryString;
                break;
            case "myIndex":
                $uri = "#/pages/index/my-index" . $queryString;
                break;
            case "addresses":
                $uri = "#/pages/my/addres/addresses" . $queryString;
                break;
            case "table":
                $uri = "#/pages/shop/in/goods" . $queryString;
                break;
            case "fastfood":
                $uri = "#/pages/shop/in/goods" . $queryString;
                break;
            case "personPay":
                $uri = "#/pages/shop/in/dmf" . $queryString;
                break;
            case "exchange":
                $uri = "#/pages/other/coupon/dhm" . $queryString;
                break;
            case "couponActivity":
                $uri = "#/pages/other/coupon/coupondl" . $queryString;
                break;
            case "couponCenter":
                $uri = "#/pages/other/coupon/center";
                break;
            case "orderDetail":
                $uri = "#/pages/order/detail" . $queryString;
                break;
            case "queuingUp":
                $uri = "#/pages/my/lineup/pdqh" . $queryString;
                break;
            case "storeWifi":
                $uri = "#/pages/other/wifi" . $queryString;
                break;
            case "oldWithNew":
                $uri = "#/pages/order/invitation/yqyl" . $queryString;
            case "storeIndex":
                $uri = "#/pages/index/index" . $queryString;
                break;
        }
        $url = "/alipay/index.html?uniacid={$uniacid}?" . $uri;
        if (strpos($request->userAgent(), 'MicroMessenger') != false) {
            $url = Request()->getSchemeAndHttpHost() . $url;
            return  redirect("/wechat/$uniacid?refererUrl=" . base64_encode($url));
        }
        return  redirect($url);
        // $link = ShortLink::where('shortLink', $shortLink)->first();
        // if (empty($link)) {
        //     throw new BadRequestException('页面不存在');
        // }
        // if (strpos($request->userAgent(), 'MicroMessenger') != false) {
        //     // if ($link->wx['type'] == 'mini') {
        //     //     $app = ChannelOpenWechat::miniProgram($link->uniacid);
        //     //     $res = $app->url_scheme->generate([
        //     //         'jump_wxa' => [
        //     //             'path' => $link->wx['path'],
        //     //             'query' => 'q=' . urlencode($request->fullUrl()),
        //     //             'env_version' => 'develop'
        //     //         ]
        //     //     ]);
        //     //     if ($res['errcode'] != 0) {
        //     //         throw new BadRequestException($res['errmsg']);
        //     //     }
        //     //     return  redirect($res['openlink']);
        //     // }
        // } elseif (strpos($request->userAgent(), 'AlipayClient') != false) {

        // } else {
        // $app = ChannelOpenWechat::miniProgram($link->uniacid);
        // $res = $app->url_scheme->generate([
        //     'jump_wxa' => [
        //         'path' => $link->wx['path'],
        //         'query' => 'q=' . urlencode($request->fullUrl()),
        //         'env_version' => 'develop'
        //     ]
        // ]);
        // if ($res['errcode'] != 0) {
        //     throw new BadRequestException($res['errmsg']);
        // }
        // return  redirect($res['openlink']);
        // }
    }
}
