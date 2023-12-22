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
 * Microsoft Graph OAuth2 provider adapter.
 *
 * Create an "Azure Active Directory" resource at https://portal.azure.com/
 * (not from the Visual Studio site).
 *
 * The "Supported account types" choice maps to the 'tenant' setting, see "Authority" @
 * https://docs.microsoft.com/en-us/azure/active-directory/develop/msal-client-application-configuration
 *
 * Example:
 *
 *   $config = [
 *       'callback' => localzet\OAuth\HttpClient\Util::getCurrentUrl(),
 *       'keys' => ['id' => '', 'secret' => ''],
 *       'tenant' => 'user',
 *         // ^ May be 'common', 'organizations' or 'consumers' or a specific tenant ID or a domain
 *   ];
 *
 *   $adapter = new localzet\OAuth\Provider\MicrosoftGraph($config);
 *
 *   try {
 *       $adapter->authenticate();
 *
 *       $userProfile = $adapter->getUserProfile();
 *       $tokens = $adapter->getAccessToken();
 *   } catch (\Exception $e) {
 *       echo $e->getMessage() ;
 *   }
 */
class MicrosoftGraph extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'openid user.read contacts.read';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://graph.microsoft.com/v1.0/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://login.microsoftonline.com/common/oauth2/v2.0/token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://developer.microsoft.com/en-us/graph/docs/concepts/php';

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        parent::initialize();

        $tenant = $this->config->get('tenant');
        if (!empty($tenant)) {
            $adjustedEndpoints = [
                'authorize_url' => str_replace('/common/', '/' . $tenant . '/', $this->authorizeUrl),
                'access_token_url' => str_replace('/common/', '/' . $tenant . '/', $this->accessTokenUrl),
            ];

            $this->setApiEndpoints($adjustedEndpoints);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $response = $this->apiRequest('me');

        $data = new Data\Collection($response);

        if (!$data->exists('id')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();

        $userProfile->identifier = $data->get('id');
        $userProfile->displayName = $data->get('displayName');
        $userProfile->firstName = $data->get('givenName');
        $userProfile->lastName = $data->get('surname');
        $userProfile->language = $data->get('preferredLanguage');

        $userProfile->phone = $data->get('mobilePhone');
        if (empty($userProfile->phone)) {
            $businessPhones = $data->get('businessPhones');
            if (isset($businessPhones[0])) {
                $userProfile->phone = $businessPhones[0];
            }
        }

        $userProfile->email = $data->get('mail');
        if (empty($userProfile->email)) {
            $email = $data->get('userPrincipalName');
            if (strpos($email, '@') !== false) {
                $userProfile->email = $email;
            }
        }

        return $userProfile;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserContacts()
    {
        $apiUrl = 'me/contacts?$top=50';
        $contacts = [];

        do {
            $response = $this->apiRequest($apiUrl);
            $data = new Data\Collection($response);
            if (!$data->exists('value')) {
                throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
            }
            foreach ($data->filter('value')->toArray() as $entry) {
                $entry = new Data\Collection($entry);
                $userContact = new User\Contact();
                $userContact->identifier = $entry->get('id');
                $userContact->displayName = $entry->get('displayName');
                $emailAddresses = $entry->get('emailAddresses');
                if (!empty($emailAddresses)) {
                    $userContact->email = $emailAddresses[0]->address;
                }
                // only add to collection if we have usefull data
                if (!empty($userContact->displayName) || !empty($userContact->email)) {
                    $contacts[] = $userContact;
                }
            }

            if ($data->exists('@odata.nextLink')) {
                $apiUrl = $data->get('@odata.nextLink');

                $pagedList = true;
            } else {
                $pagedList = false;
            }
        } while ($pagedList);

        return $contacts;
    }
}
