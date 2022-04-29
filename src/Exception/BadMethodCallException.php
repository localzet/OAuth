<?php

/**********************************************************
 *  @author Ivan Zorin <creator@localzet.ru>              *
 *  @license GNU General Public License v3.0              *
 *  @copyright Zorin Projects <www.localzet.ru>           *
 **********************************************************/

namespace LIS\Exception;

/**
 * BadMethodCallException
 *
 * Exception thrown if a callback refers to an undefined method or if some arguments are missing.
 */
class BadMethodCallException extends RuntimeException implements ExceptionInterface
{
}
