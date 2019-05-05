<?php

namespace Yiisoft\Db\ElasticSearch\Tests\Data\ActiveRecord;

use Yiisoft\Db\ElasticSearch\ActiveQuery;

/**
 * CustomerQuery
 */
class CustomerQuery extends ActiveQuery
{
    public function active()
    {
        $this->andWhere(['status' => 1]);

        return $this;
    }
}
