<?php

namespace Babble\Models;

class Block extends BaseModel
{
    private $model;

    public function __construct(BaseModel $model, string $type)
    {
        $this->model = $model;
        parent::__construct($type);
    }

    public function getModel(): BaseModel
    {
        return $this->model;
    }

    protected function getDefinitionFile()
    {
        return absPath('models/blocks/' . $this->type . '.yaml');
    }

    public function getType()
    {
        return parent::getType();
    }


    public function getCacheLocation(string $recordId)
    {
        $baseLocation = parent::getCacheLocation($recordId);
        return $baseLocation . $this->model->getType() . '/' . $recordId . '/' . $this->getType() . '/';
    }
}

