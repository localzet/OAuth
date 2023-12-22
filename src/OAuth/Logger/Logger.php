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

namespace localzet\OAuth\Logger;

use localzet\OAuth\Exception\RuntimeException;
use localzet\OAuth\Exception\InvalidArgumentException;

/**
 * Debugging and Logging utility.
 */
class Logger implements LoggerInterface
{
    const NONE = 'none';  // turn logging off
    const DEBUG = 'debug'; // debug, info and error messages
    const INFO = 'info';  // info and error messages
    const ERROR = 'error'; // only error messages

    /**
     * Debug level.
     *
     * One of Logger::NONE, Logger::DEBUG, Logger::INFO, Logger::ERROR
     *
     * @var string
     */
    protected $level;

    /**
     * Path to file writeable by the web server. Required if $this->level !== Logger::NONE.
     *
     * @var string
     */
    protected $file;

    /**
     * @param bool|string $level One of Logger::NONE, Logger::DEBUG, Logger::INFO, Logger::ERROR
     * @param string $file File where to write messages
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function __construct($level, $file)
    {
        $this->level = self::NONE;

        if ($level && $level !== self::NONE) {
            $this->initialize($file);

            $this->level = $level === true ? Logger::DEBUG : $level;
            $this->file = $file;
        }
    }

    /**
     * @param string $file
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    protected function initialize($file)
    {
        if (!$file) {
            throw new InvalidArgumentException('Log file is not specified.');
        }

        if (!file_exists($file) && !touch($file)) {
            throw new RuntimeException(sprintf('Log file %s can not be created.', $file));
        }

        if (!is_writable($file)) {
            throw new RuntimeException(sprintf('Log file %s is not writeable.', $file));
        }
    }

    /**
     * @inheritdoc
     */
    public function info($message, array $context = [])
    {
        if (!in_array($this->level, [self::DEBUG, self::INFO])) {
            return;
        }

        $this->log(self::INFO, $message, $context);
    }

    /**
     * @inheritdoc
     */
    public function debug($message, array $context = [])
    {
        if (!in_array($this->level, [self::DEBUG])) {
            return;
        }

        $this->log(self::DEBUG, $message, $context);
    }

    /**
     * @inheritdoc
     */
    public function error($message, array $context = [])
    {
        if (!in_array($this->level, [self::DEBUG, self::INFO, self::ERROR])) {
            return;
        }

        $this->log(self::ERROR, $message, $context);
    }

    /**
     * @inheritdoc
     */
    public function log($level, $message, array $context = [])
    {
        $datetime = new \DateTime();
        $datetime = $datetime->format(DATE_ATOM);

        $content = sprintf('%s -- %s -- %s -- %s', $level, $_SERVER['REMOTE_ADDR'], $datetime, $message);
        $content .= ($context ? "\n" . print_r($context, true) : '');
        $content .= "\n";

        file_put_contents($this->file, $content, FILE_APPEND);
    }
}
