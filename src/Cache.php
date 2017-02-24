<?php

namespace Rackspace\CloudFiles\Backup;

use ArrayIterator;
use IteratorAggregate;

class Cache implements IteratorAggregate
{
    const FILE_NAME = '.rackspace_cloud_files.json';

    private $directory;

    private $data = [];

    public function __construct(Directory $directory)
    {
        $this->directory = $directory;
        $this->data      = $this->get();
    }

    private function getFilename()
    {
        return $this->directory->path() . DIRECTORY_SEPARATOR . self::FILE_NAME;
    }

    private function get()
    {
        $data = [];

        $filename = $this->getFilename();
        if (file_exists($filename)) {
            $content = file_get_contents($filename);
            if ($content !== false) {
                $data = json_decode($content, true);
                if (!is_array($data)) {
                    $data = [];
                }
            }
        } else {
            $data = $this->refresh();
        }

        return $data;
    }

    public function exists($filename)
    {
        return isset($this->data[$filename]);
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

    public function findOldest()
    {
        $oldest = null;
        $time   = strtotime('now');
        $count  = 0;
        foreach ($this->data as $filename => $fileTime) {
            if ($time > $fileTime) {
                $time = $fileTime;
                $oldest = $filename;
            }
            $count ++;
        }

        return $oldest && $count > $this->container()->max() ?
            new File($this->directory, $oldest) : null;
    }

    public function findNewest()
    {
        $newest = null;
        $time   = 0;
        foreach ($this->data as $filename => $fileTime) {
            if ($fileTime > $time) {
                $time   = $fileTime;
                $newest = $filename;
            }
        }

        return $newest ? new File($this->directory, $newest) : null;
    }

    public function getIterator()
    {
        return new ArrayIterator($this->data);
    }

    public function keys()
    {
        return array_keys($this->data);
    }

    private function container()
    {
        return $this->directory->container();
    }

    private function refresh()
    {
        $data = [];
        foreach ($this->container()->all() as $file) {
            $name = $file->getName();
            if ($time = File::extractTime(basename($name))) {
                $data[$name] = $time;
            }
        }
        if ($data) {
            $this->save($data);
        }

        return $data;
    }
}
