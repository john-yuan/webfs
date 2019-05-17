<?php

class Config
{
    /**
     * @var array
     */
    private $config;

    /**
     * @var Config
     */
    private static $instance = null;

    /**
     * Get the instance
     *
     * @param array [$config=null]
     * @return Config
     */
    public static function getInstance($config = null)
    {
        if (is_null(self::$instance)) {
            if (is_null($config)) {
                throw new Exception('config is required on initialze the config.');
            }
            self::$instance = new Config($config);
        } else if (!is_null($config)) {
            throw new Exception('Failed to initialze the config, it has already been initialzed.');
        }
        return self::$instance;
    }

    /**
     * @param array $config
     */
    private function __construct($config)
    {
        if (!is_array($config)) {
            throw new Exception('config must be an array.');
        }
        $this->config = $config;
    }

    /**
     * Read config value by key.
     *
     * @param string $key
     * @param mixed [$default=null]
     * @return mixed
     */
    public function get($key, $default = null)
    {
        $key = strval($key);
        $key = preg_replace('/\\[([0-9]+)\\]/', '.$1', $key);
        $keys = explode('.', $key);
        $config = $this->config;

        $unset = false;

        foreach ($keys as $key) {
            if (is_array($config) && isset($config[$key])) {
                $config = $config[$key];
            } else {
                $unset = true;
                break;
            }
        }

        if ($unset || is_null($config)) {
            return $default;
        } else {
            return $config;
        }
    }
}
