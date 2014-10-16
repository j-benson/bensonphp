<?php

/**
 * Helper to build html in view files.
 */
class HtmlHelper {
    /**
     * Create a link to a controller and action. Uses the Domain entry in the config file.
     * @param type $controller The controller name.
     * @param type $action The action name.
     * @param type $params An array of the parameters, if only one parameter the variable may be a string.
     * @return string The full link with domain name.
     */
    public function link($controller = "index", $action = "index", $params = array()) {
        // Get the domain and make sure it does not end in '/'
        $link = rtrim(Config::navigateTo("Site.Domain")->toString());
        
        // Make sure params is an array.
        if (!is_array($params)) {
            $params = array ($params);
        }
        // Replace the underscore '_' in the controller name with a slash '/'
        //$controller = str_replace("_", "/", $controller);
        $cArray = explode("_", $controller);
        // Remove trailing 'index' from prefix_index if the action is also equal to index.
        if ($action === "index" && count($cArray) > 1 && end($cArray) == "index") {
            array_pop($cArray);
        }
        // Glue array back with slashes for urls.
        $controller = implode("/", $cArray);
        
        $routePattern = Request::reverseRoute($controller, $action);
        
        if ($routePattern === null) {
            // Below covers:
            // domain/controller
            // domain/controller/action
            // domain/controller/action/params
            if ($controller !== "index" && $action === "index" && empty($params)) {
                $link .= "/" . $controller;
            } else if ($controller !== "index" && $action !== "index" && empty($params)) {
                $link .= "/" . $controller . "/" . $action; //same1
            } else if ($controller !== "index" && $action !== "index" && !empty($params)) {
                $link .= "/" . $controller . "/" . $action . "/" . implode("/", $params); //same2
            }
            // Below covers:
            // domain/index/action
            // domain/index/action/params
            else if ($controller === "index" && $action !== "index" && empty($params)) {
                $link .= "/" . $controller . "/" . $action; //same1
            } else if ($controller === "index" && $action !== "index" && !empty($params)) {
                $link .= "/" . $controller . "/" . $action . "/" . implode("/", $params); //same2
            }
        } else {
            if (empty($params)) {
                $link .= $routePattern;
            } else {
                $link .= $routePattern . "/" . implode("/", $params);
            }
        }
        // domain/index/index/params --- illegal?
        // should have index action with params
        
        return $link;
    }
    
    /**
     * Builds the html sytax for an element's attributes from an array.
     * @param array $attrib Associated array of the elements attributes. The array shoulds contain
     *                      the attribute's name as the array's key and the attriute's value as the array's value.
     *                      The value can be either a string or for class it can be an array where the array
     *                      contains the classes the element should be a member of.
     *                      Example:
     *                      array ( "id" => "id-of-element", 
     *                              "class" => array ("class1", "class2"),
     *                              "type" => "text" )
     * @return string The html for an elements arrtributes, such as: ' id="id-of-element" class="class1 class2" type="text"'     
     */
    protected function buildAttrib($attrib) {
        $html = "";
        if ($attrib === null) { return ""; }
        foreach ($attrib as $att => $val) {
            // When value is null only write the attribute name for attributes such as disabled and readonly
            if ($att !== "" && $val === null) {
                $html .= " $att";
            }  
            // When both attribute and value are not empty add the attribute to html string
            else if ($att !== "" && $val !== "") {
                $html .= " $att=\"";
                $html .= is_array($val) ? implode(" ", $val) : strval($val);
                $html .= "\"";
            }
        }
        return $html;
    }
}
