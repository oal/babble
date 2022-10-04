<?php

namespace Babble\Content;

use Babble\Models\Model;
use Babble\Path;
use Babble\TemplateRenderer;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class StaticSiteGenerator
{
    private $renderer;
    private $processedPaths = [];
    private $output;
    /**
     * @var string
     */
    private $baseUrl;

    public function __construct(OutputInterface $output, string $baseUrl)
    {
        $dispatcher = new EventDispatcher();
        $this->renderer = new TemplateRenderer($dispatcher);
        $this->output = $output;
        $this->baseUrl = $baseUrl;
    }


    public function build()
    {
        $fs = new Filesystem();

        // Remove all previous build files.
        if ($fs->exists(absPath('build'))) {
            $finder = new Finder();
            $fs->remove($finder->in(absPath('build')));
        }

        // Find all pages to be built.
        $finder = new Finder();
        $files = $finder->in(absPath('templates'))->notName('/^[_$].*/')->notPath('/^[_$].*/');
        foreach ($files as $file) {
            if ($file->isDir()) continue;
            if (strpos($file, '$')) continue; // Not sure why $ isn't filtered out by the Symfony file matcher.

            $filename = $file->getFilename();
            $relativePath = $this->getRelativePath($file);

            // Build pages.
            $firstChar = substr($filename, 0, 1);
            if ($firstChar === strtoupper($firstChar)) {
                $this->renderRecord($relativePath);
            } else {
                $this->renderPage($relativePath);
            }
        }

        // Create symlinks to static assets and uploads.
        $fs->symlink(absPath('public/static'), absPath('build/static'));
        $fs->symlink(absPath('public/uploads'), absPath('build/uploads'));

        $fs->dumpFile(absPath('build/sitemap.xml'), $this->buildSitemap());
    }

    private function renderPage(string $relativePath)
    {
        $extLength = strlen(pathinfo($relativePath, PATHINFO_EXTENSION));
        $path = '/' . substr($relativePath, 0, strlen($relativePath) - $extLength - 1); // -1 for the slash.
        $pathObject = new Path($path);
        $this->render($pathObject);
    }

    private function renderRecord($relativePath)
    {
        $directory = pathinfo($relativePath, PATHINFO_DIRNAME);
        if ($directory === '.') $directory = '';
        else $directory = '/' . $directory;

        $extension = pathinfo(substr($relativePath, 0, -5), PATHINFO_EXTENSION);
        $modelName = pathinfo($relativePath, PATHINFO_FILENAME);
        if ($extension) {
            $extensionLength = strlen($extension) + 1;
            $modelName = substr($modelName, 0, -$extensionLength);
        }

        $model = new Model($modelName);
        $loader = new ContentLoader($model);
        $loader = $loader->withChildren();
        foreach ($loader as $record) {
            $path = $directory . '/' . $record['id'];
            if ($extension) $path .= '.' . $extension;
            $pathObject = new Path($path);
            $this->render($pathObject);
        }
    }

    private function render(Path $pathObject)
    {
        $path = '' . $pathObject;

        // Skip already rendered pages.
        if (array_key_exists($path, $this->processedPaths)) {
            $this->processedPaths[$path] += 1;
            return;
        }

        // Mark current page as processed / rendered.
        $this->processedPaths[$path] = 1;

        // Attempt to render Log error if rendering fails (404 or other error).
        $html = $this->renderer->render($pathObject);
        if ($html === null) {
            $this->log($path, 'error');
            return;
        }

        // Save and log success.
        $this->save($pathObject, $html);
        $this->log($pathObject->clean());

        // Look for links on the rendered page, and render them.
        $crawler = new Crawler($html);
        foreach ($crawler->filterXPath('//*[starts-with(@href, "/")]') as $domElement) {
            $href = $domElement->getAttribute('href');
            $url = parse_url($href);

            // Don't crawl other domains.
            if (isset($url['host'])) {
                continue;
            }

            // Drop fragment etc.
            $path = rtrim($url['path'], '/');

            // Use index if no filename is set (also handles /.amp and similar cases).
            $filename = pathinfo($path, PATHINFO_FILENAME);
            if (!$filename) {
                $path = '/index';
            }

            // Skip absolute paths without protocol, static directory and uploads directory.
            if (str_starts_with($path, '/static/') || str_starts_with($path, '/uploads/')) {
                continue;
            }

            // Render discovered path.
            $discoveredPath = new Path($path);
            $this->render($discoveredPath);
        }
    }

    private function save(Path $path, $html)
    {
        $fs = new Filesystem();

        if ($path->getExtension()) {
            $targetFile = $path;
        } else if ($path->getFilename() === 'index') {
            $targetFile = $path . '.html';
        } else {
            $targetFile = $path . '/index.html';
        }

        $fs->dumpFile(absPath('build/' . $targetFile), $html);
    }

    private function getRelativePath(SplFileInfo $file): string
    {
        $filename = $file->getFilename();
        $dir = explode('/templates', $file->getPath())[1];

        if ($dir) $relativePath = $dir . '/' . $filename;
        else $relativePath = $filename;

        return $relativePath;
    }

    private function log($message, $type = "info")
    {
        if (!$this->output) return;
        $this->output->write("<$type>$message</$type>\n");
    }

    private function buildSitemap(): string
    {
        $urls = [];
        foreach (array_keys($this->processedPaths) as $path) {
            $path = new Path($path);
            if ($path->getFilename() === 'index') $path = $path->getDirectory();

            $url = $this->baseUrl . $path . '/';
            $urls[] = "<url><loc>$url</loc></url>";
        }

        return '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . implode('', $urls) . '</urlset>';
    }
}