<?php

declare(strict_types=1);

namespace Yiisoft\Elasticsearch\Tests\Provider;

final class CommandProvider
{
    public static function dataForGetAliasInfo(): array
    {
        $index = 'alias_test';
        $type = 'alias_test_type';
        $alias = 'test';
        $filter = [
            'filter' => [
                'term' => [
                    'user' => 'satan',
                ],
            ],
        ];
        $mapping = [
            'properties' => [
                'user' => ['type' => 'keyword'],
            ],
        ];
        $singleRouting = [
            'routing' => '1',
        ];
        $singleExpectedRouting = [
            'index_routing' => '1',
            'search_routing' => '1',
        ];
        $differentRouting = [
            'index_routing' => '2',
            'search_routing' => '1,2',
        ];

        return [
            [
                $index,
                $type,
                $mapping,
                $alias,
                [
                    $index => [
                        'aliases' => [
                            $alias => [],
                        ],
                    ],
                ],
                [],
            ],
            [
                $index,
                $type,
                $mapping,
                $alias,
                [
                    $index => [
                        'aliases' => [
                            $alias => $filter,
                        ]
                    ],
                ],
                $filter,
            ],
            [
                $index,
                $type,
                $mapping,
                $alias,
                [
                    $index => [
                        'aliases' => [
                            $alias => $singleExpectedRouting,
                        ],
                    ],
                ],
                $singleRouting,
            ],
            [
                $index,
                $type,
                $mapping,
                $alias,
                [
                    $index => [
                        'aliases' => [
                            $alias => $differentRouting,
                        ],
                    ],
                ],
                $differentRouting
            ],
            [
                $index,
                $type,
                $mapping,
                $alias,
                [
                    $index => [
                        'aliases' => [
                            $alias => array_merge($filter, $singleExpectedRouting)
                        ],
                    ],
                ],
                array_merge($filter, $singleRouting),
            ],
            [
                $index,
                $type,
                $mapping,
                $alias,
                [
                    $index => [
                        'aliases' => [
                            $alias => array_merge($filter, $differentRouting)
                        ],
                    ],
                ],
                array_merge($filter, $differentRouting),
            ]
        ];
    }
}
