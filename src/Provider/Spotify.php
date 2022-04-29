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
 * Spotify OAuth2 provider adapter.
 */
class Spotify extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'user-read-email';

    /**
     * {@inheritdoc}
     */
    public $apiBaseUrl = 'https://api.spotify.com/v1/';

    /**
     * {@inheritdoc}
     */
    public $authorizeUrl = 'https://accounts.spotify.com/authorize';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://accounts.spotify.com/api/token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://developer.spotify.com/documentation/general/guides/authorization-guide/';

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
        $userProfile->displayName = $data->get('display_name');
        $userProfile->email = $data->get('email');
        $userProfile->emailVerified = $data->get('email');
        $userProfile->profileURL = $data->filter('external_urls')->get('spotify');
        $userProfile->photoURL = $data->filter('images')->get('url');
        $userProfile->country = $data->get('country');

        if ($data->exists('birthdate')) {
            $this->fetchBirthday($userProfile, $data->get('birthdate'));
        }

        return $userProfile;
    }

    /**
     * Fetch use birthday
     *
     * @param User\Profile $userProfile
     * @param              $birthday
     *
     * @return User\Profile
     */
    protected function fetchBirthday(User\Profile $userProfile, $birthday)
    {
        $result = (new Data\Parser())->parseBirthday($birthday, '-');

        $userProfile->birthDay = (int)$result[0];
        $userProfile->birthMonth = (int)$result[1];
        $userProfile->birthYear = (int)$result[2];

        return $userProfile;
    }
}
