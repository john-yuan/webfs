<?php

class UserManager
{
    /**
     * @var UserManager
     */
    private static $instance = null;

    /**
     * Get the instance of UserManager.
     *
     * @return UserManager
     */
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new UserManager();
        }
        return self::$instance;
    }

    /**
     * The private constructor.
     */
    private function __construct()
    {
        // nothing
    }

    /**
     * Get the user store.
     *
     * @return Store
     */
    private function getUserStore()
    {
        return store('user/users');
    }

    /**
     * Find the user by the specific key (username or id).
     *
     * @param string $key The key to search (username or id).
     * @param string|number $value The value to search.
     * @return User|null Returns an instance of User on user found, otherwise null is returned.
     */
    private function findUserBySpecificKey($key, $value)
    {
        $user_store = $this->getUserStore();
        $user_list = $user_store->get('user_list', array());
        $user_info = null;

        foreach ($user_list as $stored_user) {
            if ($stored_user[$key] === $value) {
                if (is_null($stored_user['deleted_at'])) {
                    $user_info = $stored_user;
                    break;
                }
            }
        }

        return is_null($user_info) ? null : new User($user_info);
    }

    /**
     * Find the user by username.
     *
     * @param string $username The username of the user.
     * @return User|null Returns an instance of User on user found, otherwise null is returned.
     */
    public function findUserByName($username)
    {
        return $this->findUserBySpecificKey('username', $username);
    }

    /**
     * Find the user by user id.
     *
     * @param string $user_id The user id of the user.
     * @return User|null Returns an instance of User on user found, otherwise null is returned.
     */
    public function findUserById($user_id)
    {
        return $this->findUserBySpecificKey('id', $user_id);
    }

    /**
     * Get the hash of the given password.
     *
     * @param string $password The password to be hashed
     * @return string The hash of the password
     */
    public function hashPassword($password)
    {
        $hasher = new PasswordHash(8, FALSE);

        return $hasher->HashPassword($password);
    }

    /**
     * Check the password with the stored password hash.
     *
     * @param string $password The password to check
     * @param string $password_hash The stored password hash
     * @return bool Returns true if the check passed, otherwise false is returned
     */
    public function checkPassword($password, $password_hash)
    {
        $hasher = new PasswordHash(8, FALSE);

        if ($hasher->CheckPassword($password, $password_hash)) {
            return true;
        } else if (strlen($password_hash) <= 32) {
            return $password === $password_hash;
        } else {
            return false;
        }
    }

    /**
     * Check whether the length of the password is ok.
     *
     * @return bool Returns true if the password length less than equal to 32.
     */
    public function checkPasswordLength($password)
    {
        return strlen($password) <= 32;
    }

    /**
     * Create a user. If the username already existed, null is returned.
     *
     * @param string $username The username.
     * @param string $password The unhashed user password.
     * @param string $type The user type, User::USER or User::ADMIN.
     * @throws Exception Throws exception on arguments error.
     * @return mixed Returns a instance of User on success. Otherwise null is returned.
     */
    public function createUser($username, $password, $type)
    {
        if ($type !== User::USER && $type !== User::ADMIN) {
            throw new Exception("User type must be USER or ADMIN, but $type is given.", 1);
        }

        if (!$this->checkPasswordLength($password)) {
            throw new Exception("The length of the password must be less than equal to 32.", 2);
        }

        $user_store = $this->getUserStore();

        $user_store->lock();

        $user_list = $user_store->get('user_list', array());

        $user_is_not_existed = true;

        foreach ($user_list as $stored_user) {
            if ($stored_user['username'] === $username) {
                if (is_null($stored_user['deleted_at'])) {
                    $user_is_not_existed = false;
                    break;
                }
            }
        }

        if ($user_is_not_existed) {
            $user_info = array();
            $user_id = $user_store->get('next_user_id', 1);
            $current_date = date('Y-m-d H:i:s');

            $user_info['id'] = $user_id;
            $user_info['type'] = $type;
            $user_info['group'] = array();
            $user_info['username'] = $username;
            $user_info['password'] = $this->hashPassword($password);
            $user_info['created_at'] = $current_date;
            $user_info['updated_at'] = $current_date;
            $user_info['deleted_at'] = null;

            array_push($user_list, $user_info);

            $user_store->set('next_user_id', $user_id + 1);
            $user_store->set('user_list', $user_list);
        } else {
            $user_info = null;
        }

        $user_store->unlock();

        return is_null($user_info) ? null : new User($user_info);
    }

    /**
     * Remove a user by the username.
     *
     * @param string $username The name of the user to be removed.
     * @return bool Returns true on success. Otherwise false is returnd.
     */
    public function deleteUser($username, $password)
    {
        $user_store = $this->getUserStore();

        $user_store->lock();
        $user_list = $user_store->get('user_list', array());

        $user_deleted = false;

        foreach ($user_list as $index => $stored_user) {
            if ($stored_user['username'] === $username) {
                if (is_null($stored_user['deleted_at'])) {
                    $user_list[$index]['deleted_at'] = date('Y-m-d H:i:s');
                    $user_deleted = true;
                    break;
                }
            }
        }

        if ($user_deleted) {
            $user_store->set('user_list', $user_list);
        }

        $user_store->unlock();

        return $user_deleted;
    }

    /**
     * Check whether the password of the user is correct
     *
     * @param string $username The username.
     * @param string $password The unhashed password.
     * @return bool
     */
    public function authenticate($username, $password)
    {
        $user_store = $this->getUserStore();

        $user_store->lock();
        $user_list = $user_store->get('user_list', array());

        $user_info = null;

        foreach ($user_list as $index => $stored_user) {
            if ($stored_user['username'] === $username) {
                if (is_null($stored_user['deleted_at'])) {
                    $password_hash = $stored_user['password'];
                    if ($this->checkPassword($password, $password_hash)) {
                        $user_info = $stored_user;
                        if (strlen($password_hash) <= 32) {
                            $user_list[$index]['password'] = $this->hashPassword($password);
                            $user_list[$index]['updated_at'] = date('Y-m-d H:i:s');
                        }
                    }
                    break;
                }
            }
        }

        $user_store->set('user_list', $user_list);
        $user_store->unlock();

        if ($user_info) {
            if ($this->checkPassword($password, $user_info['password'])) {
                return new User($user_info);
            }
        }

        return null;
    }
}
