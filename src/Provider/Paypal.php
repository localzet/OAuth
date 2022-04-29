<?php

/**********************************************************
 *  @author Ivan Zorin <creator@localzet.ru>              *
 *  @license GNU General Public License v3.0              *
 *  @copyright Zorin Projects <www.localzet.ru>           *
 **********************************************************/

namespace LIS\Provider;

use LIS\Adapter\OAuth2;
use LIS\Exception\UnexpectedApiResponseException;
use LIS\Data;
use LIS\User;

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
