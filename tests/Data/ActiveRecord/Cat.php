<?php

declare(strict_types=1);

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Db\ElasticSearch\Tests\Data\ActiveRecord;

/**
 * Class Cat
 *
 * @author Jose Lorente <jose.lorente.martin@gmail.com>
 * @since 2.0
 */
class Cat extends Animal
{
    /**
     * @param self $record
     * @param array $row
     */
    public static function populateRecord($record, $row)
    {
        parent::populateRecord($record, $row);

        $record->does = 'meow';
    }
}
