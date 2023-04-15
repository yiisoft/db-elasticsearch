<?php

declare(strict_types=1);

namespace Yiisoft\Elasticsearch\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Elasticsearch\Tests\Support\TestTrait;

final class CommandTest extends TestCase
{
    use TestTrait;

    public function testAddAliasAliasExistingIndexReturnsTrue(): void
    {
        $db = $this->getConnection();
        $command = $db->createCommand();

        $index = 'alias_test';
        $testAlias = 'test_alias';

        if ($command->indexExists($index)) {
            $command->deleteIndex($index);
        }

        $command->createIndex($index);
        $actualResult = $command->addAlias($index, $testAlias);
        $command->deleteIndex($index);

        $this->assertTrue($actualResult);

        $db->close();
    }

    public function testAddAliasAliasNonExistingIndexReturnsFalse(): void
    {
        $db = $this->getConnection();
        $command = $db->createCommand();

        $index = 'alias_test';
        $testAlias = 'test_alias';

        $actualResult = $command->addAlias($index, $testAlias);

        $this->assertFalse($actualResult);

        $db->close();
    }

    public function testAliasActionsMakingOperationOverExistingIndexReturnsTrue(): void
    {
        $db = $this->getConnection();
        $command = $db->createCommand();

        $index = 'alias_test';
        $testAlias = 'test_alias';

        if ($command->indexExists($index)) {
            $command->deleteIndex($index);
        }

        $command->createIndex($index);

        $actualResult = $command->aliasActions([
            ['add' => ['index' => $index, 'alias' => $testAlias]],
            ['remove' => ['index' => $index, 'alias' => $testAlias]],
        ]);

        $command->deleteIndex($index);

        $this->assertTrue($actualResult);

        $db->close();
    }

    public function testAliasActionsMakingOperationOverNonExistingIndexReturnsFalse(): void
    {
        $db = $this->getConnection();
        $command = $db->createCommand();

        $index = 'alias_test';
        $testAlias = 'test_alias';

        $actualResult = $command->aliasActions([
            ['add' => ['index' => $index, 'alias' => $testAlias]],
            ['remove' => ['index' => $index, 'alias' => $testAlias]],
        ]);

        $this->assertFalse($actualResult);

        $db->close();
    }

    public function testAliasExistsAliasesAreSetButWithDifferentNamereturnsFalse(): void
    {
        $db = $this->getConnection();
        $command = $db->createCommand();

        $index = 'alias_test';
        $testAlias = 'test';
        $fooAlias1 = 'alias';
        $fooAlias2 = 'alias2';

        if ($command->indexExists($index)) {
            $command->deleteIndex($index);
        }

        $command->createIndex($index);
        $command->addAlias($index, $fooAlias1);
        $command->addAlias($index, $fooAlias2);
        $aliasExists = $command->aliasExists($testAlias);
        $command->deleteIndex($index);

        $this->assertFalse($aliasExists);

        $db->close();
    }

    public function testAliasExistsAliasIsSetWithSameNameReturnsTrue(): void
    {
        $db = $this->getConnection();
        $command = $db->createCommand();

        $index = 'alias_test';
        $testAlias = 'test';

        if ($command->indexExists($index)) {
            $command->deleteIndex($index);
        }

        $command->createIndex($index);
        $command->addAlias($index, $testAlias);
        $aliasExists = $command->aliasExists($testAlias);
        $command->deleteIndex($index);

        $this->assertTrue($aliasExists);

        $db->close();
    }

    public function testAliasExistsNoAliasesSetReturnsFalse(): void
    {
        $db = $this->getConnection();
        $command = $db->createCommand();

        $testAlias = 'test';
        $aliasExists = $command->aliasExists($testAlias);

        $this->assertFalse($aliasExists);

        $db->close();
    }

    public function testDelete(): void
    {
        $db = $this->getConnection();
        $command = $db->createCommand();

        $index = 'delete_test';

        if ($command->indexExists($index)) {
            $command->deleteIndex($index);
        }

        $command->createIndex($index);
        $command->insert($index, 'test', ['name' => 'John Doe'], '1');
        $command->insert($index, 'test', ['name' => 'Jane Doe'], '2');

        $command->delete($index, 'test', '2');

        $this->assertFalse($command->exists($index, 'test', '2'));

        $command->deleteIndex($index);

        $db->close();
    }

    public function testExist(): void
    {
        $db = $this->getConnection();
        $command = $db->createCommand();

        $index = 'exist_test';

        if ($command->indexExists($index)) {
            $command->deleteIndex($index);
        }

        $command->createIndex($index);
        $command->insert($index, 'test', ['name' => 'John Doe'], '1');
        $command->insert($index, 'test', ['name' => 'Jane Doe'], '2');

        $this->assertTrue($command->exists($index, 'test', '1'));
        $this->assertFalse($command->exists($index, 'test', '5'));

        $command->deleteIndex($index);

        $db->close();
    }

    public function testGet(): void
    {
        $db = $this->getConnection();
        $command = $db->createCommand();

        $index = 'get_test';

        if ($command->indexExists($index)) {
            $command->deleteIndex($index);
        }

        $command->createIndex($index);
        $command->insert($index, 'test', ['name' => 'John Doe'], '1');
        $command->insert($index, 'test', ['name' => 'Jane Doe'], '2');

        $expected1 = match ($db->getNodeValue('version')) {
            '8.7.0' => [
                '_index' => 'get_test',
                '_id' => '1',
                '_version' => 1,
                '_seq_no' => 0,
                '_primary_term' => 1,
                'found' => true,
                '_source' => [
                    'name' => 'John Doe',
                ],
            ],
            default => [
                '_index' => 'get_test',
                '_type' => '_doc',
                '_id' => '1',
                '_version' => 1,
                '_seq_no' => 0,
                '_primary_term' => 1,
                'found' => true,
                '_source' => [
                    'name' => 'John Doe',
                ],
            ],
        };

        $expected2 = match ($db->getNodeValue('version')) {
            '8.7.0' => [
                '_index' => 'get_test',
                '_id' => '2',
                '_version' => 1,
                '_seq_no' => 1,
                '_primary_term' => 1,
                'found' => true,
                '_source' => [
                    'name' => 'Jane Doe',
                ],
            ],
            default => [
                '_index' => 'get_test',
                '_type' => '_doc',
                '_id' => '2',
                '_version' => 1,
                '_seq_no' => 1,
                '_primary_term' => 1,
                'found' => true,
                '_source' => [
                    'name' => 'Jane Doe',
                ],
            ],
        };

        $this->assertSame($expected1, $command->get($index, 'test', '1'));
        $this->assertSame($expected2, $command->get($index, 'test', '2'));

        $command->deleteIndex($index);

        $db->close();
    }

    public function testGetAliasInfoNoAliasSetReturnsEmptyArray(): void
    {
        $db = $this->getConnection();
        $command = $db->createCommand();

        $expectedResult = [];
        $actualResult = $command->getAliasInfo();

        $this->assertSame($expectedResult, $actualResult);

        $db->close();
    }

    public function testGetIndexAliasesMultipleAliasesAreSetReturnsDataForThoseAliases(): void
    {
        $db = $this->getConnection();
        $command = $db->createCommand();

        $index = 'alias_test';
        $testAlias1 = 'test_alias1';
        $testAlias2 = 'test_alias2';
        $expectedResult = [
            $testAlias1 => [],
            $testAlias2 => [],
        ];

        if ($command->indexExists($index)) {
            $command->deleteIndex($index);
        }

        $command->createIndex($index);
        $command->addAlias($index, $testAlias1);
        $command->addAlias($index, $testAlias2);
        $actualResult = $command->getIndexAliases($index);
        $command->deleteIndex($index);

        $this->assertSame($expectedResult, $actualResult);

        $db->close();
    }

    public function testGetIndexAliasesNoAliasesSetReturnsEmptyArray(): void
    {
        $db = $this->getConnection();
        $command = $db->createCommand();

        $index = 'alias_test';
        $expectedResult = [];

        $actualResult = $command->getIndexAliases($index);

        $this->assertSame($expectedResult, $actualResult);

        $db->close();
    }

    /**
     * @dataProvider \Yiisoft\Elasticsearch\Tests\Provider\CommandProvider::dataForGetAliasInfo
     */
    public function testGetAliasInfoSingleAliasIsSetReturnsInfoForAlias(
        string $index,
        string $type,
        array $mapping,
        string $alias,
        array $expectedResult,
        array $aliasParameters
    ): void {
        $db = $this->getConnection();
        $command = $db->createCommand();

        if ($command->indexExists($index)) {
            $command->deleteIndex($index);
        }

        $command->createIndex($index);

        if ($mapping) {
            $command->setMapping($index, $type, $mapping);
        }

        $command->addAlias($index, $alias, $aliasParameters);
        $actualResult = $command->getAliasInfo();
        $command->deleteIndex($index);

        // order is not guaranteed
        sort($expectedResult);
        sort($actualResult);

        $this->assertSame($expectedResult, $actualResult);

        $db->close();
    }

    public function testGetIndexInfoByAliasNoAliasesSetReturnsEmptyArray(): void
    {
        $db = $this->getConnection();
        $command = $db->createCommand();

        $testAlias = 'test';
        $expectedResult = [];

        $actualResult = $command->getIndexInfoByAlias($testAlias);

        $this->assertSame($expectedResult, $actualResult);

        $db->close();
    }

    public function testGetIndexInfoByAliasOneIndexIsSetToAliasReturnsDataForThatIndex(): void
    {
        $db = $this->getConnection();
        $command = $db->createCommand();

        $index = 'alias_test';
        $testAlias = 'test';
        $expectedResult = [
            $index => [
                'aliases' => [
                    $testAlias => [],
                ],
            ],
        ];

        if ($command->indexExists($index)) {
            $command->deleteIndex($index);
        }

        $command->createIndex($index);
        $command->addAlias($index, $testAlias);
        $actualResult = $command->getIndexInfoByAlias($testAlias);
        $command->deleteIndex($index);

        $this->assertEquals($expectedResult, $actualResult);

        $db->close();
    }

    public function testGetIndexInfoByAliasTwoIndexesAreSetToSameAliasReturnsDataForBothIndexes(): void
    {
        $db = $this->getConnection();
        $command = $db->createCommand();

        $index1 = 'alias_test1';
        $index2 = 'alias_test2';
        $testAlias = 'test';
        $expectedResult = [
            $index1 => [
                'aliases' => [
                    $testAlias => [],
                ],
            ],
            $index2 => [
                'aliases' => [
                    $testAlias => [],
                ],
            ],
        ];

        if ($command->indexExists($index1)) {
            $command->deleteIndex($index1);
        }

        if ($command->indexExists($index2)) {
            $command->deleteIndex($index2);
        }

        $command->createIndex($index1);
        $command->createIndex($index2);
        $command->addAlias($index1, $testAlias);
        $command->addAlias($index2, $testAlias);
        $actualResult = $command->getIndexInfoByAlias($testAlias);
        $command->deleteIndex($index1);
        $command->deleteIndex($index2);

        $this->assertSame($expectedResult, $actualResult);

        $db->close();
    }

    public function testGetIndexesByAliasNoAliasesSetReturnsEmptyArray(): void
    {
        $db = $this->getConnection();
        $command = $db->createCommand();

        $expectedResult = [];
        $testAlias = 'test';

        $actualResult = $command->getIndexesByAlias($testAlias);

        $this->assertSame($expectedResult, $actualResult);

        $db->close();
    }

    public function testGetIndexesByAliasOneIndexIsSetToAliasReturnsArrayWithNameOfThatIndex(): void
    {
        $db = $this->getConnection();
        $command = $db->createCommand();

        $index = 'alias_test';
        $testAlias = 'test';
        $expectedResult = [$index];

        if ($command->indexExists($index)) {
            $command->deleteIndex($index);
        }

        $command->createIndex($index);
        $command->addAlias($index, $testAlias);
        $actualResult = $command->getIndexesByAlias($testAlias);
        $command->deleteIndex($index);

        $this->assertSame($expectedResult, $actualResult);

        $db->close();
    }

    public function testGetIndexesByAliasTwoIndexesAreSetToSameAliasReturnsArrayWithNamesForBothIndexes(): void
    {
        $db = $this->getConnection();
        $command = $db->createCommand();

        $index1 = 'alias_test1';
        $index2 = 'alias_test2';
        $testAlias = 'test';
        $expectedResult = [
            $index1,
            $index2,
        ];

        if ($command->indexExists($index1)) {
            $command->deleteIndex($index1);
        }

        if ($command->indexExists($index2)) {
            $command->deleteIndex($index2);
        }

        $command->createIndex($index1);
        $command->createIndex($index2);
        $command->addAlias($index1, $testAlias);
        $command->addAlias($index2, $testAlias);
        $actualResult = $command->getIndexesByAlias($testAlias);
        $command->deleteIndex($index1);
        $command->deleteIndex($index2);

        // order is not guaranteed
        sort($expectedResult);
        sort($actualResult);

        $this->assertSame($expectedResult, $actualResult);

        $db->close();
    }

    public function testGetIndexAliasesSingleAliasIsSetReturnsDataForThatAlias(): void
    {
        $db = $this->getConnection();
        $command = $db->createCommand();

        $index = 'alias_test';
        $testAlias = 'test_alias';
        $expectedResult = [
            $testAlias => [],
        ];

        if ($command->indexExists($index)) {
            $command->deleteIndex($index);
        }

        $command->createIndex($index);
        $command->addAlias($index, $testAlias);
        $actualResult = $command->getIndexAliases($index);
        $command->deleteIndex($index);

        $this->assertSame($expectedResult, $actualResult);

        $db->close();
    }

    public function testGetSource(): void
    {
        $db = $this->getConnection();
        $command = $db->createCommand();

        $index = 'source_test';

        if ($command->indexExists($index)) {
            $command->deleteIndex($index);
        }

        $command->createIndex($index);
        $command->insert($index, 'test', ['name' => 'John Doe'], '1');
        $command->insert($index, 'test', ['name' => 'Jane Doe'], '2');

        $this->assertSame([
            'name' => 'John Doe',
        ], $command->getSource($index, 'test', '1'));

        $this->assertSame([
            'name' => 'Jane Doe',
        ], $command->getSource($index, 'test', '2'));

        $command->deleteIndex($index);

        $db->close();
    }

    public function testInsert(): void
    {
        $db = $this->getConnection();
        $command = $db->createCommand();

        $index = 'insert_test';

        if ($command->indexExists($index)) {
            $command->deleteIndex($index);
        }

        $command->createIndex($index);
        $command->insert($index, 'test', ['name' => 'John Doe'], '1');
        $command->insert($index, 'test', ['name' => 'Jane Doe'], '2');

        $this->assertTrue($command->exists($index, 'test', '1'));
        $this->assertTrue($command->exists($index, 'test', '2'));

        $command->deleteIndex($index);

        $db->close();
    }

    public function testIndexStats(): void
    {
        $db = $this->getConnection();
        $cmd = $db->createCommand();

        if (!$cmd->indexExists('command-test')) {
            $cmd->createIndex('command-test');
        }
        $stats = $cmd->getIndexStats();
        $this->assertArrayHasKey('_all', $stats, print_r(array_keys($stats), true));
        $this->assertArrayHasKey('indices', $stats, print_r(array_keys($stats), true));
        $this->assertArrayHasKey('command-test', $stats['indices'], print_r(array_keys($stats['indices']), true));

        $stats = $cmd->getIndexStats('command-test');
        $this->assertArrayHasKey('_all', $stats, print_r(array_keys($stats), true));
        $this->assertArrayHasKey('indices', $stats, print_r(array_keys($stats), true));
        $this->assertArrayHasKey('command-test', $stats['indices'], print_r(array_keys($stats['indices']), true));

        $db->close();
    }

    public function testMultipleGets(): void
    {
        $db = $this->getConnection();
        $command = $db->createCommand();

        $index = 'multiple_get_test';

        if ($command->indexExists($index)) {
            $command->deleteIndex($index);
        }

        $command->createIndex($index);
        $command->insert($index, 'test', ['name' => 'John Doe'], '1');
        $command->insert($index, 'test', ['name' => 'Jane Doe'], '2');

        $result = $command->mget($index, 'test', ['1', '2']);

        $this->assertCount(2, $result['docs']);
        $this->assertArrayHasKey('0', $result['docs']);
        $this->assertArrayHasKey('1', $result['docs']);

        $command->deleteIndex($index);

        $db->close();
    }

    public function testRemoveAliasNoAliasIsSetForIndexReturnsFalse()
    {
        $db = $this->getConnection();
        $command = $db->createCommand();

        $index = 'alias_test';
        $testAlias = 'test_alias';

        if ($command->indexExists($index)) {
            $command->deleteIndex($index);
        }

        $command->createIndex($index);
        $actualResult = $command->removeAlias($index, $testAlias);
        $command->deleteIndex($index);

        $this->assertFalse($actualResult);

        $db->close();
    }

    public function testRemoveAliasAliasWasSetForIndexReturnsTrue(): void
    {
        $db = $this->getConnection();
        $command = $db->createCommand();

        $index = 'alias_test';
        $testAlias = 'test_alias';

        if ($command->indexExists($index)) {
            $command->deleteIndex($index);
        }

        $command->createIndex($index);
        $command->addAlias($index, $testAlias);
        $actualResult = $command->removeAlias($index, $testAlias);
        $command->deleteIndex($index);

        $this->assertTrue($actualResult);

        $db->close();
    }
}
