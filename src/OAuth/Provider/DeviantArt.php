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
 * DeviantArt OAuth2 provider adapter.
 */
class DeviantArt extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'user';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://www.deviantart.com/api/v1/oauth2/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://www.deviantart.com/oauth2/authorize';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://www.deviantart.com/oauth2/token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://www.deviantart.com/developers/http/v1/20200519';

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
    }

    /**
     * {@inheritdoc}
     *
     * See: https://www.deviantart.com/developers/http/v1/20200519/user_whoami/2413749853e66c5812c9beccc0ab3495
     */
    public function getUserProfile()
    {
        $response = $this->apiRequest('user/whoami');

        $data = new Data\Collection($response);

        $userProfile = new User\Profile();

        $full_name = explode(' ', $data->filter('profile')->get('real_name'));
        if (count($full_name) < 2) {
            $full_name[1] = '';
        }

        $userProfile->identifier = $data->get('userid');
        $userProfile->displayName = $data->get('username');
        $userProfile->profileURL = $data->get('usericon');
        $userProfile->webSiteURL = $data->filter('profile')->get('website');
        $userProfile->firstName = $full_name[0];
        $userProfile->lastName = $full_name[1];
        $userProfile->profileURL = $data->filter('profile')->filter('profile_pic')->get('url');
        $userProfile->gender = $data->filter('details')->get('sex');
        $userProfile->age = $data->filter('details')->get('age');
        $userProfile->country = $data->filter('geo')->get('country');

        return $userProfile;
    }
}
