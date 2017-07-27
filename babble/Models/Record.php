<?php

namespace Babble\Models;

use ArrayAccess;
use Babble\Models\Fields\Field;
use JsonSerializable;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class Record implements JsonSerializable
{
    private $model;

    private $id;
    private $data = [];

    public function __construct(Model $model, $id, array $data = [])
    {
        $this->model = $model;
        $this->id = $id;

        foreach ($this->model->getFields() as $field) {
            $key = $field->getKey();
            $value = $data[$key] ?? null;
            $this->data[$key] = new Column($this, $field, $value);
        }
    }

    public function __toString()
    {
        return json_encode($this->data);
    }

    public function save()
    {
        $columns = $this->data;
        foreach ($columns as $key => $column) {
            $ok = $column->validate();
            if (!$ok) return;
        }

        $yaml = Yaml::dump($this->getData(), 2, 4, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);

        $fs = new Filesystem();
        $fs->dumpFile($this->getContentFilePath(), $yaml);
    }

    public function update(array $data)
    {
        foreach ($this->data as $key => $column) {
            $column->setValue($data[$key] ?? null);
        }
        return $this->save();
    }

    public function delete()
    {
        $fs = new Filesystem();
        $fs->remove($this->getContentFilePath());
    }

    public function getValue($column)
    {
        if ($column === 'id') return $this->id;
        return $this->data[$column]->getValue();
    }

    public function setValue($column, $value)
    {
        if ($column === 'id') {
            $this->id = $value;
        } else {
            $this->data[$column]->setValue($value);
        }
    }

    private function loadFromDisk()
    {
        $modelData = Yaml::parse(file_get_contents($this->getContentFilePath()));
        foreach ($this->model->getFields() as $field) {
            $key = $field->getKey();
            if (!array_key_exists($key, $modelData)) continue;
            $this->data[$key]->setValue($modelData[$key]);
        }
    }

    public function getType()
    {
        return $this->model->getType();
    }

    function jsonSerialize()
    {
        return array_merge(['id' => $this->id], $this->data);
    }

    static function fromDisk(Model $model, string $id)
    {
        $record = new Record($model, $id);
        $record->loadFromDisk();

        return $record;
    }

    /**
     * @return string
     */
    private function getContentFilePath(): string
    {
        return '../content/' . $this->getType() . '/' . $this->id . '.yaml';
    }

    private function getData()
    {
        $data = [];
        foreach ($this->data as $key => $column) {
            $data[$key] = $column->getValue();
        }
        return $data;
    }

    public function getView($column)
    {
        if ($column === 'id') return $this->id;
        if (!array_key_exists($column, $this->data)) return null;
        return $this->data[$column]->getView();
    }
}

class Column implements JsonSerializable
{
    private $record;
    private $field;
    private $value;

    public function __construct(Record $record, Field $field, $value)
    {
        $this->record = $record;
        $this->field = $field;
        $this->value = $value;
    }

    function validate()
    {
        return $this->field->validate($this->record, $this->value);
    }

    function jsonSerialize()
    {
        return $this->field->toJSON($this->value);
    }

    function getValue()
    {
        return $this->value;
    }

    function getView()
    {
        return $this->field->getView($this->value);
    }

    function setValue($value)
    {
        $this->value = $value;
    }
}


class ArrayAccessRecord implements ArrayAccess, JsonSerializable
{
    private $record;

    public function __construct(Record $record)
    {
        $this->record = $record;
    }

    public function getType()
    {
        return $this->record->getType();
    }


    public function offsetExists($offset)
    {
        return !!$this->record->getView($offset);
    }

    public function offsetGet($offset)
    {
        return $this->record->getView($offset);
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
}