<?php

declare(strict_types=1);
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Db\ElasticSearch;

use yii\base\InvalidCallException;
use yii\base\InvalidConfigException;
use Yiisoft\Db\ActiveQueryInterface;

/**
 * ActiveDataProvider is an enhanced version of [[\yii\data\ActiveDataProvider]] specific to the ElasticSearch.
 * It allows to fetch not only rows and total rows count, but full query results including aggregations and so on.
 *
 * Note: this data provider fetches result models and total count using single ElasticSearch query, so results total
 * count will be fetched after pagination limit applying, which eliminates ability to verify if requested page number
 * actually exist. Data provider disables [[yii\data\Pagination::validatePage]] automatically because of this.
 *
 * @property array $queryResults the query results.
 * @property array $aggregations all aggregations results.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 2.0.5
 */
class ActiveDataProvider extends \yii\data\ActiveDataProvider
{
    /**
     * @var array the full query results.
     */
    private $_queryResults;

    /**
     * @param array $results full query results
     */
    public function setQueryResults($results)
    {
        $this->_queryResults = $results;
    }

    /**
     * @return array full query results
     */
    public function getQueryResults()
    {
        if (!is_array($this->_queryResults)) {
            $this->prepare();
        }
        return $this->_queryResults;
    }

    /**
     * @return array all aggregations results
     */
    public function getAggregations()
    {
        $results = $this->getQueryResults();
        return $results['aggregations'] ?? [];
    }

    /**
     * Returns results of the specified aggregation.
     * @param string $name aggregation name.
     * @throws InvalidCallException if requested aggregation does not present in query results.
     * @return array aggregation results.
     */
    public function getAggregation($name)
    {
        $aggregations = $this->getAggregations();
        if (!isset($aggregations[$name])) {
            throw new InvalidCallException("Aggregation '{$name}' does not present.");
        }
        return $aggregations[$name];
    }

    /**
     * @inheritdoc
     */
    protected function prepareModels()
    {
        if (!$this->query instanceof Query) {
            throw new InvalidConfigException('The "query" property must be an instance "' . Query::className() . '" or its subclasses.');
        }

        $query = clone $this->query;
        if (($pagination = $this->getPagination()) !== false) {
            // pagination fails to validate page number, because total count is unknown at this stage
            $pagination->validatePage = false;
            $query->limit($pagination->getLimit())->offset($pagination->getOffset());
        }
        if (($sort = $this->getSort()) !== false) {
            $query->addOrderBy($sort->getOrders());
        }

        $results = $query->search($this->db);
        $this->setQueryResults($results);

        if ($pagination !== false) {
            $pagination->totalCount = $this->getTotalCount();
        }

        return $results['hits']['hits'];
    }

    /**
     * @inheritdoc
     */
    protected function prepareTotalCount()
    {
        if (!$this->query instanceof Query) {
            throw new InvalidConfigException('The "query" property must be an instance "' . Query::className() . '" or its subclasses.');
        }

        $results = $this->getQueryResults();
        return (int)$results['hits']['total'];
    }

    /**
     * @inheritdoc
     */
    protected function prepareKeys($models)
    {
        $keys = [];
        if ($this->key !== null) {
            foreach ($models as $model) {
                if (is_string($this->key)) {
                    $keys[] = $model[$this->key];
                } else {
                    $keys[] = ($this->key)($model);
                }
            }

            return $keys;
        }
        if ($this->query instanceof ActiveQueryInterface) {
            /* @var $class \Yiisoft\Db\ActiveRecord */
            $class = $this->query->modelClass;
            $pks = $class::primaryKey();
            if (count($pks) === 1) {
                foreach ($models as $model) {
                    $keys[] = $model->primaryKey;
                }
            } else {
                foreach ($models as $model) {
                    $kk = [];
                    foreach ($pks as $pk) {
                        $kk[$pk] = $model[$pk];
                    }
                    $keys[] = $kk;
                }
            }

            return $keys;
        }
        return array_keys($models);
    }
}
