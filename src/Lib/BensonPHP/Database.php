<?php

/**
 * Database class handles connecting and querying the database using the PDO object.
 * Only implements prepared statements for the security of binding parameters.
 */
class Database {
    /**
     * PDO database object.
     * @var PDO is null when not initialised.
     */
    private $dbh = null;
    
    /**
     * Stores the last prepared statement.
     * @var PDOStatement The PDOStatement object of the last prepared statement.
     */
    private $sth = null;
    
    
    /**
     * Initialise by making a persistent connection to the database.
     * @param string $dbName The name, as given in the config, of the database to connect to.
     */
    public function __construct($dbName = "default") {
        $this->connect($dbName, array(PDO::ATTR_PERSISTENT => true));
        // Set to silent mode as this class throws its own exceptions.
        $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
        $this->dbh->setAttribute(PDO::ATTR_CASE, PDO::CASE_NATURAL); //for seversfree might have to be lower
    }
    
    /**
     * Find the connection details of a given named database in the config
     * and open a connection to the database.
     * @param type $dbName The name of the database to select.
     * @param type $options Options to pass to the PDO contructor.
     * @throws DatabaseException When it failes to connect to the database.
     */
    public function connect($dbName, $options) {
        $con = null;
        $databases = Config::navigateTo("Connections.Database");
        foreach ($databases as $db) {
            if ($dbName == $db->getAttribute("name")) {
                $con["type"] = $db->getAttribute("type");
                $con["host"] = $db->get("Host")->toString();
                $con["name"] = $db->get("Name")->toString();
                $con["user"] = $db->get("User")->toString();
                $con["password"] = $db->get("Password")->toString();
            }
        }
        
        if ($con["type"] == "" || 
                $con["host"] == "" ||
                $con["name"] == "" ||
                $con["user"] == "") {
            throw new DatabaseException("No database selected");
        }
        
        
        // Try to connect to the database.
        try {
            $this->dbh = new PDO($con["type"] . ":host=" . $con["host"] . ";dbname=" . $con["name"], 
                    $con["user"],
                    $con["password"],
                    $options);
        } catch (PDOException $e) {
            throw new DatabaseException("User ".$con["user"]." failed to connect to database ".$con["name"]);
        }
    }
    
    /**
     * Prepares an sql statement.
     * @param type $statement The sql string to prepare.
     * @throws DatabaseException When the statement fails to prepare.
     */
    public function prepare($statement) {
        $this->sth = $this->dbh->prepare($statement);
        if ($this->sth === false) {
            throw new DatabaseException("Statement failed to prepare: ".$this->sth->queryString.". ");
        }
    }
    
    /**
     * Binds values to their respected placeholder in the prepared statement.
     * @param mixed $params Either a single value or an array of values
     *                      or a variable for each value.
     * @throws DatabaseException When the binding fails.
     */
    public function bindValues($params) {
        // Make sure that the statement is not null.
        $this->stmtNullCheck();
        
        if (!is_array($params)) {
            $params = func_get_args();
        }
        $pos = 1;
        foreach ($params as $p) {
            $paramType = 0;
            if (is_int($p) || is_double($p)) { $paramType = PDO::PARAM_INT; }
            else if (is_bool($p)) { $paramType = PDO::PARAM_BOOL; }
            else if (is_string($p)) { $paramType = PDO::PARAM_STR; }
            else if (is_null($p) || (is_string($p) && strtolower($p) === "null")) { $paramType = PDO::PARAM_NULL; }
            else {
                // default string
                $paramType = PDO::PARAM_STR;
            }
            if (!$this->sth->bindValue($pos++, $p, $paramType)) {
                throw new DatabaseException("Failed to bind value \"$p\" to statement \"".$this->sth->queryString."\"");
            }
        }
    }
    
    /**
     * Executes a statement with the previously prepared statement and binded values
     * or executes with the provided statement and parameters.
     * @param string $statement The sql statement to prepare. 
     * @param mixed $params The parameters to bind to the statement. Can be either
     *                      a single param, an array of params or a new variable
     *                      passed for each param. Null values should be strings 
     *                      such as "null" or use bindParams() to pass php null value.
     * @throws DatabaseException When the statement fails to execute.
     */
    public function execute($statement = null, $params = null) {
        // If a statement has been passed prepare it.
        if ($statement !== null) {
            $this->prepare($statement);
        }
        
        // If parameters have been passed bind them.
        $this->stmtNullCheck();
        if ($params !== null) {
            if (!is_array($params) && func_num_args() > 2) {
                $params = func_get_args();
                // Shift off the first arguement as this is the statement.
                array_shift($params);
            }
            $this->bindValues($params);
        }
        
        // Execute the statement.
        if (!$this->sth->execute()) {
            throw new DatabaseException("Failed to execute statement \"".$this->sth->queryString."\" " .
                    $this->sth->errorInfo()[2]);
        }
    }
    
    /**
     * Checks that the statement has been prepared.
     * @throws DatabaseException When the statement is null.
     */
    private function stmtNullCheck() {
        if ($this->sth === null) {
            throw new DatabaseException("No currently prepared statement");
        }
    } 
    
    /**
     * Gets the next row from the result set with the column name as the arr
     * @return array
     */
    public function fetchRowAssoc() {
        return $this->sth->fetch(PDO::FETCH_ASSOC);
    }
    public function fetchAllAssoc() {
        return $this->sth->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function fetchRowNum() {
        return $this->sth->fetch(PDO::FETCH_NUM);
    }
    public function fetchAllNum() {
        return $this->sth->fetchAll(PDO::FETCH_NUM);
    }
    
    public function affectedRowCount() {
        return $this->sth->rowCount();
    }
    
}
