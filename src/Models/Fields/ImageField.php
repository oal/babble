<?php

namespace Babble\Models\Fields;

use Babble\Models\Record;
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
        if(!$data) return null;

        $image = new Image($this, $data);

        $cropWidth = $this->getOption('width') ?? 0;
        $cropHeight = $this->getOption('height') ?? 0;

        $croppedURL = $image->crop($cropWidth, $cropHeight);

        $data['url'] = $croppedURL;
        return $data;
    }

    public function getView($data)
    {
        if(!$data) return '';
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
        $cropWidth = $this->field->getOption('width') ?? 0;
        $cropHeight = $this->field->getOption('height') ?? 0;
        return $this->crop($cropWidth, $cropHeight);
    }


    public function crop(int $width, int $height)
    {
        // Return original image if no cropping is specified.
        if ($width == 0 && $height == 0) {
            return '/uploads/' . $this->filename;
        }

        list($cropWidth, $cropHeight, $width, $height) = $this->getCropDimensions($width, $height);
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

        // TODO: This may squash the image. Use correct aspect ratio.
        $size = $image->getSize();
        if($cropWidth === 0) $cropWidth = $size->getWidth();
        if($cropHeight === 0) $cropHeight = $size->getHeight();

        // TODO: Support rotation and zooming from the Cropper JS component.
        $image
            ->crop(new Point($this->crop['x'], $this->crop['y']), new Box($cropWidth, $cropHeight))
            ->resize(new Box($width, $height))
            ->save($absolutePath);

        return $url;
    }

    private function getCroppedFilename($cropWidth, $cropHeight)
    {
        $baseName = pathinfo($this->filename, PATHINFO_FILENAME);
        $ext = pathinfo($this->filename, PATHINFO_EXTENSION);

        $cropFrom = intval($this->crop['x']) . '-' . intval($this->crop['y']);
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
     * @param int $width
     * @param int $height
     * @return array
     */
    private function getCropDimensions(int $width, int $height): array
    {
        $cropWidth = $this->crop['width'] ?? 0;
        $cropHeight = $this->crop['height'] ?? 0;

        // Recalculate crop dimensions and aspect ratio when exact size is set.
        if ($width && $height) {
            $ratio = $width / $height;
            if ($width > $height) {
                $cropHeight = $cropWidth / $ratio;
            } else {
                $cropWidth = $cropHeight * $ratio;
            }
        } else {
            $ratio = $cropWidth / $cropHeight;
        }

        // If only one side is set, keep ratio but calculate the missing side.
        if (!$width) {
            $width = $height * $ratio;
            $cropWidth = $cropHeight * $ratio;
        } else if (!$height) {
            $height = $width / $ratio;
            $cropHeight = $cropWidth / $ratio;
        }

        return array($cropWidth, $cropHeight, $width, $height);
    }
}