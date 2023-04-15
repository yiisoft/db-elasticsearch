<?php

declare(strict_types=1);

namespace Yiisoft\Elasticsearch\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Elasticsearch\Connection;
use Yiisoft\Elasticsearch\Tests\Support\TestTrait;

final class ElasticsearchConnectionTest extends TestCase
{
    use TestTrait;

    public function testOpen()
    {
        $db = new Connection();
        $db->addNodeValue('http_address', 'inet[/127.0.0.1:9200]');

        $this->assertFalse($db->isActive());

        $db->open();

        $version = match ($db->getNodeValue()) {
            '8.1.3' => '8.1.3',
            default => '7.17.0',
        };

        $this->assertTrue($db->isActive());
        $this->assertNotEmpty($db->getNodeValue('name'));
        $this->assertSame('127.0.0.1', $db->getNodeValue('host'));
        $this->assertSame($version, $db->getNodeValue('version'));
        $this->assertSame('127.0.0.1:9200', $db->getNodeValue('http_address'));
    }
}
