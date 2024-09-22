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

namespace Sdkgen\Client\Tests;

use GuzzleHttp\Psr7\Stream;
use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\TestCase;
use PSX\DateTime\LocalDate;
use PSX\DateTime\LocalDateTime;
use PSX\DateTime\LocalTime;
use PSX\Http\Stream\StringStream;
use Sdkgen\Client\Multipart;
use Sdkgen\Client\Tests\Generated\Client;
use Sdkgen\Client\Tests\Generated\TestMapObject;
use Sdkgen\Client\Tests\Generated\TestMapScalar;
use Sdkgen\Client\Tests\Generated\TestObject;
use Sdkgen\Client\Tests\Generated\TestRequest;

/**
 * IntegrationTest
 *
 * @author Christoph Kappestein <christoph.kappestein@gmail.com>
 * @link   https://sdkgen.app
 */
class IntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $handle = fsockopen('127.0.0.1', 8081, $errorCode, $errorMessage, 3);
        if (!is_resource($handle)) {
            $this->markTestSkipped();
        }
    }

    public function testClientGetAll(): void
    {
        $client = Client::build('my_token');

        $response = $client->product()->getAll(8, 16, 'foobar');

        $this->assertEquals('Bearer my_token', $response->getHeaders()?->get('Authorization'));
        $this->assertEquals('application/json', $response->getHeaders()?->get('Accept'));
        $this->assertEquals('SDKgen Client v1.0', $response->getHeaders()?->get('User-Agent'));
        $this->assertEquals('GET', $response->getMethod());
        $this->assertEquals(['startIndex' => 8, 'count' => 16, 'search' => 'foobar'], $response->getArgs()->getAll());
        $this->assertEquals(null, $response->getJson());
    }

    public function testClientCreate(): void
    {
        $client = Client::build('my_token');

        $payload = $this->newPayload();
        $response = $client->product()->create($payload);

        $this->assertEquals('Bearer my_token', $response->getHeaders()?->get('Authorization'));
        $this->assertEquals('application/json', $response->getHeaders()?->get('Accept'));
        $this->assertEquals('SDKgen Client v1.0', $response->getHeaders()?->get('User-Agent'));
        $this->assertEquals('POST', $response->getMethod());
        $this->assertEquals([], $response->getArgs()->getAll());
        $this->assertEquals($payload, $response->getJson());
    }

    public function testClientUpdate(): void
    {
        $client = Client::build('my_token');

        $payload = $this->newPayload();
        $response = $client->product()->update(1, $payload);

        $this->assertEquals('Bearer my_token', $response->getHeaders()?->get('Authorization'));
        $this->assertEquals('application/json', $response->getHeaders()?->get('Accept'));
        $this->assertEquals('SDKgen Client v1.0', $response->getHeaders()?->get('User-Agent'));
        $this->assertEquals('PUT', $response->getMethod());
        $this->assertEquals([], $response->getArgs()->getAll());
        $this->assertEquals($payload, $response->getJson());
    }

    public function testClientPatch(): void
    {
        $client = Client::build('my_token');

        $payload = $this->newPayload();
        $response = $client->product()->patch(1, $payload);

        $this->assertEquals('Bearer my_token', $response->getHeaders()?->get('Authorization'));
        $this->assertEquals('application/json', $response->getHeaders()?->get('Accept'));
        $this->assertEquals('SDKgen Client v1.0', $response->getHeaders()?->get('User-Agent'));
        $this->assertEquals('PATCH', $response->getMethod());
        $this->assertEquals([], $response->getArgs()->getAll());
        $this->assertEquals($payload, $response->getJson());
    }

    public function testClientDelete(): void
    {
        $client = Client::build('my_token');

        $response = $client->product()->delete(1);

        $this->assertEquals('Bearer my_token', $response->getHeaders()?->get('Authorization'));
        $this->assertEquals('application/json', $response->getHeaders()?->get('Accept'));
        $this->assertEquals('SDKgen Client v1.0', $response->getHeaders()?->get('User-Agent'));
        $this->assertEquals('DELETE', $response->getMethod());
        $this->assertEquals([], $response->getArgs()->getAll());
        $this->assertEquals(null, $response->getJson());
    }

    public function testClientBinary(): void
    {
        $client = Client::build('my_token');

        $payload = new StringStream('foobar');

        $response = $client->product()->binary($payload);

        $this->assertEquals('Bearer my_token', $response->getHeaders()?->get('Authorization'));
        $this->assertEquals('application/json', $response->getHeaders()?->get('Accept'));
        $this->assertEquals('SDKgen Client v1.0', $response->getHeaders()?->get('User-Agent'));
        $this->assertEquals('POST', $response->getMethod());
        $this->assertEquals('foobar', $response->getData());
    }

    public function testClientForm(): void
    {
        $client = Client::build('my_token');

        $response = $client->product()->form(['foo' => 'bar']);

        $this->assertEquals('Bearer my_token', $response->getHeaders()?->get('Authorization'));
        $this->assertEquals('application/json', $response->getHeaders()?->get('Accept'));
        $this->assertEquals('SDKgen Client v1.0', $response->getHeaders()?->get('User-Agent'));
        $this->assertEquals('POST', $response->getMethod());
        $this->assertEquals(['foo' => 'bar'], $response->getForm()?->getAll());
    }

    public function testClientJson(): void
    {
        $client = Client::build('my_token');

        $response = $client->product()->json(['string' => 'bar']);

        $this->assertEquals('Bearer my_token', $response->getHeaders()?->get('Authorization'));
        $this->assertEquals('application/json', $response->getHeaders()?->get('Accept'));
        $this->assertEquals('SDKgen Client v1.0', $response->getHeaders()?->get('User-Agent'));
        $this->assertEquals('POST', $response->getMethod());
        $this->assertEquals('bar', $response->getJson()?->getString());
    }

    public function testClientMultipart(): void
    {
        $client = Client::build('my_token');

        $multipart = new Multipart();
        $multipart->add('foo', Utils::tryFopen(__DIR__ . '/upload.txt', 'r'));

        $response = $client->product()->multipart($multipart);

        $this->assertEquals('Bearer my_token', $response->getHeaders()?->get('Authorization'));
        $this->assertEquals('application/json', $response->getHeaders()?->get('Accept'));
        $this->assertEquals('SDKgen Client v1.0', $response->getHeaders()?->get('User-Agent'));
        $this->assertEquals('POST', $response->getMethod());
        $this->assertEquals(['foo' => 'foobar'], $response->getFiles()?->getAll());
    }

    public function testClientText(): void
    {
        $client = Client::build('my_token');

        $response = $client->product()->text('foobar');

        $this->assertEquals('Bearer my_token', $response->getHeaders()?->get('Authorization'));
        $this->assertEquals('application/json', $response->getHeaders()?->get('Accept'));
        $this->assertEquals('SDKgen Client v1.0', $response->getHeaders()?->get('User-Agent'));
        $this->assertEquals('POST', $response->getMethod());
        $this->assertEquals('foobar', $response->getData());
    }

    public function testClientXml(): void
    {
        $client = Client::build('my_token');

        $response = $client->product()->xml('<foo>bar</foo>');

        $this->assertEquals('Bearer my_token', $response->getHeaders()?->get('Authorization'));
        $this->assertEquals('application/json', $response->getHeaders()?->get('Accept'));
        $this->assertEquals('SDKgen Client v1.0', $response->getHeaders()?->get('User-Agent'));
        $this->assertEquals('POST', $response->getMethod());
        $this->assertEquals('<foo>bar</foo>', $response->getData());
    }

    private function newPayload(): TestRequest
    {
        $objectFoo = new TestObject();
        $objectFoo->setId(1);
        $objectFoo->setName('foo');

        $objectBar = new TestObject();
        $objectBar->setId(2);
        $objectBar->setName('bar');

        $mapScalar = new TestMapScalar();
        $mapScalar['foo'] = 'bar';
        $mapScalar['bar'] = 'foo';

        $mapObject = new TestMapObject();
        $mapObject['foo'] = $objectFoo;
        $mapObject['bar'] = $objectBar;

        $payload = new TestRequest();
        $payload->setInt(1337);
        $payload->setFloat(13.37);
        $payload->setString('foobar');
        $payload->setBool(true);
        $payload->setDateString(LocalDate::of(2024, 9, 22));
        $payload->setDateTimeString(LocalDateTime::of(2024, 9, 22, 10, 9, 0));
        $payload->setTimeString(LocalTime::of(10, 9, 0));
        $payload->setArrayScalar(['foo', 'bar']);
        $payload->setArrayObject([$objectFoo, $objectBar]);
        $payload->setMapScalar($mapScalar);
        $payload->setMapObject($mapObject);
        $payload->setObject($objectFoo);

        return $payload;
    }
}