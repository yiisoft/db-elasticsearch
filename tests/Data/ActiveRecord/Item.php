<?php

declare(strict_types=1);

namespace Yiisoft\Db\ElasticSearch\Tests\Data\ActiveRecord;

use Yiisoft\Db\ElasticSearch\Command;

/**
 * Class Item
 *
 * @property int $id
 * @property string $name
 * @property int $category_id
 */
class Item extends ActiveRecord
{
    public static function primaryKey()
    {
        return ['id'];
    }

    public function attributes()
    {
        return ['id', 'name', 'category_id'];
    }

    /**
     * sets up the index for this record
     * @param Command $command
     */
    public static function setUpMapping($command)
    {
        $command->setMapping(static::index(), static::type(), [
            static::type() => [
                'properties' => [
                    'name' => ['type' => 'keyword', 'index' => 'not_analyzed', 'store' => true],
                    'category_id' => ['type' => 'integer'],
                ],
            ],
        ]);
    }
}
