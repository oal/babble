<?php

namespace Babble;


class Path
{
    private $path;

    public function __construct(string $path)
    {
        $this->path = preg_replace('/\/+/', '/', $path);
    }

    public function __toString(): string
    {
        return $this->path;
    }


    public function getExtension()
    {
        return pathinfo($this->path, PATHINFO_EXTENSION);
    }

    public function getFilename()
    {
        return pathinfo($this->path, PATHINFO_FILENAME);
    }

    public function getWithoutExtension()
    {
        $dirName = $this->getDirectory();
        $filename = $this->getFilename();
        if ($dirName !== '/') return $dirName . '/' . $filename;
        return '/' . $filename;
    }

    public function getDirectory()
    {
        return rtrim(pathinfo($this->path, PATHINFO_DIRNAME), '/');
    }
}