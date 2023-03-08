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

use PHPUnit\Framework\TestCase;
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

    public function testClientGetAll()
    {
        $client = Client::build('my_token');

        $response = $client->getAll(8, 16, 'foobar');

        $this->assertEquals('Bearer my_token', $response->getHeaders()['Authorization']);
        $this->assertEquals('application/json', $response->getHeaders()['Accept']);
        $this->assertEquals('SDKgen Client v1.0', $response->getHeaders()['User-Agent']);
        $this->assertEquals('GET', $response->getMethod());
        $this->assertEquals(['startIndex' => 8, 'count' => 16, 'search' => 'foobar'], $response->getArgs()->getProperties());
        $this->assertEquals(null, $response->getJson());
    }

    public function testClientCreate()
    {
        $client = Client::build('my_token');

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
        $payload->setArrayScalar(['foo', 'bar']);
        $payload->setArrayObject([$objectFoo, $objectBar]);
        $payload->setMapScalar($mapScalar);
        $payload->setMapObject($mapObject);
        $payload->setObject($objectFoo);

        $response = $client->create($payload);

        $this->assertEquals('Bearer my_token', $response->getHeaders()['Authorization']);
        $this->assertEquals('application/json', $response->getHeaders()['Accept']);
        $this->assertEquals('SDKgen Client v1.0', $response->getHeaders()['User-Agent']);
        $this->assertEquals('POST', $response->getMethod());
        $this->assertEquals([], $response->getArgs()->getProperties());
        $this->assertEquals($payload, $response->getJson());
    }

    public function testClientUpdate()
    {
        $client = Client::build('my_token');

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
        $payload->setArrayScalar(['foo', 'bar']);
        $payload->setArrayObject([$objectFoo, $objectBar]);
        $payload->setMapScalar($mapScalar);
        $payload->setMapObject($mapObject);
        $payload->setObject($objectFoo);

        $response = $client->update(1, $payload);

        $this->assertEquals('Bearer my_token', $response->getHeaders()['Authorization']);
        $this->assertEquals('application/json', $response->getHeaders()['Accept']);
        $this->assertEquals('SDKgen Client v1.0', $response->getHeaders()['User-Agent']);
        $this->assertEquals('PUT', $response->getMethod());
        $this->assertEquals([], $response->getArgs()->getProperties());
        $this->assertEquals($payload, $response->getJson());
    }

    public function testClientPatch()
    {
        $client = Client::build('my_token');

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
        $payload->setArrayScalar(['foo', 'bar']);
        $payload->setArrayObject([$objectFoo, $objectBar]);
        $payload->setMapScalar($mapScalar);
        $payload->setMapObject($mapObject);
        $payload->setObject($objectFoo);

        $response = $client->patch(1, $payload);

        $this->assertEquals('Bearer my_token', $response->getHeaders()['Authorization']);
        $this->assertEquals('application/json', $response->getHeaders()['Accept']);
        $this->assertEquals('SDKgen Client v1.0', $response->getHeaders()['User-Agent']);
        $this->assertEquals('PATCH', $response->getMethod());
        $this->assertEquals([], $response->getArgs()->getProperties());
        $this->assertEquals($payload, $response->getJson());
    }

    public function testClientDelete()
    {
        $client = Client::build('my_token');

        $response = $client->delete(1);

        $this->assertEquals('Bearer my_token', $response->getHeaders()['Authorization']);
        $this->assertEquals('application/json', $response->getHeaders()['Accept']);
        $this->assertEquals('SDKgen Client v1.0', $response->getHeaders()['User-Agent']);
        $this->assertEquals('DELETE', $response->getMethod());
        $this->assertEquals([], $response->getArgs()->getProperties());
        $this->assertEquals(null, $response->getJson());
    }
}