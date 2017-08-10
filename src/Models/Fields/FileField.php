<?php

namespace Babble\Models\Fields;


use Babble\Models\Record;
use Symfony\Component\Filesystem\Filesystem;

class FileField extends Field
{
    public function validate(Record $record, $data)
    {
        $fs = new Filesystem();
        return $fs->exists(absPath('public/uploads/' . $data));
    }

    public function getView($data)
    {
        return '/uploads/' . $data['filename'];
    }
}