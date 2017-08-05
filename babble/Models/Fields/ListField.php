<?php

namespace Babble\Models\Fields;


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
        error_log(json_encode($blocks));

        return $blocks;
    }

}