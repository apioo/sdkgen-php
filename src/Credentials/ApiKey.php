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

namespace Sdkgen\Client\Credentials;

use Sdkgen\Client\CredentialsInterface;

/**
 * ApiKey
 *
 * @author Christoph Kappestein <christoph.kappestein@gmail.com>
 * @link   https://sdkgen.app
 */
class ApiKey implements CredentialsInterface
{
    private string $token;
    private string $name;
    private string $in;

    public function __construct(string $token, string $name, string $in)
    {
        $this->token = $token;
        $this->name = $name;
        $this->in = $in;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getIn(): string
    {
        return $this->in;
    }
}