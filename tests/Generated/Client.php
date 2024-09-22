<?php
/**
 * Client automatically generated by SDKgen please do not edit this file manually
 * @see https://sdkgen.app
 */

namespace Sdkgen\Client\Tests\Generated;

use GuzzleHttp\Exception\BadResponseException;
use Sdkgen\Client\ClientAbstract;
use Sdkgen\Client\Credentials;
use Sdkgen\Client\CredentialsInterface;
use Sdkgen\Client\Exception\ClientException;
use Sdkgen\Client\Exception\Payload;
use Sdkgen\Client\Exception\UnknownStatusCodeException;
use Sdkgen\Client\TokenStoreInterface;

class Client extends ClientAbstract
{
    public function product(): ProductTag
    {
        return new ProductTag(
            $this->httpClient,
            $this->parser
        );
    }



    public static function build(string $token): self
    {
        return new self('http://127.0.0.1:8081', new Credentials\HttpBearer($token));
    }

    public static function buildAnonymous(): self
    {
        return new self('http://127.0.0.1:8081', new Credentials\Anonymous());
    }
}
