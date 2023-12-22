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
 * Amazon OAuth2 provider adapter.
 */
class Amazon extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'profile';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://api.amazon.com/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://www.amazon.com/ap/oa';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://api.amazon.com/auth/o2/token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://developer.amazon.com/docs/login-with-amazon/documentation-overview.html';

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $response = $this->apiRequest('user/profile');

        $data = new Data\Collection($response);

        if (!$data->exists('user_id')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();

        $userProfile->identifier = $data->get('user_id');
        $userProfile->displayName = $data->get('name');
        $userProfile->email = $data->get('email');

        return $userProfile;
    }
}
