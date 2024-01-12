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

use Exception;
use localzet\OAuth\Adapter\OAuth2;
use localzet\OAuth\Data;
use localzet\OAuth\Exception\UnexpectedApiResponseException;
use localzet\OAuth\User;

/**
 * Github OAuth2 provider adapter.
 */
class GitHub extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'user:email';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://api.github.com/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://github.com/login/oauth/authorize';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://github.com/login/oauth/access_token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://developer.github.com/v3/oauth/';

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $response = $this->apiRequest('user');

        $data = new Data\Collection($response);

        if (!$data->exists('id')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();

        $userProfile->identifier = $data->get('id');
        $userProfile->displayName = $data->get('name');
        $userProfile->description = $data->get('bio');
        $userProfile->photoURL = $data->get('avatar_url');
        $userProfile->profileURL = $data->get('html_url');
        $userProfile->email = $data->get('email');
        $userProfile->webSiteURL = $data->get('blog');
        $userProfile->region = $data->get('location');

        $userProfile->displayName = $userProfile->displayName ?: $data->get('login');

        if (empty($userProfile->email) && strpos($this->scope, 'user:email') !== false) {
            try {
                // user email is not mandatory so keep it quite.
                $userProfile = $this->requestUserEmail($userProfile);
            } catch (Exception $e) {
            }
        }

        return $userProfile;
    }

    /**
     * Request connected user email
     *
     * https://developer.github.com/v3/users/emails/
     * @param User\Profile $userProfile
     *
     * @return User\Profile
     *
     * @throws Exception
     */
    protected function requestUserEmail(User\Profile $userProfile)
    {
        $response = $this->apiRequest('user/emails');

        foreach ($response as $idx => $item) {
            if (!empty($item->primary) && $item->primary == 1) {
                $userProfile->email = $item->email;

                if (!empty($item->verified) && $item->verified == 1) {
                    $userProfile->emailVerified = $userProfile->email;
                }

                break;
            }
        }

        return $userProfile;
    }
}
