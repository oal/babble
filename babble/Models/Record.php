<?php

namespace Babble;

use ArrayAccess;
use Babble\Models\Model;
use InvalidModelFieldException;
use JsonSerializable;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class Record implements ArrayAccess, JsonSerializable
{
    private $model;

    private $id;
    private $data = [];

    public function __construct(Model $model, $id)
    {
        $this->model = $model;
        $this->id = $id;
    }

    public function __toString()
    {
        return json_encode($this->data);
    }

    private function loadFromDisk()
    {
        $modelData = Yaml::parse(file_get_contents($this->getContentFilePath()));
        foreach ($this->model->getFields() as $field) {
            if (!array_key_exists($field->getKey(), $modelData)) continue;
            $this->data[$field->getKey()] = $modelData[$field->getKey()];
        }
    }

    public function getType()
    {
        return $this->model->getType();
    }

    public function save()
    {
        $fields = $this->model->getFields();
        foreach ($fields as $field) {
            $ok = $field->validate($this->data[$field->getKey()]);
            if (!$ok) return;
        }

        foreach ($fields as $field) {
            $processedData = $field->process($this->id, $this->data[$field->getKey()]);
            $this->data[$field->getKey()] = $processedData;
        }

        $yaml = Yaml::dump($this->data, 2, 4, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);

        $fs = new Filesystem();
        $fs->dumpFile($this->getContentFilePath(), $yaml);
    }

    public function delete()
    {
        $fs = new Filesystem();
        $fs->remove($this->getContentFilePath());
    }

    public function offsetExists($offset)
    {
        if ($offset === 'id') return true;
        return isset($this->data[$offset]);
    }

    public function offsetGet($offset)
    {
        if ($offset === 'id') return $this->id;
        $field = $this->model->getField($offset);
        return $field->getView($this->data[$offset]);
    }

    public function offsetSet($key, $value)
    {
        if (!$this->model->hasField($key)) {
            $modelName = $this->model->getName();
            throw new InvalidModelFieldException("Field \"$key\" does not exist on model \"$modelName\"");
        }
        $this->data[$key] = $value;
    }

    public function offsetUnset($offset)
    {
    }

    function jsonSerialize()
    {
        return array_merge(['id' => $this->id], $this->data);
    }

    static function fromDisk(Model $model, string $id)
    {
        $modelInstance = new Record($model, $id);
        $modelInstance->loadFromDisk();

        return $modelInstance;
    }

    static function fromData(Model $model, string $id, array $data)
    {
        $modelInstance = new Record($model, $id);
        foreach ($model->getFields() as $field) {
            $key = $field->getKey();
            $value = $data[$key];
            if (!empty($value)) $modelInstance[$key] = $value;
        }

        return $modelInstance;
    }

    /**
     * @return string
     */
    private function getContentFilePath(): string
    {
        return '../content/' . $this->getType() . '/' . $this->id . '.yaml';
    }
}