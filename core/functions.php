<?php

/**
 * Open a store by name, if not exists, create it.
 *
 * @return Store
 */
function store($name)
{
    return Store::open($name);
}

/**
 * Get a instance of the current http request.
 *
 * @return Http
 */
function http()
{
    return Http::getInstance();
}

/**
 * Read the config by key or initialize the config.
 *
 * @param string|array $key The key can be string, array or null.
 * If the key is a string, the config value of the key is returned.
 * If the key is an array, the array will be used to initialze the config.
 * If the kye is null, the whole config instance will be returned.
 *
 * @param mixed $default The default value to use if the config value is not found.
 * @return mixed
 */
function config($key = null, $default = null)
{
    if (is_string($key)) {
        return Config::getInstance()->get($key, $default);
    } else if (is_array($key)) {
        return Config::getInstance($key);
    } else {
        return Config::getInstance();
    }
}

/**
 * Get the instance of the UserManager.
 *
 * @return UserManager
 */
function userManager()
{
    return UserManager::getInstance();
}

/**
 * Get the instance of the Auth.
 *
 * @return Auth
 */
function auth()
{
    return Auth::getInstance();
}
