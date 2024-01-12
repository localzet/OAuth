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
 * Authentiq OAuth2 provider adapter.
 */
class Authentiq extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'aq:name email~rs aq:push openid';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://connect.authentiq.io/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://connect.authentiq.io/authorize';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://connect.authentiq.io/token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'http://developers.authentiq.io/';

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        parent::initialize();

        $this->AuthorizeUrlParameters += [
            'prompt' => 'consent'
        ];

        $this->tokenExchangeHeaders = [
            'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret)
        ];

        $this->tokenRefreshHeaders = [
            'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret)
        ];
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

        $userProfile->displayName = $data->get('name');
        $userProfile->firstName = $data->get('given_name');
        // $userProfile->middleName  = $data->get('middle_name'); // not supported
        $userProfile->lastName = $data->get('family_name');

        if (!empty($userProfile->displayName)) {
            $userProfile->displayName = join(' ', array($userProfile->firstName,
                // $userProfile->middleName,
                $userProfile->lastName));
        }

        $userProfile->email = $data->get('email');
        $userProfile->emailVerified = $data->get('email_verified') ? $userProfile->email : '';

        $userProfile->phone = $data->get('phone');
        // $userProfile->phoneVerified = $data->get('phone_verified') ? $userProfile->phone : ''; // not supported

        $userProfile->profileURL = $data->get('profile');
        $userProfile->webSiteURL = $data->get('website');
        $userProfile->photoURL = $data->get('picture');
        $userProfile->gender = $data->get('gender');
        $userProfile->address = $data->filter('address')->get('street_address');
        $userProfile->city = $data->filter('address')->get('locality');
        $userProfile->country = $data->filter('address')->get('country');
        $userProfile->region = $data->filter('address')->get('region');
        $userProfile->zip = $data->filter('address')->get('postal_code');

        return $userProfile;
    }
}
