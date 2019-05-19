<?php

class Auth
{
    /**
     * @var Auth
     */
    private static $instance = null;

    /**
     * Get the instance of Auth.
     *
     * @return Auth
     */
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new Auth();
        }
        return self::$instance;
    }

    /**
     * @var User The logged in user.
     */
    private $user = null;

    /**
     * The private constructor.
     */
    private function __construct()
    {
        // nothing
    }

    /**
     * Get the user info of the logged in user from the session.
     *
     * @return array Returns an array containing the user_id and password_hash if the user is logged in, otherwise null
     * is returned.
     */
    private function getLoginUserInfo()
    {
        return http()->getSession('login_user', null);
    }

    /**
     * Save the login user info into the session.
     *
     * @param array $login_user
     */
    private function setLoginUserInfo($login_user)
    {
        session_regenerate_id(true);
        http()->setSession('login_user', $login_user);
    }

    /**
     * Remove the login user info from the session.
     */
    private function removeLoginUserInfo()
    {
        session_regenerate_id(true);
        http()->removeSession('login_user');
    }

    /**
     * Login
     *
     * @param string $username The name of the user.
     * @param string $password The unhashed password.
     * @return User Returns the user on success, otherwise null is returned.
     */
    public function login($username, $password)
    {
        $user = userManager()->authenticate($username, $password);

        if (is_null($user)) {
            $this->logout();
        } else {
            $this->setLoginUserInfo(array(
                'user_id' => $user->getUserId(),
                'password_hash' => $user->getPasswordHash()
            ));
            $this->user = $user;
        }

        return $user;
    }

    /**
     * Logout
     */
    public function logout()
    {
        $this->removeLoginUserInfo();
    }

    /**
     * Get the logged in user if the user is logged in, otherwise ERR_NOT_LOGIN will be thrown.
     *
     * @return User
     */
    public function user()
    {
        $login_user = $this->getLoginUserInfo();

        if (is_null($login_user)) {
            http()->error('ERR_NOT_LOGIN', 'You are not logged in!', array(
                'type' => 'NOT_LOGGED_IN'
            ));
        } else {
            $login_user_id = $login_user['user_id'];
            $login_user_password_hash = $login_user['password_hash'];

            if (is_null($this->user)) {
                $this->user = userManager()->findUserById($login_user_id);
            }

            if (is_null($this->user)) {
                $this->logout();
                http()->error('ERR_NOT_LOGIN', 'You are not logged in!', array(
                    'type' => 'USER_NOT_FOUND'
                ));
            }

            if ($this->user->getUserId() !== $login_user_id) {
                $this->logout();
                http()->error('ERR_NOT_LOGIN', 'You are not logged in!', array(
                    'type' => 'USER_ID_CHANGED'
                ));
            } else if ($this->user->getPasswordHash() !== $login_user_password_hash) {
                $this->logout();
                http()->error('ERR_NOT_LOGIN', 'You are not logged in!', array(
                    'type' => 'USER_PASSWORD_CHANGED'
                ));
            }
        }

        return $this->user;
    }

    /**
     * Get the user and check whether he or she is administrator. If the user is logged in and is administrator an
     * instance of User will be returned, otherwise ERR_NOT_LOGIN will be thrown on not login, ERR_NOT_ADMIN will be
     * thrown on not administrator.
     *
     * @return User
     */
    public function admin()
    {
        $user = $this->user();

        if (!$user->isAdmin()) {
            http()->error('ERR_NOT_ADMIN', 'Permission denied. You are not the administrator!');
        }

        return $user;
    }
}
