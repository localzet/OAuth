<?php

/**********************************************************
 *  @author Ivan Zorin <creator@localzet.ru>              *
 *  @license GNU General Public License v3.0              *
 *  @copyright Zorin Projects <www.localzet.ru>           *
 **********************************************************/

namespace LIS\Provider;

use LIS\Adapter\OAuth2;
use LIS\Exception\Exception;
use LIS\Exception\UnexpectedApiResponseException;
use LIS\Data;
use LIS\User;
// sYP0tJ5o5A_urI44VTRQn30MBg0
/**
 * Miro OAuth2 provider adapter.
 */
class Miro extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://api.miro.com/v1';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://miro.com/oauth/authorize';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://api.miro.com/v1/oauth/token';
    protected $AuthorizeUrlParameters = [
        'grant_type' => 'authorization_code',
        'client_id' => '',
        'client_secret' => '',
        'code' => '',
        'redirect_uri' => '',
    ];

    /**
     * Load the user profile from the IDp api client
     *
     * @throws Exception
     */
    public function getUserProfile()
    {
        $this->scope = implode(',', []);

        $response = $this->apiRequest($this->apiBaseUrl, 'GET', ['format' => 'json']);

        if (!isset($response->id)) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $data = new Data\Collection($response);

        if (!$data->exists('id')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();
        $userProfile->identifier = $data->get('id');
        $userProfile->firstName = $data->get('first_name');
        $userProfile->lastName = $data->get('last_name');
        $userProfile->displayName = $data->get('display_name');
        $userProfile->photoURL
            = 'https://avatars.yandex.net/get-yapic/' .
            $data->get('default_avatar_id') . '/islands-200';
        $userProfile->gender = $data->get('sex');
        $userProfile->email = $data->get('default_email');
        $userProfile->emailVerified = $data->get('default_email');

        if ($data->get('birthday')) {
            list($birthday_year, $birthday_month, $birthday_day)
                = explode('-', $response->birthday);
            $userProfile->birthDay = (int)$birthday_day;
            $userProfile->birthMonth = (int)$birthday_month;
            $userProfile->birthYear = (int)$birthday_year;
        }

        return $userProfile;
    }
}
