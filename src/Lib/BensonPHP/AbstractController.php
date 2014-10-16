<?php
/**
 * All created controllers must derive from this class.
 */
abstract class AbstractController {
    
    /**
     * The title of the view.
     * @var string Title for the view.
     */
    public $viewTitle = "";
    
    /**
     * The name of the layout to use.
     * @var string The layout to use.
     */
    public $viewLayout = "";
    
    /**
     * The level of access the user needs to access this controller.
     * @var int Level of access. Default is 0, which means anyone can access the controller.
     */
    public $accessLevel = 0;
    
    
}
