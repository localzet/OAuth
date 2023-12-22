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

namespace localzet\OAuth\User;

use localzet\OAuth\Exception\UnexpectedValueException;

/**
 * localzet\OAuth\User\Activity
 */
final class Activity
{
    /**
     * activity id on the provider side, usually given as integer
     *
     * @var string
     */
    public $id = null;

    /**
     * activity date of creation
     *
     * @var string
     */
    public $date = null;

    /**
     * activity content as a string
     *
     * @var string
     */
    public $text = null;

    /**
     * user who created the activity
     *
     * @var object
     */
    public $user = null;

    /**
     *
     */
    public function __construct()
    {
        $this->user = new \stdClass();

        // typically, we should have a few information about the user who created the event from social apis
        $this->user->identifier = null;
        $this->user->displayName = null;
        $this->user->profileURL = null;
        $this->user->photoURL = null;
    }

    /**
     * Prevent the providers adapters from adding new fields.
     *
     * @throws UnexpectedValueException
     * @var string $name
     *
     * @var mixed $value
     *
     */
    public function __set($name, $value)
    {
        // phpcs:ignore
        throw new UnexpectedValueException(sprintf('Adding new property "%s\' to %s is not allowed.', $name, __CLASS__));
    }
}
