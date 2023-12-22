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

namespace localzet\OAuth\Data;

/**
 * A very basic Data collection.
 */
final class Collection
{
    /**
     * Data collection
     *
     * @var mixed
     */
    protected $collection = null;

    /**
     * @param mixed $data
     */
    public function __construct($data = null)
    {
        $this->collection = new \stdClass();

        if (is_object($data)) {
            $this->collection = $data;
        }

        $this->collection = (object)$data;
    }

    /**
     * Retrieves the whole collection as array
     *
     * @return mixed
     */
    public function toArray()
    {
        return (array)$this->collection;
    }

    /**
     * Retrieves an item
     *
     * @param $property
     *
     * @return mixed
     */
    public function get($property)
    {
        if ($this->exists($property)) {
            return $this->collection->$property;
        }

        return null;
    }

    /**
     * Add or update an item
     *
     * @param $property
     * @param mixed $value
     */
    public function set($property, $value)
    {
        if ($property) {
            $this->collection->$property = $value;
        }
    }

    /**
     * .. until I come with a better name..
     *
     * @param $property
     *
     * @return Collection
     */
    public function filter($property)
    {
        if ($this->exists($property)) {
            $data = $this->get($property);

            if (!is_a($data, 'Collection')) {
                $data = new Collection($data);
            }

            return $data;
        }

        return new Collection([]);
    }

    /**
     * Checks whether an item within the collection
     *
     * @param $property
     *
     * @return bool
     */
    public function exists($property)
    {
        return property_exists($this->collection, $property);
    }

    /**
     * Finds whether the collection is empty
     *
     * @return bool
     */
    public function isEmpty()
    {
        return !(bool)$this->count();
    }

    /**
     * Count all items in collection
     *
     * @return int
     */
    public function count()
    {
        return count($this->properties());
    }

    /**
     * Returns all items properties names
     *
     * @return array
     */
    public function properties()
    {
        $properties = [];

        foreach ($this->collection as $key => $value) {
            $properties[] = $key;
        }

        return $properties;
    }

    /**
     * Returns all items values
     *
     * @return array
     */
    public function values()
    {
        $values = [];

        foreach ($this->collection as $value) {
            $values[] = $value;
        }

        return $values;
    }
}
