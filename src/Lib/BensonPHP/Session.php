<?php

/**
 * Class to handle sessions.
 */
class Session {
    
    /**
     * The name of the session cookie.
     * @var string session cookie name.
     */
    private static $sessionName = "session";

    /**
     * The time in seconds that the session is allowed to live.
     * 
     * @var int Number of seconds to live.
     */
    private static $lifeTime = 1800000; // default: 1800 = 30 minutes
    
    /**
     * The maximum number of read/writes allowed to the session before the 
     * session ID must be regenerated. Turn on to provide some protection to
     * session hijacking. TODO::Is this even worth doing?
     * 
     * To turn off: set to -1
     * 
     * @var int Maxium number of read/writes to the session before 
     *          session_regenerate_id is called.
     */
    private static $maxNextRegenerate = 40;
    
    public function __construct() {
        
    }
    
    /**
     * Writes all the session data and ends the session releasing the lock
     * on the session file.
     */
    public static function close() {
        if (session_id() !== "") {
            session_write_close();
        }
    }
    
    /**
     * Unset a session value at the key given.
     * @param string $key The key of the value to unset.
     */
    public static function clear($key) {
        unset($_SESSION[$key]);
    }
    
    /**
     * Unset all the session variables, invalidate the session cookie
     * and destroy the session.
     */
    public static function destroy() {
        self::_start();
        setcookie(session_name(), "", time()-3600, "/");
        session_unset();
        session_destroy();
        session_write_close();
    }
    
    /**
     * Reads a session variable at a given key.
     * @param string $key The key to look for in the session.
     * @return mixed The value of the session at the key, null if the key does not exist.
     * @throws SessionException When the key is not a string or the session 
     *                          failed to start or resume.
     * @throws SessionExpiredException When the current session has expired, must be caught
     *                                 by the client/calling class.
     */
    public static function read($key) {
        if(!self::_start()) {
            throw new SessionException("Session failed to be started or resumed.", 500);
        }
        if (!is_string($key)) {
            throw new SessionException("The key when reading from a session must a string.", 500);
        }
        self::_checkValid();
        //echo "Session read [$key] = ".(isset($_SESSION[$key]) ? $_SESSION[$key] : null)."<br>";
        return (isset($_SESSION[$key]) ? $_SESSION[$key] : null); 
    }
    
    /**
     * Writes a session variable at a given key.
     * @param string $key The key to look for in the session.
     * @param mixed $value The value to write to the session at the key.
     * @throws SessionException When the key is not a string or the session 
     *                          failed to start or resume.
     * @throws SessionExpiredException When the current session has expired, must be caught
     *                                 by the client/calling class.
     */
    public static function write($key, $value) {
        if(!self::_start()) {
            throw new SessionException("Session failed to be started or resumed.", 500);
        }
        if (!is_string($key)) {
            throw new SessionException("The key when writing to a session must a string.", 500);
        }
        self::_checkValid();
        $_SESSION[$key] = $value;
    }
    
    /**
     * Starts or resumes a session. Checking if the session ID should be reset to 
     * provide protection against session hijacking.
     * @return boolean True on success and false on failure.
     */
    private static function _start() {
        $started = true;
        if (session_id() === "") {
            session_name(self::$sessionName);
            //session_set_cookie_params(self::$lifeTime); //"/", "allbright", false, false);
                         
           $started = session_start();
        }
        
        if ($started && self::_regenerate()) {
            return session_regenerate_id(true);
        }
        return $started;
    }
    
    /**
     * Checks the session's time to live and users IP address, forces sessions to be 
     * destroyed if they have lived too long or the IP address of the session changes.
     * 
     * This function should be called whenever the session is started written
     * to or read from.
     * 
     * @throws SessionExpiredException When the current session has expired, must be caught
     *                                 by the client/calling class.
     */
    private static function _checkValid() {
//        if (!isset($_SESSION["LAST_ACCESS"])) {
//            //$_SESSION["LAST_ACCESS"] = time();
//            $checkLastAccess = false;
//            //return;
//        }
//        
        if (!isset($_SESSION["USER_IP"])) {
            $_SESSION["USER_IP"] = Request::getRemoteIP();
        } else {
            if ($_SESSION["USER_IP"] !== Request::getRemoteIP()) {
                Session::destroy();
                throw new SessionExpiredException("Session IP (".$_SESSION["USER_IP"].") "
                        . "does not match users IP (".Request::getRemoteIP().")");
            }
        }
        
        if(isset($_SESSION["LAST_ACCESS"]) && (($_SESSION["LAST_ACCESS"] + self::$lifeTime) < time())) {
            self::destroy();
            throw new SessionExpiredException("Session timed out");
        }
        // Update or set last access
        $_SESSION["LAST_ACCESS"] = time();
    }
    
    /**
     * Uses a randomly generated number between 1 and the max set in $maxNextRegenerate
     * to check whether the session ID should be regenerated in an unpredictable mannor.
     * 
     * @return boolean True if the session's ID should be regenerated. False if not.
     */
    private static function _regenerate() {
        if (self::$maxNextRegenerate > 0) {
            // Initialise for new sessions
            if (!isset($_SESSION["NEXT_REGENERATE"]) && !isset($_SESSION["REGENERATE_COUNT"])) {
                $_SESSION["NEXT_REGENERATE"] = rand(1, self::$maxNextRegenerate);
                $_SESSION["REGENERATE_COUNT"] = 0;
            }
            // Check whether the session ID needs to be regenerated.
            if ($_SESSION["REGENERATE_COUNT"] < $_SESSION["NEXT_REGENERATE"]) {
                ++$_SESSION["REGENERATE_COUNT"];
                return false;
            }
            // Reset counter and next regeneration
            $_SESSION["NEXT_REGENERATE"] = rand(intval(self::$maxNextRegenerate / 2), self::$maxNextRegenerate);
            $_SESSION["REGENERATE_COUNT"] = 0;
            return true;
        }
    }
    
    public static function dump($key = null) {
        if(!self::_start()) {
            throw new SessionException("Session failed to be started or resumed.", 500);
        }
        if ($key === null) {
            var_dump($_SESSION);
        } else {
            var_dump($_SESSION[$key]);
        }
    }
}

