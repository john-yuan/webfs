<?php

class UserManager
{
    /**
     * @var number The max length of password.
     */
    const MAX_PASSWORD_LENGTH = 32;

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
        } else if (strlen($password_hash) <= self::MAX_PASSWORD_LENGTH) {
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
        return strlen($password) <= self::MAX_PASSWORD_LENGTH;
    }

    /**
     * Create a user. If the username already existed, null is returned.
     *
     * @param string $username The username.
     * @param string $password The unhashed user password.
     * @param string $type The user type, User::USER or User::ADMIN.
     * @param bool $root Whether the user is root (The first administrator, can not be deleted).
     * @throws Exception Throws exception on arguments error.
     * @return mixed Returns a instance of User on success. Otherwise null is returned.
     */
    public function createUser($username, $password, $type, $root)
    {
        if ($type !== User::USER && $type !== User::ADMIN) {
            throw new Exception("User type must be USER or ADMIN, but $type is given!", 1);
        }

        if (!$this->checkPasswordLength($password)) {
            throw new Exception('The length of the password must be less than equal to ' .
                self::MAX_PASSWORD_LENGTH . '!', 2);
        }

        $user_store = $this->getUserStore();
        $user_list = $user_store->lock()->get('user_list', array());
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
            $user_info['root'] = $root;
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
     * Delete the user by the user id. The user will be soft deleted and can be restored latter.
     *
     * @param int $user_id The user id of the user to be deleted.
     * @return bool Returns true on success, otherwise false is returned.
     */
    public function deleteUser($user_id)
    {
        $user_store = $this->getUserStore();
        $user_list = $user_store->lock()->get('user_list', array());
        $user_deleted = false;

        foreach ($user_list as $index => $stored_user) {
            if ($stored_user['id'] === $user_id) {
                if (is_null($stored_user['deleted_at'])) {
                    $user_list[$index]['deleted_at'] = date('Y-m-d H:i:s');
                    $user_deleted = true;
                }
                break;
            }
        }

        if ($user_deleted) {
            $user_store->set('user_list', $user_list);
        }

        $user_store->unlock();

        return $user_deleted;
    }

    /**
     * Clear the user by the user id. The user will be hard deleted and remove from the storage. This operation can not
     * be undone. The user must be deleted (by calling the method `deleteUser($user_id)`) before being cleared,
     * otherwise an error code of which the values is `2` will be returned.
     *
     * @param int $user_id The user id of the user to be cleared.
     * @return int Returns `0` on success, returns `1` on user not found, returns `2` on user not deleted before.
     */
    public function clearUser($user_id)
    {
        $user_store = $this->getUserStore();
        $user_list = $user_store->lock()->get('user_list', array());
        $new_user_list = array();

        // If the user is not found, the status code remains as `1`.
        $status_code = 1;

        foreach ($user_list as $stored_user) {
            if ($stored_user['id'] === $user_id) {
                if (is_null($stored_user['deleted_at'])) {
                    // The user must be deleted before being cleared.
                    $status_code = 2;
                    break;
                } else {
                    $status_code = 0;
                }
            } else {
                array_push($new_user_list, $stored_user);
            }
        }

        if ($status_code === 0) {
            $user_store->set('user_list', $new_user_list);
        }

        $user_store->unlock();

        return $status_code;
    }

    /**
     * Update the user name by user id.
     *
     * @param int $user_id The user id.
     * @param string $new_username The new username.
     * @return User Returns the updated user on success, otherwise null is returned.
     */
    public function updateUserName($user_id, $new_username)
    {
        $user_store = $this->getUserStore();
        $user_list = $user_store->lock()->get('user_list', array());

        $user_index = null;
        $username_is_not_taken = true;
        $user = null;

        foreach ($user_list as $index => $stored_user) {
            if ($stored_user['id'] === $user_id) {
                if (is_null($stored_user['deleted_at'])) {
                    $user_index = $index;
                } else {
                    break; // The user is deleted.
                }
            }
            if (is_null($stored_user['deleted_at'])) {
                if ($stored_user['username'] === $new_username) {
                    $username_is_not_taken = false;
                    break;
                }
            }
        }

        if ((!is_null($user_index)) && $username_is_not_taken) {
            $user_list[$user_index]['username'] = $new_username;
            $user_list[$user_index]['updated_at'] = date('Y-m-d H:i:s');
            $user_store->set('user_list', $user_list);
            $user = new User($user_list[$user_index]);
        }

        $user_store->unlock();

        return $user;
    }

    /**
     * Update the user password by user id.
     *
     * @param int $user_id The user id.
     * @param string $new_password The unhashed new password.
     * @throws Exception Throws exception on arguments error.
     * @return bool
     */
    public function updateUserPassword($user_id, $new_password)
    {
        if (!$this->checkPasswordLength($new_password)) {
            throw new Exception('The length of the password must be less than equal to ' .
                self::MAX_PASSWORD_LENGTH . '.', 1);
        }

        $user_store = $this->getUserStore();
        $user_list = $user_store->lock()->get('user_list', array());
        $updated = false;

        foreach ($user_list as $index => $stored_user) {
            if ($stored_user['id'] === $user_id) {
                if (is_null($stored_user['deleted_at'])) {
                    $user_list[$index]['password'] = $this->hashPassword($new_password);
                    $user_list[$index]['updated_at'] = date('Y-m-d H:i:s');
                    $user_store->set('user_list', $user_list);
                    $updated = true;
                } else {
                    break;
                }
            }
        }

        $user_store->unlock();

        return $updated;
    }

    /**
     * Check whether the password of the user is correct.
     *
     * @param string $username The username.
     * @param string $password The unhashed password.
     * @return User Returns the user on success, otherwise null is returned.
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
                        if (strlen($password_hash) <= self::MAX_PASSWORD_LENGTH) {
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

    /**
     * Get the user list. If the status is not `active` and `deleted`, it will be treated as `any`.
     *
     * @param string $status The status to apply. Can be `active`, `deleted` and `any`.
     * @return array Returns the statused user list.
     */
    public function getUserList($status)
    {
        $user_store = $this->getUserStore();
        $user_list = $user_store->get('user_list', array());
        $filtered_user_list = array();

        if ($status === 'active') {
            foreach ($user_list as $stored_user) {
                if (is_null($stored_user['deleted_at'])) {
                    $user = new User($stored_user);
                    array_push($filtered_user_list, $user->getUserInfo());
                }
            }
        } else if ($status === 'deleted') {
            foreach ($user_list as $stored_user) {
                if (!is_null($stored_user['deleted_at'])) {
                    $user = new User($stored_user);
                    array_push($filtered_user_list, $user->getUserInfo());
                }
            }
        } else { // status === 'any'
            foreach ($user_list as $stored_user) {
                $user = new User($stored_user);
                array_push($filtered_user_list, $user->getUserInfo());
            }
        }

        return $filtered_user_list;
    }
}
