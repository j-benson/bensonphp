<?php

/**
 * Utilities class for all those handy non-standard functions.
 */
class Util {
    
    /**
     * Tests whether a string begins with a set of characters.
     * @param string $string The string to test whether it begins with given chars.
     * @param string $begins The set of characters the string should begin with.
     * @return boolean True if string begins with given characters and false if not.
     */
    public static function beginsWith($string, $begins) {
        if (strlen($begins) > strlen($string)) {
            return false;
        }
        if (substr_compare($string, $begins, 0, strlen($begins)) == 0) {
            return true;
        }
        return false;
    }
    
    /**
     * Tests whether a string ends with a set of characters.
     * @param string $string The string to test whether it ends with given chars.
     * @param string $ends The set of characters the string should end with.
     * @return boolean True if string ends with given characters and false if not.
     */
    public static function endsWith($string, $ends) {
        if (strlen($ends) > strlen($string)) {
            return false;
        }
        if (substr_compare($string, $ends, (strlen($ends) * -1), strlen($ends)) == 0) {
            return true;
        }
        return false;
    }
    
    public static function log($message) {
        $log = ROOT . DS . "src" . DS . "log";
        $now = new DateTime();
        file_put_contents($log, $now->format("Y-m-d H:i:s") . " - " . $message . "\n", FILE_APPEND);
    }
    
    public static function logError($message) {
        $now = new DateTime();
        file_put_contents(ROOT . DS . "src" . DS . "errorlog", 
                $now->format("Y-m-d H:i:s"). " - " . $message . "\r\n",
                FILE_APPEND);
    }
    
}

