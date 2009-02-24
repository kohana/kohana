<?php defined('SYSPATH') or die('No direct script access.');

class Request_Core {

	public static $routes = array();

	public static function factory($uri)
	{
		return new Requst($uri);
	}

	protected static $_instance;

	public static function instance($uri = NULL)
	{
		if (Request::$_instance === NULL)
		{
			// Create the instance singleton
			Request::$_instance = new Request($uri);
		}

		return Request::$_instance;
	}

	public $route;

	public $status   = 200;
	public $response = '';

	public $abort = FALSE;

	protected $_uri;
	protected $_params;
	protected $_get;
	protected $_post;

	public function __construct($uri, array $get = NULL, array $post = NULL)
	{
		// Remove trailing slashes from the URI
		$uri = trim($uri, '/');

		// Load routes
		$routes = Route::all();

		foreach ($routes as $name => $route)
		{
			if ($params = $route->matches($uri))
			{
				$this->route = $route;

				$this->_uri    = $uri;
				$this->_params = $params;

				$this->_get  = ($get === NULL)  ? $_GET : $get;
				$this->_post = ($post === NULL) ? $_POST : $get;

				return;
			}
		}

		throw new Exception('Unable to find a route to handle '.$uri);
	}

	public function get($key, $default = NULL)
	{
		return isset($this->_get[$key]) ? $this->_get[$key] : $default;
	}

	public function post($key, $default = NULL)
	{
		return isset($this->_post[$key]) ? $this->_post[$key] : $default;
	}

	public function param($key, $default = NULL)
	{
		return isset($this->_params[$key]) ? $this->_params[$key] : $default;
	}

	public function process()
	{
		$params = $this->_params;

		$controller = 'controller_'.$params['controller'];
		$action = 'action_'.$params['action'];

		// Remove the controller and action from the params
		unset($params['controller'], $params['action']);

		// Load the controller
		$controller = new $controller($this);

		// A new action is about to be run
		$controller->before();

		if ($this->status === 200)
		{
			// Execute the action
			$controller->$action();

			// The action has been run
			$controller->after();
		}

		echo $this->response;
	}

} // End Request