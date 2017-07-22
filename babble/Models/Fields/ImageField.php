<?php

namespace Babble\Models\Fields;

use Imagine\Imagick\Imagine;
use Imagine\Image\Box;
use Imagine\Image\Point;
use Symfony\Component\Filesystem\Filesystem;

class ImageField extends Field
{
    public function validate($data)
    {
        $fs = new Filesystem();
        return $fs->exists('./uploads/' . $data['filename']);
    }

    public function save(string $fieldKey, $data)
    {
        $imagine = new Imagine();
        $image = $imagine->open('./uploads/' . $data['filename']);

        $targetDir = './uploads/_cache/' . $this->getModel() . '/' . $fieldKey;

        $fs = new Filesystem();
        if (!$fs->exists($targetDir)) $fs->mkdir($targetDir);

        $ext = pathinfo($data['filename'], PATHINFO_EXTENSION);
        $targetFile = $targetDir . '/' . $this->getKey() . '.' . $ext;

        // TODO: Support rotation and zooming from the Cropper JS component.
        $crop = $data['crop'];
        $image
            ->crop(new Point($crop['x'], $crop['y']), new Box($crop['width'], $crop['height']))
            ->resize(new Box($this->getOption('width'), $this->getOption('height')))
            ->save($targetFile);
    }
}