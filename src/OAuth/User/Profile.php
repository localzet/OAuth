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
 * localzet\OAuth\Userobject represents the current logged in user profile.
 */
final class Profile
{
    /**
     * The Unique user's ID on the connected provider
     *
     * @var int|null
     */
    public $identifier = null;

    /**
     * User website, blog, web page
     *
     * @var string|null
     */
    public $webSiteURL = null;

    /**
     * URL link to profile page on the IDp web site
     *
     * @var string|null
     */
    public $profileURL = null;

    /**
     * URL link to user photo or avatar
     *
     * @var string|null
     */
    public $photoURL = null;

    /**
     * User displayName provided by the IDp or a concatenation of first and last name.
     *
     * @var string|null
     */
    public $displayName = null;

    /**
     * A short about_me
     *
     * @var string|null
     */
    public $description = null;

    /**
     * User's first name
     *
     * @var string|null
     */
    public $firstName = null;

    /**
     * User's last name
     *
     * @var string|null
     */
    public $lastName = null;

    /**
     * male or female
     *
     * @var string|null
     */
    public $gender = null;

    /**
     * Language
     *
     * @var string|null
     */
    public $language = null;

    /**
     * User age, we don't calculate it. we return it as is if the IDp provide it.
     *
     * @var int|null
     */
    public $age = null;

    /**
     * User birth Day
     *
     * @var int|null
     */
    public $birthDay = null;

    /**
     * User birth Month
     *
     * @var int|null
     */
    public $birthMonth = null;

    /**
     * User birth Year
     *
     * @var int|null
     */
    public $birthYear = null;

    /**
     * User email. Note: not all of IDp grant access to the user email
     *
     * @var string|null
     */
    public $email = null;

    /**
     * Verified user email. Note: not all of IDp grant access to verified user email
     *
     * @var string|null
     */
    public $emailVerified = null;

    /**
     * Phone number
     *
     * @var string|null
     */
    public $phone = null;

    /**
     * Complete user address
     *
     * @var string|null
     */
    public $address = null;

    /**
     * User country
     *
     * @var string|null
     */
    public $country = null;

    /**
     * Region
     *
     * @var string|null
     */
    public $region = null;

    /**
     * City
     *
     * @var string|null
     */
    public $city = null;

    /**
     * Postal code
     *
     * @var string|null
     */
    public $zip = null;

    /**
     * An extra data which is related to the user
     *
     * @var array
     */
    public $data = [];

    /**
     * Prevent the providers adapters from adding new fields.
     *
     * @throws UnexpectedValueException
     * @var mixed $value
     *
     * @var string $name
     */
    public function __set($name, $value)
    {
        throw new UnexpectedValueException(sprintf('Adding new property "%s" to %s is not allowed.', $name, __CLASS__));
    }
}
