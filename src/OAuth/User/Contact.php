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

namespace localzet\OAuth\User;

use localzet\OAuth\Exception\UnexpectedValueException;

/**
 * localzet\OAuth\User\Contact
 */
final class Contact
{
    /**
     * The Unique contact user ID
     *
     * @var string
     */
    public $identifier = null;

    /**
     * User website, blog, web page
     *
     * @var string
     */
    public $webSiteURL = null;

    /**
     * URL link to profile page on the IDp web site
     *
     * @var string
     */
    public $profileURL = null;

    /**
     * URL link to user photo or avatar
     *
     * @var string
     */
    public $photoURL = null;

    /**
     * User displayName provided by the IDp or a concatenation of first and last name
     *
     * @var string
     */
    public $displayName = null;

    /**
     * A short about_me
     *
     * @var string
     */
    public $description = null;

    /**
     * User email. Not all of IDp grant access to the user email
     *
     * @var string
     */
    public $email = null;

    /**
     * Prevent the providers adapters from adding new fields.
     *
     * @param string $name
     * @param mixed $value
     *
     * @throws UnexpectedValueException
     */
    public function __set($name, $value)
    {
        throw new UnexpectedValueException(sprintf('Adding new property "%s" to %s is not allowed.', $name, __CLASS__));
    }
}
