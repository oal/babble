<?php

namespace Babble\Models\Fields;

use Babble\Models\Record;
use Imagine\Image\BoxInterface;
use Imagine\Imagick\Imagine;
use Imagine\Image\Box;
use Imagine\Image\Point;
use Symfony\Component\Filesystem\Filesystem;

class ImageField extends FileField
{
    public function validate(Record $record, $data)
    {
        $fs = new Filesystem();
        return $fs->exists(absPath('public/uploads/' . $data['filename']));
    }

    public function process(Record $record, $data)
    {
        if (!$data) return null;

        $image = new Image($this, $data);

        $croppedURL = $image->crop();

        $data['url'] = $croppedURL;
        return $data;
    }

    public function getView($data)
    {
        if (!$data) return '';
        return new Image($this, $data);
    }
}

class Image
{
    private $field;
    private $filename;
    private $crop;

    public function __construct(ImageField $field, array $data)
    {
        $this->field = $field;
        $this->filename = $data['filename'];
        $this->crop = $data['crop'] ?? null;
    }

    public function __toString()
    {
        return $this->crop();
    }

    public function crop($width = null, $height = null)
    {
        if (!$width) $width = $this->field->getOption('width') ?? $this->crop['width'] ?? null;
        if (!$height) $height = $this->field->getOption('height') ?? $this->crop['height'] ?? null;

        if (!$width && !$height) return '/uploads/' . $this->filename;

        $url = $this->getCroppedURL($width, $height);

        // Return URL if cropped and cached file already exists.
        $absolutePath = absPath('public' . $url);
        $fs = new Filesystem();
        if ($fs->exists($absolutePath)) return $url;

        // Create cache dir location if it doesn't exist.
        $relativeDir = pathinfo($absolutePath, PATHINFO_DIRNAME);
        if (!$fs->exists($relativeDir)) $fs->mkdir($relativeDir);

        // Crop and save
        $imagine = new Imagine();
        $sourceFilename = absPath('public/uploads/' . $this->filename);
        if (!$fs->exists($sourceFilename)) return '';
        $image = $imagine->open($sourceFilename);

        // Where to crop from, and what portion of the image to crop.
        $size = $image->getSize();
        list($cropWidth, $cropHeight, $cropX, $cropY) = $this->getCropData($width, $height, $size);

        // TODO: Support rotation and zooming from the Cropper JS component.
        $image
            ->crop(new Point($cropX, $cropY), new Box($cropWidth, $cropHeight))
            ->resize(new Box($width, $height))
            ->save($absolutePath);

        return $url;
    }

    private function getCroppedFilename($cropWidth, $cropHeight)
    {
        $baseName = pathinfo($this->filename, PATHINFO_FILENAME);
        $ext = pathinfo($this->filename, PATHINFO_EXTENSION);

        if ($this->crop) {
            $cropFrom = intval($this->crop['x']) . '-' . intval($this->crop['y']);
        } else {
            $cropFrom = 'auto';
        }

        $size = intval($cropWidth) . 'x' . intval($cropHeight);

        $targetFilename = $baseName . '-' . $cropFrom . '-' . $size . '.' . $ext;
        $dirName = pathinfo($this->filename, PATHINFO_DIRNAME);

        if ($dirName === '.') return $targetFilename;
        return $dirName . '/' . $targetFilename;
    }

    private function getCroppedURL($cropWidth, $cropHeight)
    {
        return '/uploads/_cache/' . $this->getCroppedFilename($cropWidth, $cropHeight);
    }

    /**
     * @param $width
     * @param $height
     * @param BoxInterface $size
     * @return array
     */
    private function getCropData($width, $height, BoxInterface $size): array
    {
        if ($this->crop) {
            // Use stored crop data.
            $cropWidth = $this->crop['width'];
            $cropHeight = $this->crop['height'];
            $cropX = $this->crop['x'];
            $cropY = $this->crop['y'];
        } else {
            // Calculate crop data.
            $ratio = $width / $height;
            $side = min($size->getWidth(), $size->getHeight());

            if ($ratio > 1) {
                $cropWidth = $side;
                $cropHeight = $side / $ratio;
            } else {
                $cropWidth = $side * $ratio;
                $cropHeight = $side;
            }

            $cropX = $size->getWidth() / 2 - $cropWidth / 2;
            $cropY = $size->getHeight() / 2 - $cropHeight / 2;
        }

        return array($cropWidth, $cropHeight, $cropX, $cropY);
    }
}