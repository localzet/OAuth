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
 * Blizzard Battle.net OAuth2 provider adapter.
 */
class Blizzard extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = '';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://us.battle.net/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://us.battle.net/oauth/authorize';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://us.battle.net/oauth/token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://develop.battle.net/documentation';

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $response = $this->apiRequest('oauth/userinfo');

        $data = new Data\Collection($response);

        if (!$data->exists('id')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();

        $userProfile->identifier = $data->get('id');
        $userProfile->displayName = $data->get('battletag') ?: $data->get('login');

        return $userProfile;
    }
}
