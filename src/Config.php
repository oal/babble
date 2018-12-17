<?php

namespace Babble;


use Symfony\Component\Yaml\Yaml;

class Config
{
    // Defaults.
    private $config = [
        'cache' => false,
        'debug' => false,
        'api' => false
    ];

    public function __construct(string $currentHost)
    {
        $config = Yaml::parse(file_get_contents(absPath('config.yaml')));
        $this->initForHost($currentHost, $config);
    }

    private function initForHost(string $host, array $config)
    {
        // Overwrite global configs if host config is set for current host.
        if (array_key_exists('host', $config) && is_array($config['host']) && array_key_exists($host, $config['host'])) {
            $configHostData = $config['host'][$host];
            if ($configHostData) {
                foreach ($configHostData as $key => $value) {
                    $config[$key] = $value;
                }
            }
            unset($config['host']);
        }

        // Set config.
        $this->config = $config;
    }

    public function get($key)
    {
        return $this->config[$key];
    }

    public function toArray()
    {
        return $this->config;
    }
}
