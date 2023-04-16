<?php

declare(strict_types=1);

namespace Yiisoft\Elasticsearch;

use InvalidArgumentException;
use Yiisoft\Json\Json;
use Yiisoft\Arrays\ArrayHelper;

/**
 * The Command class implements the API for accessing the Elasticsearch REST API.
 *
 * Check the @link https://www.elastic.co/guide/en/elasticsearch/reference/current/index.html for details on these
 * commands.
 */
final class Command
{
    public function __construct(private Connection $db)
    {
    }

    /**
     * An alias can also be added with the endpoint.
     *
     * @param string $index The index the alias refers to. Can be any of `*`, `_all`, `glob pattern`, `name1`, `name2`.
     * @param string $name The name of the alias.
     * @param array $options Additional options `routing` and `filter`.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/2.0/indices-aliases.html#alias-adding
     */
    public function addAlias($index, $name, $options = []): bool
    {
        return (bool)$this->db->put([$index, '_alias', $name], [], json_encode((object) $options));
    }

    /**
     * Check if alias exists.
     *
     * @param string $name The name of the alias.
     */
    public function aliasExists(string $name): bool
    {
        return !empty($this->getIndexesByAlias($name));
    }

    /**
     * Runs alias manipulations.
     *
     * If you want to add alias1 to index1 and remove alias2 from index2 you can use following commands:
     *
     * ```php
     * $actions = [
     *      ['add' => ['index' => 'index1', 'alias' => 'alias1']],
     *      ['remove' => ['index' => 'index2', 'alias' => 'alias2']],
     * ];
     * ```
     *
     * @param array $actions list of actions to manipulate aliases.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/2.0/indices-aliases.html#indices-aliases
     */
    public function aliasActions(array $actions): bool
    {
        return (bool)$this->db->post(['_aliases'], [], json_encode(['actions' => $actions]));
    }

    /**
     * Clear caches for all data streams and indices
     *
     * @param string $index Index that the document belongs to.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-clearcache.html#clear-cache-api-all-ex
     */
    public function clearIndexCache(string $index): mixed
    {
        return $this->db->post([$index, '_cache', 'clear']);
    }

    /**
     * Clears the search context and results for a scrolling search.
     *
     * @param array $options Additional options `scroll_id`.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/clear-scroll-api.html
     */
    public function clearScroll(array $options = []): mixed
    {
        $body = array_filter(
            [
                'scroll_id' => ArrayHelper::remove($options, 'scroll_id', null),
            ],
        );

        if (empty($body)) {
            $body = (object) [];
        }

        return $this->db->delete(['_search', 'scroll'], $options, Json::encode($body));
    }

    /**
     * Closes an index.
     *
     * @param string $index Index that the document belongs to.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-close.html
     */
    public function closeIndex(string $index): mixed
    {
        return $this->db->post([$index, '_close']);
    }

    /**
     * Creates a new index.
     *
     * @param string $index Name of the index you wish to create.
     * @param array|null $configuration Index configuration.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-create-index.html
     */
    public function createIndex(string $index, array $configuration = null): mixed
    {
        $body = $configuration !== null ? Json::encode($configuration) : null;

        return $this->db->put([$index], [], $body);
    }

    /**
     * Creates a index template.
     *
     * Index templates define settings, mappings, and aliases that can be applied automatically to new indices.
     *
     * @param string $name Name of the index template to create.
     * @param array $pattern Array of wildcard `(*)` expressions used to match the names of data streams and indices
     * during creation.
     * @param array $settings Configuration options for the index.
     * @param array $mappings Mapping for fields in the index.
     * @param array $aliases The key is the alias name. Index alias names support date math.
     * @param array $options Additional options `version`, `priority`.
     *
     * ```php
     * [
     *     'index_patterns' : ['t*],
     *     'priority' : 0,
     *     'template' => [
     *         'settings' => [
     *             'number_of_shards' => 1,
     *             'number_of_replicas' => 0,
     *         ],
     *        'mappings' => [
     *            '_source' => [
     *                'enabled' => false
     *           ],
     *       ],
     * ];
     * ```
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-put-template.html
     */
    public function createIndexTemplate(
        string $name,
        array|string $pattern,
        array $settings,
        array $mappings,
        array $aliases = [],
        array $options = [],
    ): mixed {
        $body = Json::encode(
            array_merge(
                [
                    'index_patterns' => $pattern,
                    'template' => [
                        'settings' => (object) $settings,
                        'mappings' => (object) $mappings,
                        'aliases' => (object) $aliases,
                    ],
                ],
                $options,
            ),
        );

        return $this->db->put(['_index_template', $name], [], $body);
    }

    /**
     * Removes a JSON document from the specified index.
     *
     * @param string $index Name of the target index.
     * @param string $id Unique identifier for the document.
     * @param string|null $type Type that the document belongs to.
     * @param array $options Additional options.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/docs-delete.html
     */
    public function delete(string $index, string $id, string $type = null, array $options = []): mixed
    {
        if ($this->db->getDslVersion() >= 7) {
            return $this->db->delete([$index, '_doc', $id], $options);
        }

        return $this->db->delete([$index, $type, $id], $options);
    }

    /**
     * Deletes all indexes.
     *
     * To use this command set the action.destructive_requires_name cluster setting to `false`.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-delete-index.html
     */
    public function deleteAllIndexes(): mixed
    {
        return $this->db->delete(['_all']);
    }

    /**
     * Deletes documents that match the specified query.
     *
     * @param string $index Name of the index.
     * @param array $query Query to match documents.
     * @param string|null $type Type that the document belongs to.
     * @param array $options Additional options `conflicts`, `refresh`, `routing`, `timeout`, `wait_for_active_shards`,
     * `wait_for_completion`, `requests_per_second`.
     *
     * @throws InvalidArgumentException If no query is given.
     *
     * @todo Review this method. It is not working.
     */
    public function deleteByQuery(string $index, array $query, string $type = null, array $options = []): mixed
    {
        if (!isset($query['query'])) {
            throw new InvalidArgumentException('Can not call deleteByQuery when no query is given.');
        }

        $query = Json::encode($query);
        $url = [$index];

        if ($this->db->getDslVersion() < 7 && $type !== null) {
            $url[] = $type;
        }

        $url[] = '_delete_by_query';

        return $this->db->post($url, $options, $query);
    }

    /**
     * Deletes an index
     *
     * @param string $index Index that the document belongs to.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-delete-index.html
     */
    public function deleteIndex(string $index): mixed
    {
        return $this->db->delete([$index]);
    }

    /**
     * Deletes a template.
     *
     * @param string $name Template name.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-templates.html
     */
    public function deleteIndexTemplate(string $name): mixed
    {
        return $this->db->delete(['_template', $name]);
    }

    /**
     * Checks if a document exists.
     *
     * @param string $index Index that the document belongs to.
     * @param string $id The documents id.
     * @param string|null $type Type that the document belongs to.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/docs-get.html
     */
    public function exists(string $index, string $id, string $type = null): mixed
    {
        if ($this->db->getDslVersion() >= 7) {
            return $this->db->head([$index, '_doc', $id]);
        }

        return $this->db->head([$index, $type, $id]);
    }

    /**
     * Flushes an index.
     *
     * @param string $index Index that the document belongs to.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-flush.html
     */
    public function flushIndex($index = '_all'): mixed
    {
        return $this->db->post([$index, '_flush']);
    }

    /**
     * Gets a document from the index.
     *
     * @param string $index Index that the document belongs to.
     * @param string $id The documents id.
     * @param string|null $type Type that the document belongs to.
     * @param array $options Additional options.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/docs-get.html
     */
    public function get(string $index, string $id, string $type = null, array $options = []): mixed
    {
        if ($this->db->getDslVersion() >= 7) {
            return $this->db->get([$index, '_doc', $id], $options);
        }

        return $this->db->get([$index, $type, $id], $options);
    }

    /**
     * Gets the alias info.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/2.0/indices-aliases.html#alias-retrieving
     */
    public function getAliasInfo(): array
    {
        $aliasInfo = $this->db->get(['_alias', '*']);
        return $aliasInfo ?: [];
    }

    /**
     * Gets the index aliases.
     *
     * @param string $index Index that the document belongs to.
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/2.0/indices-aliases.html#alias-retrieving
     */
    public function getIndexAliases(string $index): array
    {
        $responseData = $this->db->get([$index, '_alias', '*']);
        if (empty($responseData)) {
            return [];
        }

        return $responseData[$index]['aliases'];
    }

    /**
     * Gets the index info by alias.
     *
     * @param string $alias Alias name.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/2.0/indices-aliases.html#alias-retrieving
     */
    public function getIndexInfoByAlias(string $alias): array
    {
        $responseData = $this->db->get(['_alias', $alias]);
        if (empty($responseData)) {
            return [];
        }

        return $responseData;
    }

    /**
     * Gets the index recovery stats.
     *
     * @param string $index Index that the document belongs to.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-recovery.html
     */
    public function getIndexRecoveryStats(string $index = '_all'): mixed
    {
        return $this->db->get([$index, '_recovery']);
    }

    /**
     * Gets the index stats.
     *
     * @param string $index Index that the document belongs to.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-stats.html
     */
    public function getIndexStats($index = '_all'): mixed
    {
        return $this->db->get([$index, '_stats']);
    }

    /**
     * Get a template.
     *
     * @param string $name Template name.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-templates.html
     */
    public function getIndexTemplate(string $name): mixed
    {
        return $this->db->get(['_index_template', $name]);
    }

    /**
     * Gets the index by alias.
     *
     * @param string $alias Alias name.
     */
    public function getIndexesByAlias(string $alias): array
    {
        return array_keys($this->getIndexInfoByAlias($alias));
    }

    /**
     * Gets the index settings.
     *
     * @param string $index Index that the document belongs to.
     */
    public function getSettings(string $index = '_all'): mixed
    {
        return $this->db->get([$index, '_settings']);
    }

    /**
     * Gets the mapping for an index.
     *
     * @param string $index Index that the document belongs to.
     * @param string|null $type Type that the document belongs to.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-get-mapping.html
     */
    public function getMapping(string $index = '_all', string $type = null): mixed
    {
        $url = [$index, '_mapping'];
        if ($this->db->getDslVersion() < 7 && $type !== null) {
            $url[] = $type;
        }
        return $this->db->get($url);
    }

    /**
     * Gets a documents _source from the index (>=v0.90.1).
     *
     * @param string $index Index that the document belongs to.
     * @param string $id The documents id.
     * @param string|null $type Type that the document belongs to.
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/docs-get.html#_source
     */
    public function getSource(string $index, string $id, string $type = null): mixed
    {
        if ($this->db->getDslVersion() >= 7) {
            return $this->db->get([$index, '_source', $id]);
        }
        return $this->db->get([$index, $type, $id]);
    }

    /**
     * Checks whether an index exists.
     *
     * @param string $index Index that the document belongs to.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-exists.html
     */
    public function indexExists($index): mixed
    {
        return $this->db->head([$index]);
    }

    /**
     * Inserts a document into an index.
     *
     * @param string $index Index that the document belongs to.
     * @param array|string $data Json string or array of data to store.
     * @param string|null $id The documents id. If not specified Id will be automatically chosen.
     * @param string|null $type Type that the document belongs to.
     * @param array $options URL options.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/docs-index_.html
     */
    public function insert(
        string $index,
        string|array $data,
        string $id = null,
        string $type = null,
        array $options = [],
    ): mixed {
        if (empty($data)) {
            $body = '{}';
        } else {
            $body = is_array($data) ? Json::encode($data) : $data;
        }

        if ($id !== null) {
            if ($this->db->getDslVersion() >= 7) {
                return $this->db->put([$index, '_doc', $id], $options, $body);
            }
            return $this->db->put([$index, $type, $id], $options, $body);
        }

        if ($this->db->getDslVersion() >= 7) {
            return $this->db->post([$index, '_doc'], $options, $body);
        }

        return $this->db->post([$index, $type], $options, $body);
    }

    /**
     * Gets multiple documents from the index.
     *
     * @param string $index Index that the document belongs to.
     * @param string[] $ids the documents ids as values in array.
     * @param string|null $type Type that the document belongs to.
     * @param array $options URL options.

     * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/docs-multi-get.html
     */
    public function mget(string $index, array $ids, string $type = null, array $options = []): mixed
    {
        $body = Json::encode(['ids' => array_values($ids)]);

        if ($this->db->getDslVersion() >= 7) {
            return $this->db->get([$index, '_mget'], $options, $body);
        }
        return $this->db->get([$index, $type, '_mget'], $options, $body);
    }

    /**
     * Opens an index.
     *
     * @param string $index Index that the document belongs to.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-open-close.html
     */
    public function openIndex(string $index): mixed
    {
        return $this->db->post([$index, '_open']);
    }

    /**
     * Refreshes an index.
     *
     * @param string $index Index that the document belongs to.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-refresh.html
     */
    public function refreshIndex(string $index): mixed
    {
        return $this->db->post([$index, '_refresh']);
    }

    /**
     * Removes an alias from an index.
     *
     * @param string $index Index that the document belongs to.
     * @param string $alias Alias name.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/2.0/indices-aliases.html#deleting
     */
    public function removeAlias(string $index, string $alias): bool
    {
        return (bool)$this->db->delete([$index, '_alias', $alias]);
    }

    /**
     * Scrolls through a search request.
     *
     * @param array $options URL options.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-scroll.html
     */
    public function scroll(array $options = []): mixed
    {
        $body = array_filter(
            [
                'scroll' => ArrayHelper::remove($options, 'scroll', null),
                'scroll_id' => ArrayHelper::remove($options, 'scroll_id', null),
            ],
        );

        if (empty($body)) {
            $body = (object) [];
        }

        return $this->db->post(['_search', 'scroll'], $options, Json::encode($body));
    }

    /**
     * Sends a request to the _search API and returns the result.
     *
     * @param string $index Index that the document belongs to.
     * @param array|string $query Query to send.
     * @param string|null $type Type that the document belongs to.
     * @param array $options URL options.
     */
    public function search(
        string $index,
        array|string $query = [],
        string $type = null,
        array $options = []
    ): mixed {
        if (empty($query)) {
            $query = '{}';
        }

        if (is_array($query)) {
            $query = Json::encode($query);
        }

        $url = [$index];

        if ($this->db->getDslVersion() < 7 && $type !== null) {
            $url[] = $type;
        }

        $url[] = '_search';

        return $this->db->get($url, $options, $query);
    }

    /**
     * Sets the mapping for an index.
     *
     * @param string $index Index that the document belongs to.
     * @param array|string|null $mapping Json string or array of mapping to store.
     * @param string|null $type Type that the document belongs to.
     * @param array $options URL options.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-put-mapping.html
     */
    public function setMapping(
        string $index,
        string|array|null $mapping,
        string $type = null,
        array $options = []
    ): mixed {
        $body = $mapping !== null ? (is_string($mapping) ? $mapping : Json::encode($mapping)) : null;

        if ($this->db->getDslVersion() >= 7) {
            $endpoint = [$index, '_mapping'];
        } else {
            $endpoint = [$index, '_mapping', $type];
        }

        return $this->db->put($endpoint, $options, $body);
    }

    /**
     * Sends a suggest request to the _search API and returns the result.
     *
     * @param string $index Index that the document belongs to.
     * @param array|string $suggester The suggester body.
     * @param array $options Additional options.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/search-suggesters.html
     */
    public function suggesters(string $index = '_all', string|array $suggester = [], array $options = []): mixed
    {
        if (empty($suggester)) {
            $suggester = '{}';
        }

        if (is_array($suggester)) {
            $suggester = Json::encode($suggester);
        }

        $body = '{"suggest":' . $suggester . ',"size":0}';
        $url = [$index, '_search'];
        $result = $this->db->post($url, $options, $body);

        return $result['suggest'];
    }

    /**
     * Updates a document
     *
     * @param string $index Index that the document belongs to.
     * @param string $id The documents id.
     * @param array|string $data Json string or array of data to store.
     * @param string|null $type Type that the document belongs to.
     * @param array $options URL options.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/docs-update.html
     */
    public function update(
        string $index,
        string $id,
        string|array $data,
        string $type = null,
        array $options = []
    ): mixed {
        $body = [
            'doc' => empty($data) ? new \stdClass() : $data,
        ];

        if (isset($options['detect_noop'])) {
            $body['detect_noop'] = $options['detect_noop'];
            unset($options['detect_noop']);
        }

        if ($this->db->getDslVersion() >= 7) {
            return $this->db->post([$index, '_update', $id], $options, Json::encode($body));
        }

        return $this->db->post([$index, $type, $id, '_update'], $options, Json::encode($body));
    }

    /**
     * Define new analyzers for the index.
     *
     * For example if content analyzer hasn’t been defined on "myindex" yet you can use the following commands to add
     * it:
     *
     * ```php
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
     * ```
     *
     * @param string $index Index that the document belongs to.
     * @param array|string $setting Json string or array of data to store.
     * @param array $options URL options.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-update-settings.html#update-settings-analysis
     */
    public function updateAnalyzers(string $index, string|array $setting, array $options = []): mixed
    {
        $this->closeIndex($index);
        $result = $this->updateSettings($index, $setting, $options);
        $this->openIndex($index);
        return $result;
    }

    /**
     * Changes a dynamic index setting in real time.
     *
     * Note that update analyzers required to {@see close()} the index first and {@see open()} it after the changes are
     * made, use {@see updateAnalyzers()} for it.
     *
     * @param string $index Index that the document belongs to.
     * @param array|string|null $setting Json string or array of data to store.
     * @param array $options Additional options.
     *
     * @link http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/indices-update-settings.html
     */
    public function updateSettings(string $index, string|array|null $setting, array $options = []): mixed
    {
        $body = $setting !== null ? (is_string($setting) ? $setting : Json::encode($setting)) : null;
        return $this->db->put([$index, '_settings'], $options, $body);
    }
}
