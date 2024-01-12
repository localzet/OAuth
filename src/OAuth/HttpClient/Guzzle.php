<?php declare(strict_types=1);

/**
 * @package     Localzet OAuth
 * @link        https://github.com/localzet/OAuth
 *
 * @author      Ivan Zorin <creator@localzet.com>
 * @copyright   Copyright (c) 2018-2024 Localzet Group
 * @license     https://www.gnu.org/licenses/agpl-3.0 GNU Affero General Public License v3.0
 *
 *              This program is free software: you can redistribute it and/or modify
 *              it under the terms of the GNU Affero General Public License as published
 *              by the Free Software Foundation, either version 3 of the License, or
 *              (at your option) any later version.
 *
 *              This program is distributed in the hope that it will be useful,
 *              but WITHOUT ANY WARRANTY; without even the implied warranty of
 *              MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *              GNU Affero General Public License for more details.
 *
 *              You should have received a copy of the GNU Affero General Public License
 *              along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 *              For any questions, please contact <creator@localzet.com>
 */

namespace localzet\OAuth\HttpClient;

use CURLFile;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\TransferException;

/**
 * OAuth Guzzle Http client
 *
 * Note: This is just a proof of concept. Feel free to improve it.
 *
 * Example:
 *
 * <code>
 *  $guzzle = new localzet\OAuth\HttpClient\Guzzle(new GuzzleHttp\Client(), [
 *      'verify' => '/path/to/your/certificate.crt',
 *      'headers' => ['User-Agent' => '..']
 *      // 'proxy' => ...
 *  ]);
 *
 *  $adapter = new localzet\OAuth\Provider\Github($config, $guzzle);
 *
 *  $adapter->authenticate();
 * </code>
 */
class Guzzle implements HttpClientInterface
{
    /**
     * Method request() arguments
     *
     * This is used for debugging.
     *
     * @var array
     */
    protected $requestArguments = [];

    /**
     * Default request headers
     *
     * @var array
     */
    protected $requestHeader = [];

    /**
     * Raw response returned by server
     *
     * @var string
     */
    protected $responseBody = '';

    /**
     * Headers returned in the response
     *
     * @var array
     */
    protected $responseHeader = [];

    /**
     * Response HTTP status code
     *
     * @var int
     */
    protected $responseHttpCode = 0;

    /**
     * Last curl error number
     *
     * @var mixed
     */
    protected $responseClientError = null;

    /**
     * Information about the last transfer
     *
     * @var mixed
     */
    protected $responseClientInfo = [];

    /**
     * OAuth logger instance
     *
     * @var object
     */
    protected $logger = null;

    /**
     * GuzzleHttp client
     *
     * @var Client
     */
    protected $client = null;

    /**
     * ..
     * @param null $client
     * @param array $config
     */
    public function __construct($client = null, $config = [])
    {
        $this->client = $client ? $client : new Client($config);
    }

    /**
     * {@inheritdoc}
     */
    public function request($uri, $method = 'GET', $parameters = [], $headers = [], $multipart = false)
    {
        $this->requestHeader = array_replace($this->requestHeader, (array)$headers);

        $this->requestArguments = [
            'uri' => $uri,
            'method' => $method,
            'parameters' => $parameters,
            'headers' => $this->requestHeader,
        ];

        $response = null;

        try {
            switch ($method) {
                case 'GET':
                case 'DELETE':
                    $response = $this->client->request($method, $uri, [
                        'query' => $parameters,
                        'headers' => $this->requestHeader,
                    ]);
                    break;
                case 'PUT':
                case 'PATCH':
                case 'POST':
                    $body_type = $multipart ? 'multipart' : 'form_params';

                    if (isset($this->requestHeader['Content-Type'])
                        && $this->requestHeader['Content-Type'] === 'application/json'
                    ) {
                        $body_type = 'json';
                    }

                    $body_content = $parameters;
                    if ($multipart) {
                        $body_content = [];
                        foreach ($parameters as $key => $val) {
                            if ($val instanceof CURLFile) {
                                $val = fopen($val->getFilename(), 'r');
                            }

                            $body_content[] = [
                                'name' => $key,
                                'contents' => $val,
                            ];
                        }
                    }

                    $response = $this->client->request($method, $uri, [
                        $body_type => $body_content,
                        'headers' => $this->requestHeader,
                    ]);
                    break;
            }
        } catch (Exception $e) {
            $response = $e->getResponse();

            $this->responseClientError = $e->getMessage();
        }

        if (!$this->responseClientError) {
            $this->responseBody = $response->getBody();
            $this->responseHttpCode = $response->getStatusCode();
            $this->responseHeader = $response->getHeaders();
        }

        if ($this->logger) {
            // phpcs:ignore
            $this->logger->debug(sprintf('%s::request( %s, %s ), response:', get_class($this), $uri, $method), $this->getResponse());

            if ($this->responseClientError) {
                // phpcs:ignore
                $this->logger->error(sprintf('%s::request( %s, %s ), error:', get_class($this), $uri, $method), [$this->responseClientError]);
            }
        }

        return $this->responseBody;
    }

    /**
     * Get response details
     *
     * @return array Map structure of details
     */
    public function getResponse()
    {
        return [
            'request' => $this->getRequestArguments(),
            'response' => [
                'code' => $this->getResponseHttpCode(),
                'headers' => $this->getResponseHeader(),
                'body' => $this->getResponseBody(),
            ],
            'client' => [
                'error' => $this->getResponseClientError(),
                'info' => $this->getResponseClientInfo(),
                'opts' => null,
            ],
        ];
    }

    /**
     * Set logger instance
     *
     * @param object $logger
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function getResponseBody()
    {
        return $this->responseBody;
    }

    /**
     * {@inheritdoc}
     */
    public function getResponseHeader()
    {
        return $this->responseHeader;
    }

    /**
     * {@inheritdoc}
     */
    public function getResponseHttpCode()
    {
        return $this->responseHttpCode;
    }

    /**
     * {@inheritdoc}
     */
    public function getResponseClientError()
    {
        return $this->responseClientError;
    }

    /**
     * @return array
     */
    protected function getResponseClientInfo()
    {
        return $this->responseClientInfo;
    }

    /**
     * Returns method request() arguments
     *
     * This is used for debugging.
     *
     * @return array
     */
    protected function getRequestArguments()
    {
        return $this->requestArguments;
    }
}
