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
 * Discord OAuth2 provider adapter.
 */
class Discord extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'identify email';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://discordapp.com/api/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://discordapp.com/api/oauth2/authorize';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://discordapp.com/api/oauth2/token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://discordapp.com/developers/docs/topics/oauth2';

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
     */
    public function getUserProfile()
    {
        $response = $this->apiRequest('users/@me');

        $data = new Data\Collection($response);

        if (!$data->exists('id')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        // Makes display name more unique.
        $displayName = $data->get('username') ?: $data->get('login');
        if ($discriminator = $data->get('discriminator')) {
            $displayName .= "#{$discriminator}";
        }

        $userProfile = new User\Profile();

        $userProfile->identifier = $data->get('id');
        $userProfile->displayName = $displayName;
        $userProfile->email = $data->get('email');

        if ($data->get('verified')) {
            $userProfile->emailVerified = $data->get('email');
        }

        if ($data->get('avatar')) {
            $userProfile->photoURL = 'https://cdn.discordapp.com/avatars/';
            $userProfile->photoURL .= $data->get('id') . '/' . $data->get('avatar') . '.png';
        }

        return $userProfile;
    }
}
