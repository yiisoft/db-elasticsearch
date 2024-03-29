<?php

declare(strict_types=1);

namespace Yiisoft\Db\ElasticSearch\Tests;

use Yiisoft\Db\ElasticSearch\ActiveDataProvider;
use Yiisoft\Db\ElasticSearch\Connection;
use Yiisoft\Db\ElasticSearch\Query;
use yiiunit\extensions\elasticsearch\data\ar\ActiveRecord;
use yiiunit\extensions\elasticsearch\data\ar\Customer;

class ActiveDataProviderTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        /* @var $db Connection */
        $db = ActiveRecord::$db = $this->getConnection();

        // delete index
        if ($db->createCommand()->indexExists('yiitest')) {
            $db->createCommand()->deleteIndex('yiitest');
        }
        $db->createCommand()->createIndex('yiitest');

        $command = $db->createCommand();
        Customer::setUpMapping($command);

        $db->createCommand()->flushIndex('yiitest');

        $customer = new Customer();
        $customer->id = 1;
        $customer->setAttributes(['email' => 'user1@example.com', 'name' => 'user1', 'address' => 'address1', 'status' => 1], false);
        $customer->save(false);
        $customer = new Customer();
        $customer->id = 2;
        $customer->setAttributes(['email' => 'user2@example.com', 'name' => 'user2', 'address' => 'address2', 'status' => 1], false);
        $customer->save(false);
        $customer = new Customer();
        $customer->id = 3;
        $customer->setAttributes(['email' => 'user3@example.com', 'name' => 'user3', 'address' => 'address3', 'status' => 1], false);
        $customer->save(false);

        $db->createCommand()->flushIndex('yiitest');
    }

    // Tests :

    public function testQuery()
    {
        $query = new Query();
        $query->from('yiitest', 'customer');

        $provider = new ActiveDataProvider([
            'query' => $query,
            'db' => $this->getConnection(),
        ]);
        $models = $provider->getModels();
        $this->assertCount(3, $models);

        $provider = new ActiveDataProvider([
            'query' => $query,
            'db' => $this->getConnection(),
            'pagination' => [
                'pageSize' => 1,
            ],
        ]);
        $models = $provider->getModels();
        $this->assertCount(1, $models);
    }

    public function testActiveQuery()
    {
        $provider = new ActiveDataProvider([
            'query' => Customer::find(),
        ]);
        $models = $provider->getModels();
        $this->assertCount(3, $models);
        $this->assertTrue($models[0] instanceof Customer);
        $this->assertTrue($models[1] instanceof Customer);

        $provider = new ActiveDataProvider([
            'query' => Customer::find(),
            'pagination' => [
                'pageSize' => 1,
            ],
        ]);
        $models = $provider->getModels();
        $this->assertCount(1, $models);
    }
}
