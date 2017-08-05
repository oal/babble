<?php

namespace Babble\Models\Fields;


use ArrayAccess;
use Babble\Models\Block;
use Babble\Models\Record;

class ListField extends Field
{
    private $blocks;

    public function __construct(Block $blockOrModel, $key, array $data)
    {
        $this->readBlocks($data['options']['blocks']);
        $data['options']['blocks'] = array_values($this->blocks);

        parent::__construct($blockOrModel, $key, $data);
    }

    private function readBlocks(array $blockNames)
    {
        $blocks = [];

        foreach ($blockNames as $blockName) {
            $blocks[$blockName] = new Block($blockName);
        }

        $this->blocks = $blocks;
    }

    public function process(Record $record, $data)
    {
        $processedData = [];
        foreach ($data as $blockInstanceData) {
            $type = $blockInstanceData['type'];
            $block = $this->blocks[$type];
            $values = $blockInstanceData['value'];

            $processedValues = [];
            foreach ($values as $fieldKey => $value) {
                $processedValues[$fieldKey] = $block->getField($fieldKey)->process($record, $value);
            }
            $processedData[] = [
                'type' => $type,
                'value' => $processedValues
            ];
        }

        return $processedData;
    }


    public function getView($blockDatas)
    {
        if (!is_array($blockDatas)) return [];

        $blocks = $this->blocks;
        return array_map(function ($blockData) use (&$blocks) {
            return new BlockView($blocks[$blockData['type']], $blockData);
        }, $blockDatas);
    }
}

class BlockView implements ArrayAccess
{
    private $type;
    private $data;
    private $block;

    public function __construct(Block $block, array $blockData)
    {
        $this->block = $block;
        $this->type = $blockData['type'];
        $this->data = $blockData['value'];
    }

    public function __toString(): string
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
        return $this->block->getField($offset)->getView($this->data[$offset]);
    }

    public function offsetSet($offset, $value)
    {
    }

    public function offsetUnset($offset)
    {
    }
}