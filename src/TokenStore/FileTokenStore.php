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
 * FileTokenStore
 *
 * @author Christoph Kappestein <christoph.kappestein@gmail.com>
 * @link   https://sdkgen.app
 */
class FileTokenStore implements TokenStoreInterface
{
    private string $cacheDir;
    private string $fileName;

    public function __construct(?string $cacheDir = null, string $fileName = 'sdkgen_access_token')
    {
        $this->cacheDir = $cacheDir ?? sys_get_temp_dir();
        $this->fileName = $fileName;
    }

    public function get(): ?AccessToken
    {
        $file = $this->getFileName();
        if (is_file($file)) {
            return AccessToken::fromArray(json_decode(file_get_contents($file), true));
        } else {
            return null;
        }
    }

    public function persist(AccessToken $token): void
    {
        $file = $this->getFileName();
        file_put_contents($file, json_encode($token->toArray()));
    }

    private function getFileName(): string
    {
        return $this->cacheDir . '/' . $this->fileName . '.json';
    }
}
