<?php

/*
 * This file is part of the overtrue/wechat.
 *
 * (c) overtrue <i@overtrue.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace App\Models\KuaiShou\MiniProgram;

use App\Models\KuaiShou\BasicService;
use App\Models\KuaiShou\Kernel\ServiceContainer;

/**
 * Class Application.
 *
 * @author mingyoung <mingyoungcheung@gmail.com>
 *
 * @property \App\Models\KuaiShou\MiniProgram\Auth\AccessToken           $access_token
 * @property \App\Models\KuaiShou\MiniProgram\DataCube\Client            $data_cube
 * @property \App\Models\KuaiShou\MiniProgram\AppCode\Client             $app_code
 * @property \App\Models\KuaiShou\MiniProgram\Auth\Client                $auth
 * @property \App\Models\KuaiShou\OfficialAccount\Server\Guard           $server
 * @property \App\Models\KuaiShou\MiniProgram\Encryptor                  $encryptor
 * @property \App\Models\KuaiShou\MiniProgram\TemplateMessage\Client     $template_message
 * @property \App\Models\KuaiShou\OfficialAccount\CustomerService\Client $customer_service
 * @property \App\Models\KuaiShou\MiniProgram\Plugin\Client              $plugin
 * @property \App\Models\KuaiShou\MiniProgram\Plugin\DevClient           $plugin_dev
 * @property \App\Models\KuaiShou\MiniProgram\UniformMessage\Client      $uniform_message
 * @property \App\Models\KuaiShou\MiniProgram\ActivityMessage\Client     $activity_message
 * @property \App\Models\KuaiShou\MiniProgram\Express\Client             $express
 * @property \App\Models\KuaiShou\MiniProgram\NearbyPoi\Client           $nearby_poi
 * @property \App\Models\KuaiShou\MiniProgram\OCR\Client                 $ocr
 * @property \App\Models\KuaiShou\MiniProgram\Soter\Client               $soter
 * @property \App\Models\KuaiShou\BasicService\Media\Client              $media
 * @property \App\Models\KuaiShou\BasicService\ContentSecurity\Client    $content_security
 * @property \App\Models\KuaiShou\MiniProgram\Mall\ForwardsMall          $mall
 * @property \App\Models\KuaiShou\MiniProgram\SubscribeMessage\Client    $subscribe_message
 * @property \App\Models\KuaiShou\MiniProgram\RealtimeLog\Client         $realtime_log
 * @property \App\Models\KuaiShou\MiniProgram\RiskControl\Client         $risk_control
 * @property \App\Models\KuaiShou\MiniProgram\Search\Client              $search
 * @property \App\Models\KuaiShou\MiniProgram\Live\Client                $live
 * @property \App\Models\KuaiShou\MiniProgram\Broadcast\Client           $broadcast
 * @property \App\Models\KuaiShou\MiniProgram\UrlScheme\Client           $url_scheme
 * @property \App\Models\KuaiShou\MiniProgram\Union\Client               $union
 * @property \App\Models\KuaiShou\MiniProgram\Shop\Register\Client       $shop_register
 * @property \App\Models\KuaiShou\MiniProgram\Shop\Basic\Client          $shop_basic
 * @property \App\Models\KuaiShou\MiniProgram\Shop\Account\Client        $shop_account
 * @property \App\Models\KuaiShou\MiniProgram\Shop\Spu\Client            $shop_spu
 * @property \App\Models\KuaiShou\MiniProgram\Shop\Order\Client          $shop_order
 * @property \App\Models\KuaiShou\MiniProgram\Shop\Delivery\Client       $shop_delivery
 * @property \App\Models\KuaiShou\MiniProgram\Shop\Aftersale\Client      $shop_aftersale
 * @property \App\Models\KuaiShou\MiniProgram\Business\Client            $business
 * @property \App\Models\KuaiShou\MiniProgram\UrlLink\Client             $url_link
 * @property \App\Models\KuaiShou\MiniProgram\QrCode\Client              $qr_code
 * @property \App\Models\KuaiShou\MiniProgram\PhoneNumber\Client         $phone_number
 */
class Application extends ServiceContainer
{
    /**
     * @var array
     */
    protected $providers = [
        Auth\ServiceProvider::class,
        Base\ServiceProvider::class,
    ];

    /**
     * Handle dynamic calls.
     *
     * @param string $method
     * @param array  $args
     *
     * @return mixed
     */
    public function __call($method, $args)
    {
        return $this->base->$method(...$args);
    }
}
