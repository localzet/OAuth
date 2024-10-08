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
use localzet\OAuth\Exception\HttpClientFailureException;
use localzet\OAuth\Exception\HttpRequestFailedException;
use localzet\OAuth\Exception\InvalidAccessTokenException;
use localzet\OAuth\Exception\InvalidArgumentException;
use localzet\OAuth\Exception\UnexpectedApiResponseException;
use localzet\OAuth\User;
use localzet\OAuth\User\Contact;
use localzet\OAuth\User\Profile;

/**
 * Facebook OAuth2 provider adapter.
 *
 * Facebook doesn't use standard OAuth refresh tokens.
 * Instead it has a "token exchange" system. You exchange the token prior to
 * expiry, to push back expiry. You start with a short-lived token and each
 * exchange gives you a long-lived one (90 days).
 * We control this with the 'exchange_by_expiry_days' option.
 *
 * Example:
 *
 *   $config = [
 *       'callback' => localzet\OAuth\HttpClient\Util::getCurrentUrl(),
 *       'keys' => ['id' => '', 'secret' => ''],
 *       'scope' => 'email, user_status, user_posts',
 *       'exchange_by_expiry_days' => 45, // null for no token exchange
 *   ];
 *
 *   $adapter = new localzet\OAuth\Provider\Facebook($config);
 *
 *   try {
 *       $adapter->authenticate();
 *
 *       $userProfile = $adapter->getUserProfile();
 *       $tokens = $adapter->getAccessToken();
 *       $response = $adapter->setUserStatus("OAuth test message..");
 *   } catch (\Exception $e) {
 *       echo $e->getMessage() ;
 *   }
 */
class Facebook extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'email, public_profile';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://graph.facebook.com/v8.0/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://www.facebook.com/dialog/oauth';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://graph.facebook.com/oauth/access_token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://developers.facebook.com/docs/facebook-login/overview';

    /**
     * @var string Profile URL template as the fallback when no `link` returned from the API.
     */
    protected $profileUrlTemplate = 'https://www.facebook.com/%s';

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        parent::initialize();

        // Require proof on all Facebook api calls
        // https://developers.facebook.com/docs/graph-api/securing-requests#appsecret_proof
        if ($accessToken = $this->getStoredData('access_token')) {
            $this->apiRequestParameters['appsecret_proof'] = hash_hmac('sha256', $accessToken, $this->clientSecret);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function maintainToken()
    {
        if (!$this->isConnected()) {
            return;
        }

        // Handle token exchange prior to the standard handler for an API request
        $exchange_by_expiry_days = $this->config->get('exchange_by_expiry_days') ?: 45;
        if ($exchange_by_expiry_days !== null) {
            $projected_timestamp = time() + 60 * 60 * 24 * $exchange_by_expiry_days;
            if (!$this->hasAccessTokenExpired() && $this->hasAccessTokenExpired($projected_timestamp)) {
                $this->exchangeAccessToken();
            }
        }
    }

    /**
     * Exchange the Access Token with one that expires further in the future.
     *
     * @return string Raw Provider API response
     * @throws HttpClientFailureException
     * @throws HttpRequestFailedException
     * @throws InvalidAccessTokenException
     */
    public function exchangeAccessToken()
    {
        $exchangeTokenParameters = [
            'grant_type' => 'fb_exchange_token',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'fb_exchange_token' => $this->getStoredData('access_token'),
        ];

        $response = (string)$this->httpClient->request(
            $this->accessTokenUrl,
            'GET',
            $exchangeTokenParameters
        );

        $this->validateApiResponse('Unable to exchange the access token');

        $this->validateAccessTokenExchange($response);

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $fields = [
            'id',
            'name',
            'first_name',
            'last_name',
            'website',
            'locale',
            'about',
            'email',
            'hometown',
            'birthday',
        ];

        if (strpos($this->scope, 'user_link') !== false) {
            $fields[] = 'link';
        }

        if (strpos($this->scope, 'user_gender') !== false) {
            $fields[] = 'gender';
        }

        // Note that en_US is needed for gender fields to match convention.
        $locale = $this->config->get('locale') ?: 'en_US';
        $response = $this->apiRequest('me', 'GET', [
            'fields' => implode(',', $fields),
            'locale' => $locale,
        ]);

        $data = new Data\Collection($response);

        if (!$data->exists('id')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new Profile();

        $userProfile->identifier = $data->get('id');
        $userProfile->displayName = $data->get('name');
        $userProfile->firstName = $data->get('first_name');
        $userProfile->lastName = $data->get('last_name');
        $userProfile->profileURL = $data->get('link');
        $userProfile->webSiteURL = $data->get('website');
        $userProfile->gender = $data->get('gender');
        $userProfile->language = $data->get('locale');
        $userProfile->description = $data->get('about');
        $userProfile->email = $data->get('email');

        // Fallback for profile URL in case Facebook does not provide "pretty" link with username (if user set it).
        if (empty($userProfile->profileURL)) {
            $userProfile->profileURL = $this->getProfileUrl($userProfile->identifier);
        }

        $userProfile->region = $data->filter('hometown')->get('name');

        $photoSize = $this->config->get('photo_size') ?: '150';

        $userProfile->photoURL = $this->apiBaseUrl . $userProfile->identifier;
        $userProfile->photoURL .= '/picture?width=' . $photoSize . '&height=' . $photoSize;

        $userProfile->emailVerified = $userProfile->email;

        $userProfile = $this->fetchUserRegion($userProfile);

        $userProfile = $this->fetchBirthday($userProfile, $data->get('birthday'));

        return $userProfile;
    }

    /**
     * Retrieve the user region.
     *
     * @param Profile $userProfile
     *
     * @return Profile
     */
    protected function fetchUserRegion(Profile $userProfile)
    {
        if (!empty($userProfile->region)) {
            $regionArr = explode(',', $userProfile->region);

            if (count($regionArr) > 1) {
                $userProfile->city = trim($regionArr[0]);
                $userProfile->country = trim($regionArr[1]);
            }
        }

        return $userProfile;
    }

    /**
     * Retrieve the user birthday.
     *
     * @param Profile $userProfile
     * @param string $birthday
     *
     * @return Profile
     */
    protected function fetchBirthday(Profile $userProfile, $birthday)
    {
        $result = (new Data\Parser())->parseBirthday($birthday);

        $userProfile->birthYear = (int)$result[0];
        $userProfile->birthMonth = (int)$result[1];
        $userProfile->birthDay = (int)$result[2];

        return $userProfile;
    }

    /**
     * /v2.0/me/friends only returns the user's friends who also use the app.
     * In the cases where you want to let people tag their friends in stories published by your app,
     * you can use the Taggable Friends API.
     *
     * https://developers.facebook.com/docs/apps/faq#unable_full_friend_list
     */
    public function getUserContacts()
    {
        $contacts = [];

        $apiUrl = 'me/friends?fields=link,name';

        do {
            $response = $this->apiRequest($apiUrl);

            $data = new Data\Collection($response);

            if (!$data->exists('data')) {
                throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
            }

            if (!$data->filter('data')->isEmpty()) {
                foreach ($data->filter('data')->toArray() as $item) {
                    $contacts[] = $this->fetchUserContact($item);
                }
            }

            if ($data->filter('paging')->exists('next')) {
                $apiUrl = $data->filter('paging')->get('next');

                $pagedList = true;
            } else {
                $pagedList = false;
            }
        } while ($pagedList);

        return $contacts;
    }

    /**
     * Parse the user contact.
     *
     * @param array $item
     *
     * @return Contact
     */
    protected function fetchUserContact($item)
    {
        $userContact = new Contact();

        $item = new Data\Collection($item);

        $userContact->identifier = $item->get('id');
        $userContact->displayName = $item->get('name');

        $userContact->profileURL = $item->exists('link')
            ?: $this->getProfileUrl($userContact->identifier);

        $userContact->photoURL = $this->apiBaseUrl . $userContact->identifier . '/picture?width=150&height=150';

        return $userContact;
    }

    /**
     * {@inheritdoc}
     */
    public function setPageStatus($status, $pageId)
    {
        $status = is_string($status) ? ['message' => $status] : $status;

        // Post on user wall.
        if ($pageId === 'me') {
            return $this->setUserStatus($status);
        }

        // Retrieve writable user pages and filter by given one.
        $pages = $this->getUserPages(true);
        $pages = array_filter($pages, function ($page) use ($pageId) {
            return $page->id == $pageId;
        });

        if (!$pages) {
            throw new InvalidArgumentException('Could not find a page with given id.');
        }

        $page = reset($pages);

        // Use page access token instead of user access token.
        $headers = [
            'Authorization' => 'Bearer ' . $page->access_token,
        ];

        // Refresh proof for API call.
        $parameters = $status + [
                'appsecret_proof' => hash_hmac('sha256', $page->access_token, $this->clientSecret),
            ];

        $response = $this->apiRequest("{$pageId}/feed", 'POST', $parameters, $headers);

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserPages($writable = false)
    {
        $pages = $this->apiRequest('me/accounts');

        if (!$writable) {
            return $pages->data;
        }

        // Filter user pages by CREATE_CONTENT permission.
        return array_filter($pages->data, function ($page) {
            return in_array('CREATE_CONTENT', $page->tasks);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getUserActivity($stream = 'me')
    {
        $apiUrl = $stream == 'me' ? 'me/feed' : 'me/home';

        $response = $this->apiRequest($apiUrl);

        $data = new Data\Collection($response);

        if (!$data->exists('data')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $activities = [];

        foreach ($data->filter('data')->toArray() as $item) {
            $activities[] = $this->fetchUserActivity($item);
        }

        return $activities;
    }

    /**
     * @param $item
     *
     * @return User\Activity
     */
    protected function fetchUserActivity($item)
    {
        $userActivity = new User\Activity();

        $item = new Data\Collection($item);

        $userActivity->id = $item->get('id');
        $userActivity->date = $item->get('created_time');

        if ('video' == $item->get('type') || 'link' == $item->get('type')) {
            $userActivity->text = $item->get('link');
        }

        if (empty($userActivity->text) && $item->exists('story')) {
            $userActivity->text = $item->get('link');
        }

        if (empty($userActivity->text) && $item->exists('message')) {
            $userActivity->text = $item->get('message');
        }

        if (!empty($userActivity->text) && $item->exists('from')) {
            $userActivity->user->identifier = $item->filter('from')->get('id');
            $userActivity->user->displayName = $item->filter('from')->get('name');

            $userActivity->user->profileURL = $this->getProfileUrl($userActivity->user->identifier);

            $userActivity->user->photoURL = $this->apiBaseUrl . $userActivity->user->identifier;
            $userActivity->user->photoURL .= '/picture?width=150&height=150';
        }

        return $userActivity;
    }

    /**
     * Get profile URL.
     *
     * @param int $identity User ID.
     * @return string|null NULL when identity is not provided.
     */
    protected function getProfileUrl($identity)
    {
        if (!is_numeric($identity)) {
            return null;
        }

        return sprintf($this->profileUrlTemplate, $identity);
    }
}
