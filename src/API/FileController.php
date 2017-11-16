<?php

namespace Babble\API;

use JsonSerializable;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
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
            'type' => mime_content_type($file->getRealPath()),
            'mtime' => $file->getMTime(),
            'size' => $file->getSize(),
        ];
    }
}

class FileController extends Controller
{
    public function create(Request $request, $path)
    {
        $files = $request->files->all();
        if (count($files) > 0) {
            $targetDir = absPath('public/uploads/' . $path);
            try {
                foreach ($files as $file) {
                    // Check if name already exists and rename.
                    $file->move($targetDir, $file->getClientOriginalName());
                }
            } catch (FileException $e) {
                return new JsonResponse([
                    'error' => 'Error saving file: ' . $e->getMessage()
                ], 400);
            }
        } else {
            $data = json_decode($request->getContent(), true);
            $dirName = $data['name'] ?? null;

            if (!$this->isValidDirectoryName($dirName)) {
                return new JsonResponse([
                    'error' => 'Invalid directory name.'
                ], 400);
            };

            $fs = new Filesystem();
            $fs->mkdir(absPath('public/uploads/' . $path . '/' . $dirName));
        }

        return new JsonResponse([
            'message' => 'Upload completed.'
        ]);
    }

    public function read(Request $request, $path)
    {
        if (strpos($path, '..') !== false) {
            return new JsonResponse([
                'error' => 'Invalid location provided.'
            ], 400);
        };


        $finder = new Finder();

        if (!$path) $path = '';
        $files = $finder
            ->notName('_*')
            ->in(absPath('public/uploads/' . $path))
            ->sortByType()
            ->depth(0);

        $filenames = [];
        foreach ($files as $file) {
            $filenames[] = new File($file);
        }

        return new JsonResponse($filenames);
    }

    public function update(Request $request, $path)
    {
        $data = json_decode($request->getContent(), true);
        $dirName = $data['name'] ?? null;

        if (!$this->isValidDirectoryName($dirName)) {
            return new JsonResponse([
                'error' => 'Invalid directory name.'
            ], 400);
        };

        $newPath = explode('/', $path);
        array_pop($newPath);
        $newPath = implode('/', $newPath) . '/' . $dirName;

        $fs = new Filesystem();
        $fs->rename(absPath('public/uploads/' . $path), absPath('public/uploads/' . $newPath));

        return new JsonResponse([
            'message' => 'The directory has been successfully renamed.'
        ]);
    }

    public function delete(Request $request, $path)
    {
        if (strpos($path, '..') !== false) {
            return new JsonResponse([
                'error' => 'Invalid file path'
            ], 400);
        }

        $fs = new Filesystem();
        $fs->remove(absPath('public/uploads/' . $path));

        return new JsonResponse([
            'message' => 'The file has been removed.'
        ]);
    }

    private function isValidDirectoryName(string $dirName): bool
    {
        if (!$dirName) return false;
        if (preg_match('/^[a-zA-Z0-9-_]+$/', $dirName) !== 1) return false;
        return true;
    }
}