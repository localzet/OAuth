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
use localzet\OAuth\Data;
use localzet\OAuth\Exception\UnexpectedApiResponseException;
use localzet\OAuth\User;

/**
 * Odnoklassniki OAuth2 provider adapter.
 */
class Odnoklassniki extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://api.ok.ru/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://connect.ok.ru/oauth/authorize';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://api.ok.ru/oauth/token.do';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://apiok.ru/en/ext/oauth/';

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        parent::initialize();

        if ($this->isRefreshTokenAvailable()) {
            $this->tokenRefreshParameters += [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $fields = array(
            'uid', 'locale', 'first_name', 'last_name', 'name', 'gender', 'age', 'birthday',
            'has_email', 'current_status', 'current_status_id', 'current_status_date', 'online',
            'photo_id', 'pic_1', 'pic_2', 'pic1024x768', 'location', 'email'
        );

        $sig = md5(
            'application_key=' . $this->config->get('keys')['key'] .
            'fields=' . implode(',', $fields) .
            'method=users.getCurrentUser' .
            md5($this->getStoredData('access_token') . $this->config->get('keys')['secret'])
        );

        $parameters = [
            'access_token' => $this->getStoredData('access_token'),
            'application_key' => $this->config->get('keys')['key'],
            'method' => 'users.getCurrentUser',
            'fields' => implode(',', $fields),
            'sig' => $sig,
        ];

        $response = $this->apiRequest('fb.do', 'GET', $parameters);

        $data = new Data\Collection($response);

        if (!$data->exists('uid')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();


        $userProfile->identifier = $data->get('uid');
        $userProfile->email = $data->get('email');
        $userProfile->firstName = $data->get('first_name');
        $userProfile->lastName = $data->get('last_name');
        $userProfile->displayName = $data->get('name');
        $userProfile->photoURL = $data->get('pic1024x768');
        $userProfile->profileURL = 'http://ok.ru/profile/' . $data->get('uid');

        // Handle birthday.
        if ($data->get('birthday')) {
            $bday = explode('-', $data->get('birthday'));
            $userProfile->birthDay = (int)$bday[0];
            $userProfile->birthMonth = (int)$bday[1];
            $userProfile->birthYear = (int)$bday[2];
        }

        return $userProfile;
    }
}
