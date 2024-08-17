<?php
/**
 * Session Class - Control sessions of users
 */
class Session {
    /**
     * Initialize the session
     */
    public static function init() {
        if (version_compare(phpversion(), '5.4.0', '<')) {
            if (session_id() == '') {
                session_start();
            }
        } else {
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
        }
    }

    /**
     * Set a session variable
     *
     * @param string $key
     * @param mixed $val
     */
    public static function set($key, $val) {
        $_SESSION[$key] = $val;
    }

    /**
     * Get a session variable
     *
     * @param string $key
     * @return mixed
     */
    public static function get($key) {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : false;
    }

    /**
     * Check if the session is valid
     */
    public static function checkSession() {
        self::init();
        if (self::get("userlogin") == false) {
            self::destroy();
            header("Location: signin.php");
            exit(); // Prevent further execution
        }
    }

    /**
     * Check if the user is already logged in
     */
    public static function checkLogin() {
        self::init();
        if (self::get("userlogin") == true) {
            header("Location: index.php");
            exit(); // Prevent further execution
        }
    }

    /**
     * Destroy the session
     */
    public static function destroy() {
        session_unset();
        session_destroy();
    }
}
