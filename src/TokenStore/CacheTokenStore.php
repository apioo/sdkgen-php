<?php
/*
 * SDKgen is a tool to automatically generate high quality SDKs.
 * For the current version and information visit <https://sdkgen.app>
 *
 * Copyright 2020-2021 Christoph Kappestein <christoph.kappestein@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sdkgen\Client\TokenStore;

use Psr\SimpleCache\CacheInterface;
use Sdkgen\Client\AccessToken;
use Sdkgen\Client\TokenStoreInterface;

/**
 * CacheTokenStore
 *
 * @author Christoph Kappestein <christoph.kappestein@gmail.com>
 * @link   https://sdkgen.app
 */
class CacheTokenStore implements TokenStoreInterface
{
    private CacheInterface $cache;
    private string $cacheKey;

    public function __construct(CacheInterface $cache, string $cacheKey = 'sdkgen_access_token')
    {
        $this->cache = $cache;
        $this->cacheKey = $cacheKey;
    }

    public function get(): ?AccessToken
    {
        return $this->cache->get($this->cacheKey) ?: null;
    }

    public function persist(AccessToken $token): void
    {
        $this->cache->set($this->cacheKey, $token);
    }
}
