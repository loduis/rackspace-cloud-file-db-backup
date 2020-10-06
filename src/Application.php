<?php

namespace Rackspace\CloudFiles\Backup;

use Dotenv\Dotenv;
use ReflectionClass;
use RuntimeException;
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

    /**
     * This is util when you has one folder in container that no required sync
     *
     * @var mixed|null
     */
    private $scanFiles;

    public function __construct($basePath, array $options)
    {
        $client = new Rackspace($options);

        // $this->registerProgress($client, $options);

        $this->container = new Container($client, $options['region'], $options['container'], $options['max_files']);

        $this->directory = new Directory($this->realPath($basePath, $options['directory']));

        $this->scanFiles  = isset($options['scan_files']) ? $options['scan_files'] : null;

        $this->cache     = new Cache($this->container, $this->directory, $this->scanFiles);
    }

    /**
     * @param $basePath
     * @return static
     */
    public static function fromDotEnv($basePath)
    {
        Dotenv::createImmutable($basePath)->load();
        $options = [
            'identity_endpoint' => $_ENV['RACKSPACE_IDENTITY_ENDPOINT'],
            'region'            => $_ENV['RACKSPACE_REGION'],
            'directory'         => $_ENV['RACKSPACE_DIRECTORY'],
            'container'         => $_ENV['RACKSPACE_CONTAINER'],
            'user_name'         => $_ENV['RACKSPACE_USER_NAME'],
            'api_key'           => $_ENV['RACKSPACE_API_KEY'],
            'max_files'         => $_ENV['RACKSPACE_MAX_FILES'],
            'scan_files'        => $_ENV['RACKSPACE_SCAN_FILES']
        ];

        return new static($basePath, $options);
    }

    /**
     * Scan filename in directory
     *
     * @param $directory
     * @return File[]
     */
    public function scan($directory = null)
    {
        $directory = $directory ?: $this->scanFiles;
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

    public function all()
    {
        return $this->container->all();
    }
}
