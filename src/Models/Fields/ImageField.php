<?php

namespace Babble\Models\Fields;

use Babble\Models\Record;
use Intervention\Image\Exception\NotReadableException;
use Symfony\Component\Filesystem\Filesystem;
use Intervention\Image\ImageManager;

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
    private $cropData;

    public function __construct(ImageField $field, array $data)
    {
        $this->field = $field;
        $this->filename = $data['filename'];
        $this->cropData = $data['crop'] ?? null;
    }

    public function __toString()
    {
        return $this->crop();
    }

    public function crop($width = null, $height = null)
    {
        if (!$width && !$height) {
            $width = $this->field->getOption('width') ?? null;
            $height = $this->field->getOption('height') ?? null;
        }

        $manager = new ImageManager(array('driver' => 'imagick'));
        $path = '/uploads/' . $this->filename;
        if (!$width && !$height && !$this->cropData) return $path;

        $url = $this->getCroppedURL($width, $height);

        // Return URL if cropped and cached file already exists.
        $absolutePath = absPath('public' . $url);
        $fs = new Filesystem();
        if ($fs->exists($absolutePath)) return $url;

        // Create cache dir location if it doesn't exist.
        $relativeDir = pathinfo($absolutePath, PATHINFO_DIRNAME);
        if (!$fs->exists($relativeDir)) $fs->mkdir($relativeDir);

        $sourceFilename = absPath('public' . $path);
        try {
            $img = $manager->make($sourceFilename);
        } catch (NotReadableException $e) {
            return '';
        }

        if ($width == 0) $width = null;
        if ($height == 0) $height = null;

        $absolutePath = absPath('public' . $url);
        if ($this->cropData) {
            $img->crop(
                (int)$this->cropData['width'], (int)$this->cropData['height'],
                (int)$this->cropData['x'], (int)$this->cropData['y']
            );
        }

        if ($width && $height) {
            $img->fit($width, $height);
        } else if ($width) {
            $img->resize($width, 0);
        } else if ($height) {
            $img->resize(0, $height);
        }

        $img->save($absolutePath);

        return $url;
    }

    private function getCroppedFilename($cropWidth, $cropHeight)
    {
        $baseName = pathinfo($this->filename, PATHINFO_FILENAME);
        $ext = pathinfo($this->filename, PATHINFO_EXTENSION);

        if ($this->cropData) {
            $cropFrom = intval($this->cropData['x']) . '-' . intval($this->cropData['y']);
            $cropSize = intval($this->cropData['width']) . 'x' . intval($this->cropData['height']);
        } else {
            $cropFrom = 'auto';
            $cropSize = 'auto';
        }

        $width = intval($cropWidth);
        if (!$width) $width = '';

        $height = intval($cropHeight);
        if (!$height) $height = '';

        $size = $width . 'x' . $height;

        $targetFilename = $baseName . '-' . $cropFrom . '-' . $cropSize . '-' . $size . '.' . $ext;
        $dirName = pathinfo($this->filename, PATHINFO_DIRNAME);

        if ($dirName === '.') return $targetFilename;
        return $dirName . '/' . $targetFilename;
    }

    private function getCroppedURL($cropWidth, $cropHeight)
    {
        return '/uploads/_cache/' . $this->getCroppedFilename($cropWidth, $cropHeight);
    }
}