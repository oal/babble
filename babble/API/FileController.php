<?php

namespace Babble\API;

use JsonSerializable;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\Request;

class File implements JsonSerializable
{
    private $file;

    public function __construct(SplFileInfo $file)
    {
        $this->file = $file;
    }

    function jsonSerialize()
    {
        $file = $this->file;
        return [
            'name' => $file->getFilename(),
            'type' => $file->getType(),
            'mtime' => $file->getMTime(),
            'size' => $file->getSize(),
        ];
    }
}

class FileController extends Controller
{
    public function create(Request $request, $path)
    {
        var_dump($path);
        $files = $request->files->all();
        var_export($files);
        if (count($files) > 0) {
            $targetDir = './uploads/' . $path;
            foreach ($files as $file) {
                // Check if name already exists and rename.
                $file->move($targetDir, $file->getClientOriginalName());
            }
        } else {
            $data = json_decode($request->getContent(), true);
            $dirName = $data['directory'] ?? null;

            // Validate names.
            if (!$dirName) return;
            if (preg_match('/^[a-zA-Z0-9-_]+$/', $dirName) !== 1) return;

            $fs = new Filesystem();
            $fs->mkdir('./uploads/' . $path . '/' . $dirName);
        }
    }

    public function read(Request $request, $path)
    {
        if (strpos($path, '..') !== false) return;

        $finder = new Finder();

        if (!$path) $path = '';
        $files = $finder
            ->notName('_*')
            ->in('../public/uploads/' . $path)
            ->sortByType()
            ->depth(0);

        $filenames = [];
        foreach ($files as $file) {
            $filenames[] = new File($file);
        }

        error_log('ok');

        return json_encode($filenames);
    }

    public function update(Request $request, $path)
    {
        $data = json_decode($request->getContent(), true);
        $dirName = $data['directory'] ?? null;

        // Validate names.
        if (!$dirName) return;
        if (preg_match('/^[a-zA-Z0-9-_]+$/', $dirName) !== 1) return;

        $newPath = explode('/', $path);
        array_pop($newPath);
        $newPath = implode('/', $newPath) . '/' . $dirName;

        $fs = new Filesystem();
        $fs->rename('./uploads/' . $path, './uploads/' . $newPath);
    }

    public function delete(Request $request, $id)
    {
        parent::delete($request, $id); // TODO: Change the autogenerated stub
    }

}