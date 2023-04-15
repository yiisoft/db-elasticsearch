<?php

declare(strict_types=1);

namespace Yiisoft\Db\ElasticSearch\Tests;

use yii\di\Container;
use Yiisoft\Db\ElasticSearch\Connection;
use Yii;

/**
 * This is the base class for all yii framework unit tests.
 */
abstract class TestCase extends \yii\tests\TestCase
{
    public static $params;

    /**
     * Returns a test configuration param from /data/config.php
     * @param  string $name params name
     * @param  mixed $default default value to use when param is not set.
     * @return mixed  the value of the configuration param
     */
    public static function getParam($name, $default = null)
    {
        if (static::$params === null) {
            static::$params = require(__DIR__ . '/data/config.php');
        }

        return static::$params[$name] ?? $default;
    }

    /**
     * Clean up after test.
     * By default the application created with [[mockApplication]] will be destroyed.
     */
    protected function tearDown()
    {
        parent::tearDown();
        $this->destroyApplication();
    }

    /**
     * Destroys application in Yii::$app by setting it to null.
     */
    protected function destroyApplication()
    {
        Yii::$app = null;
        Yii::$container = new Container();
    }

    protected function setUp()
    {
        $this->mockApplication();

        $config = self::getParam('elasticsearch');
        if (empty($config)) {
            $this->markTestSkipped('No elasticsearch server connection configured.');
        }
        parent::setUp();
    }

    /**
     * @param  bool    $reset whether to clean up the test database
     * @return Connection
     */
    public function getConnection($reset = true)
    {
        $config = self::getParam('elasticsearch');
        $db = new Connection($config);
        if ($reset) {
            $db->open();
        }

        return $db;
    }

    /**
     * Invokes a inaccessible method.
     * @param $object
     * @param $method
     * @param array $args
     * @param bool $revoke whether to make method inaccessible after execution
     * @return mixed
     */
    protected function invokeMethod($object, $method, $args = [], $revoke = true)
    {
        $reflection = new \ReflectionObject($object);
        $method = $reflection->getMethod($method);
        $method->setAccessible(true);
        $result = $method->invokeArgs($object, $args);
        if ($revoke) {
            $method->setAccessible(false);
        }

        return $result;
    }
}
