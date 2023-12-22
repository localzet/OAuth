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
 * Paypal OAuth2 provider adapter.
 */
class Paypal extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'openid profile email address';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://api.paypal.com/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://www.paypal.com/signin/authorize';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://api.paypal.com/v1/oauth2/token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://developer.paypal.com/docs/api/overview/#';

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        parent::initialize();

        $this->AuthorizeUrlParameters += [
            'flowEntry' => 'static'
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
     *
     * See: https://developer.paypal.com/docs/api/identity/v1/
     * See: https://developer.paypal.com/docs/connect-with-paypal/integrate/
     */
    public function getUserProfile()
    {
        $headers = [
            'Content-Type' => 'application/json',
        ];

        $parameters = [
            'schema' => 'paypalv1.1'
        ];

        $response = $this->apiRequest('v1/identity/oauth2/userinfo', 'GET', $parameters, $headers);
        $data = new Data\Collection($response);

        if (!$data->exists('user_id')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();
        $userProfile->identifier = $data->get('user_id');
        $userProfile->firstName = $data->get('given_name');
        $userProfile->lastName = $data->get('family_name');
        $userProfile->displayName = $data->get('name');
        $userProfile->address = $data->filter('address')->get('street_address');
        $userProfile->city = $data->filter('address')->get('locality');
        $userProfile->country = $data->filter('address')->get('country');
        $userProfile->region = $data->filter('address')->get('region');
        $userProfile->zip = $data->filter('address')->get('postal_code');

        $emails = $data->filter('emails')->toArray();
        foreach ($emails as $email) {
            $email = new Data\Collection($email);
            if ($email->get('confirmed')) {
                $userProfile->emailVerified = $email->get('value');
            }

            if ($email->get('primary')) {
                $userProfile->email = $email->get('value');
            }
        }

        return $userProfile;
    }
}
