<?php
    /**
     * The Request class will handle all requests. All server and input such as 
     * GET parameters and POST variables should be accessed through this class
     * as filtering is applied to all variables.
     * 
     * The POST methods allow other filters to be applied.
     * For more filters see: http://www.php.net/manual/en/filter.filters.php 
     */
    class Request {
        
       /**
        * All the defined routes.
        * @var array The defined controllers and actions for the different regular expressions
        *            Structure:
        *            array (
        *                      [0] = > array ( "pattern" => "",
        *                                      "controller" => "",
        *                                      "action" => "" )
        *            )
        */
        private static $_routes = array();
        
        /**
         * Whether the form token received is valid.
         * @var bool True when valid false when not valid.
         */
        private static $_validToken = false;

        /**
         * Gets and filters the http host name of the url.
         * @return string Host
         */
        public static function getHost() {
            return filter_input(INPUT_SERVER, "HTTP_HOST", FILTER_SANITIZE_URL);
        }
        /**
         * Gets and filters the remote IP address of the connected computer.
         * @return string IP address
         */
        public static function getRemoteIP() {
            return filter_input(INPUT_SERVER, "REMOTE_ADDR", FILTER_VALIDATE_IP);
        }
        
        /**
         * Gets and filters the request URI.
         * @return string The filtered URI.
         */
        public static function getRequestUri() {
            return filter_input(INPUT_SERVER, "REQUEST_URI", FILTER_SANITIZE_URL);
        }
        
        /**
         * Gets and filters the request with the controller, action and
         * any parameters.
         * @return array Containing Controller name, Action name and Params. Shown below:
         *               array ( 'controller' => 'controllerName',
         *                       'action' => 'actionName',
         *                       'params' => array ( 'param1', 'param2' )
         *                      )
         */
        public static function getRequestArgs() {
            $request = array ();
            $uri = self::getRequestUri();
            self::_checkIpRestrictions($uri);
            $route = self::_checkRoutes($uri);
            
            if ($route === null) {
                $uriArray = explode("/", ltrim($uri, "/"));
                self::_extractController($uri, $uriArray, $request);
                self::_extractAction($uriArray, $request);
                self::_extractParams($uriArray, $request);
            } else {
                $request = $route;
            }
            return new RequestArgs($request["controller"], $request["action"], $request["params"]);
        }
        
        /**
         * Gets and filters the value of a POST variable with the name in
         * $name.
         * @param string $name The variable name to get the value for.
         * @param int Which filter to use (using constants FILTER_SANTITIZE_*)
         * @return mixed The value of the variable. If the variable is not found null will be returned.
         *         If the filter fails false will be returned.
         */
        public static function getPost($name, $filter = FILTER_SANITIZE_STRING, $options = null) {
            if (!self::$_validToken) { self::_checkToken(); }            
            return filter_input(INPUT_POST, $name, $filter, $options);
        }
        /**
         * Gets and filters the value of a POST variable with the name in
         * $name.
         * @param mixed $definition Array defining filters to apply. Valid key would be a variable name
         *                          and a valid value would be the filter name or constant.
         *                          This can also be a filter contant where all the filter will
         *                          be applied to all the variables.
         * @param boolean $addEmpty Add missing keys from the definition as null to the return value.
         * @return mixed The value of the variable. If the variable is not found null will be returned.
         *         If the filter fails false will be returned.
         */
        public static function getPostArray($definition = FILTER_SANITIZE_STRING, $addEmpty = true) {
            if (!self::$_validToken) { self::_checkToken(); }            
            return filter_input_array(INPUT_POST, $definition, $addEmpty);
        }
        
        /**
         * Gets and filters the referer address of where the user came from.
         * @return string The referer string or null if no referer.
         */
        public static function getReferer() {
            return filter_input(INPUT_SERVER, "HTTP_REFERER", FILTER_SANITIZE_URL);
        }
        
        /**
         * Gets the method of the request.
         * @return string The method mainly: 'GET' or 'POST'.
         */
        public static function getMethod() {
            return filter_input(INPUT_SERVER, "REQUEST_METHOD", FILTER_SANITIZE_STRING);
        }
        
        /**
         * Whether the request method is a POST request.
         * @return boolean True if it is a POST request, false if it is not.
         */
        public static function isPost() {
            return self::getMethod() === "POST";
        }
        
        /**
         * Whether the request is an ajax request.
         * @return boolean True when the request is an ajax request, false if not.
         */
        public static function isAjax() {
            return strtolower(filter_input(INPUT_SERVER, "HTTP_X_REQUESTED_WITH")) === "xmlhttprequest";
        }
        
        /**
         * Generates a new form token appends to the array of valid form tokens.
         * @returns string The generated token.
         */
        public static function generateFormToken() {
            $token = hash("sha256", mcrypt_create_iv(32));
            if (Session::read($token) === null) {
                Session::write("FORM_TOKEN", array($token));
            } else {
                Session::write("FORM_TOKEN", array_push(Session::read("FORM_TOKEN"), $token));
            }
            return $token;
        }
        
        /**
         * Redirect to a given controller and action.
         * @param string $controller The controller to link to, default to index.
         * @param string $action The action to link to, default to index.
         * @param string $params The params to include, default to an empty array.
         */
        public static function redirect($controller = "index", $action = "index", $params = array()) {
            $html = new HtmlHelper();
            header("Location: " . $html->link($controller, $action, $params));
            die;
        }

        /**
         * Redirect to a given url.
         * @param string $url The url to redirect to.
         */
        public static function redirectUrl($url) {
            header("Location: " . $url);
            die;
        }

        public static function loadRoutes() {
            $routes = Config::navigateTo("Site.Routes.Route");
            foreach ($routes as $route) {
                $pattern = $route->getAttribute("pattern");
                $controller = $route->getAttribute("controller");
                $action = $route->getAttribute("action");
                self::addRoute($pattern, $controller, $action);
            }
        }
        /** 
         * Adds a new route to match to a controller and an action.
         * @param type $pattern The uri pattern to match.
         * @param type $controller
         * @param type $action
         */
        private static function addRoute($pattern, $controller, $action) {
            if ($controller == null || $controller == "") { $controller = "index"; }
            if ($action == null || $action == "") { $action = "index"; }
            
            if ($pattern === "/") {
                throw new BensonPhpException("Remove route with the pattern '/'");
            }
            array_push(self::$_routes, array("pattern" => $pattern, "controller" => $controller, "action" => $action));
        }

        /**
         * Check if uri begins with a user defined string to map/route to
         * a perticular controller and action.
         * @param string $uri The request uri.
         * @return mixed An array with controller, action and params or null 
         *               when no routes are found.
         */
        private static function _checkRoutes(&$uri) {
            $matchedPattern = "";
            $matchPos = -1;
            
            // Find best match, which will be the longest match.
            $c = 0;
            foreach (self::$_routes as $r) {
                if (Util::beginsWith($uri, $r["pattern"]) && strlen($r["pattern"]) > strlen($matchedPattern)) {
                    $matchedPattern = $r["pattern"];
                    $matchPos = $c;
                }
                ++$c;
            }
            
            // If a match was found build the request arguements
            if ($matchPos > -1) {
                $r = self::$_routes[$matchPos];
                self::_actionSuffixes($r["action"]);
                
                $params = ltrim(substr($uri, strlen($matchedPattern)), "/");
                if ($params === "" || $params === false) { // substr can return false or "" on failure
                    $params = array();
                } else {
                    $params = explode("/", $params);
                }
                
                return array (
                    "controller" => $r["controller"],
                    "action" => $r["action"],
                    "params" => $params
                );
            }
            return null;
        }
        
        /**
         * Check for a pattern in the routes for a given controller and action.
         * @param type $controller The controller to match.
         * @param type $action The action to match.
         * @return mixed A string containing the pattern if found, null if no routes are matched.
         */
        public static function reverseRoute($controller = "index", $action = "index") {#
            $controller = str_replace("/", "_", $controller);
            foreach (self::$_routes as $r) {
                if ($controller === $r["controller"] && $action === $r["action"]) {
                    return $r["pattern"];
                }
            }
            return null;
        }
        
        /**
         * Checks whether a controller prefix is used by getting the array of prefixes
         * in the config. Finds the longest prefix that mathces the beginning of the URI.
         * For example: If there are 2 prefixes => 'blog' and 'blog/admin'
         *              The uri '/blog/post' should go to controller => blog_post, action => index
         *              The uri '/blog/admin/details' should go to controller => blog_admin_details, action => index
         * Basically allows for longer prefixes.
         * 
         * @param string $uri The uri of the request by reference as no need to use more memory
         *                    for another variable.
         * @return string A string of the prefix used or an empty string if no prefix is used.
         */
        private static function _prefixUsed(&$uri) {
            $prefixUsed = "";
            $prefixes = Config::navigateTo("Site.Prefixes.Prefix");
            foreach ($prefixes as $prefix) {
                // Append forward slash / to the beginning of the prefix for comparison to the URI.
                if ((Util::beginsWith($uri, "/" . $prefix->toString()))
                        && (strlen($prefixUsed) < strlen($prefix->toString()))) {
                    $prefixUsed = $prefix->toString();
                }
            }
            return $prefixUsed;
        }
        
        /**
         * Append any appropiate suffixes to the given action name.
         */
        private static function _actionSuffixes(&$actionName) {
            if (self::isAjax()) {
                $actionName .= "Ajax";
            }
            if (self::isPost()) {
                $actionName .= "Post";
            }
        }
        
        /**
         * Extracts the name of the controller from the REQUEST_URI and puts
         * it in the $request array with the key 'controller'.
         * @param string $uri The REQUEST_URI.
         * @param array $uriArray An array of the REQUEST_URI items delimited by '/'.
         * @param array $request The request array to add the controller name to.
         */
        private static function _extractController(&$uri, &$uriArray, &$request) {
            $prefix = self::_prefixUsed($uri);
            $prefixDepth = 0; // 0 meaning no prefix is used.
            if ($prefix !== "") {
                $prefixDepth = count(explode("/", $prefix));
            }
            
            for ($d = 0; $d <= $prefixDepth; ++$d) {
                $shift = array_shift($uriArray) ;
                if ($shift == null) { $shift = 'index'; }
                if (isset($request["controller"])) {
                    $request["controller"] .= "_" . $shift;
                } else {
                    $request["controller"] = $shift;
                }
            }
        }
        
        /**
         * Extracts the name of the action from the REQUEST_URI and puts the result
         * in the $request array with the key 'action'.
         * Post requests append 'Post' to the end of the action name so that different
         * actions can handle GET and POST requests.
         * @param array $uriArray An array of the REQUEST_URI items delimited by '/'.
         * @param array $request The request array to add the controller name to.
         */
        
        private static function _extractAction(&$uriArray, &$request) {
            $shift = array_shift($uriArray) ;
            if ($shift == null) 
                { $shift = 'index'; }
            $request["action"] = $shift;
            
            self::_actionSuffixes($request["action"]);
        }
        
        /**
         * Extracts the parameters from the REQUEST_URI and puts the result array
         * in the $request array with the key 'params'.
         * @param array $uriArray An array of the REQUEST_URI items delimited by '/'.
         * @param array $request The request array to add the controller name to.
         */
        private static function _extractParams(&$uriArray, &$request) {
            $request["params"] = $uriArray;
            if ($request["params"] == null) 
                { $request["params"] = array(); }
        }
        
        /** 
         * Check whether the posted form token is a valid one set earlier to the session.
         * @throws InvalidFormToken When an invalid token is used or no token is found.
         */
        private static function _checkToken() {
            if (is_array(Session::read("FORM_TOKEN"))) {
                foreach (Session::read("FORM_TOKEN") as $token) {
                    if ($token == filter_input(INPUT_POST, "FORM_TOKEN", FILTER_SANITIZE_STRING)) {
                        self::$_validToken = true;
                        if (!Request::isAjax()) {
                            Session::clear("FORM_TOKEN");
                        }
                        return;
                    } 
                }
            }
            throw new InvalidFormToken();
        }
        /**
         * 
         * @param type $uri
         */
        private static function _checkIpRestrictions(&$uri) {
            $restrictions = Config::navigateTo("Site.IpRestrictions.Restrict");
            // Check each restriction
            foreach ($restrictions as $r) {
                $controller = $r->getAttribute("controller");
                $action = $r->getAttribute("action");
                // If the uri begins with the pattern only the given IPs are allowed access.
                if (Util::beginsWith($uri, $r->getAttribute("pattern"))) {
                    // Compare all given IPs with the current remote IP address.
                    $ipAllowed = false;
                    foreach ($restrictions->get("IP") as $ip) {
                        if (self::getRemoteIP() == $ip->toString()) {
                            $ipAllowed = true;
                            break;
                        }
                    }
                    if (!$ipAllowed) {
                        $c = new Controller(new RequestArgs($controller, $action));
                        $c->dispatch();
                    }
                }
            }
            // If no patterns are matched this function will return void and
        }
    }
    
    class RequestArgs {
        private $controller = "";
        private $action = "";
        private $params = array();
        
        public function __construct($controller = "index", $action = "index", $params = array()) {
            if ($controller == null || $controller == "") {
                $this->controller = "index";
            } else {
                $this->controller = $controller;
            }
            if ($action == null || $action == "") {
                $this->action = "index";                
            } else {
                $this->action = $action;
            }
            if (is_array($params)) {
                $this->params = $params;
            } else {
                $this->params = array($params); 
            }
        }
        
        public function getController() {
            return $this->controller;
        }
        public function getAction() {
            return $this->action;
        }
        public function getParamsArray() {
            return $this->params;
        }
    }