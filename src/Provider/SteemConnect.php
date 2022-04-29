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
 * Instagram OAuth2 provider adapter.
 */
class SteemConnect extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'login,vote';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://v2.steemconnect.com/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://v2.steemconnect.com/oauth2/authorize';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://v2.steemconnect.com/api/oauth2/token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://steemconnect.com/';

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $response = $this->apiRequest('api/me');

        $data = new Data\Collection($response);

        if (!$data->exists('result')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();

        $data = $data->filter('result');

        $userProfile->identifier = $data->get('id');
        $userProfile->description = $data->get('about');
        $userProfile->photoURL = $data->get('profile_image');
        $userProfile->webSiteURL = $data->get('website');
        $userProfile->displayName = $data->get('name');

        return $userProfile;
    }
}
