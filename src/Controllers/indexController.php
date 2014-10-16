<?php
/**
 * Controller names must suffix Controller and extend AbstractController. 
 * The actions must suffix Action for GET requests, PostAction for POST requests,
 * AjaxAction for Ajax GET requests and AjaxPostAction for Ajax POST requests to separate 
 * the methods for different action types. The actions must also be public to be accessible
 * via the uri.
 */
class indexController extends AbstractController {
    public $viewLayout = "default";
	
    public function indexAction() {
        return new ExampleModel();
    }
}

