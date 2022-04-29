<?php

/**********************************************************
 *  @author Ivan Zorin <creator@localzet.ru>              *
 *  @license GNU General Public License v3.0              *
 *  @copyright Zorin Projects <www.localzet.ru>           *
 **********************************************************/

namespace LIS\Exception;

/**
 * RuntimeException
 *
 * Exception thrown if an error which can only be found on runtime occurs.
 */
class RuntimeException extends Exception implements ExceptionInterface
{
}
