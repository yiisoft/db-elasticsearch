<?php

declare(strict_types=1);

namespace Yiisoft\Elasticsearch\Tests\Support;

use Yiisoft\Elasticsearch\Connection;

/**
 * This is the base class for all yii framework unit tests.
 */
trait TestTrait
{
    public function getConnection($reset = true): Connection
    {
        $db = new Connection();
        $db->addNodeValue('host', 'localhost');

        if ($reset) {
            $db->open();
        }

        return $db;
    }
}
