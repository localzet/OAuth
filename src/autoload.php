<?php

/**********************************************************
 *  @author Ivan Zorin <creator@localzet.ru>              *
 *  @license GNU General Public License v3.0              *
 *  @copyright Zorin Projects <www.localzet.ru>           *
 **********************************************************/

if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    throw new Exception('LIS 3 требует PHP версии 7.4 или выше.');
}

/**
 * Зарегистрируйте автозагрузчик для классов LIS.
 *
 * Исходя из официального примера AutoLoader PSR-4, найден в
 * http://www.php-fig.org/psr/psr-4/examples/
 *
 * @param string $class
 *
 * @return void
 */
spl_autoload_register(
    /**
     * @param $class
     * @return void
     */
    function ($class) {
        $prefix = 'LIS\\';

        $base_dir = __DIR__;

        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }

        $relative_class = substr($class, $len);

        $file = $base_dir . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $relative_class) . '.php';

        if (file_exists($file)) {
            require $file;
        }
    }
);
