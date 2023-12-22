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
 * TwitchTV OAuth2 provider adapter.
 */
class TwitchTV extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'user:read:email';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://api.twitch.tv/helix/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://id.twitch.tv/oauth2/authorize';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://id.twitch.tv/oauth2/token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://dev.twitch.tv/docs/authentication/';

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        parent::initialize();

        $this->apiRequestHeaders['Client-ID'] = $this->clientId;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $response = $this->apiRequest('users');

        $data = new Data\Collection($response);

        if (!$data->exists('data')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $users = $data->filter('data')->values();
        $user = new Data\Collection($users[0]);

        $userProfile = new User\Profile();

        $userProfile->identifier = $user->get('id');
        $userProfile->displayName = $user->get('display_name');
        $userProfile->photoURL = $user->get('profile_image_url');
        $userProfile->email = $user->get('email');
        $userProfile->description = strip_tags($user->get('description'));
        $userProfile->profileURL = "https://www.twitch.tv/{$userProfile->displayName}";

        return $userProfile;
    }
}
