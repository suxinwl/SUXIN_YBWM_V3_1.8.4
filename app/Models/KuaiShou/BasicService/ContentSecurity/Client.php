<?php

/*
 * This file is part of the overtrue/wechat.
 *
 * (c) overtrue <i@overtrue.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace App\Models\KuaiShou\BasicService\ContentSecurity;

use App\Models\KuaiShou\Kernel\BaseClient;
use App\Models\KuaiShou\Kernel\Exceptions\InvalidArgumentException;

/**
 * Class Client.
 *
 * @author tianyong90 <412039588@qq.com>
 */
class Client extends BaseClient
{
    /**
     * Text content security check.
     *
     * @param string $text
     * @param array $extra
     * @return array|\App\Models\KuaiShou\Kernel\Support\Collection|object|\Psr\Http\Message\ResponseInterface|string
     *
     * @throws \App\Models\KuaiShou\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function checkText(string $text, array $extra = [])
    {
        $params = array_merge(['content' => $text], $extra);

        return $this->httpPostJson('wxa/msg_sec_check', $params);
    }

    /**
     * Image security check.
     *
     * @param string $path
     *
     * @return array|\App\Models\KuaiShou\Kernel\Support\Collection|object|\Psr\Http\Message\ResponseInterface|string
     *
     * @throws \App\Models\KuaiShou\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function checkImage(string $path)
    {
        return $this->httpUpload('wxa/img_sec_check', ['media' => $path]);
    }

    /**
     * Media security check.
     *
     * @param string $mediaUrl
     * @param int    $mediaType
     *
     * @return array|\App\Models\KuaiShou\Kernel\Support\Collection|object|\Psr\Http\Message\ResponseInterface|string
     *
     * @throws \App\Models\KuaiShou\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \App\Models\KuaiShou\Kernel\Exceptions\InvalidArgumentException
     */
    public function checkMediaAsync(string $mediaUrl, int $mediaType)
    {
        /*
         * 1:音频;2:图片
         */
        $mediaTypes = [1, 2];

        if (!in_array($mediaType, $mediaTypes, true)) {
            throw new InvalidArgumentException('media type must be 1 or 2');
        }

        $params = [
            'media_url' => $mediaUrl,
            'media_type' => $mediaType,
        ];

        return $this->httpPostJson('wxa/media_check_async', $params);
    }

    /**
     * Image security check async.
     *
     * @param string $mediaUrl
     *
     * @return array|\App\Models\KuaiShou\Kernel\Support\Collection|object|\Psr\Http\Message\ResponseInterface|string
     *
     * @throws \App\Models\KuaiShou\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function checkImageAsync(string $mediaUrl)
    {
        return $this->checkMediaAsync($mediaUrl, 2);
    }

    /**
     * Audio security check async.
     *
     * @param string $mediaUrl
     *
     * @return array|\App\Models\KuaiShou\Kernel\Support\Collection|object|\Psr\Http\Message\ResponseInterface|string
     *
     * @throws \App\Models\KuaiShou\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function checkAudioAsync(string $mediaUrl)
    {
        return $this->checkMediaAsync($mediaUrl, 1);
    }
}
