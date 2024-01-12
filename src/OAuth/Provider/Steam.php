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

use Exception;
use localzet\OAuth\Adapter\OpenID;
use localzet\OAuth\Data;
use localzet\OAuth\Exception\UnexpectedApiResponseException;
use SimpleXMLElement;

/**
 * Steam OpenID provider adapter.
 *
 * Example:
 *
 *   $config = [
 *       'callback' => localzet\OAuth\HttpClient\Util::getCurrentUrl(),
 *       'keys' => ['secret' => 'steam-api-key']
 *   ];
 *
 *   $adapter = new localzet\OAuth\Provider\Steam($config);
 *
 *   try {
 *       $adapter->authenticate();
 *
 *       $userProfile = $adapter->getUserProfile();
 *   } catch (\Exception $e) {
 *       echo $e->getMessage() ;
 *   }
 */
class Steam extends OpenID
{
    /**
     * {@inheritdoc}
     */
    protected $openidIdentifier = 'http://steamcommunity.com/openid';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://steamcommunity.com/dev';

    /**
     * {@inheritdoc}
     */
    public function authenticateFinish()
    {
        parent::authenticateFinish();

        $userProfile = $this->storage->get($this->providerId . '.user');

        $userProfile->identifier = str_ireplace([
            'http://steamcommunity.com/openid/id/',
            'https://steamcommunity.com/openid/id/',
        ], '', $userProfile->identifier);

        if (!$userProfile->identifier) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        try {
            $apiKey = $this->config->filter('keys')->get('secret');

            // if api key is provided, we attempt to use steam web api
            if ($apiKey) {
                $result = $this->getUserProfileWebAPI($apiKey, $userProfile->identifier);
            } else {
                // otherwise we fallback to community data
                $result = $this->getUserProfileLegacyAPI($userProfile->identifier);
            }

            // fetch user profile
            foreach ($result as $k => $v) {
                $userProfile->$k = $v ?: $userProfile->$k;
            }
        } catch (Exception $e) {
        }

        // store user profile
        $this->storage->set($this->providerId . '.user', $userProfile);
    }

    /**
     * Fetch user profile on Steam web API
     *
     * @param $apiKey
     * @param $steam64
     *
     * @return array
     */
    public function getUserProfileWebAPI($apiKey, $steam64)
    {
        $q = http_build_query(['key' => $apiKey, 'steamids' => $steam64]);
        $apiUrl = 'http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?' . $q;

        $response = $this->httpClient->request($apiUrl);

        $data = json_decode($response);

        $data = $data->response->players[0] ?? null;

        $data = new Data\Collection($data);

        $userProfile = [];

        $userProfile['displayName'] = (string)$data->get('personaname');
        $userProfile['firstName'] = (string)$data->get('realname');
        $userProfile['photoURL'] = (string)$data->get('avatarfull');
        $userProfile['profileURL'] = (string)$data->get('profileurl');
        $userProfile['country'] = (string)$data->get('loccountrycode');

        return $userProfile;
    }

    /**
     * Fetch user profile on community API
     * @param $steam64
     * @return array
     * @throws Exception
     */
    public function getUserProfileLegacyAPI($steam64)
    {
        libxml_use_internal_errors(false);

        $apiUrl = 'http://steamcommunity.com/profiles/' . $steam64 . '/?xml=1';

        $response = $this->httpClient->request($apiUrl);

        $data = new SimpleXMLElement($response);

        $data = new Data\Collection($data);

        $userProfile = [];

        $userProfile['displayName'] = (string)$data->get('steamID');
        $userProfile['firstName'] = (string)$data->get('realname');
        $userProfile['photoURL'] = (string)$data->get('avatarFull');
        $userProfile['description'] = (string)$data->get('summary');
        $userProfile['region'] = (string)$data->get('location');
        $userProfile['profileURL'] = (string)$data->get('customURL')
            ? 'http://steamcommunity.com/id/' . (string)$data->get('customURL')
            : 'http://steamcommunity.com/profiles/' . $steam64;

        return $userProfile;
    }
}
