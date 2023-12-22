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
 * Dropbox OAuth2 provider adapter.
 */
class Dropbox extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'account_info.read';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://api.dropbox.com/2/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://www.dropbox.com/1/oauth2/authorize';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://api.dropbox.com/1/oauth2/token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://www.dropbox.com/developers/documentation/http/documentation';

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $response = $this->apiRequest('users/get_current_account', 'POST', [], [], true);

        $data = new Data\Collection($response);

        if (!$data->exists('account_id') || !$data->get('account_id')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();

        $userProfile->identifier = $data->get('account_id');
        $userProfile->displayName = $data->filter('name')->get('display_name');
        $userProfile->firstName = $data->filter('name')->get('given_name');
        $userProfile->lastName = $data->filter('name')->get('surname');
        $userProfile->email = $data->get('email');
        $userProfile->photoURL = $data->get('profile_photo_url');
        $userProfile->language = $data->get('locale');
        $userProfile->country = $data->get('country');
        if ($data->get('email_verified')) {
            $userProfile->emailVerified = $data->get('email');
        }

        return $userProfile;
    }
}
