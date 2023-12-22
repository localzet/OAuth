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
use localzet\OAuth\Exception\Exception;
use localzet\OAuth\Exception\UnexpectedApiResponseException;
use localzet\OAuth\User;

// sYP0tJ5o5A_urI44VTRQn30MBg0

/**
 * Miro OAuth2 provider adapter.
 */
class Miro extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://api.miro.com/v1';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://miro.com/oauth/authorize';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://api.miro.com/v1/oauth/token';
    protected $AuthorizeUrlParameters = [
        'grant_type' => 'authorization_code',
        'client_id' => '',
        'client_secret' => '',
        'code' => '',
        'redirect_uri' => '',
    ];

    /**
     * Load the user profile from the IDp api client
     *
     * @throws Exception
     */
    public function getUserProfile()
    {
        $this->scope = implode(',', []);

        $response = $this->apiRequest($this->apiBaseUrl, 'GET', ['format' => 'json']);

        if (!isset($response->id)) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $data = new Data\Collection($response);

        if (!$data->exists('id')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();
        $userProfile->identifier = $data->get('id');
        $userProfile->firstName = $data->get('first_name');
        $userProfile->lastName = $data->get('last_name');
        $userProfile->displayName = $data->get('display_name');
        $userProfile->photoURL
            = 'https://avatars.yandex.net/get-yapic/' .
            $data->get('default_avatar_id') . '/islands-200';
        $userProfile->gender = $data->get('sex');
        $userProfile->email = $data->get('default_email');
        $userProfile->emailVerified = $data->get('default_email');

        if ($data->get('birthday')) {
            list($birthday_year, $birthday_month, $birthday_day)
                = explode('-', $response->birthday);
            $userProfile->birthDay = (int)$birthday_day;
            $userProfile->birthMonth = (int)$birthday_month;
            $userProfile->birthYear = (int)$birthday_year;
        }

        return $userProfile;
    }
}
