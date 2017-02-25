<?php

require '../vendor/autoload.php';

use Rackspace\CloudFiles\Backup\Application;

try {
    $app = Application::fromDotEnv(__DIR__);

    // Scan directory and upload files to container

    foreach ($app->scan() as $file) {
        if ($file->upload()) {
            echo 'UPLOAD: ', $file, PHP_EOL;
        }
    }

    // Delete or move oldest from container

    if (($oldest = $app->findOldest())) {
        if ($oldest->isCopyOfEndMonth()) {
            $oldest->move('db/history');
            echo 'MOVE... ', $oldest, PHP_EOL;
        } else {
            $oldest->delete();
            echo 'DELETE... ', $oldest, PHP_EOL;
        }
    }

    // Purge file on directory

    $app->purge();
} catch (Exception $e) {
    echo $e->getMessage(), PHP_EOL;
}
