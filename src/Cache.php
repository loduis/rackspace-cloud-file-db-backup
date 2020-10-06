<?php

namespace Rackspace\CloudFiles\Backup;

use ArrayIterator;
use IteratorAggregate;

class Cache implements IteratorAggregate
{
    const FILE_NAME = '.cache_cloud_files.json';

    /**
     * Directory that contain the cache file
     *
     * @var Directory
     */
    private $directory;

    /**
     * @var Container
     */
    private $container;

    /**
     * Data of cache
     *
     * @var array
     */
    private $data = [];


    /**
     * Ignore data when refresh cache
     *
     * @var array
     */
    private $filePrefix = [];

    public function __construct(Container $container, Directory $directory, $filePrefix)
    {
        $this->directory  = $directory;
        $this->container  = $container;
        $this->filePrefix = $filePrefix;
        $this->data       = $this->get();
    }

    public function exists($filename)
    {
        return isset($this->data[$filename]);
    }

    public function put($path, $time)
    {
        $this->data = array_merge($this->data, [$path => $time]);

        return $this->save($this->data);
    }

    public function forget($path)
    {
        if ($this->exists($path)) {
            unset($this->data[$path]);

            return $this->save($this->data);
        }

        return true;
    }

    public function getIterator()
    {
        return new ArrayIterator($this->data);
    }

    public function keys()
    {
        return array_keys($this->data);
    }

    private function getFilename()
    {
        return $this->directory->path() . DIRECTORY_SEPARATOR . static::FILE_NAME;
    }

    /**
     * Get cache
     *
     * @return array
     */
    private function get()
    {
        $filename = $this->getFilename();
        if (file_exists($filename)) {
            $content = file_get_contents($filename);
            return $content !== false ? (array) json_decode($content, true) : $this->refresh();
        }

        return $this->refresh();
    }

    /**
     * Refresh cache from container
     *
     * @return array
     */
    private function refresh()
    {
        $data = [];
        foreach ($this->container->all($this->filterParams()) as $file) {
            $name = $file->name;
            if ($time = File::extractTime($name)) {
                $data[$name] = $time;
            }
        }
        if ($data) {
            $this->save($data);
        }

        return $data;
    }

    private function filterParams()
    {
        $params = [];
        if ($this->filePrefix) {
            $params['prefix'] = $this->filePrefix;
        }

        return $params;
    }

    private function save(array $data)
    {
        $content = json_encode(
            $data,
            JSON_PRETTY_PRINT |
            JSON_UNESCAPED_SLASHES
        );

        return file_put_contents($this->getFilename(), $content) !== false;
    }
}
