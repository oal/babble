<?php

namespace Babble\Models\Fields;


use ArrayAccess;
use Babble\Models\BaseModel;
use Babble\Models\Block;
use Babble\Models\Model;
use Babble\Models\Record;

class ListField extends Field
{
    private $blocks;

    protected function initOptions(array $data)
    {
        parent::initOptions($data);
    }

    public function getBlocks()
    {
        if($this->blocks) return $this->blocks;

        $blocks = [];
        $blockNames = $this->getOption('blocks');
        foreach ($blockNames as $blockName) {
            $blocks[$blockName] = new Block($this->getModel(), $blockName);
        }

        $this->blocks = $blocks;
        return $blocks;
    }

    public function getBlock($type)
    {
        return $this->getBlocks()[$type];
    }

    public function process(Record $record, $data)
    {
        if (!$data) return [];

        $processedData = [];
        foreach ($data as $blockInstanceData) {
            $type = $blockInstanceData['type'];
            $block = $this->getBlock($type);
            $values = $blockInstanceData['value'];

            $processedValues = [];
            foreach ($values as $fieldKey => $value) {
                $field = $block->getField($fieldKey);
                $processedValues[$fieldKey] = $field->process($record, $value);
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

        $blocks = $this->getBlocks();
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