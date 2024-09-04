<?php

declare(strict_types=1);

/**
 * @package     Localzet OAuth
 * @link        https://github.com/localzet/OAuth
 *
 * @author      Ivan Zorin <creator@localzet.com>
 * @copyright   Copyright (c) 2018-2024 Localzet Group
 * @license     https://www.gnu.org/licenses/agpl-3.0 GNU Affero General Public License v3.0
 *
 *              This program is free software: you can redistribute it and/or modify
 *              it under the terms of the GNU Affero General Public License as published
 *              by the Free Software Foundation, either version 3 of the License, or
 *              (at your option) any later version.
 *
 *              This program is distributed in the hope that it will be useful,
 *              but WITHOUT ANY WARRANTY; without even the implied warranty of
 *              MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *              GNU Affero General Public License for more details.
 *
 *              You should have received a copy of the GNU Affero General Public License
 *              along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 *              For any questions, please contact <creator@localzet.com>
 */

namespace localzet\OAuth\Provider;

use localzet\OAuth\Adapter\OAuth2;
use localzet\OAuth\Exception\InvalidApplicationCredentialsException;
use RuntimeException;

class ESIA extends OAuth2
{
    protected $authorizeUrl = 'https://esia-portal1.test.gosuslugi.ru/aas/oauth2/ac';

    protected array $scopes = ['fullname', 'email', 'openid'];
    private string $state;
    private string $timestamp;

    protected function configure()
    {
        $this->scopes = $this->config->exists('scopes') ? $this->config->get('scopes') : $this->scopes;
        $this->scope = implode(' ', $this->scopes);

        $this->timestamp = date('Y.m.d H:i:s O');
        $this->state = sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0x0fff) | 0x4000,
            random_int(0, 0x3fff) | 0x8000,
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff)
        );


        $this->clientId = $this->config->filter('keys')->get('id');
        $this->clientSecret = $this->signature($this->scope . $this->timestamp . $this->clientId . $this->state);

        if (!$this->clientId || !$this->clientSecret) {
            throw new InvalidApplicationCredentialsException(
                'Ваш идентификатор приложения требуется для подключения к ' . $this->providerId
            );
        }

        if ($this->config->exists('tokens')) {
            $this->setAccessToken($this->config->get('tokens'));
        }

        if ($this->config->exists('supportRequestState')) {
            $this->supportRequestState = $this->config->get('supportRequestState');
        }

        $this->setCallback($this->config->get('callback'));
        $this->setApiEndpoints($this->config->get('endpoints'));
    }

    protected function signature(string $message)
    {
        try {
            try {
                set_error_handler(function (int $errno, string $errstr) {
                    throw new RuntimeException("Не могу прочитать сертификат ($errno): $errstr");
                });
                $cert = openssl_x509_read(file_get_contents($this->config->filter('keys')->get('cert')));
            } finally {
                restore_error_handler();
                if (!is_resource($cert)) throw new RuntimeException(openssl_error_string());
            }

            $privateKey = openssl_pkey_get_private(file_get_contents($this->config->filter('keys')->get('key')), $this->config->filter('keys')->get('key_pass'));
            if (!is_resource($privateKey)) {
                throw new RuntimeException("Не могу прочитать приватный ключ");
            }

            $messageFile = tempnam(sys_get_temp_dir(), 'messageFile');
            $signFile = tempnam(sys_get_temp_dir(), 'signFile');
            file_put_contents($messageFile, $message);

            try {
                set_error_handler(function (int $errno, string $errstr) {
                    throw new RuntimeException("Не могу прочитать ключ ($errno): $errstr");
                });
                $signResult = openssl_pkcs7_sign($messageFile, $signFile, $cert, $privateKey, [], PKCS7_DETACHED | PKCS7_BINARY | PKCS7_NOATTR);
            } finally {
                restore_error_handler();
                if (!$signResult) throw new RuntimeException(openssl_error_string());
            }

            return base64_decode(explode("\n\n", file_get_contents($signFile))[3]);
        } finally {
            unlink($signFile);
            unlink($messageFile);
        }
    }

    protected function initialize()
    {
        parent::initialize();

        $this->AuthorizeUrlParameters += [
            'access_type' => 'online',
            'timestamp' => $this->timestamp,
            'state' => $this->state,
            'client_secret' => $this->clientSecret,
        ];

        if ($this->isRefreshTokenAvailable()) {
            $this->tokenRefreshParameters += [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret
            ];
        }
    }
}
