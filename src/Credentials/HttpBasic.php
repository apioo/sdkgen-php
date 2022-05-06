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

namespace Sdkgen\Client\Credentials;

use Sdkgen\Client\CredentialsInterface;

/**
 * HttpBasic
 *
 * @author Christoph Kappestein <christoph.kappestein@gmail.com>
 * @link   https://sdkgen.app
 */
class HttpBasic implements CredentialsInterface
{
    private string $userName;
    private string $password;

    public function __construct(string $userName, string $password)
    {
        $this->userName = $userName;
        $this->password = $password;
    }

    public function getUserName(): string
    {
        return $this->userName;
    }

    public function getPassword(): string
    {
        return $this->password;
    }
}