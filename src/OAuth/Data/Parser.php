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
 * Parser
 *
 * This class is used to parse plain text into objects. It's used by hybriauth adapters to converts
 * providers api responses to a more 'manageable' format.
 */
final class Parser
{
    /**
     * Decodes a string into an object.
     *
     * This method will first attempt to parse data as a JSON string (since most providers use this format)
     * then XML and parse_str.
     *
     * @param string $raw
     *
     * @return mixed
     */
    public function parse($raw = null)
    {
        $data = $this->parseJson($raw);

        if (!$data) {
            $data = $this->parseXml($raw);

            if (!$data) {
                $data = $this->parseQueryString($raw);
            }
        }

        return $data;
    }

    /**
     * Decodes a JSON string
     *
     * @param $result
     *
     * @return mixed
     */
    public function parseJson($result)
    {
        return json_decode($result);
    }

    /**
     * Decodes a XML string
     *
     * @param $result
     *
     * @return mixed
     */
    public function parseXml($result)
    {
        libxml_use_internal_errors(true);

        $result = preg_replace('/([<\/])([a-z0-9-]+):/i', '$1', $result);
        $xml = simplexml_load_string($result);

        libxml_use_internal_errors(false);

        if (!$xml) {
            return [];
        }

        $arr = json_decode(json_encode((array)$xml), true);
        $arr = array($xml->getName() => $arr);

        return $arr;
    }

    /**
     * Parses a string into variables
     *
     * @param $result
     *
     * @return \StdClass
     */
    public function parseQueryString($result)
    {
        parse_str($result, $output);

        if (!is_array($output)) {
            return $result;
        }

        $result = new \StdClass();

        foreach ($output as $k => $v) {
            $result->$k = $v;
        }

        return $result;
    }

    /**
     * needs to be improved
     *
     * @param $birthday
     *
     * @return array
     */
    public function parseBirthday($birthday)
    {
        $birthday = date_parse((string) $birthday);

        return [$birthday['year'], $birthday['month'], $birthday['day']];
    }
}
