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
use localzet\OAuth\User;

/**
 * Medium OAuth2 provider adapter.
 */
class Medium extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'basicProfile';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://api.medium.com/v1/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://medium.com/m/oauth/authorize';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://api.medium.com/v1/tokens';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://github.com/Medium/medium-api-docs';

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
     * See: https://github.com/Medium/medium-api-docs#getting-the-authenticated-users-details
     */
    public function getUserProfile()
    {
        $response = $this->apiRequest('me');

        $data = new Data\Collection($response);

        $userProfile = new User\Profile();
        $data = $data->filter('data');

        $full_name = explode(' ', $data->get('name'));
        if (count($full_name) < 2) {
            $full_name[1] = '';
        }

        $userProfile->identifier = $data->get('id');
        $userProfile->displayName = $data->get('username');
        $userProfile->profileURL = $data->get('imageUrl');
        $userProfile->firstName = $full_name[0];
        $userProfile->lastName = $full_name[1];
        $userProfile->profileURL = $data->get('url');

        return $userProfile;
    }
}
