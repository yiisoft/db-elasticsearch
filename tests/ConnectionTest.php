<?php

declare(strict_types=1);

namespace Yiisoft\Elasticsearch\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Elasticsearch\Connection;
use Yiisoft\Elasticsearch\Tests\Support\Assert;
use Yiisoft\Elasticsearch\Tests\Support\TestTrait;

final class ConnectionTest extends TestCase
{
    use TestTrait;

    public function testAuth(): void
    {
        $db = $this->getConnection();

        $db->auth(['user', 'password']);

        $this->assertSame(['user', 'password'], Assert::inaccessibleProperty($db, 'auth'));
    }

    public function testCreateUrl(): void
    {
        $db = $this->getConnection();

        $protocol = 'http';
        $httpAddress = '127.0.0.1:9200';

        $db->addNodeValue('protocol', $protocol)->addNodeValue('httpAddress', $httpAddress);

        $this->assertSame('http', $db->getNodeValue('protocol'));
        $this->assertSame('127.0.0.1:9200', $db->getNodeValue('httpAddress'));
        $this->assertEquals(
            [$protocol, $httpAddress, '_cat/indices'],
            Assert::invokeMethod($db, 'createUrl', ['_cat/indices']),
        );
        $this->assertEquals(
            [$protocol, $httpAddress, 'customer'],
            Assert::invokeMethod($db, 'createUrl', ['customer']),
        );
        $this->assertEquals(
            [$protocol, $httpAddress, 'customer/external/1'],
            Assert::invokeMethod($db, 'createUrl', [['customer', 'external', '1']])
        );
        $this->assertEquals(
            [$protocol, $httpAddress, 'customer/external/1/_update'],
            Assert::invokeMethod($db, 'createUrl', [['customer', 'external', 1, '_update']])
        );

        $db->close();
    }

    public function testCurlOptions(): void
    {
        $db = $this->getConnection();

        $db->curlOptions([CURLOPT_TIMEOUT => 10]);

        $this->assertSame(10, Assert::inaccessibleProperty($db, 'curlOptions')[CURLOPT_TIMEOUT]);
    }

    public function testDataTimeout(): void
    {
        $db = $this->getConnection();

        $db->dataTimeout(10);

        $this->assertSame(10.0, Assert::inaccessibleProperty($db, 'dataTimeout'));
    }

    public function testDefaultProtocol(): void
    {
        $db = $this->getConnection();

        $db->defaultProtocol('https');

        $this->assertSame('https', Assert::inaccessibleProperty($db, 'defaultProtocol'));
    }

    public function testDslVersion(): void
    {
        $db = $this->getConnection();

        $db->dslVersion(7);

        $this->assertSame(7, Assert::inaccessibleProperty($db, 'dslVersion'));
    }

    public function testGetClusterState(): void
    {
        $db = $this->getConnection();

        $this->assertIsArray($db->getClusterState());
    }

    public function testGetDriverName(): void
    {
        $db = $this->getConnection();

        $this->assertSame('elasticsearch', $db->getDriverName());
    }

    public function testGetDslVersion(): void
    {
        $db = $this->getConnection();

        $this->assertSame(8, $db->getDslVersion());
    }

    public function testGetNodeInfo(): void
    {
        $db = $this->getConnection();

        $this->assertIsArray($db->getNodeInfo());
    }

    public function testSerialized()
    {
        $db = $this->getConnection();

        $db->open();
        $serialized = serialize($db);
        $unserialized = unserialize($serialized);

        $this->assertInstanceOf(Connection::class, $unserialized);
    }

    public function testTimeOut(): void
    {
        $db = $this->getConnection();

        $db->timeOut(10);

        $this->assertSame(10.0, Assert::inaccessibleProperty($db, 'timeOut'));
    }
}
