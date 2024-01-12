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

namespace localzet\OAuth\Provider;

use localzet\OAuth\Adapter\OAuth2;
use localzet\OAuth\Data;
use localzet\OAuth\Exception\UnexpectedApiResponseException;
use localzet\OAuth\User;

/**
 * WeChat International OAuth2 provider adapter.
 */
class WeChat extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'snsapi_login,snsapi_userinfo,scope.userInfo';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://api.wechat.com/sns/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://open.weixin.qq.com/connect/qrconnect';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://api.wechat.com/sns/oauth2/access_token';

    /**
     * Refresh Token Endpoint
     * @var string
     */
    protected $tokenRefreshUrl = 'https://api.wechat.com/sns/oauth2/refresh_token';

    /**
     * {@ịnheritdoc}
     */
    protected $accessTokenInfoUrl = 'https://api.wechat.com/sns/auth';

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

        $this->AuthorizeUrlParameters += [
            'appid' => $this->clientId
        ];
        unset($this->AuthorizeUrlParameters['client_id']);

        $this->tokenExchangeParameters += [
            'appid' => $this->clientId,
            'secret' => $this->clientSecret
        ];
        unset($this->tokenExchangeParameters['client_id']);
        unset($this->tokenExchangeParameters['client_secret']);

        if ($this->isRefreshTokenAvailable()) {
            $this->tokenRefreshParameters += [
                'appid' => $this->clientId,
            ];
        }

        $this->apiRequestParameters = [
            'appid' => $this->clientId,
            'secret' => $this->clientSecret
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function validateAccessTokenExchange($response)
    {
        $collection = parent::validateAccessTokenExchange($response);

        $this->storeData('openid', $collection->get('openid'));
        $this->storeData('access_token', $collection->get('access_token'));
    }

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $openid = $this->getStoredData('openid');
        $access_token = $this->getStoredData('access_token');

        $response = $this->apiRequest('userinfo', 'GET', ['openid' => $openid, 'access_token' => $access_token]);

        $data = new Data\Collection($response);

        if (!$data->exists('openid')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();

        $userProfile->identifier = $data->get('openid');
        $userProfile->displayName = $data->get('nickname');
        $userProfile->photoURL = $data->get('headimgurl');
        $userProfile->city = $data->get('city');
        $userProfile->region = $data->get('province');
        $userProfile->country = $data->get('country');
        $genders = ['', 'male', 'female'];
        $userProfile->gender = $genders[(int)$data->get('sex')];

        return $userProfile;
    }
}
