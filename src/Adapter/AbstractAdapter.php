<?php

/**********************************************************
 *  @author Ivan Zorin <creator@localzet.ru>              *
 *  @license GNU General Public License v3.0              *
 *  @copyright Zorin Projects <www.localzet.ru>           *
 **********************************************************/

namespace LIS\Adapter;

use LIS\Exception\NotImplementedException;
use LIS\Exception\InvalidArgumentException;
use LIS\Exception\HttpClientFailureException;
use LIS\Exception\HttpRequestFailedException;
use LIS\Storage\StorageInterface;
use LIS\Storage\Session;
use LIS\Logger\LoggerInterface;
use LIS\Logger\Logger;
use LIS\HttpClient\HttpClientInterface;
use LIS\HttpClient\Curl as HttpClient;
use LIS\Data;

/**
 * Class AbstractAdapter
 */
abstract class AbstractAdapter implements AdapterInterface
{
    use DataStoreTrait;

    /**
     * Provider ID (unique name).
     *
     * @var string
     */
    protected $providerId = '';

    /**
     * Specific Provider config.
     *
     * @var mixed
     */
    protected $config = [];

    /**
     * Extra Provider parameters.
     *
     * @var array
     */
    protected $params;

    /**
     * Callback url
     *
     * @var string
     */
    protected $callback = '';

    /**
     * Storage.
     *
     * @var StorageInterface
     */
    public $storage;

    /**
     * HttpClient.
     *
     * @var HttpClientInterface
     */
    public $httpClient;

    /**
     * Logger.
     *
     * @var LoggerInterface
     */
    public $logger;

    /**
     * Whether to validate API status codes of http responses
     *
     * @var bool
     */
    protected $validateApiResponseHttpCode = true;

    /**
     * Common adapters constructor.
     *
     * @param array $config
     * @param HttpClientInterface $httpClient
     * @param StorageInterface $storage
     * @param LoggerInterface $logger
     */
    public function __construct(
        $config = [],
        HttpClientInterface $httpClient = null,
        StorageInterface $storage = null,
        LoggerInterface $logger = null
    ) {
        $this->providerId = (new \ReflectionClass($this))->getShortName();

        $this->config = new Data\Collection($config);

        $this->setHttpClient($httpClient);

        $this->setStorage($storage);

        $this->setLogger($logger);

        $this->configure();

        $this->logger->debug(sprintf('Initialize %s, config: ', get_class($this)), $config);

        $this->initialize();
    }

    /**
     * Load adapter's configuration
     */
    abstract protected function configure();

    /**
     * Adapter initializer
     */
    abstract protected function initialize();

    /**
     * {@inheritdoc}
     */
    abstract public function isConnected();

    /**
     * {@inheritdoc}
     */
    public function apiRequest($url, $method = 'GET', $parameters = [], $headers = [], $multipart = false)
    {
        throw new NotImplementedException('Провайдер не поддерживает эту функцию');
    }

    /**
     * {@inheritdoc}
     */
    public function maintainToken()
    {
        // Nothing needed for most providers
    }

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        throw new NotImplementedException('Провайдер не поддерживает эту функцию');
    }

    /**
     * {@inheritdoc}
     */
    public function getUserContacts()
    {
        throw new NotImplementedException('Провайдер не поддерживает эту функцию');
    }

    /**
     * {@inheritdoc}
     */
    public function getUserPages()
    {
        throw new NotImplementedException('Провайдер не поддерживает эту функцию');
    }

    /**
     * {@inheritdoc}
     */
    public function getUserActivity($stream)
    {
        throw new NotImplementedException('Провайдер не поддерживает эту функцию');
    }

    /**
     * {@inheritdoc}
     */
    public function setUserStatus($status)
    {
        throw new NotImplementedException('Провайдер не поддерживает эту функцию');
    }

    /**
     * {@inheritdoc}
     */
    public function setPageStatus($status, $pageId)
    {
        throw new NotImplementedException('Провайдер не поддерживает эту функцию');
    }

    /**
     * {@inheritdoc}
     */
    public function disconnect()
    {
        $this->clearStoredData();
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessToken()
    {
        $tokenNames = [
            'access_token',
            'access_token_secret',
            'token_type',
            'refresh_token',
            'expires_in',
            'expires_at',
        ];

        $tokens = [];

        foreach ($tokenNames as $name) {
            if ($this->getStoredData($name)) {
                $tokens[$name] = $this->getStoredData($name);
            }
        }

        return $tokens;
    }

    /**
     * {@inheritdoc}
     */
    public function setAccessToken($tokens = [])
    {
        $this->clearStoredData();

        foreach ($tokens as $token => $value) {
            $this->storeData($token, $value);
        }

        // Re-initialize token parameters.
        $this->initialize();
    }

    /**
     * {@inheritdoc}
     */
    public function setHttpClient(HttpClientInterface $httpClient = null)
    {

        $this->httpClient = $httpClient ?: new HttpClient();

        if ($this->config->exists('curl_options') && method_exists($this->httpClient, 'setCurlOptions')) {
            $this->httpClient->setCurlOptions($this->config->get('curl_options'));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getHttpClient()
    {
        return $this->httpClient;
    }

    /**
     * {@inheritdoc}
     */
    public function setStorage(StorageInterface $storage = null)
    {
        $this->storage = $storage ?: new Session();
    }

    /**
     * {@inheritdoc}
     */
    public function getStorage()
    {
        return $this->storage;
    }

    /**
     * {@inheritdoc}
     */
    public function setLogger(LoggerInterface $logger = null)
    {
        $this->logger = $logger ?: new Logger(
            $this->config->get('debug_mode'),
            $this->config->get('debug_file')
        );

        if (method_exists($this->httpClient, 'setLogger')) {
            $this->httpClient->setLogger($this->logger);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Set Adapter's API callback url
     *
     * @param string $callback
     *
     * @throws InvalidArgumentException
     */
    protected function setCallback($callback)
    {
        if (!filter_var($callback, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('Требуется действительный URL-адрес обратного вызова');
        }

        $this->callback = $callback;
    }

    /**
     * Overwrite Adapter's API endpoints
     *
     * @param array|Data\Collection $endpoints
     */
    protected function setApiEndpoints($endpoints = null)
    {
        if (empty($endpoints)) {
            return;
        }

        $collection = is_array($endpoints) ? new Data\Collection($endpoints) : $endpoints;

        $this->apiBaseUrl = $collection->get('api_base_url') ?: $this->apiBaseUrl;
        $this->authorizeUrl = $collection->get('authorize_url') ?: $this->authorizeUrl;
        $this->accessTokenUrl = $collection->get('access_token_url') ?: $this->accessTokenUrl;
    }


    /**
     * Validate signed API responses Http status code.
     *
     * Since the specifics of error responses is beyond the scope of RFC6749 and OAuth Core specifications,
     * LIS will consider any HTTP status code that is different than '200 OK' as an ERROR.
     *
     * @param string $error String to pre append to message thrown in exception
     *
     * @throws HttpClientFailureException
     * @throws HttpRequestFailedException
     */
    protected function validateApiResponse($error = '')
    {
        $error .= !empty($error) ? '. ' : '';

        if ($this->httpClient->getResponseClientError()) {
            throw new HttpClientFailureException(
                $error . 'HTTP Client Error.: ' . $this->httpClient->getResponseClientError() . '.'
            );
        }

        // if validateApiResponseHttpCode is set to false, we by pass verification of http status code
        if (!$this->validateApiResponseHttpCode) {
            return;
        }

        $status = $this->httpClient->getResponseHttpCode();

        if ($status < 200 || $status > 299) {
            throw new HttpRequestFailedException(
                $error . 'HTTP-ошибка ' . $this->httpClient->getResponseHttpCode() .
                '. Raw Provider API response: ' . $this->httpClient->getResponseBody() . '.'
            );
        }
    }
}
