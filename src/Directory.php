<?php

namespace Rackspace\CloudFiles\Backup;

use SplFileInfo;
use InvalidArgumentException;

class Directory
{
    private $path;


    public function __construct($path)
    {
        $this->path = $this->check($path);
    }

    /**
     * Given a path, trim away leading slashes and strip the base path.
     *
     * @param SplFileInfo $file
     * @return string
     */
    public function removeBasePath(SplFileInfo $file)
    {
        return ltrim(str_replace($this->path, '', $file->getPathname()), '/');
    }

    public function check($path)
    {
        if (!is_dir($path)) {
            throw new InvalidArgumentException(sprintf('%s does not exist', $path));
        }

        return $path;
    }

    public function path()
    {
        return $this->path;
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

    public function make($filename)
    {
        $directory  = $this->getName($filename);
        $isDir = true;
        if (!is_dir($directory)) {
            $isDir = mkdir($directory, 0755, true);
        }

        return $isDir;
    }

    public function getName($filename)
    {
        return dirname($this->joinPath($filename));
    }
}
