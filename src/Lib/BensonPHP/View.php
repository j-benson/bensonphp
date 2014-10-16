<?php
/**
 * Class to render the views and allow views properties and methods.
 * Views can use:
 *  - viewTitle
 *  - viewLayout The name of the layout to use.
 *  - accessLevel The access level required to view this page.
 *  - html The HtmlHelper to aid creating links from controller and action names.
 *  - form The Form HtmlHelper to aid building forms that automatically creates a unique 
 *         token for that form and adds that token to the users session.
 */
class View {
    
    /**
     * The title of the view, setting this in the view overrides the value
     * set in the controller.
     * @var string Title for the view.
     */
    public $viewTitle = "";
    
    /**
     * The name of the layout to use, setting this in the view overrides 
     * the value set in the controller.
     * @var string The layout to use.
     */
    public $viewLayout = "";
    
    /**
     * The rendered view content to pass into the layout.
     * @var string The view's rendered hmtl.
     */
    public $viewContent = "";
    
    public $accessLevel = "";
    
    public $html = null;
    public $form = null;
    
    private $_controller = "";
    private $_controllerInstance = "";
    private $_action = "";
    
    public function __construct($controller, $action, $controllerInstance) {
        $this->_controller = $controller;
        $this->_controllerInstance = $controllerInstance;
        $this->_action = $action;
        
        $this->viewTitle = $controllerInstance->viewTitle;
        $this->viewLayout = $controllerInstance->viewLayout;
        $this->accessLevel = $controllerInstance->accessLevel;
        
        $this->html = new HtmlHelper();
        $this->form = new Form();
    }
    
    public function render($model = null) {
        if (!is_null($model)) {
            // TODO: Error checking of getting name.
            $reflect = new ReflectionClass($model);
            $modelName = $reflect->getName();
            $this->$modelName = $model;
        }
        
        $viewPath = $this->_getViewFilepath();
        $this->viewContent = $this->_bufferFile($viewPath);
        
        // Now the view has been rendered, can check what access level has been set
        // and whether the user has access to the page.
        //$access = new Access();
        Access::check($this->accessLevel);
        
        $layoutPath = $this->_getLayoutFilepath();
        if ($layoutPath !== "") {
            $this->viewContent = $this->_bufferFile($layoutPath);
        }
        echo $this->viewContent;
    }
    
    private function _getViewFilepath() {
        $controller = str_replace("_", DS, $this->_controller);
        // If the request was a POST Request then 'Post' would have been appended to the action name.
        $action = Util::endsWith($this->_action, "Post") ? substr($this->_action, 0, -4) : $this->_action;
        
        $path = ROOT . Config::getOption("ViewsFilepath", "BensonPHP") . DS
                . $controller . DS . $action . Config::getOption("Extension", "BensonPHP");
        if (!file_exists($path)) {
            throw new ViewNotFoundException("The view at the location \"$path\" could not be found.");
        }
        return $path;
    }
    
    private function _getLayoutFilepath() {
        if ($this->viewLayout === "") {
            return "";
        }
        $path = ROOT . Config::getOption("LayoutsFilepath", "BensonPHP") . DS 
                . $this->viewLayout . Config::getOption("Extension", "BensonPHP");
        if (!file_exists($path)) {
            throw new LayoutNotFoundException("The layout at the location \"$path\" could not be found.");
        }
        return $path;
    }
    
    private function _bufferFile($path) {
        ob_start();
        include_once ($path);
        return ob_get_clean();
    }
}
