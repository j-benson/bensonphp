<?php

/**
 * Controls access to the controllers and views. Sets an integer
 * to the session as the access level required for the user to have access to the controller.
 */
class Access {
    
    private static $_accessKey = "ACCESS_LEVEL";
    
    /**
     * Called on controller dispatch to check whether the user has access to the
     * controller.
     * @param int $levelRequired The level required by the controller.
     */
    public static function check($levelRequired) {
        if ($levelRequired > 0 && $levelRequired > self::getLevel()) {
            throw new ForbiddenException("User is not authenticated to view page \"". Request::getRequestUri()."\"");
        }
    }
    
    /**
     * Set the access level of the user logged in.
     * @param int $level The level to set to the session.
     */
    public static function setLevel($level) {
        if (!is_int($level)) {
            throw new InvalidArgumentException("Access Level must be an integer");
        }
        Session::write(self::$_accessKey, $level);
    }
    
    /**
     * Get the access level for the user in the current session.
     * @return int The access level set to the session or 0 if not set in session.
     */
    public static function getLevel() {
        //$level = Session::hasSession() ? Session::read(self::_accessKey) : 0;
        $level = Session::read(self::$_accessKey);
        return $level === null ? 0 : $level;
    }
}
