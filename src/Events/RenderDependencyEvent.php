<?php

namespace Babble\Events;

use Symfony\Contracts\EventDispatcher\Event;

class RenderDependencyEvent extends Event
{
    const NAME = 'render.dependency';

    private $modelDependency;
    private $path;

    public function __construct($modelDependency, $path)
    {
        $this->modelDependency = $modelDependency;
        $this->path = $path;
    }

    /**
     * @return mixed
     */
    public function getModelDependency()
    {
        return $this->modelDependency;
    }

    /**
     * @return mixed
     */
    public function getPath()
    {
        return $this->path;
    }
}