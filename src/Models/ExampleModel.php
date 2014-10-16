<?php 
/**
 * Models can be passed into views by returning the model in the controller and accessed in the 
 * view by $this->ExampleModel. (Must be called by the model name in the view, $this->ModelName)
 * All models must extend the AbstractModel class.
 */
class ExampleModel extends AbstractModel {
	private $field = "This is the example method in the ExampleModel class.";
	
	public function example() {
		return $field;
	}
}