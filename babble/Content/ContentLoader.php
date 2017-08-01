<?php

namespace Babble\Content;

use ArrayIterator;
use Babble\Exceptions\RecordNotFoundException;
use Babble\Models\TemplateRecord;
use Babble\Models\Record;
use Babble\Models\Model;
use InvalidArgumentException;
use Iterator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class ContentLoader implements Iterator
{
    private $model;
    private $filters;
    private $withChildren = false;
    private $parentId;

    private $fileIterator;
    private $currentRecord = null;

    public function __construct(string $modelType)
    {
        $this->model = new Model($modelType);
        $this->filters = new FilterContainer();
    }

    public function find($id)
    {
        $fs = new Filesystem();
        $filePath = $this->getModelDirectory() . $id . '.yaml';
        $dataFileExists = $fs->exists($filePath);

        if ($dataFileExists) return new TemplateRecord(Record::fromDisk($this->model, $id));
        throw new RecordNotFoundException($this->model->getType() . ' record with ID "' . $id . '" does not exist.');
    }

    public function where($key, $comparison, $value)
    {
        // TODO: Validate that model actually has the key / column provided.
        $this->filters->and(new WhereFilter($key, $comparison, $value));
        return $this;
    }

    public function orWhere($key, $comparison, $value)
    {
        // TODO: Validate that model actually has the key / column provided.
        $this->filters->or(new WhereFilter($key, $comparison, $value));
        return $this;
    }

    public function withChildren()
    {
        // TODO: Warn if not hierarchical?
        $this->withChildren = true;
        return $this;
    }

    public function childrenOf(string $id)
    {
        // TODO: Warn if not hierarchical?
        $this->parentId = $id;
        return $this;
    }

    private function initIterator()
    {
        $finder = new Finder();
        try {
            $finder->files()
                ->name('*.yaml')
                ->sortByName()
                ->in($this->getModelDirectory());

            if (!$this->withChildren || !$this->model->isHierarchical()) {
                $finder->depth(0);
            }

            $this->fileIterator = $finder->getIterator();
        } catch (InvalidArgumentException $e) {
            $this->fileIterator = new ArrayIterator([]);
        }
    }

    /**
     * @return string
     */
    private function getModelDirectory(): string
    {
        $type = $this->model->getType();
        $path = '../content/' . $type . '/';

        // Add parent ID to loader path.
        if ($this->parentId) {
            $path .= $this->parentId . '/';
        }

        return $path;
    }

    private function filenameToId(string $filename): string
    {
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $id = substr($filename, 0, strlen($filename) - strlen($ext) - 1);

        // Add parent ID.
        if ($this->parentId) {
            $id = $this->parentId . '/' . $id;
        }

        return $id;
    }

    static function getModelNames()
    {
        $models = [];
        $finder = new Finder();
        $files = $finder
            ->files()
            ->depth(0)
            ->name('/^[A-Z].+\.yaml$/')
            ->in('../models/');

        foreach ($files as $filename) {
            $modelName = pathinfo($filename, PATHINFO_FILENAME);
            $models[] = $modelName;
        }

        return $models;
    }

    /**
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     * @since 5.0.0
     */
    public function current()
    {
        return $this->currentRecord;
    }

    /**
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function next()
    {
        if (!$this->fileIterator) $this->initIterator();

        // Stop if no more files are available.
        if (!$this->fileIterator->valid()) {
            $this->currentRecord = null;
            return;
        };

        // Get start file and move iterator along,
        $file = $this->fileIterator->current();
        $this->fileIterator->next();

        // Load record files until a matching record is found.
        $tmplRecord = null;
        while (!$tmplRecord) {
            $id = $this->filenameToId($file->getRelativePathname());
            $record = Record::fromDisk($this->model, $id);
            if ($this->filters->isMatch($record)) {
                // Matching record was found.
                $this->currentRecord = new TemplateRecord($record);
                return;
            } else if (!$this->fileIterator->valid()) {
                // End of iterator. No more matches possible.
                $this->currentRecord = null;
                return;
            } else {
                // File didn't match, but there may be more to check. Move iterator along.
                $this->fileIterator->next();
            }
        }
    }

    /**
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     * @since 5.0.0
     */
    public function key()
    {
        return $this->currentRecord['id'];
    }

    /**
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     * @since 5.0.0
     */
    public function valid()
    {
        return $this->currentRecord !== null;
    }

    /**
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function rewind()
    {
        // If file iterator is already initialized, rewind it.
        // Otherwise it'll be initialized first time next() is called.
        if ($this->fileIterator) {
            $this->fileIterator->rewind();
        }

        $this->next();
    }
}
