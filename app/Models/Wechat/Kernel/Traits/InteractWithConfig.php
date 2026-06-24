<?php

declare(strict_types=1);

namespace App\Models\Wechat\Kernel\Traits;

use App\Models\Wechat\Kernel\Config;
use App\Models\Wechat\Kernel\Contracts\Config as ConfigInterface;

trait InteractWithConfig
{
    protected ConfigInterface $config;

    /**
     * @param array<string,mixed>|ConfigInterface $config
     * @throws \App\Models\Wechat\Kernel\Exceptions\InvalidArgumentException
     */
    public function __construct(array | ConfigInterface $config)
    {
        $this->config = \is_array($config) ? new Config($config) : $config;
    }

    public function getConfig(): ConfigInterface
    {
        return $this->config;
    }

    public function setConfig(ConfigInterface $config): static
    {
        $this->config = $config;

        return $this;
    }
}
