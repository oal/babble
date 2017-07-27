<?php

namespace Babble\Models\Fields;

use Babble\Models\Record;
use Imagine\Imagick\Imagine;
use Imagine\Image\Box;
use Imagine\Image\Point;
use Symfony\Component\Filesystem\Filesystem;

class ImageField extends Field
{
    public function validate(Record $record, $data)
    {
        $fs = new Filesystem();
        return $fs->exists('./uploads/' . $data['filename']);
    }

    public function process(Record $record, $data)
    {
        // Directory part of the URL.
        $targetDir = '/uploads/_cache/' . $this->getModel()->getType() . '/' . $record->getValue('id');

        $fs = new Filesystem();
        $relativeTargetDir = '.' . $targetDir; // Make relative for file system access.
        if (!$fs->exists($relativeTargetDir)) $fs->mkdir($relativeTargetDir);

        // Filename = ID + file extension.
        $ext = pathinfo($data['filename'], PATHINFO_EXTENSION);
        $targetFilename = $this->getKey() . '.' . $ext;

        $targetFile = $relativeTargetDir . '/' . $targetFilename;
        $this->cropAndSave($data['filename'], $data['crop'], $targetFile);

        // Return data array with URL to cropped version.
        $url = $targetDir . '/' . $targetFilename;
        $data['url'] = $url;
        return $data;
    }

    private function cropAndSave(string $filename, array $crop, string $targetFile)
    {
        $imagine = new Imagine();
        $image = $imagine->open('./uploads/' . $filename);

        // TODO: Support rotation and zooming from the Cropper JS component.
        $image
            ->crop(new Point($crop['x'], $crop['y']), new Box($crop['width'], $crop['height']))
            ->resize(new Box($this->getOption('width'), $this->getOption('height')))
            ->save($targetFile);
    }

    public function getView($data)
    {
        if (!array_key_exists('url', $data)) return '';
        return $data['url'];
    }
}
