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

/*!
* This file is part of the OAuth PHP Library (https://code.google.com/p/oauth/)
*
* OAuth `PHP' Library is an open source software available under the MIT License.
*/

namespace localzet\OAuth\Thirdparty\OAuth;

/**
 * Class OAuthSignatureMethod
 *
 * @package localzet\OAuth\Thirdparty\OAuth
 */
abstract class OAuthSignatureMethod
{
    /**
    * Needs to return the name of the Signature Method (ie HMAC-SHA1)
    *
    * @return string
    */
    abstract public function get_name();

    /**
    * Build up the signature
    * NOTE: The output of this function MUST NOT be urlencoded.
    * the encoding is handled in OAuthRequest when the final
    * request is serialized
    *
    * @param OAuthRequest $request
    * @param OAuthConsumer $consumer
    * @param OAuthToken $token
    * @return string
    */
    abstract public function build_signature($request, $consumer, $token);

    /**
    * Verifies that a given signature is correct
    *
    * @param OAuthRequest $request
    * @param OAuthConsumer $consumer
    * @param OAuthToken $token
    * @param string $signature
    * @return bool
    */
    public function check_signature($request, $consumer, $token, $signature)
    {
        $built = $this->build_signature($request, $consumer, $token);

        // Check for zero length, although unlikely here
        if (strlen($built) == 0 || strlen($signature) == 0) {
            return false;
        }

        if (strlen($built) != strlen($signature)) {
            return false;
        }

        // Avoid a timing leak with a (hopefully) time insensitive compare
        $result = 0;
        for ($i = 0; $i < strlen($signature); $i ++) {
            $result |= ord($built[$i]) ^ ord($signature[$i]);
        }

        return $result == 0;
    }
}
