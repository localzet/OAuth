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
 * Pinterest OAuth2 provider adapter.
 */
class Pinterest extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'read_public';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://api.pinterest.com/v1/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://api.pinterest.com/oauth';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://api.pinterest.com/v1/oauth/token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://developers.pinterest.com/docs/api/overview/';

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $response = $this->apiRequest('me');

        $data = new Data\Collection($response);

        $data = $data->filter('data');

        if (!$data->exists('id')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();

        $userProfile->identifier = $data->get('id');
        $userProfile->description = $data->get('bio');
        $userProfile->photoURL = $data->get('image');
        $userProfile->displayName = $data->get('username');
        $userProfile->firstName = $data->get('first_name');
        $userProfile->lastName = $data->get('last_name');
        $userProfile->profileURL = "https://pinterest.com/{$data->get('username')}";

        $userProfile->data = (array)$data->get('counts');

        return $userProfile;
    }
}
