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

namespace Sdkgen\Client\Authenticator;

use Psr\Http\Message\RequestInterface;
use Sdkgen\Client\AuthenticatorInterface;
use Sdkgen\Client\Credentials;

/**
 * Anonymous
 *
 * @author Christoph Kappestein <christoph.kappestein@gmail.com>
 * @link   https://sdkgen.app
 */
class Anonymous implements AuthenticatorInterface
{
    public function __construct(Credentials\Anonymous $credentials)
    {
    }

    public function __invoke(RequestInterface $request): RequestInterface
    {
        return $request;
    }
}
