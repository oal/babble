<?php

namespace Babble\Content;


use ArrayIterator;
use Iterator;

class Paginator implements Iterator
{
    private $arrayIterator;

    private $perPage;
    private $currentPage;
    private $total = 0;

    public function __construct(ContentLoader $loader, $perPage, $currentPage)
    {
        $this->perPage = $perPage;
        $this->currentPage = $currentPage;

        $this->initIterator($loader);
    }

    private function initIterator(ContentLoader $loader)
    {
        $records = iterator_to_array($loader);
        $this->total = count($records);

        $fromIndex = ($this->currentPage - 1) * $this->perPage;
        $this->arrayIterator = new ArrayIterator(
            array_slice($records, $fromIndex, $this->perPage)
        );
    }

    /**
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     * @since 5.0.0
     */
    public function current()
    {
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

    public function total(): int
    {
        return $this->total;
    }

    public function perPage()
    {
        return $this->perPage;
    }

    public function currentPage()
    {
        return $this->currentPage;
    }

    public function hasPrevious(): bool
    {
        return $this->currentPage > 1;
    }

    public function hasNext(): bool
    {
        return $this->currentPage < $this->lastPage();
    }

    public function lastPage(): int
    {
        return ceil($this->total / $this->perPage());
    }
}