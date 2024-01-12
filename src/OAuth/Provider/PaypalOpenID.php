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

use localzet\OAuth\Adapter\OpenID;
use localzet\OAuth\HttpClient;

/**
 * PayPal OpenID provider adapter.
 */
class PaypalOpenID extends OpenID
{
    /**
     * {@inheritdoc}
     */
    protected $openidIdentifier = 'https://www.sandbox.paypal.com/webapps/auth/server';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://developer.paypal.com/docs/connect-with-paypal/';

    /**
     * {@inheritdoc}
     */
    public function authenticateBegin()
    {
        $this->openIdClient->identity = $this->openidIdentifier;
        $this->openIdClient->returnUrl = $this->callback;
        $this->openIdClient->required = [
            'namePerson/prefix',
            'namePerson/first',
            'namePerson/last',
            'namePerson/middle',
            'namePerson/suffix',
            'namePerson/friendly',
            'person/guid',
            'birthDate/birthYear',
            'birthDate/birthMonth',
            'birthDate/birthday',
            'gender',
            'language/pref',
            'contact/phone/default',
            'contact/phone/home',
            'contact/phone/business',
            'contact/phone/cell',
            'contact/phone/fax',
            'contact/postaladdress/home',
            'contact/postaladdressadditional/home',
            'contact/city/home',
            'contact/state/home',
            'contact/country/home',
            'contact/postalcode/home',
            'contact/postaladdress/business',
            'contact/postaladdressadditional/business',
            'contact/city/business',
            'contact/state/business',
            'contact/country/business',
            'contact/postalcode/business',
            'company/name',
            'company/title',
        ];

        HttpClient\Util::redirect($this->openIdClient->authUrl());
    }
}
