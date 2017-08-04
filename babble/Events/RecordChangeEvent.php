<?php

namespace Babble\Events;


use Symfony\Component\EventDispatcher\Event;

class RecordChangeEvent extends Event
{
    const NAME = 'record.change';

    private $modelType;
    private $recordId;

    public function __construct($modelType, $recordId)
    {
        $this->modelType = $modelType;
        $this->recordId = $recordId;
    }

    /**
     * @return mixed
     */
    public function getModelType()
    {
        return $this->modelType;
    }

    /**
     * @return mixed
     */
    public function getRecordId()
    {
        return $this->recordId;
    }

}