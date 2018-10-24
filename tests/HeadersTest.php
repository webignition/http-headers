<?php

namespace webignition\HttpHeaders\Tests;

use webignition\HttpHeaders\Headers;

class HeadersTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider createDataProvider
     *
     * @param array $headersArray
     * @param array $expectedHeaders
     */
    public function testCreate(array $headersArray, array $expectedHeaders)
    {
        $headers = new Headers($headersArray);

        $this->assertEquals($expectedHeaders, $headers->toArray());
    }

    public function createDataProvider(): array
    {
        return [
            'empty' => [
                'headersArray' => [],
                'expectedHeaders' => [],
            ],
            'all invalid' => [
                'headersArray' => [
                    'boolean' => true,
                    'array' => [],
                    'object' => (object) [],
                ],
                'expectedHeaders' => [],
            ],
            'all valid' => [
                'headersArray' => [
                    'foo' => 'bar',
                ],
                'expectedHeaders' => [
                    'foo' => 'bar',
                ],
            ],
            'some valid' => [
                'headersArray' => [
                    'foo' => 'bar',
                    'boolean' => true,
                    'array' => [],
                    'object' => (object) [],
                ],
                'expectedHeaders' => [
                    'foo' => 'bar',
                ],
            ],
        ];
    }

    /**
     * @dataProvider withHeaderDataProvider
     *
     * @param array $existingHeaders
     * @param string $key
     * @param $value
     * @param array $expectedHeaders
     */
    public function testWithHeader(array $existingHeaders, string $key, $value, array $expectedHeaders)
    {
        $headers = new Headers($existingHeaders);
        $updatedHeaders = $headers->withHeader($key, $value);

        $this->assertNotSame($headers, $updatedHeaders);

        $this->assertEquals($existingHeaders, $headers->toArray());
        $this->assertEquals($expectedHeaders, $updatedHeaders->toArray());
    }

    public function withHeaderDataProvider(): array
    {
        return [
            'no existing headers' => [
                'existingHeaders' => [],
                'key' => 'foo',
                'value' => 'bar',
                'expectedHeaders' => [
                    'foo' => 'bar',
                ],
            ],
            'no existing headers, add invalid' => [
                'existingHeaders' => [],
                'key' => 'foo',
                'value' => true,
                'expectedHeaders' => [],
            ],
            'has existing headers, add invalid' => [
                'existingHeaders' => [
                    'foo' => 'bar',
                ],
                'key' => 'foo',
                'value' => true,
                'expectedHeaders' => [
                    'foo' => 'bar',
                ],
            ],
            'has existing headers, add new valid' => [
                'existingHeaders' => [
                    'foo' => 'bar',
                ],
                'key' => 'fizz',
                'value' => 'buzz',
                'expectedHeaders' => [
                    'foo' => 'bar',
                    'fizz' => 'buzz',
                ],
            ],
            'has existing headers, overwrite' => [
                'existingHeaders' => [
                    'foo' => 'bar',
                ],
                'key' => 'foo',
                'value' => 'buzz',
                'expectedHeaders' => [
                    'foo' => 'buzz',
                ],
            ],
            'headers are sorted' => [
                'existingHeaders' => [
                    'zebra' => 'stripey monochrome horse',
                ],
                'key' => 'ant',
                'value' => 'tiny insect',
                'expectedHeaders' => [
                    'ant' => 'tiny insect',
                    'zebra' => 'stripey monochrome horse',
                ],
            ],
        ];
    }

    /**
     * @dataProvider createHashDataProvider
     *
     * @param array $existingHeaders
     * @param array $newHeaders
     * @param string $expectedHash
     */
    public function testCreateHash(array $existingHeaders, array $newHeaders, string $expectedHash)
    {
        $headers = new Headers($existingHeaders);

        foreach ($newHeaders as $key => $value) {
            $headers = $headers->withHeader($key, $value);
        }

        $this->assertEquals($expectedHash, $headers->createHash());
    }

    public function createHashDataProvider(): array
    {
        return [
            'no existing headers, no new headers' => [
                'existingHeaders' => [],
                'newHeaders' => [],
                'expectedHash' => 'd751713988987e9331980363e24189ce',
            ],
            'has existing headers, no new headers' => [
                'existingHeaders' => [
                    'foo' => 'bar',
                ],
                'newHeaders' => [],
                'expectedHash' => '9bb58f26192e4ba00f01e2e7b136bbd8',
            ],
            'no existing headers, has new headers' => [
                'existingHeaders' => [],
                'newHeaders' => [
                    'foo' => 'bar',
                ],
                'expectedHash' => '9bb58f26192e4ba00f01e2e7b136bbd8',
            ],
            'has existing headers, has new headers' => [
                'existingHeaders' => [
                    'foo' => 'bar',
                ],
                'newHeaders' => [
                    'fizz' => 'buzz',
                ],
                'expectedHash' => '9ec0f73790c61c71905e8a3dc7dacbcc',
            ],
            'add order does not affect hash' => [
                'existingHeaders' => [
                    'fizz' => 'buzz',
                ],
                'newHeaders' => [
                    'foo' => 'bar',
                ],
                'expectedHash' => '9ec0f73790c61c71905e8a3dc7dacbcc',
            ],
        ];
    }

    public function testGet()
    {
        $headers = new Headers([
            'a' => 1,
            'foo' => 'bar',
        ]);

        $this->assertSame(1, $headers->get('a'));
        $this->assertSame('bar', $headers->get('foo'));
        $this->assertNull($headers->get('not-set'));
    }

    /**
     * @dataProvider getLastModifiedDataProvider
     *
     * @param Headers $headers
     * @param \DateTime|null $expectedLastModified
     */
    public function testGetLastModified(Headers $headers, ?\DateTime $expectedLastModified)
    {
        $this->assertEquals($expectedLastModified, $headers->getLastModified());
    }

    public function getLastModifiedDataProvider(): array
    {
        return [
            'no last-modified' => [
                'headers' => new Headers(),
                'expectedLastModified' => null,
            ],
            'has last-modified, invalid' => [
                'headers' => new Headers([
                    'last-modified' => 'foo',
                ]),
                'expectedLastModified' => null,
            ],
            'has last-modified, valid' => [
                'headers' => new Headers([
                    'last-modified' => 'Wed, 21 Oct 2015 07:28:00 GMT',
                ]),
                'expectedLastModified' => new \DateTime('Wed, 21 Oct 2015 07:28:00 GMT'),
            ],
        ];
    }

    /**
     * @dataProvider getAgeDataProvider
     *
     * @param Headers $headers
     * @param \DateTime $now
     * @param int|null $expectedAge
     */
    public function testGetAge(Headers $headers, ?\DateTime $now, ?int $expectedAge)
    {
        $this->assertSame($expectedAge, $headers->getAge($now));
    }

    public function getAgeDataProvider(): array
    {
        return [
            'no last-modified, no now' => [
                'headers' => new Headers(),
                'now' => null,
                'expectedAge' => null,
            ],
            'no last-modified, has now' => [
                'headers' => new Headers(),
                'now' => new \DateTime(),
                'expectedAge' => null,
            ],
            'has last-modified, invalid' => [
                'headers' => new Headers([
                    'last-modified' => 'foo',
                ]),
                'now' => new \DateTime(),
                'expectedAge' => null,
            ],
            'has last-modified, valid' => [
                'headers' => new Headers([
                    'last-modified' => 'Wed, 21 Oct 2015 07:28:00 GMT',
                ]),
                'now' => new \DateTime('Wed, 21 Oct 2015 07:29:31 GMT'),
                'expectedAge' => 91,
            ],
        ];
    }

    public function testGetAgeNow()
    {
        $headers = new Headers([
            'last-modified' => 'Wed, 21 Oct 2015 07:28:00 GMT',
        ]);

        $this->assertGreaterThan(0, $headers->getAge());
    }

    /**
     * @dataProvider getExpiresDataProvider
     *
     * @param Headers $headers
     * @param int|float|null $expectedExpires
     */
    public function testGetExpires(Headers $headers, $expectedExpires)
    {
        if ($expectedExpires instanceof \DateTime) {
            $this->assertEquals($expectedExpires, $headers->getExpires());
        } else {
            $this->assertSame($expectedExpires, $headers->getExpires());
        }
    }

    public function getExpiresDataProvider(): array
    {
        return [
            'no expires' => [
                'headers' => new Headers(),
                'expectedExpires' => null,
            ],
            'invalid expires (0)' => [
                'headers' => new Headers([
                    'expires' => 0,
                ]),
                'expectedExpires' => -INF,
            ],
            'invalid expires (foo)' => [
                'headers' => new Headers([
                    'expires' => 'foo',
                ]),
                'expectedExpires' => -INF,
            ],
            'valid' => [
                'headers' => new Headers([
                    'expires' => 'Wed, 21 Oct 2015 07:28:00 GMT',
                ]),
                'expectedExpires' => new \DateTime('Wed, 21 Oct 2015 07:28:00 GMT'),
            ],
        ];
    }

    /**
     * @dataProvider hasExpiredDataProvider
     *
     * @param Headers $headers
     * @param \DateTime $now
     * @param bool $expectedHasExpired
     */
    public function testHasExpired(Headers $headers, \DateTime $now, bool $expectedHasExpired)
    {
        $this->assertSame($expectedHasExpired, $headers->hasExpired($now));
    }

    public function hasExpiredDataProvider(): array
    {
        return [
            'no expires' => [
                'headers' => new Headers(),
                'now' => new \DateTime(),
                'expectedHasExpired' => false,
            ],
            'invalid expires (0)' => [
                'headers' => new Headers([
                    'expires' => 0,
                ]),
                'now' => new \DateTime(),
                'expectedHasExpired' => true,
            ],
            'invalid expires (foo)' => [
                'headers' => new Headers([
                    'expires' => 'foo',
                ]),
                'now' => new \DateTime(),
                'expectedHasExpired' => true,
            ],
            'expires in the past and cache-control: max-age' => [
                'headers' => new Headers([
                    'expires' => 'Wed, 21 Oct 2015 07:28:00 GMT',
                    'cache-control' => 'max-age=1'
                ]),
                'now' => new \DateTime(),
                'expectedHasExpired' => false,
            ],
            'expires in the past and cache-control: s-maxage' => [
                'headers' => new Headers([
                    'expires' => 'Wed, 21 Oct 2015 07:28:00 GMT',
                    'cache-control' => 's-maxage=1'
                ]),
                'now' => new \DateTime(),
                'expectedHasExpired' => false,
            ],
            'expires in the past' => [
                'headers' => new Headers([
                    'expires' => 'Wed, 21 Oct 2015 07:28:00 GMT',
                ]),
                'now' => new \DateTime('Wed, 21 Oct 2015 07:28:01 GMT'),
                'expectedHasExpired' => true,
            ],
            'expires now' => [
                'headers' => new Headers([
                    'expires' => 'Wed, 21 Oct 2015 07:28:00 GMT',
                ]),
                'now' => new \DateTime('Wed, 21 Oct 2015 07:28:00 GMT'),
                'expectedHasExpired' => true,
            ],
            'expires in the future' => [
                'headers' => new Headers([
                    'expires' => 'Wed, 21 Oct 2015 07:28:00 GMT',
                ]),
                'now' => new \DateTime('Wed, 21 Oct 2015 07:27:59 GMT'),
                'expectedHasExpired' => false,
            ],
        ];
    }

    public function testHasExpiresNow()
    {
        $headers = new Headers([
            'expires' => 'Wed, 21 Oct 3000 07:28:00 GMT',
        ]);

        $this->assertFalse($headers->hasExpired());
    }
}
