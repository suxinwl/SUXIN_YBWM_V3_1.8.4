<?php

namespace App\Models\Wechat\Kernel\Traits;

use App\Models\Wechat\Kernel\Encryptor;
use App\Models\Wechat\Kernel\Exceptions\BadRequestException;
use App\Models\Wechat\Kernel\Message;
use App\Models\Wechat\Kernel\Support\Xml;

trait DecryptXmlMessage
{
    /**
     * @throws \App\Models\Wechat\Kernel\Exceptions\RuntimeException
     * @throws \App\Models\Wechat\Kernel\Exceptions\BadRequestException
     */
    public function decryptMessage(Message $message, Encryptor $encryptor, string $signature, int | string $timestamp, string $nonce): Message
    {
        $ciphertext = $message->Encrypt;

        $this->validateSignature($encryptor->getToken(), $ciphertext, $signature, $timestamp, $nonce);

        $message->merge(Xml::parse(
            $encryptor->decrypt(
                ciphertext: $ciphertext,
                msgSignature: $signature,
                nonce: $nonce,
                timestamp: $timestamp
            )
        ) ?? []);

        return $message;
    }

    /**
     * @throws \App\Models\Wechat\Kernel\Exceptions\BadRequestException
     */
    protected function validateSignature(string $token, string $ciphertext, string $signature, int | string $timestamp, string $nonce): void
    {
        if (empty($signature)) {
            throw new BadRequestException('Request signature must not be empty.');
        }

        $params = [$token, $timestamp, $nonce, $ciphertext];

        sort($params, SORT_STRING);

        if ($signature !== sha1(implode($params))) {
            throw new BadRequestException('Invalid request signature.');
        }
    }
}
