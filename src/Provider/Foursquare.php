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
 * Foursquare OAuth2 provider adapter.
 */
class Foursquare extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://api.foursquare.com/v2/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://foursquare.com/oauth2/authenticate';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://foursquare.com/oauth2/access_token';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenName = 'oauth_token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://developer.foursquare.com/overview/auth';

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        parent::initialize();

        $apiVersion = $this->config->get('api_version') ?: '20140201';

        $this->apiRequestParameters = [
            'oauth_token' => $this->getStoredData('access_token'),
            'v' => $apiVersion,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $response = $this->apiRequest('users/self');

        $data = new Data\Collection($response);

        if (!$data->exists('response')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();

        $data = $data->filter('response')->filter('user');

        $userProfile->identifier = $data->get('id');
        $userProfile->firstName = $data->get('firstName');
        $userProfile->lastName = $data->get('lastName');
        $userProfile->gender = $data->get('gender');
        $userProfile->city = $data->get('homeCity');
        $userProfile->email = $data->filter('contact')->get('email');
        $userProfile->emailVerified = $userProfile->email;
        $userProfile->profileURL = 'https://www.foursquare.com/user/' . $userProfile->identifier;
        $userProfile->displayName = trim($userProfile->firstName . ' ' . $userProfile->lastName);

        if ($data->exists('photo')) {
            $photoSize = $this->config->get('photo_size') ?: '150x150';

            $userProfile->photoURL = $data->filter('photo')->get('prefix');
            $userProfile->photoURL .= $photoSize . $data->filter('photo')->get('suffix');
        }

        return $userProfile;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserContacts()
    {
        $response = $this->apiRequest('users/self/friends');

        $data = new Data\Collection($response);

        if (!$data->exists('response')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $contacts = [];

        foreach ($data->filter('response')->filter('friends')->filter('items')->toArray() as $item) {
            $contacts[] = $this->fetchUserContact($item);
        }

        return $contacts;
    }

    /**
     * @param $item
     *
     * @return User\Contact
     */
    protected function fetchUserContact($item)
    {
        $photoSize = $this->config->get('photo_size') ?: '150x150';

        $item = new Data\Collection($item);

        $userContact = new User\Contact();

        $userContact->identifier = $item->get('id');
        $userContact->photoURL = $item->filter('photo')->get('prefix');
        $userContact->photoURL .= $photoSize . $item->filter('photo')->get('suffix');
        $userContact->displayName = trim($item->get('firstName') . ' ' . $item->get('lastName'));
        $userContact->email = $item->filter('contact')->get('email');

        return $userContact;
    }
}
