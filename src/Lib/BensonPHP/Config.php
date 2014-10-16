<?php

/**
 * The Config class will read configuration options from the
 * XML file.
 * 
 * The root is the default location, if the config file is moved 
 * the path field in this class should be updated. IMPORTANT: Alter 
 * .htaccess files to make sure the config is not accessable via the web.
 */
class Config implements Iterator {
    
    // Future idea maybe
    const PRODUCTION = "production";
    const DEVELOPMENT = "development";
    private static $environment = self::PRODUCTION;
    
    /**
     * The file path to the config.xml file relative to the root.
     * The root is defined by the index.php file in the public directory.
     */
    private static $path = "/src/Lib/Config.xml";
    
    /**
     * SimpleXmlElement containing the configuration xml file.
     * @var SimpleXmlElement Holds Config.xml.
     */
    private static $xml = null;
    
    private $node = null;
    private $position = 0;
    const NO_VALID_ENV = -1;
    const VALID_ENV = 0;
    private $validEnv = self::NO_VALID_ENV;
    
    
    public function __construct($node) {
        $this->node = $node;
        $this->position = 0;
    }
    
    public static function enableDevelopmentEnvironment() {
        self::$environment = self::DEVELOPMENT;
    }
    public static function getEnvironment() {
        return self::$environment;
    }

    /**
     * Loads the configuration file.
     */
    public static function load() {
        self::$xml = simplexml_load_file(ROOT . self::$path);
        
        // When the xml object is false an error has occurred.
        if (self::$xml === false) {
            throw new ConfigException("Failed to load the configuration flle.");
        }
    }
        
    /**
     * Navigates to a given node from the root of the xml config file.
     * @param string $path The path should be the name of the nodes to navigate
     *                     to separated by a period.
     *                     Example: 
     *                     <Parent>
     *                         <Child />
     *                     </Parent>
     *                     To navigate to child from parent the path should be:
     *                     "Parent.Child"
     * @returns Config The Config object of the requested node. If the node does
     *                 not exist the Config object returned will be empty (count = 0)
     *                 and the toString method will return an empty string.
     */
    public static function navigateTo($path) {
        return self::_get($path, self::$xml);
    }
    /**
     * Navigates to a given node from the current node in the Config object.
     * @param string $path The path should be the name of the nodes to navigate
     *                     to separated by a period.
     *                     Example: 
     *                     <Parent>
     *                         <Child />
     *                     </Parent>
     *                     To navigate to child from parent the path should be:
     *                     "Parent.Child"
     * @returns Config The Config object of the requested node. If the node does
     *                 not exist the Config object returned will be empty (count = 0)
     *                 and the toString method will return an empty string.
     */
    public function get($path) {
        return self::_get($path, $this->node);
    }
    
    private static function _get($path, $fromNode) {
        // Get an array of each xml node to tranverse.
        $nodes = explode(".", $path);
        // Traverse xml to node
        $node = $fromNode; //= self::_nextValidEnvironment($fromNode); // = $fromNode;
        if ($node != null) {
            foreach ($nodes as $n) {
                $node = $node->$n;
            }
        }
        $newNode = new Config($node);
        $newNode->setNextValidPos();
        return $newNode;
    }
    
    /**
     * Checks the required environment of the current node if it was given one and compares
     * it to the current environment for the application to decide whether the rule should be
     * enforced. If it was not given one in the config the default is the rule will be allowed.
     * 
     * @return bool True if the environment matches or is not defined, false if the current node
     *              does not meet the environment requirement.
     */
    private static function _validEnvironment($node) {
        if (isset($node->attributes()->env) && $node->attributes()->env->__toString() != self::getEnvironment()) {
            return false;
        }
        return true;
    }
    
    // Not yet working as intended.
    /**
     * Check to see if the current node has a env attribute and if it matches the current env.
     * If it doesn't match find the next node that does.
     * @param int $pos
     */
    public function setNextValidPos($pos = null) {
        if ($pos !== null) {
            $this->validEnv = $pos;
        } else {
            if ($this->node != null) {
                $validIndex = self::NO_VALID_ENV;
                for($i = 0; $i < $this->node->count(); $i++) {
                    if (self::_validEnvironment($this->node[$i])) {
                        $validIndex = $i;
                        break;
                    }
                }
                // Returns the index of the next valid node or -1 if none of them are valid.
                $this->validEnv = $validIndex;
            }
        }
    }

    /**
     * The string value of the given attribute, if environment has been set the node with the
     * correct environment will be selected.
     * @return string Value of the attribute or empty string on failure.
     */
    public function getAttribute($name) {
        // When the given attribute is not there SimpleXmlElement returns null.
        if ($this->invalidNode() || $this->node[$this->validEnv][$name] === null) { 
            return "";
        }
        return $this->node[$this->validEnv][$name]->__toString();
    }
    
    
    /**
     * The number of nodes selected, includes all nodes whatever the environment is set to.
     * @return int The number of nodes.
     */
    public function count() {
        if ($this->node == null) { return 0; }
        return $this->node->count();
    }
    /**
     * Gets the name of the current node.
     * @return string Name of node.
     */
    public function getName() {
        if ($this->invalidNode()) { return ""; }
        return $this->node[$this->validEnv]->getName();
    }
    /**
     * The string value of the node, if environment has been set the node with the
     * correct environment will be selected.
     * @return string Value of the node or empty string on failure.
     */
    public function toString() {
        if ($this->invalidNode()) { return ""; }
        return $this->node[$this->validEnv]->__toString();
    }
    
    public function dump() {
        echo "Node Count: ". $this->count()."<br>";
        echo "Node Valid: ". $this->validEnv."<br>";
        var_dump($this->node);
    }
    
    /**
     * Checks whether no valid node could be found.
     * @return boolean True if node is invalid and false if it is valid.
     */
    private function invalidNode() {
        if ($this->node == null || $this->validEnv == self::NO_VALID_ENV) { 
            return true;
        } else {
            return false;
        }
    }
    
    // Iterator Functions
    public function rewind() {
        $this->position = 0;
    }
    public function current() {
        $config = new Config($this->node[$this->key()]);
        $config->setNextValidPos(self::VALID_ENV);
        return $config;
    }
    public function key() {
        return $this->position;
    }
    public function next() {
        ++$this->position;
    }
    public function valid() {
        while (isset($this->node[$this->position])) {
            if (self::_validEnvironment($this->node[$this->position])) {
                return true;
            } else {
                $this->next();
            }
        }
        return false;
    }
    // End Iterator Functions
    
    
    /** DEPRECATED
     * Gets an option in the config file. There are 3 main types: Site is the default
     * and should contain all the specific options for the site; BensonPHP contain all framework
     * options; and Connections contain connections for the database(s).
     * @param String $name The name of the option to get.
     * @param String $type Which type of option to get either: Site, BensonPHP or Connections.
     * @return mixed The string value of the option, an array of values, an empty string if 
     *               the option cannot be found or null if the operation failed.
     */
    public static function getOption($name, $type = "Site") {
        $option = self::$xml->$type->$name;
        if ($option instanceof SimpleXMLElement) {
            $optionArray = self::optionArray($option);
            if ($optionArray !== false) {
                return $optionArray;
            }
            return $option->__toString();
        }
        return null;
    }
    /**
     * Checks whether the option has the attribute type="array" in which case the
     * getOption function should return an array of values from its children elements.
     * @param string $option The SimpleXMLElement containing the option.
     * @return array An array of values. 
     */
    private static function optionArray($option) {
        if (isset($option["type"]) && $option["type"] == "array") {
            $optionArray = array();
            foreach ($option->children() as $name => $value ) {
                array_push($optionArray, $value->__toString());
            }
            return $optionArray;
        }
        return false;
    }
    
    /**
     * Gets details for the database connection, including host username
     * and password
     * @param string $databaseName The name of the database
     * @return Array The database connection details as an associative array.
     *               Example:
     *               ( host => "localhost",
     *                 name => "DatabaseName",
     *                 user => "root",
     *                 password => "pass" )
     *         Returns Null if the database name is not found in the config file.
     */
    public static function getConnection($databaseName) {
        $con = null;
        foreach (self::$xml->Connections->Database as $database) {
            foreach ($database->attributes() as $name => $value) {
                if ($value == $databaseName) {
                    $con = array (
                        "host" => $database->Host->__toString(),
                        "name" => $database->Name->__toString(),
                        "user" => $database->User->__toString(),
                        "password" => $database->Password->__toString()
                    );
                    return $con;
                }
            }
        }
        return $con;
    }
}
