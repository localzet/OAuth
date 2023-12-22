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

namespace localzet\OAuth\Exception;

/**
 * localzet\OAuth Base Exception
 */
class Exception extends \Exception implements ExceptionInterface
{
    /**
     * Shamelessly Borrowed from Slimframework
     *
     * @param $object
     */
    public function debug($object)
    {
        $title = 'OAuth Exception';
        $code = $this->getCode();
        $message = $this->getMessage();
        $file = $this->getFile();
        $line = $this->getLine();
        $trace = $this->getTraceAsString();

        $html = sprintf('<h1>%s</h1>', $title);
        $html .= '<p>OAuth has encountered the following error:</p>';
        $html .= '<h2>Details</h2>';

        $html .= sprintf('<div><strong>Exception:</strong> %s</div>', get_class($this));

        $html .= sprintf('<div><strong>Message:</strong> <font color="#cc0000">%s</font></div>', $message);

        $html .= sprintf('<div><strong>File:</strong> %s</div>', $file);

        $html .= sprintf('<div><strong>Line:</strong> %s</div>', $line);

        $html .= sprintf('<div><strong>Code:</strong> %s</div>', $code);

        $html .= '<h2>Trace</h2>';
        $html .= sprintf('<pre>%s</pre>', $trace);

        if ($object) {
            $html .= '<h2>Debug</h2>';

            $obj_dump = print_r($object, true);

            // phpcs:ignore
            $html .= sprintf('<b>' . get_class($object) . '</b> extends <b>' . get_parent_class($object) . '</b><pre>%s</pre>', $obj_dump);
        }

        $html .= '<h2>Session</h2>';

        $session_dump = print_r($_SESSION, true);

        $html .= sprintf('<pre>%s</pre>', $session_dump);

        // phpcs:ignore
        echo sprintf("<html><head><title>%s</title><style>body{margin:0;padding:30px;font:12px/1.5 Helvetica,Arial,Verdana,sans-serif;}h1{margin:0;font-size:48px;font-weight:normal;line-height:48px;}strong{display:inline-block;width:75px;}</style></head><body>%s</body></html>", $title, $html);
    }
}
