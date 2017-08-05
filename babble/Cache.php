<?php

namespace Babble;

use Babble\Events\RecordChangeEvent;
use Babble\Events\RenderDependencyEvent;
use Babble\Events\RenderEvent;
use Exception;
use SplFileObject;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Filesystem\Filesystem;

class Cache
{
    private $dispatcher;

    public function __construct(EventDispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;

//        $dispatcher->addListener(RenderEvent::NAME, [$this, 'onRender']);
        $dispatcher->addListener(RenderDependencyEvent::NAME, [$this, 'onRenderDependency']);
        $dispatcher->addListener(RecordChangeEvent::NAME, [$this, 'onRecordChange']);
    }


    public function store(string $path, string $content)
    {
        $fs = new Filesystem();
        $filename = $this->pathToCachePath($path);
        $fs->dumpFile($filename, $content);
    }

    public function load(string $path)
    {
        $filename = $this->pathToCachePath($path);

        try {
            $content = file_get_contents($filename);

            return $content;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * @param string $path
     * @return string
     */
    private function pathToCachePath(string $path): string
    {
        return '../cache' . self::pathToFilename($path);
    }

    public function onRender(RenderEvent $event)
    {
        $this->store($event->getPath(), $event->getContent());
    }

    public function onRenderDependency(RenderDependencyEvent $event)
    {
        $this->addDependency($event->getModelDependency(), $event->getPath());
    }

    private function addDependency($modelName, $path)
    {
        $dependencyFilename = $this->getModelDependencyFile($modelName);
        $content = $path . "\n";

        $fs = new Filesystem();
        $fs->appendToFile($dependencyFilename, $content);
    }

    public function onRecordChange(RecordChangeEvent $event)
    {
        $this->invalidateCache($event->getModelType());
    }

    public static function pathToFilename(string $path)
    {
        if ($path[strlen($path) - 1] == '/') {
            $path .= 'index';
        }
        return $path . '.html';
    }

    private function invalidateCache($modelName)
    {
        $dependencyFilename = $this->getModelDependencyFile($modelName);
        $file = new SplFileObject($dependencyFilename);

        $fs = new Filesystem();

        // Loop until we reach the end of the file.
        $removeFilenames = [];
        while (!$file->eof()) {
            // Convert from path (/blog) to cached filename (/blog.html) and add to array.
            $dependentPath = trim($file->fgets());
            if (strlen($dependentPath) === 0) continue;
            $cachedFilename = $this->pathToCachePath($dependentPath);
            $removeFilenames[] = $cachedFilename;
        }

        // Remove all files which depend on the changed record's model type.
        $fs->remove($removeFilenames);

        // Unset the file to call __destruct(), closing the file handle.
        $file = null;
    }

    /**
     * @param $modelName
     * @return string
     */
    private function getModelDependencyFile($modelName): string
    {
        $dependencyFilename = '../cache/_dependencies/' . $modelName . '.txt';
        return $dependencyFilename;
    }
}