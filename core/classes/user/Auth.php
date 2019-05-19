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
     * @var User The logged-in user.
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
     * Get the logged-in user id from the session.
     *
     * @return int
     */
    private function getLoginUserId()
    {
        return http()->getSession('login_user_id', null);
    }

    /**
     * Save the logged-in user id to the session.
     *
     * @param int $user_id
     */
    private function setLoginUserId($user_id)
    {
        session_regenerate_id(true);
        http()->setSession('login_user_id', $user_id);
    }

    /**
     * Remove the logged-in user id from the session.
     */
    private function removeLoginUserId()
    {
        session_regenerate_id(true);
        http()->removeSession('login_user_id');
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
            $this->setLoginUserId($user->getUserId());
            $this->user = $user;
        }

        return $user;
    }

    /**
     * Logout
     */
    public function logout()
    {
        $this->removeLoginUserId();
    }

    /**
     * Get the logged-in user if the user is logged-in, otherwise ERR_NOT_LOGIN will be thrown.
     *
     * @return User
     */
    public function user()
    {
        $login_user_id = $this->getLoginUserId();

        if (is_null($login_user_id)) {
            http()->error('ERR_NOT_LOGIN', 'You are not logged-in!', array(
                'type' => 'NOT_LOGGED_IN'
            ));
        } else {
            if (is_null($this->user)) {
                $this->user = userManager()->findUserById($login_user_id);
            } else if ($this->user->getUserId() !== $login_user_id) {
                $this->user = userManager()->findUserById($login_user_id);
            }
            if (is_null($this->user)) {
                $this->logout();
                http()->error('ERR_NOT_LOGIN', 'You are not logged-in!', array(
                    'type' => 'USER_NOT_FOUND'
                ));
            }
        }

        return $this->user;
    }

    /**
     * Get the user and check whether he or she is administrator. If the user is logged-in and is administrator an
     * instance of User will be returned, otherwise ERR_NOT_LOGIN will be thrown on not login, ERR_NOT_ADMIN will be
     * thrown on not administrator.
     *
     * @return User
     */
    public function admin()
    {
        $user = $this->user();

        if (!$user->isAdmin()) {
            http()->error('ERR_NOT_ADMIN', 'You are not the administrator!');
        }

        return $user;
    }
}
