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
    private $orderBy = ['id', 'asc'];
    private $skip = 0;
    private $take = null;
    private $withChildren = false;
    private $parentId;

    private $arrayIterator;

    public function __construct(Model $model)
    {
        $this->model = $model;
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

    public function whereContains($key, $value)
    {
        $this->filters->and(new WhereContainsFilter($key, $value));
        return $this;
    }

    public function orWhereContains($key, $value)
    {
        $this->filters->or(new WhereContainsFilter($key, $value));
        return $this;
    }

    public function orderBy($key, $direction = 'desc')
    {
        $this->orderBy = [$key, strtolower($direction)];
        return $this;
    }

    public function skip($skip)
    {
        $this->skip = $skip;
        return $this;
    }

    public function take($take)
    {
        $this->take = $take;
        return $this;
    }

    public function paginate($perPage, $currentPage)
    {
        return new Paginator($this, $perPage, $currentPage);
    }

    public function count()
    {
        if (!$this->arrayIterator) $this->initIterator();
        return iterator_count($this->arrayIterator);
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

            // TODO: Split up.
            $records = [];
            foreach ($finder as $file) {
                $id = $this->filenameToId($file->getRelativePathname());
                $record = Record::fromDisk($this->model, $id);
                if ($this->filters->isMatch($record)) {
                    // Matching record was found.
                    $records[] = new TemplateRecord($record);
                    continue;
                }
            }

            $orderBy = $this->orderBy;
            usort($records, function ($a, $b) use ($orderBy) {
                $key = $orderBy[0];
                $val = 0;
                if ($a[$key] < $b[$key]) $val = -1;
                else if ($a[$key] > $b[$key]) $val = 1;

                if ($orderBy[1] === 'desc') $val *= -1;
                return $val;
            });

            if ($this->skip || $this->take) {
                $records = array_slice($records, $this->skip, $this->take);
            }

            $this->arrayIterator = new ArrayIterator($records);
        } catch (InvalidArgumentException $e) {
            $this->arrayIterator = new ArrayIterator([]);
        }
    }

    /**
     * @return string
     */
    private function getModelDirectory(): string
    {
        $type = $this->model->getType();
        $path = absPath('content/' . $type . '/');

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
            ->in(absPath('models'));

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
        if (!$this->arrayIterator) $this->initIterator();
        return $this->arrayIterator->current();
    }

    /**
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function next()
    {
        if (!$this->arrayIterator) $this->initIterator();
        $this->arrayIterator->next();
    }

    /**
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     * @since 5.0.0
     */
    public function key()
    {
        return $this->arrayIterator->current()['id'];
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
        return $this->arrayIterator->valid();
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
        if ($this->arrayIterator) {
            $this->arrayIterator->rewind();
        } else {
            $this->initIterator();
        }
    }
}
