<?php

class Http
{
    /**
     * @var string The session key prefix.
     */
    const SESSION_PREFIX = '_SESSION_';

    /**
     * @var string
     */
    private $reqeust_method = null;

    /**
     * @var array
     */
    private $request_input = null;

    /**
     * @var Http
     */
    private static $instance = null;

    /**
     * Get the Http instance.
     *
     * @return Http
     */
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new Http();
        }
        return self::$instance;
    }

    /**
     * The private constructor.
     */
    private function __construct()
    {
        $this->reqeust_method = strtoupper(trim($_SERVER['REQUEST_METHOD']));
    }

    /**
     * Get the user input.
     *
     * @param string $key
     * @param boolean $default
     * @param boolean $raw
     * @return mixed
     */
    public function input($key = null, $default = null, $raw = false)
    {
        if (is_null($this->request_input)) {
            $is_json = false;
            if (isset($_SERVER['HTTP_CONTENT_TYPE'])) {
                $content_type = $_SERVER['HTTP_CONTENT_TYPE'];
            } else if (isset($_SERVER['CONTENT_TYPE'])) {
                $content_type = $_SERVER['CONTENT_TYPE'];
            } else {
                $content_type = null;
            }
            // is Content-Type json ?
            if (is_string($content_type)) {
                if (preg_match('/^application\/json\b/i', $content_type)) {
                    $is_json = true;
                }
            }
            if (!$is_json) {
                if ($this->isMethod('post')) {
                    if ($content_type || count($_POST)) {
                        $data = $_POST;
                    } else {
                        $is_json = true;
                    }
                } else {
                    $data = $_GET;
                }
            }
            if ($is_json) {
                $text = file_get_contents('php://input');
                $data = json_decode($text, true);
                if (!is_array($data)) {
                    $data = array();
                }
            }
            $this->request_input = $data;
        } else {
            $data = $this->request_input;
        }

        if (is_null($key)) {
            return $data;
        } else {
            $key = strval($key);
            $key = preg_replace('/\\[([0-9]+)\\]/', '.$1', $key);
            $keys = explode('.', $key);
            $unset = false;
            foreach ($keys as $key) {
                if (is_array($data) && isset($data[$key])) {
                    $data = $data[$key];
                } else {
                    $data = null;
                    $unset = true;
                    break;
                }
            }
            if ($unset || is_null($data)) {
                return $default;
            } else {
                return $data;
            }
        }
    }

    /**
     * Check whether the http method is the given method (case-insensitive).
     *
     * @param string $method
     * @return bool
     */
    public function isMethod($method)
    {
        return strtoupper(trim($method)) === $this->reqeust_method;
    }

    /**
     * Set the allowed method of the current request. If the method is not in the allowed method list. A 405 error will
     * be sent to the browser.
     *
     * @param string|array $method
     */
    public function allowedMethod($method)
    {
        if (is_array($method)) {
            $methods = $method;
        } else {
            $methods = array($method);
        }
        foreach ($methods as $method) {
            if ($this->isMethod($method)) {
                return;
            }
        }
        $this->e405();
    }

    /**
     * Send a 405 error to the browser and exit the program.
     */
    public function e405()
    {
        header('HTTP/1.1 405 Method Not Allowed');
        header('status: 405 Method Not Allowed');
        echo '405 Method Not Allowed';
        exit;
    }

    /**
     * Send a 404 error to the browser and exit the program.
     */
    public function e404()
    {
        header('HTTP/1.1 404 Not Found');
        header('status: 404 Not Found');
        echo '404 Not Found';
        exit;
    }

    /**
     * Send the data as JSON to the browser and exit the program.
     *
     * @param mixed $data
     */
    public function json($data)
    {
        if (defined('JSON_UNESCAPED_UNICODE')) {
            $text = json_encode($data, JSON_UNESCAPED_UNICODE);
        } else {
            $text = json_encode($data);
        }

        if ($text === false) {
            throw new Exception('Failed to encode the data to JSON.');
        } else {
            header('Content-Type: application/json; charset=utf-8');
            echo $text;
            exit;
        }
    }

    /**
     * Send the text to the browser and exit the program.
     *
     * @param string $text
     */
    public function text($text)
    {
        header('Content-Type: text/plain; charset=utf-8');
        echo $text;
        exit;
    }

    /**
     * Send the error to the browser and exit the program.
     *
     * @param string $errno
     * @param string $message
     * @param string $error
     */
    public function error($errno, $message = null, $error = null)
    {
        if (is_null($errno)) {
            throw new Exception('The errno is required!');

        }

        if (!is_string($errno)) {
            throw new Exception('The errno must be a string!');
        }

        $errno = trim($errno);

        if ($errno === '') {
            throw new Exception('The errno can not be a empty string!');
        }

        if (is_null($message)) {
            $message = 'Uncaught system error!';
        }

        $res = array();

        $res['code'] = 127;
        $res['errno'] = $errno;

        if (!is_null($error)) {
            $res['error'] = $error;
        }

        $res['message'] = $message;

        $this->json($res);
    }

    /**
     * Send the data as JSON to the browser and exit the program.
     *
     * @param mixed $data
     * @param string $message
     */
    public function send($data = null, $message = 'Operation succeeded!')
    {
        $res = array();

        $res['code'] = 0;

        if (!is_null($data)) {
            $res['data'] = $data;
        }

        $res['message'] = $message;

        $this->json($res);
    }

    /**
     * Get the ip of the request.
     *
     * @return string
     */
    public function ip()
    {
        $ip_address = 'unknown';
        $keys = array(
            'HTTP_CDN_SRC_IP',
            'HTTP_PROXY_CLIENT_IP',
            'HTTP_WL_PROXY_CLIENT_IP',
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'REMOTE_ADDR',
        );
        foreach ($keys as $key) {
            if (isset($_SERVER[$key])) {
                if (strtolower($_SERVER[$key]) !== 'unknown') {
                    $ip_address = $_SERVER[$key];
                }
            }
        }
        return $ip_address;
    }

    /**
     * Get the session value by name
     *
     * @return mixed
     */
    public function getSession($name, $default = null)
    {
        $session_key = self::SESSION_PREFIX . $name;

        if (isset($_SESSION[$session_key])) {
            $value = $_SESSION[$session_key];
            $value = $value === null ? $default : $value;
        } else {
            $value = $default;
        }

        return $value;
    }

    /**
     * Set the session
     *
     * @param string $name
     * @param mixed $value
     */
    public function setSession($name, $value)
    {
        $_SESSION[self::SESSION_PREFIX . $name] = $value;
    }

    /**
     * Remove the session by name
     *
     * @param string $name
     */
    public function removeSession($name)
    {
        unset($_SESSION[self::SESSION_PREFIX . $name]);
    }
}
