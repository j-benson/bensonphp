<?php
/**
 * Helper to create the form html and automatically adds a form token to both the
 * form and the session.
 */
class Form extends HtmlHelper {
    
    /**
     * Build html to begin a form, the default form method is post.
     * Appends a unique token for the form to help prevent CSRF.
     * @id string ID to give the form.
     * @param array $attrib Associated array of the element's attributes. The array should contain
     *                      the attribute's name as the array's key and the attriute's value as the array's value.
     *                      The value can be either a string or for class it can be an array where the array
     *                      contains the classes the element should be a member of.
     *                      Example:
     *                      array ( "id" => "id-of-element", 
     *                              "class" => array ("class1", "class2"),
     *                              "type" => "text" )
     * @return string The html for the begining of the form.
     */
    public function begin($id = null, $attrib = array()) {
        $token = Request::generateFormToken();
        
        if ($id !== null) { $attrib["id"] = $id; }
        if (!array_key_exists("method", $attrib)) { $attrib["method"] = "post"; }
        $html = "<form" . $this->buildAttrib($attrib) . ">";
        $html .= $this->input("FORM_TOKEN", "hidden", array("value" => $token));
        
        return $html;
    }
    
    /**
     * Build the html that will create a hidden input field. The value of the hidden element
     * should be provided in the attrib array under the key 'value'.
     * @param string $name The value of the name attribute.
     * @param array $attrib Associated array of the element's attributes. The array shoulds contain
     *                      the attribute's name as the array's key and the attriute's value as the array's value.
     *                      The value can be either a string or for class it can be an array where the array
     *                      contains the classes the element should be a member of.
     *                      Example:
     *                      array ( "id" => "id-of-element", 
     *                              "class" => array ("class1", "class2"),
     *                              "type" => "text" )
     * @return string The html for the hidden element.
     */
    public function hidden($name, $attrib = array()) {
        return $this->input($name, "hidden", $attrib);
    }
    
    /**
     * Build the hmtl for a text input element.
     * @param string $name The value of the name attribute.
     * @param array $attrib Associated array of the element's attributes. The array shoulds contain
     *                      the attribute's name as the array's key and the attriute's value as the array's value.
     *                      The value can be either a string or for class it can be an array where the array
     *                      contains the classes the element should be a member of.
     *                      Example:
     *                      array ( "id" => "id-of-element", 
     *                              "class" => array ("class1", "class2"),
     *                              "type" => "text" )
     * @return string The html for the text input element.
     */
    public function text($name, $attrib = array()) {
        return $this->input($name, "text", $attrib);
    }
    
    /**
     * Build the hmtl for a password input element.
     * @param string $name The value of the name attribute.
     * @param array $attrib Associated array of the element's attributes. The array shoulds contain
     *                      the attribute's name as the array's key and the attriute's value as the array's value.
     *                      The value can be either a string or for class it can be an array where the array
     *                      contains the classes the element should be a member of.
     *                      Example:
     *                      array ( "id" => "id-of-element", 
     *                              "class" => array ("class1", "class2"),
     *                              "type" => "text" )
     * @return string The html for the password input element.
     */
    public function password($name, $attrib = array()) {
        return $this->input($name, "password", $attrib);
    }
    
    /**
     * Build the hmtl for a file input element.
     * @param string $name The value of the name attribute.
     * @param array $attrib Associated array of the element's attributes. The array shoulds contain
     *                      the attribute's name as the array's key and the attriute's value as the array's value.
     *                      The value can be either a string or for class it can be an array where the array
     *                      contains the classes the element should be a member of.
     *                      Example:
     *                      array ( "id" => "id-of-element", 
     *                              "class" => array ("class1", "class2"),
     *                              "type" => "text" )
     * @return string The html for the file input element.
     */
    public function file($name, $attrib = array()) {
        return $this->input($name, "file", $attrib);
    }
    
    public function textArea($name, $attrib = array()) {
        $attrib["name"] = $name;
        $value = "";
        // Text area does not have a value attribute
        if (array_key_exists("value", $attrib)) {
            $value = $attrib["value"];
            unset($attrib["value"]);
        }
        return "<textarea" . $this->buildAttrib($attrib) . ">$value</textarea>";
    }
    
    public function beginSelect($name, $attrib = array()) {
        $attrib["name"] = $name;
        return "<select" . $this->buildAttrib($attrib) . ">";
    }
    
    public function addOption($option, $value, $attrib = array()) {
        $attrib["value"] = $value;
        return "<option" . $this->buildAttrib($attrib) . ">" . $option . "</option>";
    }
    
    public function endSelect() {
        return "</select>";
    }
    
    public function checkbox($name, $value, $attrib = array()) {
        $attrib["value"] = $value;
        return $this->input($name, "checkbox", $attrib);
    }
    
    
    public function input($name, $type, $attrib = array()) {
        $attrib["name"] = $name;
        $attrib["type"] = $type;
        return "<input" . $this->buildAttrib($attrib) . ">";
    }
    
    public function submit($value, $attrib = array()) {
        $attrib["value"] = $value;
        return $this->input("", "submit", $attrib);
    }
    
    /**
     * Build html to end a form. Adds a submit button before the form is closed if submit value given.
     * @return string The html for the end of the form.
     */
    public function end($submit = null, $attrib = array()) {
        $html = "";
        if ($submit !== null) {
            $html = $this->submit($submit, $attrib);
        }
        
        return $html . "</form>";
    }
    
    public function label($value, $attrib = array()) {
        return "<label" . $this->buildAttrib($attrib) . ">$value</label>";
    }
    
    public function labelFor($for, $value, $attrib = array()) {
        $attrib["for"] = $for;
        return "<label" . $this->buildAttrib($attrib) . ">$value</label>";
    }
}
