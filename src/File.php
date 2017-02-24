<?php

namespace Rackspace\CloudFiles\Backup;

class File
{
    private $path;

    private $directory;

    public function __construct(Directory $directory, $path)
    {
        $this->path      = $path;
        $this->directory = $directory;
    }

    public function isCopyOfEndMonth()
    {
        return ($time = $this->getTime()) && $this->currentDay($time) == $this->endDay($time);
    }

    public function getTime()
    {
        return self::extractTime($this->path);
    }

    public static function extractTime($filename)
    {
        $regex = '/^(\d{4}-\d{2}-\d{2})/';

        return preg_match($regex, basename($filename), $match) && ($time = strtotime($match[1])) > 0 ? $time : null;
    }

    public function content()
    {
        return file_get_contents($this->directory->joinPath($this->path));
    }

    public function upload()
    {
        $response = true;
        if (!$this->cache()->exists($this->path) && ($response = $this->container()->upload($this))) {
            $this->cache()->put($this->path(), $this->getTime());
        }

        return $response;
    }

    public function download()
    {
        return file_put_contents(
            $this->directory->joinPath($this->path),
            $this->container()->download($this->path)
        ) !== false;
    }

    public function move($directory)
    {
        $filename  = basename($this->path);
        $directory = trim($directory, '/');

        return $this->container()->copy($this->path, $directory. '/' . $filename) &&
               $this->delete();
    }

    public function delete()
    {
        if ($this->container()->delete($this->path) && $this->directory->delete($this->path)) {
            return $this->cache()->forget($this->path);
        }

        return false;
    }

    public function path()
    {
        return $this->path;
    }

    public function __toString()
    {
        return $this->path();
    }

    private function container()
    {
        return $this->directory->container();
    }

    private function cache()
    {
        return $this->directory->cache();
    }

    private function currentDay($time)
    {
        return (int) date('j', $time);
    }

    private function endDay($time)
    {
        return (int) date('t', $time);
    }
}
