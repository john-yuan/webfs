<?php

class User
{
    /**
     * @var string The normal user.
     */
    const USER = 'USER';

    /**
     * @var string The administrator.
     */
    const ADMIN = 'ADMIN';

    /**
     * @var array The user info.
     */
    private $user_info;

    /**
     * @var string The password hash of the user.
     */
    private $password_hash;

    /**
     * The constructor.
     *
     * @param array $user_info The user info.
     */
    public function __construct($user_info)
    {
        // Save the password hash value.
        $this->password_hash = $user_info['password'];

        // Save the user info without password.
        $this->user_info = array(
            'id' => $user_info['id'],
            'root' => $user_info['root'],
            'type' => $user_info['type'],
            'group' => $user_info['group'],
            'username' => $user_info['username'],
            'created_at' => $user_info['created_at'],
            'updated_at' => $user_info['updated_at'],
            'deleted_at' => $user_info['deleted_at']
        );
    }

    /**
     * Get the user info.
     *
     * @return array
     */
    public function getUserInfo()
    {
        return $this->user_info;
    }

    /**
     * Get the id of the user.
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->user_info['id'];
    }

    /**
     * Check whether the user is root user.
     *
     * @return bool Returns true on the user is root user, otherwise false is returned.
     */
    public function isRootUser()
    {
        return $this->user_info['root'];
    }

    /**
     * Check whether the user is administrator.
     *
     * @return bool Returns true on the user is administrator, otherwise false is returned.
     */
    public function isAdmin()
    {
        return $this->user_info['type'] === self::ADMIN;
    }

    /**
     * For some operactions we want the user to confirm their password. This function is used to chececk whether the
     * password is correct.
     *
     * @param string $password The unhashed user password.
     * @return bool Returns true if the password is confirmed, otherwise false is returned.
     */
    public function confirmPassword($password)
    {
        return UserManager::getInstance()->checkPassword($password, $this->password_hash);
    }
}
