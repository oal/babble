<?php

namespace Babble;

use Babble\Exceptions\RecordNotFoundException;
use Babble\Models\Model;
use Babble\Models\Record;

class Template
{
    private $path;
    private $templatePath;

    public function __construct(Path $path, string $templatePath)
    {
        $this->path = new Path($path);
        $this->path->bindRoute($templatePath);
        $this->templatePath = $templatePath;
    }

    private function getModelName()
    {
        $modelTemplate = explode('/', $this->templatePath);
        $modelTemplate = end($modelTemplate);
        if (!ctype_upper($modelTemplate[0])) return null;

        // .twig, plus it might have "amp" or some other extension before .twig
        $extensionLength = strlen($this->path->getExtension());
        if ($extensionLength) $extensionLength += 1; // +1 to include the first "." if extension is set.

        $extensionLength += strlen('.twig');
        $modelName = substr($modelTemplate, 0, strlen($modelTemplate) - $extensionLength);

        // A model name can't contain ".".
        if (strpos($modelName, '.') !== false) return null;

        return $modelName;
    }

    public function hasModel(): bool
    {
        return !!$this->getModelName();
    }

    public function getModel()
    {
        $modelName = $this->getModelName();
        if (!$modelName) return null;

        $model = new Model($modelName);
        return $model;
    }

    public function getRecord()
    {
        $model = $this->getModel();
        if (!$model) return null;

        $numSlashes = count(explode('/', trim($this->templatePath, '/')));
        $id = implode('/', array_slice(explode('/', $this->path->getWithoutExtension()), $numSlashes));

        try {
            $record = Record::fromDisk($model, $id);
            return $record;
        } catch (RecordNotFoundException $e) {
            return null;
        }
    }

    public function getBoundPath()
    {
        return $this->path;
    }

    public function getTemplatePath()
    {
        return $this->templatePath;
    }
}

class TemplateLocator
{
    /**
     * @var Path
     */
    private $path;

    public function __construct(Path $path)
    {
        $this->path = $path;
    }

    private function explodePath(): array
    {
        return explode('/', trim($this->path, '/'));
    }

    public function next()
    {
        $pathParts = $this->explodePath();
        $globParts = [];
        foreach ($pathParts as $part) {
            if (strlen($part) === 0) $part = 'index';
            $globParts[] = '{[$]*,' . $part . '}';
        }

        $extension = '.twig';
        $userExtension = $this->path->getExtension();
        if ($userExtension) {
            $extension = '.' . $userExtension . $extension;
        }

        $varDirFinder = implode('/', $globParts) . $extension;
        $absVarDirFinder = absPath('templates/' . $varDirFinder);
        $globMatches = glob($absVarDirFinder, GLOB_BRACE);

        // Strip absolute prefix and yield.
        $absPathLength = strlen(absPath('templates'));

        // Templates with exact matches and variables.
        foreach ($globMatches as $globMatch) {
            $templatePath = substr($globMatch, $absPathLength);
            yield new Template($this->path, $templatePath);
        }

        // If no exact matches were found, look for model files.
        for ($i = count($globParts) - 1; $i >= 0; $i--) {
            // Start removing dirs from the back and substitute model names.
            $subGlob = array_slice($globParts, 0, $i);
            $modelFinder = ltrim(implode('/', $subGlob) . '/[A-Z]*' . $extension, '/');
            $absModelFinder = absPath('templates/' . $modelFinder);

            // Yield matches.
            $globMatches = glob($absModelFinder, GLOB_BRACE);
            foreach ($globMatches as $globMatch) {
                $templatePath = substr($globMatch, $absPathLength);
                $template = new Template($this->path, $templatePath);

                // If we got this far, the entry must have a model, or it's an invalid match.
                if (!$template->hasModel()) continue;

                yield new Template($this->path, $templatePath);
            }
        }
    }
}