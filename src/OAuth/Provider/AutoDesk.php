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
 * AutoDesk OAuth2 provider adapter.
 */
class AutoDesk extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'data:read';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://developer.api.autodesk.com/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl
        = 'https://developer.api.autodesk.com/authentication/v1/authorize';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl
        = 'https://developer.api.autodesk.com/authentication/v1/gettoken';

    /**
     * {@inheritdoc}
     */
    protected $refreshTokenUrl
        = 'https://developer.api.autodesk.com/authentication/v1/refreshtoken';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation
        = 'https://forge.autodesk.com/en/docs/oauth/v2/developers_guide/overview/';

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        parent::initialize();
        
        if ($this->isRefreshTokenAvailable()) {
            $this->tokenRefreshParameters += [
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret,
                'grant_type'    => 'refresh_token',
            ];
        }
    }

    /**
     * {@inheritdoc}
     *
     * See: https://forge.autodesk.com/en/docs/oauth/v2/reference/http/users-@me-GET/
     */
    public function getUserProfile()
    {
        $response = $this->apiRequest('userprofile/v1/users/@me');

        $collection = new Data\Collection($response);

        $userProfile = new User\Profile();

        $userProfile->identifier = $collection->get('userId');
        $userProfile->displayName
            = $collection->get('firstName') .' '. $collection->get('lastName');
        $userProfile->firstName = $collection->get('firstName');
        $userProfile->lastName = $collection->get('lastName');
        $userProfile->email = $collection->get('emailId');
        $userProfile->language = $collection->get('language');
        $userProfile->webSiteURL = $collection->get('websiteUrl');
        $userProfile->photoURL
            = $collection->filter('profileImages')->get('sizeX360');

        return $userProfile;
    }
}
