<?php

namespace Babble\Models\Fields;


use ArrayAccess;
use Babble\Models\Block;

class ListField extends Field
{
    public function __construct(Block $blockOrModel, $key, array $data)
    {
        $data['options']['blocks'] = $this->readBlocks($data['options']['blocks']);
        parent::__construct($blockOrModel, $key, $data);
    }

    private function readBlocks(array $blockNames)
    {
        $blocks = [];

        foreach ($blockNames as $blockName) {
            $blocks[] = new Block($blockName);
        }

        return $blocks;
    }

    public function getView($blocks)
    {
        if (!is_array($blocks)) return [];

        return array_map(function ($blockData) {
            return new BlockView($blockData);
        }, $blocks);
    }
}

class BlockView implements ArrayAccess
{
    private $type;
    private $data;

    public function __construct(array $blockData)
    {
        $this->type = $blockData['type'];
        $this->data = $blockData['value'];
    }

    public function __toString()
    {
        return $this->getType();
    }


    public function getType(): string
    {
        return $this->type;
    }

    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->data);
    }

    public function offsetGet($offset)
    {
        return $this->data[$offset];
    }

    public function offsetSet($offset, $value)
    {
    }

    public function offsetUnset($offset)
    {
    }
}