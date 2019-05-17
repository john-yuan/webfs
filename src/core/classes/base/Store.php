<?php

class Store
{
    /**
     * @var string
     */
    private $filename;

    /**
     * @var array
     */
    private $store_cached = null;

    /**
     * @var boolean
     */
    private $locked = false;

    /**
     * @var FileReader
     */
    private $reader = null;

    /**
     * @var array
     */
    private static $locked_store_array = array();

    /**
     * Open a store
     *
     * @param string $name
     * @return Store
     */
    public static function open($name)
    {
        return new Store($name);
    }

    /**
     * Get data by name.
     *
     * @param string $name
     * @param mixed [$default=null]
     * @return mixed
     */
    public function get($name, $default = null)
    {
        if ($this->locked) {
            $store = &$this->store_cached;
        } else {
            $this->lockCheck();
            $reader = new FileReader($this->filename);
            $reader->rlock();
            $content = $reader->read();
            $reader->free();
            $store = self::decode($content);
        }

        if (!isset($store[$name]) || is_null($store[$name])) {
            return $default;
        } else {
            return $store[$name];
        }
    }

    /**
     * Set data by name.
     *
     * @param string $name
     * @param mixed $value
     */
    public function set($name, $value)
    {
        if ($this->locked) {
            $this->store_cached[$name] = $value;
        } else {
            $this->lockCheck();
            $reader = new FileReader($this->filename);
            $reader->wlock();
            $content = $reader->read();
            $store = self::decode($content);
            $store[$name] = $value;
            $content = self::encode($store);
            $reader->write($content);
            $reader->free();
        }
    }

    /**
     * Remove data by name.
     *
     * @param string $name
     */
    public function remove($name)
    {
        if ($this->locked) {
            unset($this->store_cached[$name]);
        } else {
            $this->lockCheck();
            $reader = new FileReader($this->filename);
            $reader->wlock();
            $content = $reader->read();
            $store = self::decode($content);
            unset($store[$name]);
            $content = self::encode($store);
            $reader->write($content);
            $reader->free();
        }
    }

    /**
     * Lock the store
     */
    public function lock()
    {
        if ($this->locked) {
            return;
        }

        $this->lockCheck();

        $this->locked = true;
        self::$locked_store_array[$this->filename] = true;

        $this->reader = new FileReader($this->filename);
        $this->reader->wlock();

        $content = $this->reader->read();
        $this->store_cached = self::decode($content);
    }

    /**
     * Unlock the store.
     */
    public function unlock()
    {
        if ($this->locked) {
            $content = self::encode($this->store_cached);

            $this->reader->write($content);
            $this->reader->free();
            $this->reader = null;

            $this->store_cached = null;
            $this->locked = false;

            unset(self::$locked_store_array[$this->filename]);
        }
    }

    /**
     * Get the filename of the store.
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Pirvate constructor.
     *
     * @param string $name
     */
    private function __construct($name)
    {
        if (!is_string($name) || !strlen($name)) {
            throw new Exception('Failed to open a store. The name of the store is required!');
        }

        $store_name = strval($name);

        if (FileReader::isAbsolutePath($store_name)) {
            $filename = $name . '.store.php';
        } else {
            $filename = config('default_storage_dir') . '/' . $store_name . '.store.php';
        }

        $diranme = dirname($filename);

        if (!is_dir($diranme)) {
            if (mkdir($diranme, 0755, true) === false) {
                throw new Exception("Failed to create the directory of the store: $filename");
            }
        }

        $this->filename = $filename;
    }

    /**
     * Unlock the file on destruct.
     */
    public function __destruct()
    {
        $this->unlock();
    }

    /**
     * Check whether the store file is locked somewhere else.
     */
    private function lockCheck()
    {
        if (isset(self::$locked_store_array[$this->filename])) {
            throw new Exception('The store file is locked somewhere else.');
        }
    }

    /**
     * Decode the data.
     *
     * @param string &$content
     * @return array
     */
    private static function decode(&$content)
    {
        if ($content === '') {
            return array();
        }

        $pos = strpos($content, '/*');

        if ($pos === false) {
            if (trim($content) === '') {
                return array();
            } else {
                throw new Exception('Failed to decode the store data. No comment start token found.');
            }
        }

        $json_str = substr($content, $pos + 2);

        if ($json_str === false) {
            throw new Exception('Failed to decode the store data. No JSON text available.');
        }

        $pos = strrpos($json_str, '*/');

        if ($json_str === false) {
            throw new Exception('Failed to decode the store data. No JSON text available.');
        }

        $json_str = substr($json_str, 0, $pos);

        $store = json_decode($json_str, true);

        if (!is_array($store)) {
            if (trim($json_str) === '') {
                $store = array();
            } else {
                throw new Exception('Failed to decode the store data. The root node of the JSON must be an array.');
            }
        }

        return $store;
    }

    /**
     * Encode the data.
     *
     * @param array $array
     * @return string
     */
    private static function encode(&$array)
    {
        if (defined('JSON_PRETTY_PRINT')) {
            if (defined('JSON_UNESCAPED_UNICODE')) {
                $json_str = json_encode($array, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            } else {
                $json_str = json_encode($array, JSON_PRETTY_PRINT);
            }
        } else {
            $json_str = json_encode($array);
        }

        if ($json_str === false) {
            throw new Exception('Failed to encode the data. Error on encode JSON.');
        }

        return "<?php \n\n/*" . $json_str . "*/\n";
    }
}
