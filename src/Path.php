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

    private function clean()
    {
        $cleanPath = $this->path;
        if (substr($cleanPath, -6) === '/index') {
            $cleanPath = substr($cleanPath, 0, -6);
        }
        $cleanPath = rtrim($cleanPath, '/');

        if(!$cleanPath) return '/';
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

    public function is(string $url)
    {
        $url = rtrim($url, '/');
        $clean = $this->clean();
        return strpos($clean, $url) === 0;
    }

    public function isExactly(string $url)
    {
        $url = rtrim($url, '/');
        $clean = $this->clean();
        return $clean == $url;
    }
}