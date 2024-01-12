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
use localzet\OAuth\Data\Collection;
use localzet\OAuth\Exception\UnexpectedApiResponseException;
use localzet\OAuth\User\Contact;
use localzet\OAuth\User\Profile;

/**
 * Vkontakte OAuth2 provider adapter.
 *
 * Example:
 *
 *   $config = [
 *       'callback' => localzet\OAuth\HttpClient\Util::getCurrentUrl(),
 *       'keys' => [
 *           'id' => '', // App ID
 *           'secret' => '' // Secure key
 *       ],
 *   ];
 *
 *   $adapter = new localzet\OAuth\Provider\Vkontakte($config);
 *
 *   try {
 *       if (!$adapter->isConnected()) {
 *           $adapter->authenticate();
 *       }
 *
 *       $userProfile = $adapter->getUserProfile();
 *   } catch (\Exception $e) {
 *       print $e->getMessage() ;
 *   }
 */
class Vkontakte extends OAuth2
{
    const API_VERSION = '5.95';

    const URL = 'https://vk.com/';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://api.vk.com/method/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://api.vk.com/oauth/authorize';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://api.vk.com/oauth/token';

    /**
     * {@inheritdoc}
     */
    protected $scope = 'email,offline';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = ''; // Not available

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        parent::initialize();

        // The VK API requires version and access_token from authenticated users
        // for each endpoint.
        $accessToken = $this->getStoredData($this->accessTokenName);
        $this->apiRequestParameters[$this->accessTokenName] = $accessToken;
        $this->apiRequestParameters['v'] = static::API_VERSION;
    }

    /**
     * {@inheritdoc}
     */
    protected function validateAccessTokenExchange($response)
    {
        $data = parent::validateAccessTokenExchange($response);

        // Need to store email for later use.
        $this->storeData('email', $data->get('email'));
    }

    /**
     * {@inheritdoc}
     */
    public function hasAccessTokenExpired($time = null)
    {
        if ($time === null) {
            $time = time();
        }

        // If we are using offline scope, $expired will be false.
        $expired = $this->getStoredData('expires_in')
            ? $this->getStoredData('expires_at') <= $time
            : false;

        return $expired;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $photoField = 'photo_' . ($this->config->get('photo_size') ?: 'max_orig');

        $response = $this->apiRequest('users.get', 'GET', [
            'fields' => 'screen_name,sex,education,bdate,has_photo,' . $photoField,
        ]);

        if (property_exists($response, 'error')) {
            throw new UnexpectedApiResponseException($response->error->error_msg);
        }

        $data = new Collection($response->response[0]);

        if (!$data->exists('id')) {
            throw new UnexpectedApiResponseException('API Provider вернул неожиданный ответ');
        }

        $userProfile = new Profile();

        $userProfile->identifier = $data->get('id');
        $userProfile->email = $this->getStoredData('email');
        $userProfile->firstName = $data->get('first_name');
        $userProfile->lastName = $data->get('last_name');
        $userProfile->displayName = $data->get('screen_name');
        $userProfile->photoURL = $data->get('has_photo') === 1 ? $data->get($photoField) : '';

        // Handle b-date.
        if ($data->get('bdate')) {
            $bday = explode('.', $data->get('bdate'));
            $userProfile->birthDay = (int)$bday[0];
            $userProfile->birthMonth = (int)$bday[1];
            $userProfile->birthYear = (int)$bday[2];
        }

        $userProfile->data = [
            'education' => $data->get('education'),
        ];

        $screen_name = static::URL . ($data->get('screen_name') ?: 'id' . $data->get('id'));
        $userProfile->profileURL = $screen_name;

        switch ($data->get('sex')) {
            case 1:
                $userProfile->gender = 'female';
                break;

            case 2:
                $userProfile->gender = 'male';
                break;
        }

        return $userProfile;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserContacts()
    {
        $response = $this->apiRequest('friends.get', 'GET', [
            'fields' => 'uid,name,photo_200_orig',
        ]);

        $data = new Data\Collection($response);
        if (!$data->exists('response')) {
            throw new UnexpectedApiResponseException('API Provider вернул неожиданный ответ');
        }

        $contacts = [];
        if (!$data->filter('response')->filter('items')->isEmpty()) {
            foreach ($data->filter('response')->filter('items')->toArray() as $item) {
                $contacts[] = $this->fetchUserContact($item);
            }
        }

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
        $data = new Data\Collection($item);

        $userContact->identifier = $data->get('id');
        $userContact->displayName = sprintf('%s %s', $data->get('first_name'), $data->get('last_name'));
        $userContact->profileURL = static::URL . ($data->get('screen_name') ?: 'id' . $data->get('id'));
        $userContact->photoURL = $data->get('photo_200_orig');

        return $userContact;
    }
}
