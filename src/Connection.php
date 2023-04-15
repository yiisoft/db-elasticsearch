<?php

declare(strict_types=1);

namespace Yiisoft\Elasticsearch;

use CurlHandle;
use Exception;
use InvalidArgumentException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LogLevel;
use RuntimeException;
use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Elasticsearch\Profiler\Context\ConnectionContext;
use Yiisoft\Elasticsearch\Profiler\ProfilerAwareInterface;
use Yiisoft\Elasticsearch\Profiler\ProfilerAwareTrait;
use Yiisoft\Json\Json;

use function array_filter;
use function array_map;
use function curl_errno;
use function curl_exec;
use function curl_getinfo;
use function curl_reset;
use function curl_setopt_array;
use function curl_setopt;
use function explode;
use function function_exists;
use function http_build_query;
use function implode;
use function is_array;
use function mb_strlen;
use function preg_replace;
use function str_contains;
use function strncmp;
use function strpos;
use function strtolower;
use function substr;
use function trim;
use function urlencode;

/**
 * Connection is used to connect to an Elasticsearch cluster version 0.20 or higher.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class Connection implements LoggerAwareInterface, ProfilerAwareInterface
{
    use LoggerAwareTrait;
    use ProfilerAwareTrait;

    /**
     * @var bool Whether to autodetect available cluster nodes on {@see open()}.
     */
    private bool $autodetectCluster = true;
    private array $nodes = [
        ['http_address' => 'inet[/127.0.0.1:9200]'],
    ];
    /**
     * @var string The active node. Key of one of the {@see nodes}.
     * Will be selected on {@see open()}.
     */
    private string $activeNode = '';
    private array $auth = [];
    /**
     * @var CurlHandle The curl instance returned by @link http://php.net/manual/en/function.curl-init.php.
     */
    private CurlHandle $curl;
    private array $curlOptions = [];
    private string $defaultProtocol = 'http';
    private float|null $dataTimeout = null;
    private int $dslVersion = 8;
    private float|null $timeOut = null;

    /**
     * @throws InvalidArgumentException
     */
    public function __construct()
    {
        foreach ($this->nodes as &$node) {
            if (!isset($node['http_address'])) {
                throw new InvalidArgumentException('Elasticsearch node needs at least a http_address configured.');
            }
            if (!isset($node['protocol'])) {
                $node['protocol'] = $this->defaultProtocol;
            }
            if (!in_array($node['protocol'], ['http', 'https'])) {
                throw new InvalidArgumentException('Valid node protocol settings are "http" and "https".');
            }
        }
    }

    /**
     * Closes the connection when this component is being serialized.
     */
    public function __sleep(): array
    {
        $this->close();

        return array_keys(get_object_vars($this));
    }

    /**
     * The Elasticsearch cluster nodes to connect to.
     *
     * This is populated with the result of a cluster nodes request when {@see autodetectCluster} is true.
     *
     * Additional special options:
     *
     *  - `auth`: overrides [[auth]] property. For example:
     *
     * ```php
     * [
     *  'http_address' => 'inet[/127.0.0.1:9200]',
     *  'auth' => ['username' => 'yiiuser', 'password' => 'yiipw'], // Overrides the `auth` property of the class with
     *  specific login and password
     *  //'auth' => ['username' => 'yiiuser', 'password' => 'yiipw'], // Disabled auth regardless of `auth` property of
     *  the class
     * ]
     * ```
     *
     *  - `protocol`: explicitly sets the protocol for the current node (useful when manually defining a HTTPS cluster)
     *
     * @param string $key The node to set.
     * @param mixed $value The node value.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/cluster-nodes-info.html#cluster-nodes-info
     */
    public function addNodeValue(string $key, mixed $value): self
    {
        $this->nodes[$this->activeNode][$key] = $value;

        return $this;
    }

    /**
     * Set credentials for authentication.
     *
     * @param array $values The credentials for authentication.
     *
     * Array elements:
     *
     *  - `username`: the username for authentication.
     *  - `password`: the password for authentication.
     *
     * Array either MUST contain both username and password on not contain any authentication credentials.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/security-api-authenticate.html
     */
    public function auth(array $values): self
    {
        $this->auth = $values;
        return $this;
    }

    /**
     * Closes the currently active DB connection.
     *
     * It does nothing if the connection is already closed.
     */
    public function close(): void
    {
        $this->logger?->log(
            LogLevel::INFO,
            'Closing connection to Elasticsearch. Active node was: ' .
            $this->nodes[$this->activeNode]['http']['publish_address'],
            [__CLASS__],
        );

        $this->activeNode = '';

        curl_close($this->curl);
        unset($this->curl);
    }

    /**
     * Creates a command for execution.
     *
     * @return Command the DB command
     *
     * @throws Exception
     */
    public function createCommand(): Command
    {
        $this->open();

        return new Command($this);
    }

    /**
     * Additional options used to configure curl session.
     *
     * @param array $values The curl options.
     */
    public function curlOptions(array $values): self
    {
        $this->curlOptions = $values;
        return $this;
    }

    /**
     * Set timeout to use when reading the response from an Elasticsearch node.
     *
     * This value will be used to configure the curl `CURLOPT_TIMEOUT` option.
     *
     * If not set, no explicit timeout will be set for curl.
     *
     * @param float|null $value The timeout to use when reading the response from an Elasticsearch node.
     */
    public function dataTimeout(float $value = null): self
    {
        $this->dataTimeout = $value;
        return $this;
    }

    /**
     * Elasticsearch has no knowledge of the protocol used to access its nodes.
     *
     * Specifically, cluster autodetect request returns node hosts and ports, but not the protocols to access them.
     *
     * Therefore, we need to specify a default protocol here, which can be overridden for specific nodes in the
     * {@see nodes()} property.
     *
     * If {@see autodetectCluster} is true, all nodes received from cluster will be set to use the protocol defined by
     * {@see defaultProtocol}
     *
     * @param string $value The default protocol to connect to nodes.
     */
    public function defaultProtocol(string $value): self
    {
        $this->defaultProtocol = $value;
        return $this;
    }

    /**
     * Set version of the domain-specific language to use with the server.
     *
     * @param int $value The version of the domain-specific.
     */
    public function dslVersion(int $value): self
    {
        $this->dslVersion = $value;
        return $this;
    }

    /**
     * Return the cluster state.
     *
     * @return Exception
     * @throws InvalidArgumentException
     */
    public function getClusterState(): mixed
    {
        return $this->get(['_cluster', 'state']);
    }

    /**
     * Returns the dsl version.
     */
    public function getDslVersion(): int
    {
        return $this->dslVersion;
    }

    /**
     * Returns the name of the DB driver for the current {@see dsn}.
     */
    public function getDriverName(): string
    {
        return 'elasticsearch';
    }

    /**
     * Returns the Elasticsearch node value.
     *
     * @param string $key The node value to get.
     */
    public function getNodeValue(string $key = ''): mixed
    {
        if ($this->activeNode === '') {
            return null;
        }

        return ArrayHelper::getValue($this->nodes[$this->activeNode], $key);
    }

    /**
     * Return the active node.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function getNodeInfo(): mixed
    {
        return $this->get([]);
    }

    /**
     * Returns a value indicating whether the DB connection is established.
     *
     * @return bool whether the DB connection is established.
     */
    public function isActive(): bool
    {
        return $this->activeNode !== '';
    }

    /**
     * Establishes a DB connection.
     *
     * It does nothing if a DB connection has already been established.
     *
     * @throws Exception If connection fails or autodetectCluster is true and no active node(s) found.
     */
    public function open(): void
    {
        if ($this->activeNode !== '') {
            return;
        }

        if (empty($this->nodes)) {
            throw new InvalidArgumentException('Elasticsearch needs at least one node to operate.');
        }

        $this->curl = curl_init();

        if ($this->autodetectCluster) {
            $this->populateNodes();
        }

        $this->selectActiveNode();
    }

    /**
     * Set timeout to use for connecting to an Elasticsearch node.
     *
     * This value will be used to configure the curl `CURLOPT_CONNECTTIMEOUT` option.
     *
     * If not set, no explicit timeout will be set for curl.
     *
     * @param float|null $timeOut The timeout to use for connecting to an Elasticsearch node.
     */
    public function timeOut(float $timeOut = null): self
    {
        $this->timeOut = $timeOut;
        return $this;
    }


    /**
     * Populates {@see nodes} with the result of a cluster nodes request.
     *
     * @throws Exception If no active node(s) found.
     */
    protected function populateNodes(): void
    {
        $node = reset($this->nodes);
        $host = $node['http_address'];
        $protocol = $node['protocol'] ?? $this->defaultProtocol;

        if (strncmp($host, 'inet[/', 6) === 0) {
            $host = substr($host, 6, -1);
        }

        $response = $this->httpRequest('GET', "$protocol://$host/_nodes/_all/http");

        if (!empty($response['nodes'])) {
            $nodes = $response['nodes'];
        } else {
            $nodes = [];
        }

        foreach ($nodes as $key => &$node) {
            /**
             * Make sure that nodes have an 'http_address' property, which isn't the case if you're using AWS.
             * Elasticsearch service (at least as of Oct., 2015). - TO BE VERIFIED.
             * Temporary workaround - simply ignore all invalid nodes.
             */
            if (!isset($node['http']['publish_address'])) {
                unset($nodes[$key]);
            }

            $node['http_address'] = $node['http']['publish_address'];

            // Protocol isn't a standard ES node property, so we add it manually
            $node['protocol'] = $this->defaultProtocol;
        }

        if (!empty($nodes)) {
            $this->nodes = array_values($nodes);
        } else {
            curl_close($this->curl);
            throw new RuntimeException(
                'Cluster autodetection did not find any active node. Make sure a GET /_nodes reguest on the hosts defined in the config returns the "http_address" field for each node.'
            );
        }
    }

    /**
     * select active node.
     *
     * @throws Exception
     */
    protected function selectActiveNode(): void
    {
        $keys = array_keys($this->nodes);
        $this->activeNode = (string) $keys[random_int(0, count($keys) - 1)];
    }

    /**
     * Performs GET HTTP request
     *
     * @param array|string $url URL.
     * @param array $options URL options.
     * @param string|null $body Request body
     * @param bool $raw If response body has JSON and should be decoded.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function get(array|string $url, array $options = [], string $body = null, bool $raw = false): mixed
    {
        $this->open();
        return $this->httpRequest('GET', $this->createUrl($url, $options), $body, $raw);
    }

    /**
     * Performs HEAD HTTP request
     *
     * @param array|string $url URL.
     * @param array $options URL options.
     * @param string|null $body Request body.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function head(array|string $url, array $options = [], string $body = null): mixed
    {
        $this->open();
        return $this->httpRequest('HEAD', $this->createUrl($url, $options), $body);
    }

    /**
     * Performs POST HTTP request
     *
     * @param array|string $url URL.
     * @param array $options URL options.
     * @param string|null $body Request body.
     * @param bool $raw If response body has JSON and should be decoded
     *
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function post(array|string $url, array $options = [], string $body = null, bool $raw = false): mixed
    {
        $this->open();
        return $this->httpRequest('POST', $this->createUrl($url, $options), $body, $raw);
    }

    /**
     * Performs PUT HTTP request
     *
     * @param array|string $url URL.
     * @param array $options URL options.
     * @param string|null $body Request body.
     * @param bool $raw If response body has JSON and should be decoded/
     *
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function put(array|string $url, array $options = [], string $body = null, bool $raw = false): mixed
    {
        $this->open();
        return $this->httpRequest('PUT', $this->createUrl($url, $options), $body, $raw);
    }

    /**
     * Performs DELETE HTTP request.
     *
     * @param string|array $url URL.
     * @param array $options URL options.
     * @param string|null $body Request body.
     * @param bool $raw If response body has JSON and should be decoded.
     *
     * @return mixed
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function delete(string|array $url, array $options = [], string $body = null, bool $raw = false): mixed
    {
        $this->open();
        return $this->httpRequest('DELETE', $this->createUrl($url, $options), $body, $raw);
    }

    /**
     * Creates URL.
     *
     * @param string|array $path URL path.
     * @param array $options URL options.
     */
    private function createUrl(string|array $path, array $options = []): array
    {
        if (!is_string($path)) {
            $url = implode('/', array_map(static function ($a) {
                return urlencode(is_array($a) ? implode(',', $a) : (string) $a);
            }, $path));
            if (!empty($options)) {
                $url .= '?' . http_build_query($options);
            }
        } else {
            $url = $path;
            if (!empty($options)) {
                $url .= (!str_contains($url, '?') ? '?' : '&') . http_build_query($options);
            }
        }

        $node = $this->nodes[$this->activeNode];
        $protocol = $node['protocol'] ?? $this->defaultProtocol;
        $host = $node['http_address'];

        return [$protocol, $host, $url];
    }

    /**
     * Try to decode error information if it's valid json, return it if not.ll
     */
    protected function decodeErrorBody(string $body): mixed
    {
        try {
            $decoded = Json::decode($body);
            if (isset($decoded['error']) && !is_array($decoded['error'])) {
                $decoded['error'] = preg_replace(
                    '/\b\w+?Exception\[/',
                    "<span style=\"color: red;\">\\0</span>\n               ",
                    $decoded['error'],
                );
            }
            return $decoded;
        } catch (InvalidArgumentException $e) {
            return $body;
        }
    }

    /**
     * Performs HTTP request.
     *
     * @param string $method The method name.
     * @param string|array $url Request URL.
     * @param string|null $requestBody Request body.
     * @param bool $raw If response body has JSON and should be decoded.
     *
     * @return mixed
     * @throws InvalidArgumentException
     */
    protected function httpRequest(
        string $method,
        string|array $url,
        string $requestBody = null,
        bool $raw = false
    ): mixed {
        $method = strtoupper($method);

        // response body and headers
        $headers = [];
        $headersFinished = false;
        $body = '';

        $options = [
            CURLOPT_USERAGENT      => 'Yii Framework 3.0' . ' ' . __CLASS__,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_HEADER         => false,
            // http://www.php.net/manual/en/function.curl-setopt.php#82418
            CURLOPT_HTTPHEADER     => [
                'Expect:',
                'Content-Type: application/json',
            ],

            CURLOPT_WRITEFUNCTION  => function (CurlHandle $curl, string $data) use (&$body): int {
                $body .= $data;

                return mb_strlen($data, '8bit');
            },
            CURLOPT_HEADERFUNCTION => function (CurlHandle $curl, string $data) use (&$headers, &$headersFinished): int {
                if ($data === '') {
                    $headersFinished = true;
                } elseif ($headersFinished) {
                    $headersFinished = false;
                }

                if (!$headersFinished && ($pos = strpos($data, ':')) !== false) {
                    $headers[strtolower(substr($data, 0, $pos))] = trim(substr($data, $pos + 1));
                }

                return mb_strlen($data, '8bit');
            },
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_FORBID_REUSE   => false,
        ];

        foreach ($this->curlOptions as $key => $value) {
            $options[$key] = $value;
        }

        if (!empty($this->auth) || (isset($this->nodes[$this->activeNode]['auth']) && $this->nodes[$this->activeNode]['auth'] !== false)) {
            $auth = $this->nodes[$this->activeNode]['auth'] ?? $this->auth;
            if (empty($auth['username'])) {
                throw new InvalidArgumentException('Username is required to use authentication');
            }
            if (empty($auth['password'])) {
                throw new InvalidArgumentException('Password is required to use authentication');
            }

            $options[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
            $options[CURLOPT_USERPWD] = $auth['username'] . ':' . $auth['password'];
        }

        if ($this->timeOut !== null) {
            $options[CURLOPT_CONNECTTIMEOUT] = $this->timeOut;
        }
        if ($this->dataTimeout !== null) {
            $options[CURLOPT_TIMEOUT] = $this->dataTimeout;
        }
        if ($requestBody !== null) {
            $options[CURLOPT_POSTFIELDS] = $requestBody;
        }
        if ($method === 'HEAD') {
            $options[CURLOPT_NOBODY] = true;
            unset($options[CURLOPT_WRITEFUNCTION]);
        } else {
            $options[CURLOPT_NOBODY] = false;
        }

        if (is_array($url)) {
            [$protocol, $host, $q] = $url;
            if (strncmp($host, 'inet[', 5) === 0) {
                $host = substr($host, 5, -1);
                if (($pos = strpos($host, '/')) !== false) {
                    $host = substr($host, $pos + 1);
                }
            }
            $profile = "$method $q#$requestBody";
            $url = "$protocol://$host/$q";
        } else {
            $profile = false;
        }

        $token = 'Yii Framework 3.0' . ' ' . __CLASS__;
        $connectionContext = new ConnectionContext(__METHOD__);

        if ($profile !== false) {
            $this->profiler?->begin($token, $connectionContext);
        }

        $this->logger?->log(
            LogLevel::INFO,
            'Sending request to Elasticsearch node: $method $url\n$requestBody", __METHOD__',
        );

        $this->resetCurlHandle();

        curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt_array($this->curl, $options);

        if (curl_exec($this->curl) === false) {
            throw new RuntimeException(
                'Elasticsearch request failed: ' . curl_errno($this->curl) . ' - ' . curl_error($this->curl)
            );
        }

        $responseCode = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);

        if ($profile !== false) {
            $this->profiler?->end($token, $connectionContext);
        }

        if ($responseCode >= 200 && $responseCode < 300) {
            if ($method === 'HEAD') {
                return true;
            }

            if (isset($headers['content-length']) && ($len = mb_strlen($body, '8bit')) < $headers['content-length']) {
                throw new RuntimeException(
                    "Incomplete data received from Elasticsearch: $len < {$headers['content-length']}"
                );
            }
            if (isset($headers['content-type'])) {
                if (!strncmp($headers['content-type'], 'application/json', 16)) {
                    return $raw ? $body : Json::decode($body);
                }
                if (!strncmp($headers['content-type'], 'text/plain', 10)) {
                    return $raw ? $body : array_filter(explode("\n", $body));
                }
            }
            throw new RuntimeException('Unsupported data received from Elasticsearch: ' . $headers['content-type']);
        }

        if ($responseCode === 404) {
            return false;
        }

        throw new RuntimeException(
            "Elasticsearch request failed with code $responseCode. Response body:\n$body",
        );
    }

    private function resetCurlHandle(): void
    {
        // these functions don't get reset by curl automatically
        $unsetValues = [
            CURLOPT_HEADERFUNCTION => null,
            CURLOPT_WRITEFUNCTION => null,
            CURLOPT_READFUNCTION => null,
            CURLOPT_PROGRESSFUNCTION => null,
            CURLOPT_POSTFIELDS => null,
        ];

        curl_setopt_array($this->curl, $unsetValues);

        if (function_exists('curl_reset')) { // since PHP 5.5.0
            curl_reset($this->curl);
        }
    }
}
