<?php

/**********************************************************
 *  @author Ivan Zorin <creator@localzet.ru>              *
 *  @license GNU General Public License v3.0              *
 *  @copyright Zorin Projects <www.localzet.ru>           *
 **********************************************************/

namespace LIS\HttpClient;

/**
 * LIS Http clients interface
 */
interface HttpClientInterface
{
    /**
     * Send request to the remote server
     *
     * Returns the result (Raw response from the server) on success, FALSE on failure
     *
     * @param string $uri
     * @param string $method
     * @param array $parameters
     * @param array $headers
     * @param bool $multipart
     *
     * @return mixed
     */
    public function request($uri, $method = 'GET', $parameters = [], $headers = [], $multipart = false);

    /**
     * Returns raw response from the server on success, FALSE on failure
     *
     * @return mixed
     */
    public function getResponseBody();

    /**
     * Retriever the headers returned in the response
     *
     * @return array
     */
    public function getResponseHeader();

    /**
     * Returns latest request HTTP status code
     *
     * @return int
     */
    public function getResponseHttpCode();

    /**
     * Returns latest error encountered by the client
     * This can be either a code or error message
     *
     * @return mixed
     */
    public function getResponseClientError();
}
