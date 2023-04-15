<?php

declare(strict_types=1);

namespace Yiisoft\Db\ElasticSearch\Tests\Data\ActiveRecord;

/**
 * Class Dog
 *
 * @author Jose Lorente <jose.lorente.martin@gmail.com>
 * @since 2.0
 */
class Dog extends Animal
{
    /**
     * @param self $record
     * @param array $row
     */
    public static function populateRecord($record, $row)
    {
        parent::populateRecord($record, $row);

        $record->does = 'bark';
    }
}
