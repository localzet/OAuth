<?php

/**********************************************************
 *  @author Ivan Zorin <creator@localzet.ru>              *
 *  @license GNU General Public License v3.0              *
 *  @copyright Zorin Projects <www.localzet.ru>           *
 **********************************************************/

namespace LIS\Provider;

use LIS\Adapter\OpenID;
use LIS\HttpClient;

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
