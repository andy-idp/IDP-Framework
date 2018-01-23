<?php

namespace Core;

class Securite {

    /**     
     * @var string $token
     * @access private
     * @static
     */
    private static $token;

    /**
     * @var string $instance
     * @access private
     * @static
     */
    private static $instance;

    /**
     * @access private
     * @return void 
     */
    private function __construct() {
        if (empty($_SESSION['token'])) {
            $_SESSION['token'] = self::$token = uniqid();
        } else {
            self::$token = $_SESSION['token'];
        }
    }

    /**
     * @access public 
     * @return string
     * @static
     */
    public static function getToken() {
        if (!isset(self::$instance)) {
            self::$instance = new Securite();
        }
        return self::$token;
    }

    /**
     * @param string $token 
     * @access public
     * @return boolean
     * @static
     */
    public static function verifToken($token) {
        if (!isset(self::$instance)) {
            self::$instance = new Securite();
        }
        return self::$token == $token;
    }
    
    /**
     * @param type $hash
     * @param type $token
     * @return type
     */
    public static function hashWithToken($variable, $token) {
        return md5($variable.$token);
    }

}

?>
