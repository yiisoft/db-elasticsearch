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

    public function testClearIndexCache(): void
    {
        $db = $this->getConnection();
        $command = $db->createCommand();

        $index = 'cache_test';

        if ($command->indexExists($index)) {
            $command->deleteIndex($index);
        }

        $command->createIndex($index);

        $this->assertSame(
            ['_shards' => ['total' => 2, 'successful' => 1, 'failed' => 0]],
            $command->clearIndexCache($index),
        );

        $command->deleteIndex($index);

        $db->close();
    }

    public function testClearScroll(): void
    {
        $db = $this->getConnection();
        $command = $db->createCommand();

        $index = 'scroll_test';

        if ($command->indexExists($index)) {
            $command->deleteIndex($index);
        }

        $command->createIndex($index);
        $scroll = $command->search('_all', options: ['scroll' => '1m']);

        $this->assertSame(
            ['succeeded' => true, 'num_freed' => 5],
            $command->clearScroll(['scroll_id' => $scroll['_scroll_id']]),
        );

        $command->deleteIndex($index);

        $db->close();
    }

    public function testCloseIndex(): void
    {
        $db = $this->getConnection();
        $command = $db->createCommand();

        $index = 'close_test';

        if ($command->indexExists($index)) {
            $command->deleteIndex($index);
        }

        $command->createIndex($index);

        $this->assertSame(
            [
                'acknowledged' => true,
                'shards_acknowledged' => true,
                'indices' => [
                    'close_test' => [
                        'closed' => true,
                    ],
                ],
            ],
            $command->closeIndex($index),
        );

        $command->deleteIndex($index);

        $db->close();
    }

    public function testCreateIndexTemplate(): void
    {
        $db = $this->getConnection();
        $command = $db->createCommand();

        $template = 'test_template';

        $this->assertSame(
            ['acknowledged' => true],
            $command->createIndexTemplate(
                $template,
                ['t*'],
                ['number_of_shards' => 1, 'number_of_replicas' => 0],
                ['_source' => ['enabled' => false]],
                options: ['priority' => 1]
            ),
        );

        $command->deleteIndexTemplate($template);

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
        $command->insert($index, ['name' => 'John Doe'], '1');
        $command->insert($index, ['name' => 'Jane Doe'], '2');

        $this->assertSame(
            [
                '_index' => 'delete_test',
                '_id' => '2',
                '_version' => 2,
                'result' => 'deleted',
                '_shards' => [
                    'total' => 2,
                    'successful' => 1,
                    'failed' => 0,
                ],
                '_seq_no' => 2,
                '_primary_term' => 1,
            ],
            $command->delete($index, '2'),
        );

        $this->assertFalse($command->exists($index, '2'));

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
        $command->insert($index, ['name' => 'John Doe'], '1');
        $command->insert($index, ['name' => 'Jane Doe'], '2');

        $this->assertTrue($command->exists($index, '1'));
        $this->assertFalse($command->exists($index, '5'));

        $command->deleteIndex($index);

        $db->close();
    }

    public function testFlushIndex(): void
    {
        $db = $this->getConnection();
        $command = $db->createCommand();

        $index = 'flush_test';

        if ($command->indexExists($index)) {
            $command->deleteIndex($index);
        }

        $command->createIndex($index);
        $command->insert($index, ['name' => 'John Doe'], '1');
        $command->insert($index, ['name' => 'Jane Doe'], '2');

        $this->assertSame(
            ['_shards' => ['total' => 2, 'successful' => 1, 'failed' => 0]],
            $command->flushIndex($index),
        );

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
        $command->insert($index, ['name' => 'John Doe'], '1');
        $command->insert($index, ['name' => 'Jane Doe'], '2');

        $this->assertSame(
            [
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
            $command->get($index, '1'),
        );
        $this->assertSame(
            [
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
            $command->get($index, '2'),
        );

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
            $command->setMapping($index, $mapping, $type);
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

    public function testGetIndexRecoveryStats(): void
    {
        $db = $this->getConnection();
        $command = $db->createCommand();

        $this->assertSame(
            [
                'command-test' => [
                    'shards' => [
                        0 => [
                            'id' => 0,
                            'type' => 'EXISTING_STORE',
                            'stage' => 'DONE',
                            'primary' => true,
                            'start_time_in_millis' => 1681650957903,
                            'stop_time_in_millis' => 1681650957924,
                            'total_time_in_millis' => 20,
                            'source' => [
                                'bootstrap_new_history_uuid' => false,
                            ],
                            'target' => [
                                'id' => 'EQWC5q1-Qkm28v04QVZqlQ',
                                'host' => '127.0.0.1',
                                'transport_address' => '127.0.0.1:9300',
                                'ip' => '127.0.0.1',
                                'name' => '32a7c3b4e828',
                            ],
                            'index' => [
                                'size' => [
                                    'total_in_bytes' => 225,
                                    'reused_in_bytes' => 225,
                                    'recovered_in_bytes' => 0,
                                    'recovered_from_snapshot_in_bytes' => 0,
                                    'percent' => '100.0%',
                                ],
                                'files' => [
                                    'total' => 1,
                                    'reused' => 1,
                                    'recovered' => 0,
                                    'percent' => '100.0%',
                                ],
                                'total_time_in_millis' => 0,
                                'source_throttle_time_in_millis' => 0,
                                'target_throttle_time_in_millis' => 0,
                            ],
                            'translog' => [
                                'recovered' => 0,
                                'total' => 0,
                                'percent' => '100.0%',
                                'total_on_start' => 0,
                                'total_time_in_millis' => 13,
                            ],
                            'verify_index' => [
                                'check_index_time_in_millis' => 0,
                                'total_time_in_millis' => 0,
                            ],
                        ],
                    ],
                ],
            ],
            $command->getIndexRecoveryStats('command-test'),
        );
    }

    public function testGetMapping(): void
    {
        $db = $this->getConnection();
        $command = $db->createCommand();

        $index = 'mapping_test';

        if ($command->indexExists($index)) {
            $command->deleteIndex($index);
        }

        $command->createIndex($index);
        $command->setMapping(
            $index,
            [
                'properties' => [
                    'name' => [
                        'type' => 'text',
                    ],
                ],
            ],
        );

        $this->assertSame(
            [
                'mapping_test' => [
                    'mappings' => [
                        'properties' => [
                            'name' => [
                                'type' => 'text',
                            ],
                        ],
                    ],
                ],
            ],
            $command->getMapping($index),
        );

        $command->deleteIndex($index);

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
        $command->insert($index, ['name' => 'John Doe'], '1');
        $command->insert($index, ['name' => 'Jane Doe'], '2');

        $this->assertSame([
            'name' => 'John Doe',
        ], $command->getSource($index, '1'));

        $this->assertSame([
            'name' => 'Jane Doe',
        ], $command->getSource($index, '2'));

        $command->deleteIndex($index);

        $db->close();
    }

    public function testGetTemplate(): void
    {
        $db = $this->getConnection();
        $command = $db->createCommand();

        $template = 'template_test_get';

        $command->createIndexTemplate(
            $template,
            ['template_*'],
            ['number_of_shards' => 1, 'number_of_replicas' => 0],
            ['_source' => ['enabled' => false]],
            options: ['priority' => 2]
        );

        $this->assertSame(
            [
                'index_templates' => [
                    0 => [
                        'name' => 'template_test_get',
                        'index_template' => [
                            'index_patterns' => [
                                0 => 'template_*',
                            ],
                            'template' => [
                                'settings' => [
                                    'index' => [
                                        'number_of_shards' => '1',
                                        'number_of_replicas' => '0',
                                    ],
                                ],
                                'mappings' => [
                                    '_source' => [
                                        'enabled' => false,
                                    ],
                                ],
                                'aliases' => [],
                            ],
                            'composed_of' => [],
                            'priority' => 2,
                        ],
                    ],
                ],
            ],
            $command->getIndexTemplate($template),
        );

        $command->deleteIndexTemplate($template);

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
        $command->insert($index, ['name' => 'John Doe'], '1');
        $command->insert($index, ['name' => 'Jane Doe'], '2');

        $this->assertTrue($command->exists($index, '1'));
        $this->assertTrue($command->exists($index, '2'));

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
        $command->insert($index, ['name' => 'John Doe'], '1');
        $command->insert($index, ['name' => 'Jane Doe'], '2');

        $result = $command->mget($index, ['1', '2']);

        $this->assertCount(2, $result['docs']);
        $this->assertSame(
            [
                'docs' => [
                    0 => [
                        '_index' => 'multiple_get_test',
                        '_id' => '1',
                        '_version' => 1,
                        '_seq_no' => 0,
                        '_primary_term' => 1,
                        'found' => true,
                        '_source' => [
                            'name' => 'John Doe',
                        ],
                    ],
                    1 => [
                        '_index' => 'multiple_get_test',
                        '_id' => '2',
                        '_version' => 1,
                        '_seq_no' => 1,
                        '_primary_term' => 1,
                        'found' => true,
                        '_source' => [
                            'name' => 'Jane Doe',
                        ],
                    ],
                ],
            ],
            $result,
        );
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

    public function testScroll(): void
    {
        $db = $this->getConnection();
        $command = $db->createCommand();

        $index = 'scroll_test';

        if ($command->indexExists($index)) {
            $command->deleteIndex($index);
        }

        $command->createIndex($index);
        $command->insert($index, ['name' => 'John Doe'], '1');
        $command->insert($index, ['name' => 'Jane Doe'], '2');

        $command->refreshIndex($index);

        $result = $command->search($index, ['query' => ['match_all' => new \stdClass()]], options: ['scroll' => '1m']);

        $this->assertArrayHasKey('_scroll_id', $result);

        $this->assertArrayHasKey('hits', $result);
        $this->assertArrayHasKey('hits', $result['hits']);
        $this->assertCount(2, $result['hits']['hits']);

        $result = $command->scroll(['scroll_id' => $result['_scroll_id']]);

        $this->assertArrayHasKey('hits', $result);
        $this->assertArrayHasKey('hits', $result['hits']);
        $this->assertSame(2, $result['hits']['total']['value']);

        $command->deleteIndex($index);
    }

    public function testSuggesters(): void
    {
        $db = $this->getConnection();
        $command = $db->createCommand();

        $index = 'suggest_test';

        if ($command->indexExists($index)) {
            $command->deleteIndex($index);
        }

        $command->createIndex($index);
        $command->insert($index, ['name' => 'John Doe'], '1');
        $command->insert($index, ['name' => 'Jane Doe'], '2');

        $result = $command->suggesters(
            $index,
            [
                'my-suggest-1' => [
                    'text' => 'John',
                    'term' => [
                        'field' => 'name',
                    ],
                ],
                'my-suggest-2' => [
                    'text' => 'Jane',
                    'term' => [
                        'field' => 'name',
                    ],
                ],
            ],
        );

        $this->assertSame(
            [
                'my-suggest-1' => [
                    0 => [
                        'text' => 'john',
                        'offset' => 0,
                        'length' => 4,
                        'options' => [],
                    ],
                ],
                'my-suggest-2' => [
                    0 => [
                        'text' => 'jane',
                        'offset' => 0,
                        'length' => 4,
                        'options' => [],
                    ],
                ],
            ],
            $result,
        );

        $command->deleteIndex($index);

        $db->close();
    }

    public function testUpdate(): void
    {
        $db = $this->getConnection();
        $command = $db->createCommand();

        $index = 'update_test';

        if ($command->indexExists($index)) {
            $command->deleteIndex($index);
        }

        $command->createIndex($index);
        $command->insert($index, ['name' => 'John Doe'], '1');
        $command->insert($index, ['name' => 'Jane Doe'], '2');

        $command->update($index, '1', ['name' => 'John Doe Jr.'], options: ['detect_noop' => true]);

        $this->assertSame(['name' => 'John Doe Jr.'], $command->getSource($index, '1'));

        $command->deleteIndex($index);

        $db->close();
    }

    public function testUpdateAnalizers(): void
    {
        $db = $this->getConnection();
        $command = $db->createCommand();

        $index = 'update_analyzer_test';

        if ($command->indexExists($index)) {
            $command->deleteIndex($index);
        }

        $command->createIndex($index);
        $command->closeIndex($index);
        $command->updateAnalyzers(
            $index,
            [
                'analysis' => [
                    'analyzer' => [
                        'content' => [
                            'type' => 'custom',
                            'tokenizer' => 'whitespace',
                        ],
                    ],
                ],
            ],
        );
        $command->openIndex($index);

        $this->assertSame(
            ['content' => ['type' => 'custom', 'tokenizer' => 'whitespace']],
            $command->getSettings($index)[$index]['settings']['index']['analysis']['analyzer'],
        );

        $command->deleteIndex($index);

        $db->close();
    }

    public function testUpdateSettings(): void
    {
        $db = $this->getConnection();
        $command = $db->createCommand();

        $index = 'update_settings_test';

        if ($command->indexExists($index)) {
            $command->deleteIndex($index);
        }

        $command->createIndex($index);
        $command->updateSettings($index, ['index' => ['number_of_replicas' => 4]]);

        $this->assertSame('4', $command->getSettings($index)[$index]['settings']['index']['number_of_replicas']);

        $command->deleteIndex($index);

        $db->close();
    }
}
