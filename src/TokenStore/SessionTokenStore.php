<?php
/*
 * SDKgen is a tool to automatically generate high quality SDKs.
 * For the current version and information visit <https://sdkgen.app>
 *
 * Copyright 2020-2022 Christoph Kappestein <christoph.kappestein@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sdkgen\Client\TokenStore;

use Sdkgen\Client\AccessToken;
use Sdkgen\Client\TokenStoreInterface;

/**
 * SessionTokenStore
 *
 * @author Christoph Kappestein <christoph.kappestein@gmail.com>
 * @link   https://sdkgen.app
 */
class SessionTokenStore implements TokenStoreInterface
{
    private string $sessionKey;

    public function __construct(string $sessionKey = 'sdkgen_access_token')
    {
        $this->sessionKey = $sessionKey;
    }

    public function get(): ?AccessToken
    {
        return $_SESSION[$this->sessionKey] ?? null;
    }

    public function persist(AccessToken $token): void
    {
        $_SESSION[$this->sessionKey] = $token;
    }

    public function remove(): void
    {
        if (isset($_SESSION[$this->sessionKey])) {
            unset($_SESSION[$this->sessionKey]);
        }
    }
}
