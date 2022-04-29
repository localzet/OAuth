<?php

/**********************************************************
 *  @author Ivan Zorin <creator@localzet.ru>              *
 *  @license GNU General Public License v3.0              *
 *  @copyright Zorin Projects <www.localzet.ru>           *
 **********************************************************/

namespace LIS\Storage;

/**
 * LIS storage manager interface
 */
interface StorageInterface
{
    /**
     * Retrieve a item from storage
     *
     * @param string $key
     *
     * @return mixed
     */
    public function get($key);

    /**
     * Add or Update an item to storage
     *
     * @param string $key
     * @param string $value
     */
    public function set($key, $value);

    /**
     * Delete an item from storage
     *
     * @param string $key
     */
    public function delete($key);

    /**
     * Delete a item from storage
     *
     * @param string $key
     */
    public function deleteMatch($key);

    /**
     * Clear all items in storage
     */
    public function clear();
}
