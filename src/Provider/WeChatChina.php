<?php

/**********************************************************
 *  @author Ivan Zorin <creator@localzet.ru>              *
 *  @license GNU General Public License v3.0              *
 *  @copyright Zorin Projects <www.localzet.ru>           *
 **********************************************************/

namespace LIS\Provider;

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
