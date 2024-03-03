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
use PSX\DateTime\LocalDate;
use PSX\DateTime\LocalDateTime;
use PSX\DateTime\LocalTime;
use Sdkgen\Client\Parser;
use Sdkgen\Client\Tests\Generated\TestObject;

/**
 * ParserTest
 *
 * @author Christoph Kappestein <christoph.kappestein@gmail.com>
 * @link   https://sdkgen.app
 */
class ParserTest extends TestCase
{
    public function testUrl()
    {
        $parser = new Parser('https://api.acme.com/');

        $this->assertEquals('https://api.acme.com/foo/bar', $parser->url('/foo/bar', []));
        $this->assertEquals('https://api.acme.com/foo/foo', $parser->url('/foo/:bar', ['bar' => 'foo']));
        $this->assertEquals('https://api.acme.com/foo/foo', $parser->url('/foo/$bar<[0-9]+>', ['bar' => 'foo']));
        $this->assertEquals('https://api.acme.com/foo/foo', $parser->url('/foo/$bar', ['bar' => 'foo']));
        $this->assertEquals('https://api.acme.com/foo/foo', $parser->url('/foo/{bar}', ['bar' => 'foo']));
        $this->assertEquals('https://api.acme.com/foo/foo/bar', $parser->url('/foo/:bar/bar', ['bar' => 'foo']));
        $this->assertEquals('https://api.acme.com/foo/foo/bar', $parser->url('/foo/$bar<[0-9]+>/bar', ['bar' => 'foo']));
        $this->assertEquals('https://api.acme.com/foo/foo/bar', $parser->url('/foo/$bar/bar', ['bar' => 'foo']));
        $this->assertEquals('https://api.acme.com/foo/foo/bar', $parser->url('/foo/{bar}/bar', ['bar' => 'foo']));

        $this->assertEquals('https://api.acme.com/foo/', $parser->url('/foo/:bar', ['bar' => null]));
        $this->assertEquals('https://api.acme.com/foo/1337', $parser->url('/foo/:bar', ['bar' => 1337]));
        $this->assertEquals('https://api.acme.com/foo/13.37', $parser->url('/foo/:bar', ['bar' => 13.37]));
        $this->assertEquals('https://api.acme.com/foo/1', $parser->url('/foo/:bar', ['bar' => true]));
        $this->assertEquals('https://api.acme.com/foo/0', $parser->url('/foo/:bar', ['bar' => false]));
        $this->assertEquals('https://api.acme.com/foo/foo', $parser->url('/foo/:bar', ['bar' => 'foo']));
        $this->assertEquals('https://api.acme.com/foo/2023-02-21', $parser->url('/foo/:bar', ['bar' => LocalDate::parse('2023-02-21')]));
        $this->assertEquals('https://api.acme.com/foo/2023-02-21T19:19:00Z', $parser->url('/foo/:bar', ['bar' => LocalDateTime::parse('2023-02-21T19:19:00')]));
        $this->assertEquals('https://api.acme.com/foo/19:19:00', $parser->url('/foo/:bar', ['bar' => LocalTime::parse('19:19:00')]));
    }

    public function testQuery(): void
    {
        $parser = new Parser('https://api.acme.com/');

        $test = new TestObject();
        $test->setName("foo");

        $parameters = [
            'null' => null,
            'int' => 1337,
            'float' => 13.37,
            'true' => true,
            'false' => false,
            'string' => 'foo',
            'date' => LocalDate::parse('2023-02-21'),
            'datetime' => LocalDateTime::parse('2023-02-21T19:19:00'),
            'time' => LocalTime::parse('19:19:00'),
            'args' => $test,
        ];

        $result = $parser->query($parameters, ['args']);

        $this->assertSame('1337', $result['int']);
        $this->assertSame('13.37', $result['float']);
        $this->assertSame('1', $result['true']);
        $this->assertSame('0', $result['false']);
        $this->assertSame('foo', $result['string']);
        $this->assertSame('2023-02-21', $result['date']);
        $this->assertSame('2023-02-21T19:19:00Z', $result['datetime']);
        $this->assertSame('19:19:00', $result['time']);
        $this->assertSame('foo', $result['name']);
    }
}
