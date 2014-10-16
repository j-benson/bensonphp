<?php

/**
 * All created models have to be derive from this parent class.
 */
abstract class AbstractModel {
    
    protected $_errorList = array();
    
    public function hasErrors() {
        return count($this->_errorList) > 0;
    }
    
    public function getError($item = null) {
        if ($item === null) 
            { return $this->_errorList; }
        if (isset($this->_errorList[$item])) 
            { return $this->_errorList[$item]; }
        return null;
    }
    
    /**
     * Adds an error message to the key, where the key already exists the value
     * will be added to an array of values.
     * @param string $key The error identifer.
     * @param mixed $value The message related to the error. An array of messages
     *                     if there many messages.
     */
    public function addError($key, $value) {
        if (array_key_exists($key, $this->_errorList)) {
            if (is_array($this->_errorList[$key])) {
                array_push($this->_errorList[$key], $value);
            } else {
                $this->_errorList[$key] = array($this->_errorList[$key], $value);
            }
        } else {
            $this->_errorList[$key] = $value;
        }
    }
    
    /**
     * Set the value of the error list.
     * @param mixed $value value of the error list.
     */
    public function setErrorList($value) {
        $this->_errorList = $value;
    }
    /**
     * Get the error list.
     * @return mixed The contents of the error list variable.
     */
    public function getErrorList() {
        return $this->_errorList;
    }


    //public abstract function validate();
}

