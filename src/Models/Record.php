<?php

namespace Babble\Models;

use Babble\Exceptions\RecordNotFoundException;
use JsonSerializable;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class Record implements JsonSerializable
{
    private $model;

    private $id;
    private $data = [];

    public function __construct(Model $model, $id = null, array $data = [])
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

    /**
     * Validates and saves record to disk.
     * @param array $data
     * @param array|null $updateColumns
     */
    public function save(array $data, array $updateColumns = null)
    {
        if (!$this->getModel()->isSingle()) {
            $id = $data['id'] ?? $this->id;
            $this->id = $id;
        }

        $columns = $this->data;
        if($updateColumns) {
            $columns = array_filter($columns, function($column) use (&$updateColumns) {
                return in_array($column, $updateColumns);
            }, ARRAY_FILTER_USE_KEY);
        }
        foreach ($columns as $key => $column) {
            if (array_key_exists($key, $data)) {
                $column->setValue($data[$key]);
            } else {
                $column->setValue(null);
            }
        }

        $yaml = Yaml::dump($this->getData(), 5, 4, YAML::DUMP_MULTI_LINE_LITERAL_BLOCK | YAML::DUMP_OBJECT_AS_MAP);

        $fs = new Filesystem();
        $fs->dumpFile($this->getContentFilePath(), $yaml);
    }

    /**
     * Delete resource.
     */
    public function delete()
    {
        $fs = new Filesystem();
        $fs->remove($this->getContentFilePath());
    }

    /**
     * Returns a Template (Twig) friendly version of the value.
     * @param string $column
     * @return null
     */
    public function getView(string $column)
    {
        if ($column === 'id') return $this->id;
        if (!array_key_exists($column, $this->data)) return null;
        return $this->data[$column]->getView();
    }

    /**
     * Returns the value of a column.
     *
     * @param string $column
     * @return mixed
     */
    public function getValue(string $column)
    {
        if ($column === 'id') return $this->id;
        return $this->data[$column]->getValue();
    }

    /**
     * Sets the value of a column.
     *
     * @param string $column
     * @param $value
     */
    public function setValue(string $column, $value)
    {
        $this->data[$column]->setValue($value);
    }

    /**
     * Gets type of model as a string.
     *
     * @return mixed
     */
    public function getType()
    {
        return $this->model->getType();
    }

    static function fromDisk(Model $model, $id = null): Record
    {
        if (!$model->isSingle() && !$id) throw new RecordNotFoundException();

        $path = getContentDir($model, $id);
        $content = @file_get_contents($path);
        if ($content === false) throw new RecordNotFoundException();

        $dataArray = Yaml::parse($content);
        return new Record($model, $id, $dataArray);
    }

    private function getContentFilePath(): string
    {
        return getContentDir($this->getModel(), $this->id);
    }

    private function getData(): array
    {
        $data = [];
        foreach ($this->data as $key => $column) {
            $data[$key] = $column->getValue();
        }
        return $data;
    }

    function jsonSerialize()
    {
        $computedFields = ['id' => $this->id];
        if ($this->getModel()->hasProperty('permalink')) {
            $computedFields['_permalink'] = $this->getModel()->getProperty('permalink', ['this' => new TemplateRecord($this)]);
        }
        return array_merge($computedFields, $this->data);
    }

    public function getModel(): Model
    {
        return $this->model;
    }

    public function getAbsoluteURL(): string
    {
        $baseURL = $this->getModel()->getBaseURL();
        if ($baseURL === '/') $baseURL = '';
        if ($this->id === 'index') return $baseURL . '/';
        return $baseURL . '/' . $this->id . '/';
    }
}

function getContentDir(Model $model, $id): string
{
    $typeDir = 'content/' . $model->getType();
    if ($model->isSingle()) return absPath($typeDir . '.yaml');
    return absPath($typeDir . '/' . $id . '.yaml');
}