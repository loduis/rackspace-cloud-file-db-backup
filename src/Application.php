<?php

namespace Rackspace\CloudFiles\Backup;

use Dotenv\Dotenv;
use ReflectionClass;
use RuntimeException;
use OpenCloud\Rackspace;
use Guzzle\Common\Event;
use InvalidArgumentException;
use RecursiveDirectoryIterator;

/**
 * Class Application
 *
 * @package Rackspace\CloudFiles\Backup
 */
class Application
{
    /**
     * @var Directory
     */
    private $directory;

    private $container;

    private $cache;

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

        $this->container = new Container($client, $options['region'], $options['container'], $options['max_files']);

        $this->directory = new Directory($this->realPath($basePath, $options['directory']));

        $this->cache     = new Cache($this->container, $this->directory, $options['file_prefix']);
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
            'max_files'         => getenv('RACKSPACE_MAX_FILES'),
            'file_prefix'       => getenv('RACKSPACE_FILE_PREFIX')
        ];

        return new static($basePath, $options);
    }

    /**
     * Scan filename in directory
     *
     * @param $directory
     * @return File[]
     */
    public function scan($directory)
    {
        $directory = $this->directory->joinPath($directory);

        $this->directory->check($directory);

        $dir   = new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = [];

        foreach ($dir as $fileInfo) {
            if (File::isDaily($fileInfo)) {
                $files[] = $this->file($this->directory->removeBasePath($fileInfo));
            }
        }

        return $files;
    }

    public function download($filename)
    {
        if (!$this->directory->exists($filename)) {
            if (!$this->directory->make($filename)) {
                throw new InvalidArgumentException(sprintf('%s does not exist', $this->directory->getName($filename)));
            }
            return $this->file($filename)->download();
        }

        return true;
    }

    public function purge($keepTheLast = true)
    {
        $newest = $keepTheLast ? $this->findNewest() : null;
        foreach ($this->cache->keys() as $filename) {
            if ((!$newest || $newest->path() != $filename)) {
                $this->directory->delete($filename);
            }
        }
    }

    public function findOldest()
    {
        $oldest = null;
        $time   = strtotime('now');
        $count  = 0;
        foreach ($this->cache as $filename => $fileTime) {
            if ($time > $fileTime) {
                $time = $fileTime;
                $oldest = $filename;
            }
            $count ++;
        }

        return $oldest && $count > $this->container->maxFiles() ? $this->file($oldest) : null;
    }

    private function findNewest()
    {
        $newest = null;
        $time   = 0;
        foreach ($this->cache as $filename => $fileTime) {
            if ($fileTime > $time) {
                $time   = $fileTime;
                $newest = $filename;
            }
        }

        return $newest ? $this->file($newest) : null;
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

    private function file($name)
    {
        return new File($this->container, $this->directory, $this->cache, $name);
    }
}
