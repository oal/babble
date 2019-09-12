<?php

namespace Babble\Models;

use ArrayAccess;
use Babble\Content\ContentLoader;
use JsonSerializable;

class TemplateRecord implements ArrayAccess, JsonSerializable
{
    private $record;

    public function __construct(Record $record)
    {
        $this->record = $record;
    }

    public function __toString()
    {
        return json_encode($this->record);
    }


    public function getType()
    {
        return $this->record->getType();
    }

    public function children()
    {
        $model = $this->record->getModel();
        if (!$model->isHierarchical()) return [];

        $loader = new ContentLoader($model);
        return $loader->childrenOf($this->record->getValue('id'));
    }

    private function renderProperty(string $property)
    {
        return $this->record->getModel()->getProperty($property, ['this' => $this]);
    }

    public function field($name)
    {
        return $this->record->getModel()->getField($name);
    }


    public function offsetExists($offset)
    {
        return !!$this->record->getView($offset) || $this->record->getModel()->hasProperty($offset);
    }

    public function offsetGet($offset)
    {
        $result = $this->record->getView($offset);
        if(!$result) $result = $this->renderProperty($offset);

        return $result;
    }

    public function offsetSet($offset, $value)
    {
    }

    public function offsetUnset($offset)
    {
    }

    function jsonSerialize()
    {
        return $this->record;
    }

    public function getAbsoluteURL()
    {
        return $this->record->getAbsoluteURL();
    }
}
