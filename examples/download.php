<?php

require '../vendor/autoload.php';

use Rackspace\CloudFiles\Backup\Application;

if (!(isset($_SERVER['argc']) && $_SERVER['argc'] > 1)) {
    die(
        'Give me the object to be downloaded' . PHP_EOL .
        'Example: php download.php db/daily/2015-02-15.txt' . PHP_EOL
    );
}

try {
    $app = Application::fromDotEnv(__DIR__/*, [
        'curl.options' => [
            'progress' => true
        ]
    ]*/);

    $filename = $_SERVER['argv'][1];

    if ($app->download($filename)) {
        echo 'Downloaded: ', $filename, PHP_EOL;
    }
} catch (Exception $e) {
    echo $e->getMessage(), PHP_EOL;
}
