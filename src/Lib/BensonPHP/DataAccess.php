<?php
/**
 * Data Access class manage a connection to a database and to handle all
 * requests to the database
 * 
 * NO LONGER USED
 */
class DataAccess {
    
    /**
     * The mysqli database object.
     * @var mysqli Database object. 
     */
    private $db = null;
    
    /**
     * The result object of the last query.
     * @var mysqli_result The result object.
     */
    private $result = null;
    
    /**
     * The statement object of the last prepared statement.
     * @var mysqli_stmt The statement object.
     */
    private $prepared = null;
        
    /**
     * Initialise by making a connection to the database.
     */
    public function __construct($name = "default") {
        $connection = null;
        $databases = Config::navigateTo("Connections.Database");
        foreach ($databases as $db) {
            if ($name == $db->getAttribute("name")) {
                $connection["host"] = $db->get("Host")->toString();
                $connection["name"] = $db->get("Name")->toString();
                $connection["user"] = $db->get("User")->toString();
                $connection["password"] = $db->get("Password")->toString();
            }
        }
        if ($connection["host"] == "" || 
                $connection["name"] == "" ||
                $connection["user"] == "") {
            throw new DataAccessException("No database selected.");
        }
        
//        echo "Connection Host: '".$connection['host']."'<br>";
//        echo "Connection Name: '".$connection['name']."'<br>";
//        echo "Connection User: '".$connection['user']."'<br>";
//        echo "Connection Password: '".$connection['password']."'<br>";
        
        $this->db = new mysqli($connection["host"], $connection["user"], 
                $connection["password"], $connection["name"]);
        // Check for any connection errors
        if (mysqli_connect_errno()) {
            echo "Connection Failed.";
            throw new DataAccessException("Connection Failed. " . mysqli_connect_error(), 500);
        }
    }
    
//    Tried and failed to implement prepare statements but cannot bind params dynamically.
//    
//    public function prepareAndExecute($query, &$params = null) {
//        $this->prepare($query);
//        if ($params !== null ) {
//            // Pass the arguments excluding the first which is the query into the function autoBindParams()
//            call_user_func_array($this->autoBindParams(), array_shift(func_get_args()));
//        } 
//        $this->execute();
//    }
//    
//    /**
//     * Prepares a statement
//     * @param type $query
//     * @throws DataAccessException Thrown when the statement fails
//     */
//    public function prepare($query) {
//        $this->prepared = $this->db->prepare($query);
//        // Check for error in statement
//        if ($this->prepared == false) {
//            throw new DataAccessException("Failed to prepare statement: \"$query\". ".$this->prepared->error, 
//                    $this->prepared->errno);
//        }
//    }
//    public function autoBindParams(&$param1, &$_ = null) {
//        // All params should be references
//        $params = func_get_args();
//        var_dump($params);
//        if (!empty($params)) {
//            $long = array();
//            $typeString = "";
//            // Foreach of the params check their types and add appropiate type to the string
//            for ($i = 0; $i < count($params); $i++) {
//                if (is_int($params[$i])) { $typeString .= "i"; }
//                // Mysqli does not accept boolean so convert to integer.
//                else if (is_bool($params[$i])) { $params[$i] = & intval($params[$i]); $typeString .= "i"; } //intval by reference?
//                else if (is_double($params[$i])) { $typeString .= "d"; }
//                else if (is_string($params[$i])) { $typeString .= "s"; }
//                else { 
//                    // If the data is a blob it needs to be sent by send_long_data()
//                    $typeString .= "b";
//                    $long[strval($i)] = $params[$i];
//                }
//            }
//            // Add the type string to the array to be passed into the bind_param function
//            array_unshift($params, $typeString);
//            var_dump($params);
//            $result = call_user_func_array(array($this->prepared, "bind_param"), $params);
//            if (!$result) {
//                throw new DataAccessException("Failed to bind parameters to statement \"".$this->prepared->sqlstate."\"");
//            }
//            // If any param needs to be sent via send_long_data()
//            // I have no idea if this works, needs testing still TODO!
//            if (!empty($long)) {
//                foreach (array_keys($long) as $k) {
//                    if (!$this->prepared->send_long_data(intval($k), $long[$k])) {
//                        throw new DataAccessException("Failed to send long data on statement \"".
//                                $this->prepared->sqlstate."\" at parameter $k");
//                    }  
//                }
//            }
//        }
//    }
//    
//    public function execute() {
//        if (!$this->prepared->execute()) {
//            throw new DataAccessException("Failed to ececute statement \"".$this->prepared->sqlstate."\"");
//        }
//    }
//    
    
    /**
     * Execute a SQL query. Parameters should be represented with a ? in the 
     * query string.
     * @param string $sql
     * @param array $params An array of the parameters to replace the ?s.
     * @return mixed An associative array or the insert id depending on the query.
     */
    public function query($sql, $params = array()) {
        $sql = $this->buildStatement($sql, $params);
        
        $this->executeStatement($sql);
        
        $this->_checkErrors();
        
        /*if ($this->result instanceof mysqli_result) {
            return $this->result->fetch_all(MYSQLI_ASSOC);
        } else if ($this->result === true) {
            if ($this->getInsertID() > 0) {
                return $this->getInsertID();
            } else {
                return $this->getAffectedRows();
            }
        }
        return null;*/
    }
    
    /**
     * Gets the insert ID from the last query.
     * @return The insert ID.
     */
    public function getInsertID() {
        return $this->db->insert_id;
    }
    
    /**
     * Gets the number of rows in the result from the last query.
     * @return int The number of rows.
     */
    public function getNumRows() {
        return $this->result->num_rows;
    }
    
    /**
     * Gets the number of affected rows from the previous query.
     * @return int The number of affected rows.
     */
    public function getAffectedRows() {
        return $this->db->affected_rows;
    }
        
    /**
     * Gets an associative array of the the next row in the result set.
     * @return array Associative array with the field name as the key.
     */
    public function getNextRowAssoc() {
        return $this->result->fetch_assoc();
    }
    
    /**
     * Gets an enumerated array of the the next row in the result set.
     * @return array Enumerated array of the result row.
     */
    public function getNextRowEnum() {
        return $this->result->fetch_row();
    }
    
    /**
     * Build a sql statement by replacing the placeholders ? with the values
     * in the param array.
     * @param type $sql
     * @param type $params
     * @return The built sql statement.
     */
    private function buildStatement($sql, $params) {
        $sql = strtolower($sql);
        if (!is_array($params)) {
            $params = array($params);
        }
        $i = 0;
        while (strpos($sql, "?") > 0) {
            if ($i < count($params)) {
                $sql = preg_replace("/\\?/", $this->escape($params[$i]), $sql, 1);
            } else {
                new DataAccessException("The number of placeholders in the SQL statement '" . 
                        $sql . "' exceeds the given number of parameters.", 500);
                break;
            }
            $i++;
        }
        return $sql;
    }
    
    /**
     * Executes a sql statement.
     * @param string $sql The statement to execute.
     */
    private function executeStatement($sql) {
        $result = $this->db->query($sql);
        if ($result === false) {
            throw new DataAccessException("Execution of statement '$sql' failed. " . $this->db->error, 500);
        }
        $this->result = $result; //->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Escapes a given variable depending on its type for use in a SQL query
     * 
     * Escapes strings and makes sure they are surrounded with single quotes.
     * @param string $str The string to escape.
     */
    private function escape ($var) {
        if (is_string($var)) {
            $var = $this->db->real_escape_string(trim($var));
            $var = "'" . $var . "'";
        } else if (is_int($var)) {
            $var = intval($var);
        } else if (is_float($var)) {
            $var = floatval($var);
        } else if (is_bool($var)) {
            $var = (int)$var;
        } else if (is_null($var)) {
            $var = 'NULL';
        }
        return $var;
    }
    
    private function _checkErrors() {
        if ($this->db->errno > 0) {
            throw new DataAccessException($this->db->error);
        }
    }


    /**
     * Closes the connection.
     */
    public function close() {
        if ($this->prepared != null) { $this->prepared->close(); }
        $this->db->close();
    }
    
    /**
     * Close the connection when the variable is unset.
     */
    public function __destruct() {
        $this->close();
    }
}

