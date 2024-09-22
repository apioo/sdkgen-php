<?php
/*
 * SDKgen is a tool to automatically generate high quality SDKs.
 * For the current version and information visit <https://sdkgen.app>
 *
 * Copyright 2020-2023 Christoph Kappestein <christoph.kappestein@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sdkgen\Client;

use Psr\Http\Message\StreamInterface;

/**
 * Multipart
 *
 * @author Christoph Kappestein <christoph.kappestein@gmail.com>
 * @link   https://sdkgen.app
 */
class Multipart
{
    private array $parts = [];

    /**
     * @param StreamInterface|resource|string $contents
     */
    public function add(string $name, mixed $contents, ?string $fileName = null, ?array $headers = null): void
    {
        $this->parts[] = [
            'name' => $name,
            'contents' => $contents,
            'headers' => $headers,
            'filename' => $fileName,
        ];
    }

    public function getParts(): array
    {
        return $this->parts;
    }
}
