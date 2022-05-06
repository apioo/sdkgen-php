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
 * MemoryTokenStore
 *
 * @author Christoph Kappestein <christoph.kappestein@gmail.com>
 * @link   https://sdkgen.app
 */
class MemoryTokenStore implements TokenStoreInterface
{
    private ?AccessToken $token = null;

    public function get(): ?AccessToken
    {
        return $this->token;
    }

    public function persist(AccessToken $token): void
    {
        $this->token = $token;
    }
}
