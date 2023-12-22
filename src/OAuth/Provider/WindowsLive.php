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
 * Windows Live OAuth2 provider adapter.
 */
class WindowsLive extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'wl.basic wl.contacts_emails wl.emails wl.signin wl.share wl.birthday';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://apis.live.net/v5.0/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://login.live.com/oauth20_authorize.srf';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://login.live.com/oauth20_token.srf';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://msdn.microsoft.com/en-us/library/hh243647.aspx';

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
        $userProfile->displayName = $data->get('name');
        $userProfile->firstName = $data->get('first_name');
        $userProfile->lastName = $data->get('last_name');
        $userProfile->gender = $data->get('gender');
        $userProfile->profileURL = $data->get('link');
        $userProfile->email = $data->filter('emails')->get('preferred');
        $userProfile->emailVerified = $data->filter('emails')->get('account');
        $userProfile->birthDay = $data->get('birth_day');
        $userProfile->birthMonth = $data->get('birth_month');
        $userProfile->birthYear = $data->get('birth_year');
        $userProfile->language = $data->get('locale');

        return $userProfile;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserContacts()
    {
        $response = $this->apiRequest('me/contacts');

        $data = new Data\Collection($response);

        if (!$data->exists('data')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $contacts = [];

        foreach ($data->filter('data')->toArray() as $idx => $entry) {
            $userContact = new User\Contact();

            $userContact->identifier = $entry->get('id');
            $userContact->displayName = $entry->get('name');
            $userContact->email = $entry->filter('emails')->get('preferred');

            $contacts[] = $userContact;
        }

        return $contacts;
    }
}
