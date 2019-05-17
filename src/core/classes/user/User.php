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
     * The constructor.
     *
     * @param array $user_info The user info.
     */
    public function __construct($user_info)
    {
        $this->user_info = array(
            'id' => $user_info['id'],
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
}
