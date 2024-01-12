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

namespace localzet\OAuth\Storage;

use localzet\OAuth\Exception\RuntimeException;

/**
 * localzet\OAuth storage manager
 */
class Session implements StorageInterface
{
    /**
     * Namespace
     *
     * @var string
     */
    protected $storeNamespace = 'OAuth::STORAGE';

    /**
     * Key prefix
     *
     * @var string
     */
    protected $keyPrefix = '';

    /**
     * Initiate a new session
     *
     * @throws RuntimeException
     */
    public function __construct()
    {
        if (session_id()) {
            return;
        }

        if (headers_sent()) {
            // phpcs:ignore
            throw new RuntimeException('HTTP headers already sent to browser and localzet\OAuth won\'t be able to start/resume PHP session. To resolve this, session_start() must be called before outputing any data.');
        }

        if (!session_start()) {
            throw new RuntimeException('PHP session failed to start.');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        $key = $this->keyPrefix . strtolower($key);

        if (isset($_SESSION[$this->storeNamespace], $_SESSION[$this->storeNamespace][$key])) {
            $value = $_SESSION[$this->storeNamespace][$key];

            if (is_array($value) && array_key_exists('lateObject', $value)) {
                $value = unserialize($value['lateObject']);
            }

            return $value;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value)
    {
        $key = $this->keyPrefix . strtolower($key);

        if (is_object($value)) {
            // We encapsulate as our classes may be defined after session is initialized.
            $value = ['lateObject' => serialize($value)];
        }

        $_SESSION[$this->storeNamespace][$key] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $_SESSION[$this->storeNamespace] = [];
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        $key = $this->keyPrefix . strtolower($key);

        if (isset($_SESSION[$this->storeNamespace], $_SESSION[$this->storeNamespace][$key])) {
            $tmp = $_SESSION[$this->storeNamespace];

            unset($tmp[$key]);

            $_SESSION[$this->storeNamespace] = $tmp;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMatch($key)
    {
        $key = $this->keyPrefix . strtolower($key);

        if (isset($_SESSION[$this->storeNamespace]) && count($_SESSION[$this->storeNamespace])) {
            $tmp = $_SESSION[$this->storeNamespace];

            foreach ($tmp as $k => $v) {
                if (strstr($k, $key)) {
                    unset($tmp[$k]);
                }
            }

            $_SESSION[$this->storeNamespace] = $tmp;
        }
    }
}
