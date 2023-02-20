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

namespace Sdkgen\Client;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;

/**
 * AuthenticatorFactory
 *
 * @author Christoph Kappestein <christoph.kappestein@gmail.com>
 * @link   https://sdkgen.app
 */
class HttpClientFactory
{
    private AuthenticatorInterface $authenticator;

    public function __construct(AuthenticatorInterface $authenticator)
    {
        $this->authenticator = $authenticator;
    }

    public function factory(): Client
    {
        $stack = HandlerStack::create();
        $stack->push(Middleware::mapRequest($this->authenticator));
        $stack->push(Middleware::mapRequest(function(RequestInterface $request) {
            return $request->withHeader('User-Agent', ClientAbstract::USER_AGENT);
        }));

        return new Client(['handler' => $stack]);
    }
}
