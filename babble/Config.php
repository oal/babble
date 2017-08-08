<?php

namespace Babble;


use Symfony\Component\Yaml\Yaml;

class Config
{
    private $config;

    public function __construct(string $currentHost)
    {
        $config = Yaml::parse(file_get_contents(absPath('content/config.yaml')));
        $this->initForHost($currentHost, $config);
    }

    private function initForHost(string $host, array $config)
    {
        // Overwrite global configs if host config is set for current host.
        $configHostData = $config['host'][$host] ?? [];
        if ($configHostData) {
            foreach ($configHostData as $key => $value) {
                $config[$key] = $value;
            }
        }
        unset($config['host']);

        // Set config.
        $this->config = $config;
    }

    public function get($key)
    {
        return $this->config[$key];
    }
}
