<?php

namespace Rackspace\CloudFiles\Backup;

use SplFileInfo;
use RecursiveDirectoryIterator;
use InvalidArgumentException;

class Directory
{
    private $path;

    private $container;

    private $cache;

    public function __construct($path, Container $container)
    {
        $this->path      = $this->checkDirectory($path);
        $this->container = $container;
        $this->cache     = new Cache($this, $container);
    }

    /**
     * Scan filename in directory
     *
     * @param $directory
     * @return File[]
     */
    public function scan($directory)
    {
        $directory = $this->joinPath($directory);

        $this->checkDirectory($directory);

        $dir   = new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = [];

        foreach ($dir as $fileInfo) {
            if ($this->isFileDaily($fileInfo)) {
                $files[] = new File($this, $this->trimFilename($fileInfo));
            }
        }

        return $files;
    }

    /**
     * Given a path, trim away leading slashes and strip the base path.
     *
     * @param SplFileInfo $file
     * @return string
     */
    private function trimFilename(SplFileInfo $file)
    {
        return ltrim(str_replace($this->path, '', $file->getPathname()), '/');
    }


    private function isFileDaily(SplFileInfo $fileInfo)
    {
        return strpos($fileInfo->getFilename(), '.') > 0 && File::extractTime($fileInfo->getPathname());
    }

    private function checkDirectory($path)
    {
        if (!is_dir($path)) {
            throw new InvalidArgumentException(sprintf('%s does not exist', $path));
        }

        return $path;
    }

    public function cache()
    {
        return $this->cache;
    }

    public function container()
    {
        return $this->container;
    }

    public function path()
    {
        return $this->path;
    }

    public function download($filename)
    {
        if (!$this->exists($filename)) {
            if (!$this->makeDirectory($filename)) {
                throw new InvalidArgumentException(sprintf('%s does not exist', $this->dirName($filename)));
            }
            return (new File($this, $filename))->download();
        }

        return true;
    }

    public function purge($keepTheLast = true)
    {
        $newest = $keepTheLast ? $this->cache->findNewest() : null;
        foreach ($this->cache->keys() as $filename) {
            if ((!$newest || $newest->path() != $filename)) {
                $this->delete($filename);
            }
        }
    }

    public function exists($filename)
    {
        return file_exists($this->joinPath($filename));
    }

    public function delete($filename)
    {
        return $this->exists($filename) ? unlink($this->joinPath($filename)) : true;
    }

    public function joinPath($path)
    {
        return $this->path . DIRECTORY_SEPARATOR . $path;
    }

    private function makeDirectory($filename)
    {
        $directory  = $this->dirName($filename);
        $isDir = true;
        if (!is_dir($directory)) {
            $isDir = mkdir($directory, 0755, true);
        }

        return $isDir;
    }

    private function dirName($filename)
    {
        return dirname($this->joinPath($filename));
    }
}
