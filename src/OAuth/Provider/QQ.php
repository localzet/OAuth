<?php declare(strict_types=1);

/**
 * @package     Localzet OAuth
 * @link        https://github.com/localzet/OAuth
 *
 * @author      Ivan Zorin <creator@localzet.com>
 * @copyright   Copyright (c) 2018-2023 Localzet Group
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

namespace localzet\OAuth\Provider;

use localzet\OAuth\Adapter\OAuth2;
use localzet\OAuth\Exception\UnexpectedApiResponseException;
use localzet\OAuth\Data;
use localzet\OAuth\User\Profile;

/**
 * Tencent QQ International OAuth2 provider adapter.
 */
class QQ extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'get_user_info';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://graph.qq.com/oauth2.0/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://graph.qq.com/oauth2.0/authorize';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://graph.qq.com/oauth2.0/token';

    /**
     * {@ịnheritdoc}
     */
    protected $accessTokenInfoUrl = 'https://graph.qq.com/oauth2.0/me';

    /**
     * User Information Endpoint
     * @var string
     */
    protected $accessUserInfo = 'https://graph.qq.com/user/get_user_info';

    /**
     * {@inheritdoc}
     */
    protected $tokenExchangeMethod = 'GET';

    /**
     * {@inheritdoc}
     */
    protected $tokenRefreshMethod = 'GET';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = ''; // Not available

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        parent::initialize();

        if ($this->isRefreshTokenAvailable()) {
            $this->tokenRefreshParameters += [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ];
        }

        $this->apiRequestParameters = [
            'access_token' => $this->getStoredData('access_token')
        ];

        $this->apiRequestHeaders = [];
    }

    /**
     * {@inheritdoc}
     */
    protected function validateAccessTokenExchange($response)
    {
        $collection = parent::validateAccessTokenExchange($response);

        $resp = $this->apiRequest($this->accessTokenInfoUrl);
        $resp = key($resp);

        $len = strlen($resp);
        $res = substr($resp, 10, $len - 14);

        $response = (new Data\Parser())->parse($res);

        if (!isset($response->openid)) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $this->storeData('openid', $response->openid);

        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $openid = $this->getStoredData('openid');

        $userRequestParameters = [
            'oauth_consumer_key' => $this->clientId,
            'openid' => $openid,
            'format' => 'json'
        ];

        $response = $this->apiRequest($this->accessUserInfo, 'GET', $userRequestParameters);

        $data = new Data\Collection($response);

        if ($data->get('ret') < 0) {
            throw new UnexpectedApiResponseException('Provider API returned an error: ' . $data->get('msg'));
        }

        $userProfile = new Profile();

        $userProfile->identifier = $openid;
        $userProfile->displayName = $data->get('nickname');
        $userProfile->photoURL = $data->get('figureurl_2');
        $userProfile->gender = $data->get('gender');
        $userProfile->region = $data->get('province');
        $userProfile->city = $data->get('city');

        return $userProfile;
    }
}
