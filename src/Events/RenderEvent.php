<?php

namespace Babble\Events;


use Symfony\Contracts\EventDispatcher\Event;

class RenderEvent extends Event
{
    const NAME = 'render';
    /**
     * @var
     */
    private $path;
    /**
     * @var
     */
    private $content;

    public function __construct($path, $content)
    {
        $this->path = $path;
        $this->content = $content;
    }

    /**
     * @return mixed
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }

}