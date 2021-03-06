<?php

namespace Babble;


class Path
{
    private $path;
    private $routeBindings = [];

    public function __construct(string $path)
    {
        $this->path = preg_replace('/\/+/', '/', $path);
    }

    public function __toString(): string
    {
        return $this->path;
    }

    public function clean(): string
    {
        $cleanPath = $this->path;
        if (substr($cleanPath, -6) === '/index') {
            $cleanPath = substr($cleanPath, 0, -6);
        }
        $cleanPath = rtrim($cleanPath, '/');

        if (!$cleanPath) return '/';
        return $cleanPath;
    }


    public function getExtension()
    {
        return pathinfo($this->path, PATHINFO_EXTENSION);
    }

    public function getFilename()
    {
        return pathinfo($this->path, PATHINFO_FILENAME);
    }

    public function getWithoutExtension(): string
    {
        $dirName = $this->getDirectory();
        $filename = $this->getFilename();
        if ($dirName !== '/') return $dirName . '/' . $filename;
        return '/' . $filename;
    }

    public function getDirectory(): string
    {
        return rtrim(pathinfo($this->path, PATHINFO_DIRNAME), '/');
    }

    public function is(string $url): bool
    {
        $url = rtrim($url, '/');
        $clean = rtrim($this, '/');

        // Special case for index:
        if($url === '') {
            return $url === $clean;
        }

        return strpos($clean, $url) === 0;
    }

    public function isExactly(string $url): bool
    {
        $url = rtrim($url, '/');
        $clean = rtrim($this, '/');
        return $clean == $url;
    }

    public function isHidden(): bool
    {
        return count(array_filter(explode('/', $this->path), function ($dir) {
                return strlen($dir) > 0 && $dir[0] === '_';
            })) > 0;
    }

    /**
     * Takes in a template directory and matches variables with values in the path.
     * Should not be called outside Babble core!
     *
     * @param string $templateDir
     */
    public function bindRoute(string $templateDir)
    {
        $this->routeBindings = [];
        if (strlen($templateDir) === 0) return;

        // $dirsOrVars contains directories or variables like this: ['blog', '$year', '$month', '$day'].
        $dirsOrVars = explode('/', trim($templateDir, '/'));

        // $varValues contains values to be bound like ['blog', '2017', '12', '02', 'some-id']
        // it may be longer than $dirsOrVars as the last value in $varValues may be the resource ID or "index".
        $varValues = explode('/', trim($this->path, '/'));

        for ($i = 0; $i < count($dirsOrVars); $i++) {
            $dirOrVar = $dirsOrVars[$i];
            if ($dirOrVar[0] !== '$') continue; // Only bind variables like "$year", and ignore literals like "blog".

            $boundVar = substr($dirOrVar, 1); // Drop the dollar sign.

            // If this is matched with a file, it might look like "$page.twig", so we need to drop the extension.
            $extStart = strpos($boundVar, '.');
            if ($extStart !== false) {
                $boundVar = substr($boundVar, 0, $extStart);
            }

            $this->routeBindings[$boundVar] = $varValues[$i];
        }
    }

    /**
     * Access path variables. If the rendered template is "/blog/$year/$month/$day/Post.twig" and path is
     * "/blog/2017/12/02/test-post", then route('year') will return "2017" etc.
     *
     * @param string $var
     * @return string|null
     */
    public function route(string $var)
    {
        return $this->routeBindings[$var] ?? null;
    }
}
