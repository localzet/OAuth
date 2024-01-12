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

/**
 * WeChat China OAuth2 provider adapter.
 */
class WeChatChina extends WeChat
{
    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://api.weixin.qq.com/sns/';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://api.weixin.qq.com/sns/oauth2/access_token';

    /**
     * {@inheritdoc}
     */
    protected $tokenRefreshUrl = 'https://api.weixin.qq.com/sns/oauth2/refresh_token';

    /**
     * {@ịnheritdoc}
     */
    protected $accessTokenInfoUrl = 'https://api.weixin.qq.com/sns/auth';
}
