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
use localzet\OAuth\User;

/**
 * For this provider to work it is necessary to assign the "OpenID Connect Permissions",
 * even if you only use basic OAuth2.
 */

/**
 * Yahoo OAuth2 provider adapter.
 */
class Yahoo extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'profile';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://api.login.yahoo.com/openid/v1/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://api.login.yahoo.com/oauth2/request_auth';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://api.login.yahoo.com/oauth2/get_token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://developer.yahoo.com/oauth2/guide/';

    /**
     * Currently authenticated user
     */
    protected $userId = null;

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        parent::initialize();

        $this->tokenExchangeHeaders = [
            'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret)
        ];

        $this->tokenRefreshHeaders = $this->tokenExchangeHeaders;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $response = $this->apiRequest('userinfo');

        $data = new Data\Collection($response);

        if (!$data->exists('sub')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();

        $userProfile->identifier = $data->get('sub');
        $userProfile->firstName = $data->get('given_name');
        $userProfile->lastName = $data->get('family_name');
        $userProfile->displayName = $data->get('name');
        $userProfile->gender = $data->get('gender');
        $userProfile->language = $data->get('locale');
        $userProfile->email = $data->get('email');

        $userProfile->emailVerified = $data->get('email_verified') ? $userProfile->email : '';

        $profileImages = $data->get('profile_images');
        if ($this->config->get('photo_size')) {
            $prop = 'image' . $this->config->get('photo_size');
        } else {
            $prop = 'image192';
        }
        $userProfile->photoURL = $profileImages->$prop;

        return $userProfile;
    }
}
