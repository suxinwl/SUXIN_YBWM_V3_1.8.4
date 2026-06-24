<?php

declare(strict_types=1);

namespace App\Models\Wechat\Pay\Contracts;

use Psr\Http\Message\ResponseInterface;

interface ResponseValidator
{
    /**
     * @throws \App\Models\Wechat\Kernel\Exceptions\BadResponseException if the response is not successful.
     */
    public function validate(ResponseInterface $response): void;
}
