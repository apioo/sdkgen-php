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

namespace Sdkgen\Client\Authenticator;

use Psr\Http\Message\RequestInterface;
use Sdkgen\Client\AuthenticatorInterface;
use Sdkgen\Client\Credentials;

/**
 * HttpBasicAuthenticator
 *
 * @author Christoph Kappestein <christoph.kappestein@gmail.com>
 * @link   https://sdkgen.app
 */
class HttpBasicAuthenticator implements AuthenticatorInterface
{
    private Credentials\HttpBasic $credentials;

    public function __construct(Credentials\HttpBasic $credentials)
    {
        $this->credentials = $credentials;
    }

    public function __invoke(RequestInterface $request): RequestInterface
    {
        return $request->withHeader('Authorization', 'Basic ' . base64_encode($this->credentials->getUserName() . ':' . $this->credentials->getPassword()));
    }
}
