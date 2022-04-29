<?php

/**********************************************************
 *  @author Ivan Zorin <creator@localzet.ru>              *
 *  @license GNU General Public License v3.0              *
 *  @copyright Zorin Projects <www.localzet.ru>           *
 **********************************************************/

namespace LIS\Exception;

/**
 * UnexpectedValueException
 *
 * Exception thrown if a value does not match with a set of values. Typically this happens when a function calls
 * another function and expects the return value to be of a certain type or value not including arithmetic or
 * buffer related errors.
 */
class UnexpectedValueException extends RuntimeException implements ExceptionInterface
{
}
