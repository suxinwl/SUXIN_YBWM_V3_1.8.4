<?php

/*
 * This file is part of the overtrue/wechat.
 *
 * (c) overtrue <i@overtrue.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace App\Models\KuaiShou\BasicService;

use App\Models\KuaiShou\Kernel\ServiceContainer;

/**
 * Class Application.
 *
 * @author overtrue <i@overtrue.me>
 *
 * @property \App\Models\KuaiShou\BasicService\Jssdk\Client           $jssdk
 * @property \App\Models\KuaiShou\BasicService\Media\Client           $media
 * @property \App\Models\KuaiShou\BasicService\QrCode\Client          $qrcode
 * @property \App\Models\KuaiShou\BasicService\Url\Client             $url
 * @property \App\Models\KuaiShou\BasicService\ContentSecurity\Client $content_security
 */
class Application extends ServiceContainer
{
    /**
     * @var array
     */
    protected $providers = [
        Jssdk\ServiceProvider::class,
        QrCode\ServiceProvider::class,
        Media\ServiceProvider::class,
        Url\ServiceProvider::class,
        ContentSecurity\ServiceProvider::class,
    ];
}
