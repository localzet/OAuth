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
use localzet\OAuth\Exception\InvalidApplicationCredentialsException;
use localzet\OAuth\Exception\UnexpectedApiResponseException;
use localzet\OAuth\User;

/**
 * Keycloak OpenId Connect provider adapter.
 *
 * Example:
 *         'Keycloak' => [
 *             'enabled' => true,
 *             'url' => 'https://your-keycloak', // depending on your setup you might need to add '/auth'
 *             'realm' => 'your-realm',
 *             'keys' => [
 *                 'id' => 'client-id',
 *                 'secret' => 'client-secret'
 *             ]
 *         ]
 *
 */
class Keycloak extends OAuth2
{

    /**
     * {@inheritdoc}
     */
    public $scope = 'openid profile email';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://www.keycloak.org/docs/latest/securing_apps/#_oidc';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        if (!$this->config->exists('url')) {
            throw new InvalidApplicationCredentialsException(
                'You must define a provider url'
            );
        }
        $url = $this->config->get('url');

        if (!$this->config->exists('realm')) {
            throw new InvalidApplicationCredentialsException(
                'You must define a realm'
            );
        }
        $realm = $this->config->get('realm');

        $this->apiBaseUrl = $url . '/realms/' . $realm . '/protocol/openid-connect/';

        $this->authorizeUrl = $this->apiBaseUrl . 'auth';
        $this->accessTokenUrl = $this->apiBaseUrl . 'token';

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
        $userProfile->displayName = $data->get('preferred_username');
        $userProfile->email = $data->get('email');
        $userProfile->firstName = $data->get('given_name');
        $userProfile->lastName = $data->get('family_name');
        $userProfile->emailVerified = $data->get('email_verified');

        return $userProfile;
    }
}
