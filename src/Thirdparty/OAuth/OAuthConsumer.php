<?php

/**********************************************************
 *  @author Ivan Zorin <creator@localzet.ru>              *
 *  @license GNU General Public License v3.0              *
 *  @copyright Zorin Projects <www.localzet.ru>           *
 **********************************************************/

/*!
* This file is part of the OAuth PHP Library (https://code.google.com/p/oauth/)
*
* OAuth `PHP' Library is an open source software available under the MIT License.
*/

namespace LIS\Thirdparty\OAuth;

/**
 * Class OAuthConsumer
 *
 * @package LIS\Thirdparty\OAuth
 */
class OAuthConsumer
{
    public $key;
    public $secret;

    /**
     * OAuthConsumer constructor.
     *
     * @param      $key
     * @param      $secret
     * @param null $callback_url
     */
    public function __construct($key, $secret, $callback_url = null)
    {
        $this->key = $key;
        $this->secret = $secret;
        $this->callback_url = $callback_url;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return "OAuthConsumer[key=$this->key,secret=$this->secret]";
    }
}
