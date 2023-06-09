<?php

declare(strict_types=1);
/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace Yiisoft\Db\ElasticSearch\Tests\Data\ActiveRecord;

/**
 * ActiveRecord is ...
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class ActiveRecord extends \Yiisoft\Db\ElasticSearch\ActiveRecord
{
    public static $db;

    /**
     * @return \Yiisoft\Db\ElasticSearch\Connection
     */
    public static function getDb()
    {
        return self::$db;
    }

    public static function index()
    {
        return 'yiitest';
    }
}
