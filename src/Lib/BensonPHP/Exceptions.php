<?php
/*
 * All the core exceptions.
 * 
 * Any site specific exceptions should be declared in the Lib/Exceptions.php
 */

//set_exception_handler("exceptionHandle");
//function exceptionHandle($exception) {
//    $reflect = new ReflectionClass($exception);
////    Util::logError($reflect->getName() . ": "
////            . $exception->getMessage()
////            . " (" . $exception->getFile() . " on line " . $exception->getLine(). ")");
//    if (Config::navigateTo("Site.ShowExceptions")->toString()) {
//        echo "<h3>" . $reflect->getName() . "</h3><p>" . $exception->getMessage() . "</p>"
//            . "<p>(" . $exception->getFile() . " on line " . $exception->getLine(). ")</p>";
//    }
//}

interface RedirectableException {
    public function redirectUser();
}

/**
 * Framework based exceptions all derive from this exception class. All thrown
 * exceptions are logged when they occur.
 */
class BensonPhpException extends Exception {
    public function __construct($message = null, $code = null, $previous = null) {
        parent::__construct($message, $code, $previous);
        $reflect = new ReflectionClass($this);
        Util::logError($reflect->getName() 
                . ": " . $message
                . " (" . $this->getFile() 
                . " on line " 
                . $this->getLine(). ")");
        
        if (Config::navigateTo("Site.ShowExceptions")->toString() === "true") {
            echo '<div style="background-color:white;color:black"><h3>' . $reflect->getName() . '</h3><p>' . $this->getMessage() . "</p>"
                . '<p>(' . $this->getFile() . ' on line ' . $this->getLine(). ')</p></div>';
        }
    }
}
class ControllerException extends BensonPhpException implements RedirectableException {
    public function redirectUser() {
        if ($this->getCode() == 404) {
            // Not Found Exception
            $err = Config::navigateTo("Site.ErrorRedirects.NotFound");
            $con = new Controller(new RequestArgs($err->getAttribute("controller"), 
                    $err->getAttribute("action")));
            
            $con->dispatch();
        } else if ($this->getCode() == 403) {
            // Forbidden Exception
            $err = Config::navigateTo("Site.ErrorRedirects.Forbidden");
            $con = new Controller(new RequestArgs($err->getAttribute("controller"), 
                    $err->getAttribute("action")));
            $con->dispatch();
        } else {
            // Internal Server Exception (500)
            $err = Config::navigateTo("Site.ErrorRedirects.GenericError");
            $con = new Controller(new RequestArgs($err->getAttribute("controller"), 
                    $err->getAttribute("action")));
            $con->dispatch();
        }
    }
}
class InvalidRequestArgsException extends ControllerException {
    
}
class ControllerNotFoundException extends ControllerException {
    public function __construct($message = null, $code = null, $previous = null) {
        parent::__construct($message, $code, $previous);
        http_response_code(404);
    }
}
class ActionException extends ControllerException {
    
}
class ActionVisibilityException extends ControllerException {
    
}
class ViewException extends BensonPhpException {
    
}
class ViewNotFoundException extends ViewException implements RedirectableException {
    public function __construct($message = null, $code = null, $previous = null) {
        parent::__construct($message, $code, $previous);
        http_response_code(404);
    }
    
    public function redirectUser() {
        $err = Config::navigateTo("Site.ErrorRedirects.NotFound");
        $con = new Controller($err->getAttribute("controller"), 
                $err->getAttribute("action"));
        $con->dispatch();
    }
}
class LayoutException extends BensonPhpException {
    
}
class LayoutNotFoundException extends LayoutException implements RedirectableException {
    public function __construct($message = null, $code = null, $previous = null) {
        parent::__construct($message, $code, $previous);
        http_response_code(404);
    }
    
    public function redirectUser() {
        $err = Config::navigateTo("Site.ErrorRedirects.NotFound");
        $con = new Controller($err->getAttribute("controller"), 
                $err->getAttribute("action"));
        $con->dispatch();
    }
    
}
class SessionException extends BensonPhpException {
    
}
class SessionExpiredException extends SessionException implements RedirectableException {
    public function redirectUser() {
        $s = Config::navigateTo("Site.ErrorRedirects.SessionExpired");
        Request::redirect($s->getAttribute("controller"), $s->getAttribute("action"), explode("/", $s->getAttribute("params")));
    }
}
class ForbiddenException extends BensonPhpException implements RedirectableException {
    public function __construct($message = null, $code = null, $previous = null) {
        parent::__construct($message, $code, $previous);
        http_response_code(403);
    }
    
    public function redirectUser() {
        $s = Config::navigateTo("Site.ErrorRedirects.Forbidden");
        Request::redirect($s->getAttribute("controller"), $s->getAttribute("action"));
    }
}
class DataAccessException extends BensonPhpException {
    
}
class DatabaseException extends BensonPhpException {
    
}
class InvalidFormToken extends ControllerException {
    
}
class ConfigException extends BensonPhpException {
    
}