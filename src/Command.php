<?php

declare(strict_types=1);
/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace Yiisoft\Db\ElasticSearch;

use yii\base\Component;
use yii\base\InvalidCallException;
use yii\helpers\Json;

/**
 * The Command class implements the API for accessing the elasticsearch REST API.
 *
 * Check the [elasticsearch guide](https://www.elastic.co/guide/en/elasticsearch/reference/current/index.html)
 * for details on these commands.
 *
 * @author Carsten Brandt <mail@cebe.cc>
 * @since 2.0
 */
class Command extends Component
{
    /**
     * @var Connection
     */
    public $db;
    /**
     * @var array|string the indexes to execute the query on. Defaults to null meaning all indexes
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/search-search.html#search-multi-index-type
     */
    public $index;
    /**
     * @var array|string the types to execute the query on. Defaults to null meaning all types
     */
    public $type;
    /**
     * @var array list of arrays or json strings that become parts of a query
     */
    public $queryParts;
    /**
     * @var array options to be appended to the query URL, such as "search_type" for search or "timeout" for delete
     */
    public $options = [];

    /**
     * Sends a request to the _search API and returns the result
     * @param array $options
     * @return mixed
     */
    public function search($options = [])
    {
        $query = $this->queryParts;
        if (empty($query)) {
            $query = '{}';
        }
        if (is_array($query)) {
            $query = Json::encode($query);
        }
        $url = [$this->index !== null ? $this->index : '_all'];
        if ($this->type !== null) {
            $url[] = $this->type;
        }
        $url[] = '_search';

        return $this->db->get($url, array_merge($this->options, $options), $query);
    }

    /**
     * Sends a request to the delete by query
     * @param array $options
     * @return mixed
     */
    public function deleteByQuery($options = [])
    {
        if (!isset($this->queryParts['query'])) {
            throw new InvalidCallException('Can not call deleteByQuery when no query is given.');
        }
        $query = [
            'query' => $this->queryParts['query'],
        ];
        if (isset($this->queryParts['filter'])) {
            $query['filter'] = $this->queryParts['filter'];
        }
        $query = Json::encode($query);
        $url = [$this->index !== null ? $this->index : '_all'];
        if ($this->type !== null) {
            $url[] = $this->type;
        }
        $url[] = '_delete_by_query';

        return $this->db->post($url, array_merge($this->options, $options), $query);
    }

    /**
     * Sends a request to the _suggest API and returns the result
     * @param array|string $suggester the suggester body
     * @param array $options
     * @return mixed
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/search-suggesters.html
     */
    public function suggest($suggester, $options = [])
    {
        if (empty($suggester)) {
            $suggester = '{}';
        }
        if (is_array($suggester)) {
            $suggester = Json::encode($suggester);
        }
        $url = [
            $this->index !== null ? $this->index : '_all',
            '_suggest',
        ];

        return $this->db->post($url, array_merge($this->options, $options), $suggester);
    }

    /**
     * Inserts a document into an index
     * @param string $index
     * @param string $type
     * @param array|string $data json string or array of data to store
     * @param null $id the documents id. If not specified Id will be automatically chosen
     * @param array $options
     * @return mixed
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/docs-index_.html
     */
    public function insert($index, $type, $data, $id = null, $options = [])
    {
        if (empty($data)) {
            $body = '{}';
        } else {
            $body = is_array($data) ? Json::encode($data) : $data;
        }

        if ($id !== null) {
            return $this->db->put([$index, $type, $id], $options, $body);
        }
        return $this->db->post([$index, $type], $options, $body);
    }

    /**
     * gets a document from the index
     * @param $index
     * @param $type
     * @param $id
     * @param array $options
     * @return mixed
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/docs-get.html
     */
    public function get($index, $type, $id, $options = [])
    {
        return $this->db->get([$index, $type, $id], $options);
    }

    /**
     * gets multiple documents from the index
     *
     * TODO allow specifying type and index + fields
     * @param $index
     * @param $type
     * @param $ids
     * @param array $options
     * @return mixed
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/docs-multi-get.html
     */
    public function mget($index, $type, $ids, $options = [])
    {
        $body = Json::encode(['ids' => array_values($ids)]);

        return $this->db->get([$index, $type, '_mget'], $options, $body);
    }

    /**
     * gets a documents _source from the index (>=v0.90.1)
     * @param $index
     * @param $type
     * @param $id
     * @return mixed
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/docs-get.html#_source
     */
    public function getSource($index, $type, $id)
    {
        return $this->db->get([$index, $type, $id]);
    }

    /**
     * gets a document from the index
     * @param $index
     * @param $type
     * @param $id
     * @return mixed
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/docs-get.html
     */
    public function exists($index, $type, $id)
    {
        return $this->db->head([$index, $type, $id]);
    }

    /**
     * deletes a document from the index
     * @param $index
     * @param $type
     * @param $id
     * @param array $options
     * @return mixed
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/docs-delete.html
     */
    public function delete($index, $type, $id, $options = [])
    {
        return $this->db->delete([$index, $type, $id], $options);
    }

    /**
     * updates a document
     * @param $index
     * @param $type
     * @param $id
     * @param array $options
     * @return mixed
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/docs-update.html
     */
    public function update($index, $type, $id, $data, $options = [])
    {
        $body = [
            'doc' => empty($data) ? new \stdClass() : $data,
        ];
        if (isset($options['detect_noop'])) {
            $body['detect_noop'] = $options['detect_noop'];
            unset($options['detect_noop']);
        }

        return $this->db->post([$index, $type, $id, '_update'], $options, Json::encode($body));
    }

    // TODO bulk https://www.elastic.co/guide/en/elasticsearch/reference/current/docs-bulk.html

    /**
     * creates an index
     * @param $index
     * @param array $configuration
     * @return mixed
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-create-index.html
     */
    public function createIndex($index, $configuration = null)
    {
        $body = $configuration !== null ? Json::encode($configuration) : null;

        return $this->db->put([$index], [], $body);
    }

    /**
     * deletes an index
     * @param $index
     * @return mixed
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-delete-index.html
     */
    public function deleteIndex($index)
    {
        return $this->db->delete([$index]);
    }

    /**
     * deletes all indexes
     * @return mixed
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-delete-index.html
     */
    public function deleteAllIndexes()
    {
        return $this->db->delete(['_all']);
    }

    /**
     * checks whether an index exists
     * @param $index
     * @return mixed
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-exists.html
     */
    public function indexExists($index)
    {
        return $this->db->head([$index]);
    }

    /**
     * @param $index
     * @param $type
     * @return mixed
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-types-exists.html
     */
    public function typeExists($index, $type)
    {
        return $this->db->head([$index, $type]);
    }

    // TODO https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-aliases.html

    /**
     * Change specific index level settings in real time.
     * Note that update analyzers required to [[close()]] the index first and [[open()]] it after the changes are made,
     * use [[updateAnalyzers()]] for it.
     *
     * @param string $index
     * @param array|string $setting
     * @param array $options URL options
     * @return mixed
     * @see https://www.elasticsearch.org/guide/en/elasticsearch/reference/current/indices-update-settings.html
     * @since 2.0.4
     */
    public function updateSettings($index, $setting, $options = [])
    {
        $body = $setting !== null ? (is_string($setting) ? $setting : Json::encode($setting)) : null;
        return $this->db->put([$index, '_settings'], $options, $body);
    }

    /**
     * Define new analyzers for the index.
     * For example if content analyzer hasn’t been defined on "myindex" yet
     * you can use the following commands to add it:
     *
     * ~~~
     *  $setting = [
     *      'analysis' => [
     *          'analyzer' => [
     *              'ngram_analyzer_with_filter' => [
     *                  'tokenizer' => 'ngram_tokenizer',
     *                  'filter' => 'lowercase, snowball'
     *              ],
     *          ],
     *          'tokenizer' => [
     *              'ngram_tokenizer' => [
     *                  'type' => 'nGram',
     *                  'min_gram' => 3,
     *                  'max_gram' => 10,
     *                  'token_chars' => ['letter', 'digit', 'whitespace', 'punctuation', 'symbol']
     *              ],
     *          ],
     *      ]
     * ];
     * $elasticQuery->createCommand()->updateAnalyzers('myindex', $setting);
     * ~~~
     *
     * @param string $index
     * @param array|string $setting
     * @param array $options URL options
     * @return mixed
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-update-settings.html#update-settings-analysis
     * @since 2.0.4
     */
    public function updateAnalyzers($index, $setting, $options = [])
    {
        $this->closeIndex($index);
        $result = $this->updateSettings($index, $setting, $options);
        $this->openIndex($index);
        return $result;
    }

    // TODO https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-get-settings.html

    // TODO https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-warmers.html

    /**
     * @param $index
     * @return mixed
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-open-close.html
     */
    public function openIndex($index)
    {
        return $this->db->post([$index, '_open']);
    }

    /**
     * @param $index
     * @return mixed
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-open-close.html
     */
    public function closeIndex($index)
    {
        return $this->db->post([$index, '_close']);
    }

    /**
     * @param array $options
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-scroll.html
     * @return mixed
     * @since 2.0.4
     */
    public function scroll($options = [])
    {
        return $this->db->get(['_search', 'scroll'], $options);
    }

    /**
     * @param array $options
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-scroll.html
     * @return mixed
     * @since 2.0.4
     */
    public function clearScroll($options = [])
    {
        return $this->db->delete(['_search', 'scroll'], $options);
    }

    /**
     * @param $index
     * @return mixed
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-stats.html
     */
    public function getIndexStats($index = '_all')
    {
        return $this->db->get([$index, '_stats']);
    }

    /**
     * @param $index
     * @return mixed
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-recovery.html
     */
    public function getIndexRecoveryStats($index = '_all')
    {
        return $this->db->get([$index, '_recovery']);
    }

    // https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-segments.html

    /**
     * @param $index
     * @return mixed
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-clearcache.html
     */
    public function clearIndexCache($index)
    {
        return $this->db->post([$index, '_cache', 'clear']);
    }

    /**
     * @param $index
     * @return mixed
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-flush.html
     */
    public function flushIndex($index = '_all')
    {
        return $this->db->post([$index, '_flush']);
    }

    /**
     * @param $index
     * @return mixed
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-refresh.html
     */
    public function refreshIndex($index)
    {
        return $this->db->post([$index, '_refresh']);
    }

    // TODO https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-optimize.html

    // TODO https://www.elastic.co/guide/en/elasticsearch/reference/0.90/indices-gateway-snapshot.html

    /**
     * @param string $index
     * @param string $type
     * @param array|string $mapping
     * @param array $options
     * @return mixed
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-put-mapping.html
     */
    public function setMapping($index, $type, $mapping, $options = [])
    {
        $body = $mapping !== null ? (is_string($mapping) ? $mapping : Json::encode($mapping)) : null;

        return $this->db->put([$index, '_mapping', $type], $options, $body);
    }

    /**
     * @param string $index
     * @param string $type
     * @return mixed
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-get-mapping.html
     */
    public function getMapping($index = '_all', $type = null)
    {
        $url = [$index, '_mapping'];
        if ($type !== null) {
            $url[] = $type;
        }
        return $this->db->get($url);
    }

    /**
     * @param $index
     * @param string $type
     * @return mixed
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-get-field-mapping.html
     */
//    public function getFieldMapping($index, $type = '_all')
//    {
    //		// TODO implement
//        return $this->db->put([$index, $type, '_mapping']);
//    }

    /**
     * @param $options
     * @param $index
     * @return mixed
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-analyze.html
     */
    //	public function analyze($options, $index = null)
    //	{
    //		// TODO implement
    ////		return $this->db->put([$index]);
    //	}

    /**
     * @param $name
     * @param $pattern
     * @param $settings
     * @param $mappings
     * @param int $order
     * @return mixed
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-templates.html
     */
    public function createTemplate($name, $pattern, $settings, $mappings, $order = 0)
    {
        $body = Json::encode([
            'template' => $pattern,
            'order' => $order,
            'settings' => (object) $settings,
            'mappings' => (object) $mappings,
        ]);

        return $this->db->put(['_template', $name], [], $body);
    }

    /**
     * @param $name
     * @return mixed
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-templates.html
     */
    public function deleteTemplate($name)
    {
        return $this->db->delete(['_template', $name]);
    }

    /**
     * @param $name
     * @return mixed
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-templates.html
     */
    public function getTemplate($name)
    {
        return $this->db->get(['_template', $name]);
    }
}
