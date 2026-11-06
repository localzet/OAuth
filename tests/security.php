<?php declare(strict_types=1);

require __DIR__ . '/../src/OAuth/HttpClient/HttpClientInterface.php';
require __DIR__ . '/../src/OAuth/HttpClient/Curl.php';

use localzet\OAuth\HttpClient\Curl;

if (!extension_loaded('curl')) {
    fwrite(STDERR, "The curl PHP extension is required for this test\n");
    exit(1);
}

$client = new Curl();
$property = new ReflectionProperty(Curl::class, 'curlOptions');
$property->setAccessible(true); // Required on PHP 7.4.
$options = $property->getValue($client);
if (($options[CURLOPT_SSL_VERIFYPEER] ?? null) !== true) {
    throw new RuntimeException('TLS peer verification must be enabled by default');
}
if (($options[CURLOPT_SSL_VERIFYHOST] ?? null) !== 2) {
    throw new RuntimeException('TLS hostname verification must be enabled by default');
}

echo "OAuth secure cURL defaults: OK\n";
