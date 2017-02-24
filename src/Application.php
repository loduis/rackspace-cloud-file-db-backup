<?php

namespace Rackspace\CloudFiles\Backup;

use Dotenv\Dotenv;
use ReflectionClass;
use RuntimeException;
use OpenCloud\Rackspace;
use Guzzle\Common\Event;

/**
 * Class Application
 * @property Cache $cache
 * @package Rackspace\CloudFiles\Backup
 */
class Application
{
    /**
     * @var Directory
     */
    private $directory;

    public function __construct($basePath, array $options)
    {
        $client = new Rackspace(
            $this->resolveEndPoint($options['identity_endpoint']),
            [
                'username' => $options['user_name'],
                'apiKey'   => $options['api_key']
            ],
            $options
        );

        $this->registerProgress($client, $options);

        $container = new Container($client, $options['region'], $options['container']);

        $this->directory = new Directory($this->realPath($basePath, $options['directory']), $container);

        $this->max($options['max_files']);
    }

    /**
     * @param $basePath
     * @return static
     */
    public static function fromDotEnv($basePath)
    {
        (new Dotenv($basePath))->load();
        $options = [
            'identity_endpoint' => getenv('RACKSPACE_IDENTITY_ENDPOINT'),
            'region'            => getenv('RACKSPACE_REGION'),
            'directory'         => getenv('RACKSPACE_DIRECTORY'),
            'container'         => getenv('RACKSPACE_CONTAINER'),
            'user_name'         => getenv('RACKSPACE_USER_NAME'),
            'api_key'           => getenv('RACKSPACE_API_KEY'),
            'max_files'         => getenv('RACKSPACE_MAX_FILES')
        ];

        return new static($basePath, $options);
    }

    /**
     * @param string $directory
     * @return File[]
     */
    public function scan($directory)
    {
        return $this->directory->scan($directory);
    }

    public function download($filename)
    {
        return $this->directory->download($filename);
    }

    public function purge($keepTheLast = true)
    {
        $this->directory->purge($keepTheLast);
    }

    public function findOldest()
    {
        return $this->cache()->findOldest();
    }

    public function max($max)
    {
        $this->directory->container()->max($max);

        return $this;
    }

    public function cache()
    {
        return $this->directory->cache();
    }

    private function resolveEndPoint($endPoint)
    {
        $reflection = new ReflectionClass(Rackspace::class);

        foreach ($reflection->getConstants() as $constant) {
            if ($endPoint == $constant) {
                return $endPoint;
            }
        }

        $endPoint = $endPoint . '_IDENTITY_ENDPOINT';

        if ($reflection->hasConstant($endPoint)) {
            return $reflection->getConstant($endPoint);
        }

        return new RuntimeException('Not can\'t be resolve endpoint: ' . $endPoint);
    }

    private function registerProgress($client, array $options)
    {
        if (isset($options[Rackspace::CURL_OPTIONS])) {
            $client->getEventDispatcher()->addListener('curl.callback.progress', function (Event $event) {
                print_r($event);
            });
        }
    }

    private function realPath($basePath, $directory)
    {
        return realpath($basePath . DIRECTORY_SEPARATOR . $directory);
    }


    public function __get($property)
    {
        if (method_exists($this, $property)) {
            return $this->$property();
        }

        return null;
    }
}
