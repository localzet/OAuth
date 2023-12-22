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

namespace localzet\OAuth\Adapter;

use localzet\OAuth\Storage\StorageInterface;

/**
 * Trait DataStoreTrait
 */
trait DataStoreTrait
{
    /**
     * Returns storage instance
     *
     * @return StorageInterface
     */
    abstract public function getStorage();

    /**
     * Store a piece of data in storage.
     *
     * This method is mainly used for OAuth tokens (access, secret, refresh, and whatnot), but it
     * can be also used by providers to store any other useful data (i.g., user_id, auth_nonce, etc.)
     *
     * @param string $name
     * @param mixed $value
     */
    protected function storeData($name, $value = null)
    {
        // if empty, we simply delete the thing as we'd want to only store necessary data
        if (empty($value)) {
            $this->deleteStoredData($name);
        }

        $this->getStorage()->set($this->providerId . '.' . $name, $value);
    }

    /**
     * Retrieve a piece of data from storage.
     *
     * This method is mainly used for OAuth tokens (access, secret, refresh, and whatnot), but it
     * can be also used by providers to retrieve from store any other useful data (i.g., user_id,
     * auth_nonce, etc.)
     *
     * @param string $name
     *
     * @return mixed
     */
    protected function getStoredData($name)
    {
        return $this->getStorage()->get($this->providerId . '.' . $name);
    }

    /**
     * Delete a stored piece of data.
     *
     * @param string $name
     */
    protected function deleteStoredData($name)
    {
        $this->getStorage()->delete($this->providerId . '.' . $name);
    }

    /**
     * Delete all stored data of the instantiated adapter
     */
    protected function clearStoredData()
    {
        $this->getStorage()->deleteMatch($this->providerId . '.');
    }
}
