<?php

/**
 * The controller is the main class to render pages to display to the user from
 * the given request arguements.
 */
class Controller extends AbstractController {

    protected $_controller = "";
    protected $_controllerInstance = null;
    protected $_action = "";
    protected $_params = array();
    
    /**
     * The suffixes for controllers and actions.
     * @var array Array position 0 for controllers.
     *            Array position 1 for actions.
     */
    private $_suffix = array ("Controller", "Action");
    
    /**
     * Initialise the controller with the request arguements, so it can later be dispatched.
     * @param RequestArgs $requestArgs Containing details of the controller, action and parameters
     * @throws InvalidRequestArgsException When an unexpected object is passed as the request args.
     */
    public function __construct($requestArgs) {
        if ($requestArgs instanceof RequestArgs) {
            $this->_controller = $requestArgs->getController();
            $this->_action = $requestArgs->getAction();
            $this->_params = $requestArgs->getParamsArray();
            return;
        } else {
            throw new InvalidRequestArgsException();
        }
    }
    
    //prob unused now
    public function getController() {
        return $this->_controller;
    }
    
    public function getAction() {
        return $this->_action;
    }
    
    /**
     * Dispatches a controller request to execute the relevant action in the controller
     * and display then view.
     * 
     * @param array $requestArgs The request arguements containing, controller name, action name,
     *                           and an array of parameters.
     */
    public function dispatch() {
        try{
            $model = $this->_executeAction($this->_controller, $this->_action, $this->_params);
            if (!Request::isAjax()) {
                $this->_dispatchView($model);
            }
        } catch (RedirectableException $e) {
            $e->redirectUser();
        }
    }
    
    /**
     * Gets the controller's expected filepath and checks that it exists on the server.
     * 
     * @param string $controller The controller's name.
     * @return string The controller's filepath.
     */
    private function _getControllerFilepath($controller) {
        $path = ROOT . Config::getOption("ControllersFilepath", "BensonPHP") . DS
                . $controller . $this->_suffix[0] . Config::getOption("Extension", "BensonPHP");
        if (file_exists($path)) {
            return $path;
        }
        throw new ControllerNotFoundException("The controller at the location \"$path\" could not be found.", 404);
    }
    
    /**
     * Executes a given action in a given controller returning the value of the 
     * action called, which most likely will be a model or nothing will be returned at all.
     * 
     * @param string $path The filepath to the controller.
     * @param string $controller The name of the controller.
     * @param string $action The name of the action.
     * @param array $params An array of parameters.
     * @return mixed The value of the action called.
     * @throws ActionExecutionException If the action fails to execute.
     */
    private function _executeAction($controller, $action, $params) {
        include_once($this->_getControllerFilepath($this->_controller));
        $class = $controller . $this->_suffix[0];
        $method = $action . $this->_suffix[1];
        
        try {
            $actionVis = new ReflectionMethod($class, $method);
        } catch (ReflectionException $re) {
            throw new ActionException("The action \"$method\" does not exist in \"$class\"", 404);
        }
        if(!$actionVis->isPublic()) {
            throw new ActionVisibilityException("The action invoked \"$method\" in \"$class\" is not public.", 404);
        }
        
        $this->_controllerInstance = new $class();
        
        $callbackValue = call_user_func_array(array($this->_controllerInstance, $method), $params);
        
        if ($callbackValue === false) {
            throw new ActionException("The action \"$method\"" 
                    . ((count($params) > 0) ? " with the parameters " . implode(", ", $params) :  "")
                     . " failed to execute in \"$class\"", 404);
        }
        
        return $callbackValue;
    }
    
    private function _dispatchView($model) {
        $view = new View($this->_controller, $this->_action, $this->_controllerInstance);
        if ($model instanceof AbstractModel) {
            $view->render($model);
        } else {
            $view->render(null);
        }
    }
}

