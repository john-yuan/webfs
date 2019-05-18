<?php

class FileReader
{
    /**
     * @var string
     */
    private $filename;

    /**
     * @var resource
     */
    private $handle;

    /**
     * @var string
     */
    private $content;

    /**
     * @var int
     */
    private $lock;

    /**
     * @var boolean
     */
    private $touched;

    /**
     * @var array
     */
    private static $locked_file_list = array();

    /**
     * Create an instance of `FileReader` and open the given file.
     *
     * @param string $filename The name of the file to open.
     */
    public function __construct($filename)
    {
        $filename = self::fullpath($filename);

        if (isset(self::$locked_file_list[$filename])) {
            throw new Exception("Failed to open the file. It is locked somewhere else. ($this->filename)");
        }

        self::$locked_file_list[$filename] = true;

        $this->filename = $filename;
        $this->handle = null;
        $this->content = null;
        $this->touched = false;
        $this->lock = LOCK_UN;
    }

    /**
     * Release the file locked on destruct.
     */
    public function __destruct()
    {
        $this->free();
    }

    /**
     * Add a read lock to this file.
     */
    public function rlock()
    {
        if (is_null($this->filename)) {
            throw new Exception("Failed to add read lock. The file is released.");
        }

        if ($this->lock === LOCK_EX) {
            throw new Exception("Failed to add read lock. The file is lockded. ($this->filename)");
        }

        if ($this->lock === LOCK_SH) {
            return;
        }

        if (!file_exists($this->filename)) {
            $this->create();
        }

        if (!is_file($this->filename)) {
            throw new Exception("Failed to add read lock. $this->filename is not a file.");
        }

        $handle = fopen($this->filename, 'r');

        if ($handle === false) {
            throw new Exception("Failed to open the file: $this->filename");
        }

        if (flock($handle, LOCK_SH) === false) {
            throw new Exception("Failed to add read lock. $this->filename");
        }

        $this->handle = $handle;
        $this->lock = LOCK_SH;
    }

    /**
     * Add a write lock to this file.
     */
    public function wlock()
    {
        if (is_null($this->filename)) {
            throw new Exception("Failed to add write lock. The file is released.");
        }

        if ($this->lock === LOCK_SH) {
            throw new Exception("Failed to add write lock. The file is locked. ($this->filename)");
        }

        if ($this->lock === LOCK_EX) {
            return;
        }

        if (!file_exists($this->filename)) {
            $this->create();
        }

        if (!is_file($this->filename)) {
            throw new Exception("Failed to add read lock. $this->filename is not a file.");
        }

        $handle = fopen($this->filename, 'r+');

        if ($handle === false) {
            throw new Exception("Failed to open the file: $this->filename");
        }

        if (flock($handle, LOCK_EX) === false) {
            throw new Exception("Failed to add read lock. $this->filename");
        }

        $this->handle = $handle;
        $this->lock = LOCK_EX;
    }

    /**
     * Get the size of the file.
     *
     * @return int
     */
    public function size()
    {
        if (is_null($this->filename)) {
            throw new Exception('Failed to get the size of the file. It is released.');
        }

        if ($this->lock === LOCK_UN) {
            throw new Exception("Failed to get the size of the file. It is unlocked. ($this->filename)");
        }

        if (is_null($this->content)) {
            $stat = fstat($this->handle);
            if (!is_array($stat)) {
                throw new Exception("Failed to get the information of the file: $this->filename");
            }
            return $stat['size'];
        } else {
            return strlen($this->content);
        }
    }

    /**
     * Read the content of this file.
     *
     * @return string
     */
    public function read()
    {
        if (is_null($this->filename)) {
            throw new Exception('Failed to read the content of this file. It is released.');
        }

        if ($this->lock === LOCK_UN) {
            throw new Exception("Failed to read the content of this file. It is unlocked. ($this->filename)");
        }

        if (is_null($this->content)) {
            $content = '';
            while (true) {
                $buffer = fread($this->handle, 8192);
                if ($buffer === false) {
                    throw new Exception("Failed to read the content of the file: $this->filename");
                }
                if ($buffer === '') {
                    break;
                }
                $content .= $buffer;
            }
            if ($this->lock === LOCK_EX) {
                if (rewind($this->handle) === false) {
                    throw new Exception("Failed to rewind the file: $this->filename");
                }
            }
            $this->content = $content;
            return $content;
        } else {
            return $this->content;
        }
    }

    /**
     * Write content to this file. Overwite the current conent.
     *
     * @param string $content
     */
    public function write($content)
    {
        if (is_null($this->filename)) {
            throw new Exception('Failed to write the file. It is released.');
        }

        if ($this->lock !== LOCK_EX) {
            throw new Exception("Failed to write the file. It is not locked by a write lock. ($this->filename)");
        }

        $this->content = $content;
        $this->touched = true;
    }

    /**
     * Unlock the file and write the content of this file to the file system.
     */
    public function unlock()
    {
        if (!is_null($this->handle)) {
            if ($this->lock === LOCK_EX && $this->touched) {
                if (ftruncate($this->handle, 0) === false) {
                    throw new Exception("Failed to clear the content of the file: $this->filename");
                }
                if (rewind($this->handle) === false) {
                    throw new Exception("Failed to rewind the file: $this->filename");
                }
                if (fwrite($this->handle, $this->content) === false) {
                    throw new Exception("Failed to write content to the file: $this->filename");
                }
                $this->touched = false;
            }
            if (flock($this->handle, LOCK_UN) === false) {
                throw new Exception("Failed to unlock the file: $this->filename");
            }
            if (fclose($this->handle) === false) {
                throw new Exception("Failed to close the file: $this->filename");
            }
            $this->handle = null;
            $this->content = null;
            $this->lock = LOCK_UN;
        }
    }

    /**
     * Unlock and release the file.
     */
    public function free()
    {
        $this->unlock();

        if (!is_null($this->filename)) {
            unset(self::$locked_file_list[$this->filename]);
            $this->filename = null;
        }
    }

    /**
     * Create the file if it does not exist.
     */
    private function create()
    {
        $handle = fopen($this->filename, 'a');

        if ($handle === false) {
            throw new Exception("Failed to create the file: $this->filename");
        }

        if (fclose($handle) === false) {
            throw new Exception("Failed to close the file: $this->filename");
        }
    }

    /**
     * Check whether the path is absolute.
     *
     * @param string $path
     * @return boolean
     */
    public static function isAbsolutePath($path)
    {
        return preg_match('/^(\\/|[a-zA-Z]+\\:(\\/|\\\\))/', $path) > 0;
    }

    /**
     * The function to normalize the given path. If the path is not absolute, it will be convert to absolute path based
     * on the return value of `getcwd()`. We will use `/` as separator regardless of the platform. That's if the
     * separator is `\` (on windows), we will replace it with `/`. The `.` and `..` in the path will be parsed. The `/`
     * next to each other, such as `///` will be repalce with a single `/`.
     *
     * @param string $path The path to normalize.
     * @return string Returns the absolute path always.
     */
    public static function normalizePath($path)
    {
        return self::fullpath($path);
    }

    /**
     * Get the absolute path of the path if it is not absolute path.
     *
     * @param string $path
     * @return string Returns the absolute path always.
     */
    public static function fullpath($path)
    {
        // Replace something like `\` or `\\` or `//` to `/`.
        $filename = preg_replace('/(\\/|\\\\)+/', '/', $path);

        // If the path is not absolute convert it to absolute.
        if (!self::isAbsolutePath($filename)) {
            $filename = getcwd() . '/' . $filename;
            $filename = preg_replace('/(\\/|\\\\)+/', '/', $filename);
        }

        // Get the disk name if exists.
        if (preg_match('/^[a-zA-Z]+\\:\\//', $filename) > 0) {
            $pos = strpos($filename, ':');
            $disk = substr($filename, 0, $pos);
            // Remove the disk name and `:/` in the path.
            $filename = substr($filename, $pos + 2);
        } else {
            $disk = null;
            // Remove the starting `/` in the path.
            $filename = substr($filename, 1);
        }

        $parent = array();
        $children = explode('/', $filename);

        // Parse `.` and `..` in the path.
        while (count($children)) {
            $name = array_shift($children);
            if ($name === '..') {
                if (count($parent)) {
                    array_pop($parent);
                }
            } else if ($name && $name !== '.') {
                array_push($parent, $name);
            }
        }

        $filename = implode('/', $parent);

        if (is_null($disk)) {
            $filename = '/' . $filename;
        } else {
            $filename = $disk . ':/' . $filename;
        }

        return $filename;
    }

    /**
     * Check whether the path is a child of the parent path.
     *
     * @param string $path
     * @param string $parent_path
     * @return boolean
     */
    public static function isChildPathOf($path, $parent_path)
    {
        $path = self::fullpath($path);
        $parent_path = self::fullpath($parent_path) . '/';

        return strpos($path, $parent_path) === 0;
    }
}
