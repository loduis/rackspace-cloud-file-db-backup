<?php

namespace Rackspace\CloudFiles\Backup;

use SplFileInfo;
use GuzzleHttp\Psr7\Utils;

class File
{
    const DATE_ISO_REGEXP = '/^(\d{4}-\d{2}-\d{2})/';

    private $path;

    private $directory;

    private $cache;

    private $container;

    public function __construct(Container $container, Directory $directory, Cache $cache, $path)
    {
        $this->path      = $path;
        $this->directory = $directory;
        $this->cache     = $cache;
        $this->container = $container;
    }

    public function isCopyOfEndMonth()
    {
        return ($time = $this->getTime()) && $this->currentDay($time) == $this->endDay($time);
    }

    public function getTime()
    {
        return static::extractTime($this->path);
    }

    public static function extractTime($filename)
    {
        return preg_match(static::DATE_ISO_REGEXP, basename($filename), $match) &&
            ($time = strtotime($match[1])) > 0 ? $time : null;
    }

    public function content()
    {
        return file_get_contents($this->fullPath());
    }

    public function resource($mode = 'r')
    {
        return fopen($this->fullPath(), $mode);
    }

    public function stream()
    {
        return Utils::streamFor($this->resource());
    }

    public function upload()
    {
        if (!$this->cache->exists($this->path)) {
            return $this->container->upload($this) && $this->cache->put($this->path(), $this->getTime());
        }

        return true;
    }

    public function download()
    {
        return file_put_contents(
            $this->directory->joinPath($this->path),
            $this->container->download($this->path)
        ) !== false;
    }

    public function move($directory)
    {
        $filename  = basename($this->path);
        $directory = trim($directory, '/');

        return $this->container->copy($this->path, $directory. '/' . $filename) &&
               $this->delete();
    }

    public function delete()
    {
        return $this->container->delete($this->path) &&
            $this->directory->delete($this->path) &&
            $this->cache->forget($this->path);
    }

    public static function isDaily(SplFileInfo $fileInfo)
    {
        return strpos($fileInfo->getFilename(), '.') > 0 && static::extractTime($fileInfo->getPathname());
    }

    public function path()
    {
        return $this->path;
    }

    public function __toString()
    {
        return $this->path();
    }

    private function currentDay($time)
    {
        return (int) date('j', $time);
    }

    private function endDay($time)
    {
        return (int) date('t', $time);
    }

    private function fullPath()
    {
        return $this->directory->joinPath($this->path);
    }
}
