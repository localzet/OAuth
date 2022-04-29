<?php

/**********************************************************
 *  @author Ivan Zorin <creator@localzet.ru>              *
 *  @license GNU General Public License v3.0              *
 *  @copyright Zorin Projects <www.localzet.ru>           *
 **********************************************************/

namespace LIS\Provider;

use LIS\Adapter\OAuth2;
use LIS\Exception\UnexpectedApiResponseException;
use LIS\Data;
use LIS\User;

/**
 * StackExchange OAuth2 provider adapter.
 *
 * Example:
 *
 *   $config = [
 *       'callback' => LIS\HttpClient\Util::getCurrentUrl(),
 *       'keys' => ['id' => '', 'secret' => ''],
 *       'site' => 'stackoverflow' // required parameter to call getUserProfile()
 *       'api_key' => '...' // that thing to receive a higher request quota.
 *   ];
 *
 *   $adapter = new LIS\Provider\StackExchange($config);
 *
 *   try {
 *       $adapter->authenticate();
 *
 *       $userProfile = $adapter->getUserProfile();
 *       $tokens = $adapter->getAccessToken();
 *   } catch (\Exception $e ){
 *       echo $e->getMessage() ;
 *   }
 */
class StackExchange extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = null;

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://api.stackexchange.com/2.2/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://stackexchange.com/oauth';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://stackexchange.com/oauth/access_token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://api.stackexchange.com/docs/authentication';

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        parent::initialize();

        $apiKey = $this->config->get('api_key');

        $this->apiRequestParameters = ['key' => $apiKey];
    }

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $site = $this->config->get('site');

        $response = $this->apiRequest('me', 'GET', [
            'site' => $site,
            'access_token' => $this->getStoredData('access_token'),
        ]);

        if (!$response || !isset($response->items) || !isset($response->items[0])) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $data = new Data\Collection($response->items[0]);

        $userProfile = new User\Profile();

        $userProfile->identifier = strval($data->get('user_id'));
        $userProfile->displayName = $data->get('display_name');
        $userProfile->photoURL = $data->get('profile_image');
        $userProfile->profileURL = $data->get('link');
        $userProfile->region = $data->get('location');
        $userProfile->age = $data->get('age');

        return $userProfile;
    }
}
