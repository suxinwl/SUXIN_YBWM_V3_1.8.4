<?php

declare(strict_types=1);

namespace App\Models\Wechat\WaiSongBang;

use App\Models\Wechat\Kernel\Contracts\Config as ConfigInterface;
use App\Models\Wechat\Kernel\Contracts\Server as ServerInterface;
use App\Models\Wechat\Kernel\Traits\InteractWithConfig;
use App\Models\Wechat\Kernel\Traits\InteractWithHttpClient;
use App\Models\Wechat\Kernel\Traits\InteractWithServerRequest;
use App\Models\Wechat\WaiSongBang\Contracts\Merchant;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class Application implements \App\Models\Wechat\WaiSongBang\Contracts\Application
{
    use InteractWithConfig;
    use InteractWithHttpClient;
    use InteractWithServerRequest;
    protected ?ServerInterface $server = null;
    protected ?HttpClientInterface $client = null;
    protected ?Merchant $merchant = null;

    /**
     * @throws \App\Models\Wechat\Kernel\Exceptions\InvalidArgumentException
     * @throws \App\Models\Wechat\Kernel\Exceptions\InvalidConfigException

    /**
     * @throws \App\Models\Wechat\Kernel\Exceptions\InvalidArgumentException
     * @throws \App\Models\Wechat\Kernel\Exceptions\InvalidConfigException
     */
    public function getMerchant(): Merchant
    {
        if (!$this->merchant) {
            $this->merchant = new \App\Models\Wechat\WaiSongBang\Merchant(
                mchId: $this->config['mch_id'],
                /** @phpstan-ignore-line */
                appkey: (string) $this->config['appkey'],
                /** @phpstan-ignore-line */
                secretKey: (string) $this->config['secretKey'],
                thirdPartnerId: $this->config['thirdPartnerId'] ?: 0                /** @phpstan-ignore-line */
                /** @phpstan-ignore-line */
            );
        }

        return $this->merchant;
    }

    /**
     * @throws \ReflectionException
     * @throws \App\Models\Wechat\Kernel\Exceptions\InvalidArgumentException
     * @throws \Throwable
     */

    public function setServer(ServerInterface $server): static
    {
        $this->server = $server;

        return $this;
    }

    public function setConfig(ConfigInterface $config): static
    {
        $this->config = $config;

        return $this;
    }

    public function getConfig(): ConfigInterface
    {
        return $this->config;
    }

    /**
     * @throws \App\Models\Wechat\Kernel\Exceptions\InvalidArgumentException
     * @throws \App\Models\Wechat\Kernel\Exceptions\InvalidConfigException
     */
    public function getClient(): HttpClientInterface
    {
        return $this->client ?? $this->client = (new Client(
            $this->getMerchant(),
            $this->getHttpClient(),
            (array) $this->config->get('http', [])
        ))->setPresets($this->config->all());
    }

    public function setClient(HttpClientInterface $client): static
    {
        $this->client = $client;

        return $this;
    }
}
